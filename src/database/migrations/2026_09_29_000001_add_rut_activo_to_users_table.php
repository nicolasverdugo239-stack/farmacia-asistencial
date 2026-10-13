<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // nullable solo a nivel de BD (SQLite no permite agregar NOT NULL sin default);
            // la obligatoriedad se exige en StoreUsuarioRequest.
            $table->string('rut', 12)->nullable()->unique()->after('id');
            $table->boolean('activo')->default(true)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['rut']);
            $table->dropColumn(['rut', 'activo']);
        });
    }
};
