<?php

namespace App\Console\Commands;

use App\Services\AuditoriaService;
use App\Services\LibroControladosService;
use Illuminate\Console\Command;

class VerificarLibro extends Command
{
    protected $signature = 'farmacia:verificar-libro';

    protected $description = 'Recalcula la cadena de hashes del libro de controlados y avisa si fue alterado';

    public function handle(LibroControladosService $libro, AuditoriaService $auditoria): int
    {
        $rota = $libro->verificarCadena();

        if ($rota === null) {
            $this->info('Libro íntegro.');

            return self::SUCCESS;
        }

        $this->error("Cadena rota en el registro #{$rota}.");
        $auditoria->registrar('libro_alterado', null, [], ['registro' => $rota]);

        return self::FAILURE;
    }
}
