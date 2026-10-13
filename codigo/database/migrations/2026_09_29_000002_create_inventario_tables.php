<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventario: categorías, productos, remesas CENABAST y lotes (RF02, RF03, RF04, RF09).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80)->unique();
            $table->timestamps();
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->string('codigo_barras', 32)->nullable()->unique();
            $table->string('nombre', 120);
            $table->string('principio_activo', 120);
            $table->string('presentacion', 80);
            $table->boolean('es_controlado')->default(false);
            $table->enum('tipo_control', ['estupefaciente', 'psicotropico'])->nullable();
            $table->unsignedInteger('stock_minimo')->default(0);
            $table->timestamps();
        });

        Schema::create('remesas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_guia', 40)->unique();
            $table->date('fecha_recepcion');
            $table->foreignId('recibido_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->foreignId('remesa_id')->nullable()->constrained('remesas')->nullOnDelete();
            $table->string('numero_lote', 40);
            $table->date('fecha_vencimiento');
            $table->unsignedInteger('cantidad_actual');
            $table->enum('ubicacion', ['vitrina', 'bodega'])->default('bodega');
            $table->timestamps();

            $table->unique(['producto_id', 'numero_lote']);
            // Índice que usa la consulta FEFO (producto + vencimiento).
            $table->index(['producto_id', 'fecha_vencimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes');
        Schema::dropIfExists('remesas');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('categorias');
    }
};
