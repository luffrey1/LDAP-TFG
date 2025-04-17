<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    public function test()
    {
        Log::info('Prueba de TestController ejecutada');
        return response()->json(['message' => 'Prueba exitosa desde TestController']);
    }
} 