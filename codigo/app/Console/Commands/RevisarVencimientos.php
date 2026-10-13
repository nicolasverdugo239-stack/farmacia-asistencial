<?php

namespace App\Console\Commands;

use App\Services\AlertaService;
use Illuminate\Console\Command;

class RevisarVencimientos extends Command
{
    protected $signature = 'farmacia:revisar-vencimientos {--dias=60 : Días de anticipación}';

    protected $description = 'Genera alertas para lotes vencidos o próximos a vencer';

    public function handle(AlertaService $alertas): int
    {
        $creadas = $alertas->revisarVencimientos((int) $this->option('dias'));
        $this->info("Alertas de vencimiento nuevas: {$creadas}");

        return self::SUCCESS;
    }
}
