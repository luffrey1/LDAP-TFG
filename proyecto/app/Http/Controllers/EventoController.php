<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Evento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EventoController extends Controller
{
    /**
     * Muestra la página del calendario.
     */
    public function index()
    {
        try {
            $eventos = Evento::all()->map(function ($evento) {
                // Obtenemos información sobre quién creó el evento
                $creador = $evento->creador ? $evento->creador->nombre : 'Sistema';
                $esAdmin = session('auth_user.is_admin') || session('auth_user.username') === 'ldap-admin';
                $creadoPorUsuarioActual = $evento->creado_por == Auth::id();
                
                // Determinamos si el usuario actual puede editar/eliminar este evento
                $puedeEditar = $esAdmin || $creadoPorUsuarioActual;
                
                return [
                    'id' => $evento->id,
                    'title' => $evento->titulo,
                    'start' => $evento->fecha_inicio,
                    'end' => $evento->fecha_fin,
                    'description' => $evento->descripcion,
                    'backgroundColor' => $evento->color,
                    'borderColor' => $evento->color,
                    'allDay' => $evento->todo_el_dia,
                    'creador' => $creador,
                    'creado_por' => $evento->creado_por,
                    'editable' => $puedeEditar // Este valor será usado en el frontend
                ];
            });

            return view('dashboard.calendario', compact('eventos'));
        } catch (\Exception $e) {
            Log::error('Error al cargar eventos: ' . $e->getMessage());
            return view('dashboard.calendario', ['eventos' => []]);
        }
    }

    /**
     * Almacena un nuevo evento en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'color' => 'required|string|max:50',
            'todo_el_dia' => 'boolean'
        ]);

        try {
            $todoElDia = $request->has('todo_el_dia');
            
            // Formatear las fechas según si es todo el día o no
            $fechaInicio = Carbon::parse($request->fecha_inicio);
            $fechaFin = Carbon::parse($request->fecha_fin);
            
            if ($todoElDia) {
                // Para eventos de todo el día, asegurar que no tenga horas específicas
                $fechaInicio = $fechaInicio->startOfDay();
                $fechaFin = $fechaFin->endOfDay();
            }

            $evento = Evento::create([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'color' => $request->color,
                'todo_el_dia' => $todoElDia,
                'creado_por' => Auth::id() ?: session('auth_user.id')
            ]);

            Log::info('Evento creado con ID: ' . $evento->id . ' por usuario: ' . (Auth::id() ?: session('auth_user.id')));
            
            return redirect()->route('dashboard.calendario')->with('success', 'Evento creado correctamente');
        } catch (\Exception $e) {
            Log::error('Error al crear evento: ' . $e->getMessage());
            return redirect()->route('dashboard.calendario')->with('error', 'Error al crear el evento: ' . $e->getMessage());
        }
    }

    /**
     * Actualiza un evento existente.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'color' => 'required|string|max:50',
            'todo_el_dia' => 'boolean'
        ]);

        try {
            $evento = Evento::findOrFail($id);
            
            // Verificar permisos para editar
            $esAdmin = session('auth_user.is_admin') || session('auth_user.username') === 'ldap-admin';
            $creadoPorUsuarioActual = $evento->creado_por == (Auth::id() ?: session('auth_user.id'));
            
            if (!$esAdmin && !$creadoPorUsuarioActual) {
                Log::warning('Intento no autorizado de actualizar evento ID: ' . $id . ' por usuario: ' . (Auth::id() ?: session('auth_user.id')));
                return redirect()->route('dashboard.calendario')
                    ->with('error', 'No tienes permiso para editar este evento.');
            }
            
            $todoElDia = $request->has('todo_el_dia');
            
            // Formatear las fechas según si es todo el día o no
            $fechaInicio = Carbon::parse($request->fecha_inicio);
            $fechaFin = Carbon::parse($request->fecha_fin);
            
            if ($todoElDia) {
                // Para eventos de todo el día, asegurar que no tenga horas específicas
                $fechaInicio = $fechaInicio->startOfDay();
                $fechaFin = $fechaFin->endOfDay();
            }

            $evento->update([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'color' => $request->color,
                'todo_el_dia' => $todoElDia,
                'actualizado_por' => Auth::id() ?: session('auth_user.id')
            ]);

            Log::info('Evento actualizado con ID: ' . $id . ' por usuario: ' . (Auth::id() ?: session('auth_user.id')));
            
            return redirect()->route('dashboard.calendario')->with('success', 'Evento actualizado correctamente');
        } catch (\Exception $e) {
            Log::error('Error al actualizar evento: ' . $e->getMessage());
            return redirect()->route('dashboard.calendario')->with('error', 'Error al actualizar el evento: ' . $e->getMessage());
        }
    }

    /**
     * Elimina un evento.
     */
    public function destroy($id)
    {
        try {
            $evento = Evento::findOrFail($id);
            
            // Verificar permisos para eliminar
            $esAdmin = session('auth_user.is_admin') || session('auth_user.username') === 'ldap-admin';
            $creadoPorUsuarioActual = $evento->creado_por == (Auth::id() ?: session('auth_user.id'));
            
            if (!$esAdmin && !$creadoPorUsuarioActual) {
                Log::warning('Intento no autorizado de eliminar evento ID: ' . $id . ' por usuario: ' . (Auth::id() ?: session('auth_user.id')));
                return redirect()->route('dashboard.calendario')
                    ->with('error', 'No tienes permiso para eliminar este evento.');
            }
            
            $evento->delete();

            Log::info('Evento eliminado con ID: ' . $id . ' por usuario: ' . (Auth::id() ?: session('auth_user.id')));
            
            return redirect()->route('dashboard.calendario')->with('success', 'Evento eliminado correctamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar evento: ' . $e->getMessage());
            return redirect()->route('dashboard.calendario')->with('error', 'Error al eliminar el evento: ' . $e->getMessage());
        }
    }
} 