<?php

namespace App\Http\Controllers;

use App\Services\UdhTokenService;
use Illuminate\Support\Facades\Http;

class TokenUdhController extends Controller
{
    protected $udhToken;

    public function __construct(UdhTokenService $udhToken)
    {
        $this->udhToken = $udhToken;
    }

    public function token()
    {
        $token = $this->udhToken->getActiveToken();

        return response()->json([
            'token_actual'  => $token->token_actual,
            'valido_hasta'  => $token->valido_hasta,
            'token_proximo' => $token->token_proximo,
        ]);
    }

    public function docentes()
    {
        $token = $this->udhToken->getActiveToken()->token_actual;
        $url = "http://www.udh.edu.pe/websauh/DocentesAPI.aspx?action=all&token={$token}";

        $response = Http::get($url);

        if ($response->failed()) {
            return response()->json(['error' => 'No se pudo obtener docentes'], 500);
        }

        return $response->json();
    }
}
