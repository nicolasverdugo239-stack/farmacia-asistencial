<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pacientes, recetas y dispensación (RF06, RF07, RF15, RF16).
 * Los datos personales del paciente se guardan cifrados (Ley 19.628);
 * rut_hash permite buscar por RUT sin descifrar toda la tabla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->char('rut_hash', 64)->unique();
            $table->text('rut');       // cifrado (cast "encrypted")
            $table->string('nombre', 120);
            $table->text('telefono')->nullable(); // cifrado
            $table->timestamps();
        });

        Schema::create('medicos', function (Blueprint $table) {
            $table->id();
            $table->string('rut', 12)->unique();
            $table->string('nombre', 120);
            $table->timestamps();
        });

        Schema::create('programas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80)->unique();
            $table->boolean('sin_costo')->default(true);
            $table->timestamps();
        });

        Schema::create('recetas', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->unique();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->foreignId('programa_id')->nullable()->constrained('programas')->nullOnDelete();
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->enum('estado', ['vigente', 'dispensada', 'anulada'])->default('vigente');
            $table->timestamps();
        });

        Schema::create('receta_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receta_id')->constrained('recetas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->string('dosis', 80);
            $table->unsignedInteger('cantidad');
            $table->timestamps();
        });

        Schema::create('dispensaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receta_id')->constrained('recetas')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('dispensacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispensacion_id')->constrained('dispensaciones')->cascadeOnDelete();
            $table->foreignId('lote_id')->constrained('lotes')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->boolean('fuera_de_fefo')->default(false);
            $table->timestamps();
        });

        Schema::create('retiros_terceros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispensacion_id')->unique()->constrained('dispensaciones')->cascadeOnDelete();
            $table->string('nombre', 120);
            $table->text('rut'); // cifrado
            $table->string('parentesco', 40);
            $table->timestamps();
        });

        Schema::create('rotulos_unidosis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('lote_id')->constrained('lotes')->restrictOnDelete();
            $table->string('dosis', 80);
            $table->string('horario', 40);
            $table->foreignId('preparado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rotulos_unidosis');
        Schema::dropIfExists('retiros_terceros');
        Schema::dropIfExists('dispensacion_detalles');
        Schema::dropIfExists('dispensaciones');
        Schema::dropIfExists('receta_detalles');
        Schema::dropIfExists('recetas');
        Schema::dropIfExists('programas');
        Schema::dropIfExists('medicos');
        Schema::dropIfExists('pacientes');
    }
};
