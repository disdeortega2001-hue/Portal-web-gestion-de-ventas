<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Services\TableroService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Tablero administrativo. La vista se dibuja con los datos iniciales y luego
 * consulta "datos" cada 60 s para mantener las métricas en tiempo real.
 */
class TableroController extends Controller
{
    public function __construct(private readonly TableroService $tablero)
    {
    }

    public function index(): View
    {
        return view('gestion.tablero', ['metricas' => $this->tablero->metricas()]);
    }

    public function datos(): JsonResponse
    {
        return response()->json($this->tablero->metricas());
    }
}
