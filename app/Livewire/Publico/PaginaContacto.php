<?php

namespace App\Livewire\Publico;

use App\Models\ConfiguracionContacto;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.publico')]
#[Title('Contacto | RE/MAX Zona')]
class PaginaContacto extends Component
{
    public function render()
    {
        $contacto = ConfiguracionContacto::obtenerUnica();

        return view('livewire.publico.pagina-contacto', [
            'contacto' => $contacto,
        ])->layoutData([
            'metaDescription' => 'Comunícate con RE/MAX Zona por teléfono, WhatsApp o correo electrónico para recibir asesoría inmobiliaria.',
        ]);
    }
}
