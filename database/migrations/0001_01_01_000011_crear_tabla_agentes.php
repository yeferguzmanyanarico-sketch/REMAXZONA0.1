<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agentes', function (Blueprint $table) {
            $table->id();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('fotografia', 255);
            $table->text('biografia');
            $table->string('celular', 30);
            $table->string('whatsapp', 30);
            $table->string('correo', 150)->nullable();
            $table->boolean('activo')->default(true)->index('idx_agentes_activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agentes');
    }
};
