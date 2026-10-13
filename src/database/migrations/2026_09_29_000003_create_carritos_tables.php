<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Carro de emergencia: un mueble con dos secciones de inventario independientes (RF14 / HU11).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carritos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('servicio', 80);
            $table->timestamps();
        });

        Schema::create('carrito_secciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrito_id')->constrained('carritos')->cascadeOnDelete();
            $table->enum('tipo', ['paro', 'unidosis']);
            $table->timestamps();

            $table->unique(['carrito_id', 'tipo']);
        });

        Schema::create('carrito_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrito_seccion_id')->constrained('carrito_secciones')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->unsignedInteger('cantidad')->default(0);
            $table->unsignedInteger('stock_minimo');
            $table->unsignedInteger('stock_maximo');
            $table->timestamps();

            $table->unique(['carrito_seccion_id', 'producto_id']);
        });

        Schema::create('carrito_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrito_stock_id')->constrained('carrito_stock')->cascadeOnDelete();
            $table->enum('tipo', ['consumo', 'reposicion']);
            $table->unsignedInteger('cantidad');
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrito_movimientos');
        Schema::dropIfExists('carrito_stock');
        Schema::dropIfExists('carrito_secciones');
        Schema::dropIfExists('carritos');
    }
};
