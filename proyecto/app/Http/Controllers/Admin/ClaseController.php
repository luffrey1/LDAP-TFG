<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClaseGrupo;
use App\Models\Alumno;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ClaseController extends Controller
{
    /**
     * Display a listing of all classes.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $grupos = ClaseGrupo::withCount('alumnos')
            ->with('profesor')
            ->orderBy('nivel')
            ->orderBy('curso')
            ->orderBy('seccion')
            ->get();
            
        return view('admin.clases.index', compact('grupos'));
    }

    /**
     * Display the specified class with its students and details.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $grupo = ClaseGrupo::with('profesor')
            ->findOrFail($id);
        
        $alumnos = Alumno::where('clase_grupo_id', $id)
            ->orderBy('apellidos')
            ->orderBy('nombre')
            ->get();
            
        return view('admin.clases.show', compact('grupo', 'alumnos'));
    }
} 