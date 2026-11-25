<?php

namespace App\Console\Commands;

use App\Services\OrderDispatchService;
use Illuminate\Console\Command;

class ReleaseScheduledOrders extends Command
{
    protected $signature = 'orders:release-scheduled';

    protected $description = 'Asigna los pedidos en espera: programados cuyo día llegó o creados cuando no había mensajero';

    public function handle(OrderDispatchService $dispatcher): int
    {
        $assigned = $dispatcher->assignWaiting();

        $this->info("Pedidos en espera asignados: {$assigned}");

        return self::SUCCESS;
    }
}
