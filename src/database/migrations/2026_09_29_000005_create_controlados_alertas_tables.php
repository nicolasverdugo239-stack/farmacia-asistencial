<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Libro digital de estupefacientes y psicotrópicos (RF08, RF18, RF19) y alertas (RF05).
 * El libro es de solo inserción: cada fila guarda el hash SHA-256 de la anterior.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_controlados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_id')->constrained('lotes')->restrictOnDelete();
            $table->enum('tipo', ['ingreso', 'egreso', 'ajuste']);
            $table->unsignedInteger('cantidad');
            $table->unsignedInteger('saldo_resultante');
            $table->string('observacion', 255)->nullable();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('autorizado_por')->constrained('users')->restrictOnDelete();
            $table->char('hash_anterior', 64);
            $table->char('hash', 64)->unique();
            $table->timestamps();
        });

        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['stock_critico', 'vencimiento', 'descuadre', 'carro']);
            $table->foreignId('producto_id')->nullable()->constrained('productos')->cascadeOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->cascadeOnDelete();
            $table->string('mensaje', 255);
            $table->boolean('leida')->default(false);
            $table->timestamps();

            $table->index(['tipo', 'leida']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
        Schema::dropIfExists('movimientos_controlados');
    }
};
