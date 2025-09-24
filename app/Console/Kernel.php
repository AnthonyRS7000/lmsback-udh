<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Los comandos Artisan de tu aplicación.
     *
     * Aquí puedes registrar comandos manualmente si no están en Commands/.
     */
    protected $commands = [
        // Ejemplo:
        // \App\Console\Commands\RefreshUdhToken::class,
    ];

    /**
     * Definir la programación de tareas de Artisan.
     */
    protected function schedule(Schedule $schedule)
    {
        // Ejecutar el comando que refresca el token cada 20 minutos
        $schedule->command('udh:refresh-token')->everyTwentyMinutes();

        // 👇 aquí puedes ir agregando más tareas si las necesitas
        // $schedule->command('importar:docentes')->dailyAt('02:00');
    }

    /**
     * Registrar los comandos de la aplicación.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
