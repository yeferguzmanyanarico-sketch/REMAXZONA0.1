<?php

use App\Models\PosicionRanking;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posiciones_ranking', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['vendedor_id']);
            }
            $table->dropUnique('uk_posicion_vendedor');
        });

        Schema::table('posiciones_ranking', function (Blueprint $table) {
            $table->unsignedBigInteger('vendedor_id')->nullable()->change();
            $table->string('codigo_interno', 40)->nullable()->after('puesto');
            $table->string('celular_normalizado', 20)->nullable()->after('celular_snapshot');

            $table->unique('codigo_interno', 'uk_posicion_codigo_interno');
            $table->unique(['ranking_mensual_id', 'celular_normalizado'], 'uk_posicion_celular_periodo');
        });

        // Completar codigo_interno y celular_normalizado en registros históricos existentes sin alterar sus datos
        DB::table('posiciones_ranking')
            ->orderBy('id')
            ->get(['id', 'celular_snapshot', 'codigo_interno', 'celular_normalizado'])
            ->each(function ($fila) {
                $actualizaciones = [];
                if (empty($fila->codigo_interno)) {
                    $actualizaciones['codigo_interno'] = 'PART-' . strtoupper(Str::random(10));
                }
                if (empty($fila->celular_normalizado) && ! empty($fila->celular_snapshot)) {
                    $actualizaciones['celular_normalizado'] = PosicionRanking::normalizarCelular((string) $fila->celular_snapshot);
                }
                if (! empty($actualizaciones)) {
                    DB::table('posiciones_ranking')->where('id', $fila->id)->update($actualizaciones);
                }
            });
    }

    public function down(): void
    {
        Schema::table('posiciones_ranking', function (Blueprint $table) {
            $table->dropUnique('uk_posicion_codigo_interno');
            $table->dropUnique('uk_posicion_celular_periodo');
            $table->dropColumn(['codigo_interno', 'celular_normalizado']);
        });
    }
};
