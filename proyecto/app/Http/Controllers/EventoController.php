<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Evento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EventoController extends Controller
{
    /**
     * Muestra el calendario con eventos.
     */
    public function index()
    {
        // Obtener el ID del usuario actual
        $userId = session('auth_user.id') ?: (Auth::check() ? Auth::id() : null);
        
        if (!$userId) {
            return redirect()->route('login')->with('error', 'Debe iniciar sesión para acceder al calendario');
        }
        
        try {
            // DEPURACIÓN - Registrar TODOS los datos de la sesión
            $sesionCompleta = session()->all();
            Log::info('SESIÓN COMPLETA: ' . json_encode($sesionCompleta));
            
            // Datos específicos del usuario
            $datosUsuario = [
                'auth_user_id' => session('auth_user.id'),
                'auth_user_nombre' => session('auth_user.nombre'),
                'auth_user_name' => session('auth_user.name'),
                'auth_user_username' => session('auth_user.username'),
                'auth_user_role' => session('auth_user.role'),
                'es_profesor' => session('auth_user.role') === 'profesor',
                'grupos' => session('auth_user.grupos') ?? []
            ];
            Log::info('DATOS DE USUARIO EN INDEX: ' . json_encode($datosUsuario));
            
            // FORZAR ACCESO: Obtener todos los eventos sin filtro por usuario/grupo
            $query = Evento::query();
            
            // Si necesitas depurar qué eventos se están cargando
            Log::info('Total de eventos encontrados: ' . $query->count());
            
            // Opcional: Listar los eventos encontrados para depuración
            $eventosEncontrados = $query->get();
            $eventosDebug = $eventosEncontrados->map(function($evento) {
                return [
                    'id' => $evento->id,
                    'titulo' => $evento->titulo,
                    'creado_por' => $evento->creado_por,
                    'nombre_creador' => $evento->nombre_creador ?? 'No definido'
                ];
            });
            Log::info('EVENTOS ENCONTRADOS EN BD: ' . json_encode($eventosDebug));
            
            // Formatear eventos para FullCalendar
            $eventosFormateados = $eventosEncontrados->map(function($evento) use ($userId, $datosUsuario) {
                // Verificar si el usuario puede editar este evento
                $esAdmin = session('auth_user.is_admin') ?? false;
                $creadoPorUsuarioActual = $evento->creado_por == $userId;
                $puedeEditar = $esAdmin || $creadoPorUsuarioActual;
                
                // CORREGIR NOMBRE: Si el nombre es 'Sistema' o está vacío, asignar uno mejor
                if (empty($evento->nombre_creador) || $evento->nombre_creador == 'Sistema' || $evento->nombre_creador == 'Usuario del sistema') {
                    // Si es ID 7, sabemos que es ldap-admin
                    if ($evento->creado_por == 7) {
                        $nombreCreador = 'Administrador LDAP';
                    } else {
                        // Para otros usuarios, usamos una etiqueta genérica pero clara
                        $nombreCreador = 'Usuario #' . $evento->creado_por;
                    }
                    
                    // Actualizar en la base de datos
                    DB::table('eventos')
                        ->where('id', $evento->id)
                        ->update(['nombre_creador' => $nombreCreador]);
                        
                    Log::info("Corregido nombre 'Sistema' para evento {$evento->id}: {$nombreCreador}");
                } else {
                    $nombreCreador = $evento->nombre_creador;
                }
                
                // DEPURACIÓN - Registrar el nombre que se usará para mostrar
                Log::info("NOMBRE A MOSTRAR para evento {$evento->id}: {$nombreCreador}");
                
                return [
                    'id' => $evento->id,
                    'title' => $evento->titulo,
                    'start' => $evento->fecha_inicio->toIso8601String(),
                    'end' => $evento->fecha_fin->toIso8601String(),
                    'description' => $evento->descripcion,
                    'backgroundColor' => $evento->color,
                    'borderColor' => $evento->color,
                    'allDay' => $evento->todo_el_dia,
                    'editable' => $puedeEditar,
                    'creador' => $nombreCreador,
                    'creado_por' => $evento->creado_por // Añadir para depuración
                ];
            });
            
            // Si no hay eventos, añadir un log
            if ($eventosFormateados->isEmpty()) {
                Log::warning('No se encontraron eventos para mostrar al usuario: ' . json_encode($datosUsuario));
            } else {
                Log::info('Cantidad de eventos enviados a la vista: ' . $eventosFormateados->count());
            }
            
            return view('dashboard.calendario', compact('eventosFormateados'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar eventos: ' . $e->getMessage() . ' - Traza: ' . $e->getTraceAsString());
            return view('dashboard.calendario')->with('error', 'Error al cargar los eventos: ' . $e->getMessage());
        }
    }

    /**
     * Almacena un nuevo evento.
     */
    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'color' => 'nullable|string|max:50',
            'todo_el_dia' => 'nullable|boolean'
        ]);

        try {
            DB::beginTransaction();
            
            // Obtener el ID del usuario actual
            $userId = session('auth_user.id') ?: (Auth::check() ? Auth::id() : null);
            
            if (!$userId) {
                throw new \Exception('No se pudo identificar al usuario.');
            }
            
            // Formatear las fechas según si es todo el día o no
            $todoElDia = $request->has('todo_el_dia') && $request->todo_el_dia == "1";
            $fechaInicio = Carbon::parse($request->fecha_inicio);
            $fechaFin = Carbon::parse($request->fecha_fin);
            
            if ($todoElDia) {
                // Para eventos de todo el día, asegurar que no tenga horas específicas
                $fechaInicio = $fechaInicio->startOfDay();
                $fechaFin = $fechaFin->endOfDay();
            }

            // Crear el evento
            $evento = new Evento([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'color' => $request->color ?? '#3788d8',
                'todo_el_dia' => $todoElDia,
                'creado_por' => $userId
            ]);

            $evento->save();
            DB::commit();
            
            // Obtener el nombre del usuario desde LDAP o la sesión
            $nombreLDAP = session('auth_user.nombre') ?: session('auth_user.username');
            
            Log::info('Evento creado por usuario: ' . $nombreLDAP . ' (ID: ' . $userId . ')');
            $mensajeDepuracion = "Evento creado por: " . $nombreLDAP;
            
            return redirect()->route('dashboard.calendario')
                ->with('success', 'Evento creado correctamente.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear evento: ' . $e->getMessage() . ' - Traza: ' . $e->getTraceAsString());
            
            return redirect()->route('dashboard.calendario')
                ->with('error', 'Error al crear el evento: ' . $e->getMessage());
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