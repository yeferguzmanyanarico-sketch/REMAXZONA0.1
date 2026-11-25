<?php

use App\Livewire\Autenticacion\ConfirmarPassword;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Security;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'rol:ADMINISTRADOR,MARKETING'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('user/confirm-password', ConfirmarPassword::class)->name('password.confirm');

    Route::get('settings/security', Security::class)
        ->middleware(['password.confirm'])
        ->name('security.edit');

    // Rutas integradas bajo el prefijo administrativo /admin/configuracion/*
    Route::redirect('admin/configuracion', '/admin/configuracion/perfil');
    Route::get('admin/configuracion/perfil', Profile::class)->name('admin.configuracion.perfil');
    Route::get('admin/configuracion/apariencia', Appearance::class)->name('admin.configuracion.apariencia');
    Route::get('admin/configuracion/seguridad', Security::class)->name('admin.configuracion.seguridad');
});
