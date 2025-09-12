<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UdhTokenService;

class RefreshUdhToken extends Command
{
    protected $signature = 'udh:refresh-token';
    protected $description = 'Renueva el token de la API UDH y lo guarda en BD';

    public function handle(UdhTokenService $tokenService)
    {
        $tokenService->refreshToken();
        $this->info('✅ Token UDH actualizado y guardado en BD');
    }
}
