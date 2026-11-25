<?php

namespace App\Services;

use App\Models\PosicionRanking;
use App\Models\RankingMensual;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ServicioRankingMensual
{
    /**
     * Sincroniza los estados vencidos (solo se invoca en el panel administrativo o comando consola,
     * nunca durante consultas del sitio público).
     */
    public function sincronizarEstadosVencidos(): void
    {
        $ahora = now('America/Lima');

        $rankingsAbiertos = RankingMensual::whereIn('estado', [
            RankingMensual::ESTADO_BORRADOR,
            RankingMensual::ESTADO_PUBLICADO,
        ])->get();

        foreach ($rankingsAbiertos as $ranking) {
            if (! RankingMensual::dentroDePlazoDeGestion((int) $ranking->anio_publicacion, (int) $ranking->mes_publicacion)) {
                DB::transaction(function () use ($ranking, $ahora) {
                    $bloqueado = RankingMensual::where('id', $ranking->id)->lockForUpdate()->first();
                    if (! $bloqueado || in_array($bloqueado->estado, [RankingMensual::ESTADO_CERRADO, RankingMensual::ESTADO_VENCIDO], true)) {
                        return;
                    }

                    $nuevoEstado = $bloqueado->estado === RankingMensual::ESTADO_PUBLICADO
                        ? RankingMensual::ESTADO_CERRADO
                        : RankingMensual::ESTADO_VENCIDO;

                    $bloqueado->update([
                        'estado' => $nuevoEstado,
                        'fecha_cierre' => $ahora,
                    ]);
                });
            }
        }
    }

    /**
     * Registra o actualiza manualmente al participante de uno de los tres puestos fijos (1, 2 o 3).
     *
     * @param  array{nombres: string, apellidos: string, celular: string}  $datos
     * @return array{ranking: RankingMensual, posicion: PosicionRanking, advertencia_homonimo: ?string}
     */
    public function guardarParticipantePuesto(
        int $puesto,
        array $datos,
        int $usuarioId,
        ?UploadedFile $fotoArchivo = null
    ): array {
        if (! in_array($puesto, [1, 2, 3], true)) {
            throw new DomainException('Solo se permiten los tres puestos fijos del ranking (1, 2 y 3).');
        }

        $rutaFotoNueva = null;
        $rutaFotoAnterior = null;
        $advertenciaHomonimo = null;

        try {
            $resultado = DB::transaction(function () use (
                $puesto,
                $datos,
                $usuarioId,
                $fotoArchivo,
                &$rutaFotoNueva,
                &$rutaFotoAnterior,
                &$advertenciaHomonimo
            ) {
                $ranking = $this->obtenerYValidarRankingEditable($usuarioId);

                $nombres = trim($datos['nombres']);
                $apellidos = trim($datos['apellidos']);
                $celular = trim($datos['celular']);
                $celularNormalizado = PosicionRanking::normalizarCelular($celular);

                if (strlen($celularNormalizado) < 6) {
                    throw new DomainException('Ingresa un número de celular válido.');
                }

                $posicionesExistentes = $ranking->posiciones()->lockForUpdate()->get()->keyBy('puesto');
                /** @var PosicionRanking|null $posicionActual */
                $posicionActual = $posicionesExistentes->get($puesto);

                // Validar unicidad por número de celular normalizado dentro del mismo período
                foreach ($posicionesExistentes as $otroPuesto => $otraPosicion) {
                    if ((int) $otroPuesto === $puesto) {
                        continue;
                    }

                    $otroCelular = $otraPosicion->celular_normalizado
                        ?: PosicionRanking::normalizarCelular((string) $otraPosicion->celular_snapshot);

                    if ($otroCelular !== '' && hash_equals($otroCelular, $celularNormalizado)) {
                        throw new DomainException("El número de celular ya está registrado en el Puesto #{$otroPuesto} de este ranking mensual.");
                    }

                    // Detectar coincidencia de nombres y apellidos como advertencia (no bloqueante)
                    $nombreActualNorm = PosicionRanking::normalizarNombreCompleto($nombres, $apellidos);
                    $otroNombreNorm = PosicionRanking::normalizarNombreCompleto(
                        (string) $otraPosicion->nombres_snapshot,
                        (string) $otraPosicion->apellidos_snapshot
                    );

                    if ($nombreActualNorm !== '' && $nombreActualNorm === $otroNombreNorm) {
                        $advertenciaHomonimo = "Aviso: El nombre «{$nombres} {$apellidos}» coincide con el participante del Puesto #{$otroPuesto}. Verifica si se trata de un homónimo.";
                    }
                }

                // Gestionar fotografía especial del ranking
                $rutaFotoFinal = $posicionActual?->foto_snapshot;
                if ($fotoArchivo !== null) {
                    $rutaFotoNueva = $this->guardarFotografiaPuesto($ranking, $puesto, $fotoArchivo);
                    if (! empty($rutaFotoFinal) && $rutaFotoFinal !== $rutaFotoNueva) {
                        $rutaFotoAnterior = $rutaFotoFinal;
                    }
                    $rutaFotoFinal = $rutaFotoNueva;
                }

                if (blank($rutaFotoFinal) || ! Storage::disk('public')->exists($rutaFotoFinal)) {
                    throw new DomainException("Debes cargar una fotografía válida para el Puesto #{$puesto}.");
                }

                if ($posicionActual) {
                    $posicionActual->update([
                        'vendedor_id' => null,
                        'nombres_snapshot' => $nombres,
                        'apellidos_snapshot' => $apellidos,
                        'celular_snapshot' => $celular,
                        'celular_normalizado' => $celularNormalizado,
                        'foto_snapshot' => $rutaFotoFinal,
                    ]);
                    $posicion = $posicionActual->fresh();
                } else {
                    $posicion = $ranking->posiciones()->create([
                        'puesto' => $puesto,
                        'codigo_interno' => 'PART-' . strtoupper(Str::random(10)),
                        'vendedor_id' => null,
                        'nombres_snapshot' => $nombres,
                        'apellidos_snapshot' => $apellidos,
                        'celular_snapshot' => $celular,
                        'celular_normalizado' => $celularNormalizado,
                        'foto_snapshot' => $rutaFotoFinal,
                    ]);
                }

                $ranking->update([
                    'actualizado_por' => $usuarioId,
                ]);

                return [
                    'ranking' => $ranking->fresh('posiciones'),
                    'posicion' => $posicion,
                    'advertencia_homonimo' => $advertenciaHomonimo,
                ];
            });

            if ($rutaFotoAnterior !== null) {
                $this->limpiarFotografiasReemplazadasPostCommit(
                    $resultado['ranking'],
                    [$rutaFotoAnterior],
                    $rutaFotoNueva ? [$rutaFotoNueva] : []
                );
            }

            return $resultado;
        } catch (Throwable $e) {
            if ($rutaFotoNueva !== null && Storage::disk('public')->exists($rutaFotoNueva)) {
                Storage::disk('public')->delete($rutaFotoNueva);
            }

            throw $e;
        }
    }

    /**
     * Publica definitivamente el ranking vigente solo cuando los tres puestos fijos están completos y sin duplicados.
     */
    public function publicarRankingActual(int $usuarioId): RankingMensual
    {
        return DB::transaction(function () use ($usuarioId) {
            $ranking = $this->obtenerYValidarRankingEditable($usuarioId);
            $posiciones = $ranking->posiciones()->lockForUpdate()->get();

            if (
                $posiciones->count() !== 3
                || $posiciones->pluck('puesto')->sort()->values()->all() !== [1, 2, 3]
            ) {
                throw new DomainException('Debes completar los tres puestos (1.er, 2.º y 3.er puesto) antes de publicar el ranking.');
            }

            $celulares = $posiciones->map(fn (PosicionRanking $p) => $p->celular_normalizado
                ?: PosicionRanking::normalizarCelular((string) $p->celular_snapshot));

            if ($celulares->unique()->count() !== 3) {
                throw new DomainException('No se puede publicar el ranking porque existen números de celular duplicados entre puestos.');
            }

            foreach ($posiciones as $pos) {
                if (
                    blank($pos->nombres_snapshot)
                    || blank($pos->apellidos_snapshot)
                    || blank($pos->celular_snapshot)
                    || blank($pos->foto_snapshot)
                    || ! Storage::disk('public')->exists($pos->foto_snapshot)
                ) {
                    throw new DomainException("El Puesto #{$pos->puesto} tiene datos o fotografía incompletos.");
                }
            }

            $ranking->update([
                'estado' => RankingMensual::ESTADO_PUBLICADO,
                'fecha_publicacion' => $ranking->fecha_publicacion ?? now('America/Lima'),
                'actualizado_por' => $usuarioId,
            ]);

            return $ranking->fresh('posiciones');
        });
    }

    private function obtenerYValidarRankingEditable(int $usuarioId): RankingMensual
    {
        $periodo = RankingMensual::periodoActual();
        if (! RankingMensual::dentroDePlazoDeGestion($periodo['anio_publicacion'], $periodo['mes_publicacion'])) {
            throw new DomainException('El plazo de gestión del ranking (días 1 al 3 del mes) ha finalizado. El ranking se encuentra bloqueado.');
        }

        RankingMensual::firstOrCreate(
            [
                'anio_desempeno' => $periodo['anio_desempeno'],
                'mes_desempeno' => $periodo['mes_desempeno'],
            ],
            [
                'anio_publicacion' => $periodo['anio_publicacion'],
                'mes_publicacion' => $periodo['mes_publicacion'],
                'estado' => RankingMensual::ESTADO_BORRADOR,
                'creado_por' => $usuarioId,
            ]
        );

        $ranking = RankingMensual::where('anio_desempeno', $periodo['anio_desempeno'])
            ->where('mes_desempeno', $periodo['mes_desempeno'])
            ->lockForUpdate()
            ->firstOrFail();

        if (
            in_array($ranking->estado, [RankingMensual::ESTADO_CERRADO, RankingMensual::ESTADO_VENCIDO], true)
            || ! RankingMensual::dentroDePlazoDeGestion((int) $ranking->anio_publicacion, (int) $ranking->mes_publicacion)
        ) {
            throw new DomainException('El ranking ya se encuentra cerrado o fuera del plazo permitido de los primeros 3 días.');
        }

        return $ranking;
    }

    private function guardarFotografiaPuesto(RankingMensual $ranking, int $puesto, UploadedFile $fotoArchivo): string
    {
        $extension = strtolower($fotoArchivo->getClientOriginalExtension() ?: $fotoArchivo->extension() ?: 'jpg');
        $mesFormateado = str_pad((string) $ranking->mes_desempeno, 2, '0', STR_PAD_LEFT);
        $directorioPeriodo = "rankings/{$ranking->anio_desempeno}/{$mesFormateado}";
        $nombreArchivo = "puesto-{$puesto}-" . Str::uuid()->toString() . ".{$extension}";

        $ruta = $fotoArchivo->storeAs($directorioPeriodo, $nombreArchivo, 'public');

        if (! $ruta || ! Storage::disk('public')->exists($ruta)) {
            throw new DomainException("No se pudo guardar la fotografía del Puesto #{$puesto}.");
        }

        app(ServicioOptimizacionImagen::class)->generarWebpDesdeDiscoPublico($ruta, 600);

        return $ruta;
    }

    private function limpiarFotografiasReemplazadasPostCommit(
        RankingMensual $ranking,
        array $archivosObsoletos,
        array $archivosNuevos
    ): void {
        if ($ranking->estado === RankingMensual::ESTADO_CERRADO) {
            return;
        }

        $mesFormateado = str_pad((string) $ranking->mes_desempeno, 2, '0', STR_PAD_LEFT);
        $prefijoPermitido = "rankings/{$ranking->anio_desempeno}/{$mesFormateado}/";

        foreach (array_unique($archivosObsoletos) as $rutaAnterior) {
            if (in_array($rutaAnterior, $archivosNuevos, true)) {
                continue;
            }

            if (! str_starts_with($rutaAnterior, $prefijoPermitido)) {
                continue;
            }

            $sigueReferenciado = PosicionRanking::where('foto_snapshot', $rutaAnterior)->exists();

            if (! $sigueReferenciado && Storage::disk('public')->exists($rutaAnterior)) {
                Storage::disk('public')->delete($rutaAnterior);

                $rutaWebp = preg_replace('/\.[^.]+$/', '.webp', $rutaAnterior);
                if (is_string($rutaWebp) && Storage::disk('public')->exists($rutaWebp)) {
                    Storage::disk('public')->delete($rutaWebp);
                }
            }
        }
    }
}
