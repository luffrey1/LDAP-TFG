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
        // Log de todos los datos recibidos para depuración
        Log::debug('Datos recibidos en EventoController@store: ' . json_encode($request->all()));
        
        // Validación con nombres de los campos del formulario
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
            'color' => 'nullable|string|max:20',
            'allDay' => 'sometimes|boolean'
        ]);

        try {
            DB::beginTransaction();
            
            // DEPURACIÓN: Registrar toda la sesión para ver qué hay disponible
            Log::debug('TODA LA SESIÓN: ' . json_encode(session()->all()));
            
            // Obtener el nombre completo del usuario LDAP de la sesión
            $nombreLDAP = 'Usuario del sistema';
            
            // DEPURACIÓN: Alternativas para identificar el usuario LDAP
            $alternativas = [];
            
            // Mostrar todos los enfoques posibles
            if (session()->has('auth_user')) {
                $alternativas['auth_user_completo'] = session('auth_user');
                $alternativas['auth_user.nombre'] = session('auth_user.nombre');
                $alternativas['auth_user.name'] = session('auth_user.name');
                $alternativas['auth_user.username'] = session('auth_user.username');
                $alternativas['auth_user.displayName'] = session('auth_user.displayName');
                $alternativas['auth_user.email'] = session('auth_user.email'); 
                
                Log::info('ALTERNATIVAS DE NOMBRE: ' . json_encode($alternativas));
                
                // Intentar obtener el nombre completo real (no el username)
                if (!empty(session('auth_user.nombre'))) {
                    $nombreLDAP = session('auth_user.nombre');
                    Log::debug('1. Usando nombre completo del LDAP: ' . $nombreLDAP);
                } 
                elseif (!empty(session('auth_user.name'))) {
                    $nombreLDAP = session('auth_user.name');
                    Log::debug('2. Usando name del LDAP: ' . $nombreLDAP);
                }
                // Si no hay nombre completo, usar el username
                elseif (!empty(session('auth_user.username'))) {
                    $nombreLDAP = session('auth_user.username');
                    Log::debug('3. Usando username del LDAP: ' . $nombreLDAP);
                }
                // Si aún no tenemos nombre, intentar con displayName
                elseif (!empty(session('auth_user.displayName'))) {
                    $nombreLDAP = session('auth_user.displayName');
                    Log::debug('4. Usando displayName del LDAP: ' . $nombreLDAP);
                }
                // LDAP-Admin como caso especial
                elseif (session('auth_user.username') === 'ldap-admin') {
                    $nombreLDAP = 'Administrador LDAP';
                    Log::debug('5. Detectado ldap-admin, usando nombre especial');
                }
                // Si nada funciona, usar un valor por defecto identificable pero con un mensaje claro
                else {
                    $nombreLDAP = 'Usuario LDAP';
                    Log::warning('6. No se pudo obtener el nombre del usuario LDAP, usando valor por defecto');
                }
            } else {
                // Fallback si no hay sesión de LDAP
                $nombreLDAP = Auth::check() ? Auth::user()->name : 'Usuario del sistema';
                Log::warning('7. No hay sesión LDAP activa, usando Auth: ' . $nombreLDAP);
            }
            
            // HARDCODEAR para LDAP-ADMIN
            if (session('auth_user.username') === 'ldap-admin') {
                $nombreLDAP = 'Administrador LDAP';
                Log::info('CASE ESPECIAL: Detectado ldap-admin, forzando nombre: Administrador LDAP');
            }
            
            Log::info('NOMBRE FINAL SELECCIONADO: ' . $nombreLDAP);
            
            // Crear evento directamente usando el usuario autenticado vía LDAP
            $evento = new Evento;
            $evento->titulo = $request->title;
            $evento->descripcion = $request->description;
            $evento->fecha_inicio = $request->start;
            $evento->fecha_fin = $request->end;
            $evento->color = $request->color ?? '#3788d8';
            $evento->todo_el_dia = $request->has('allDay') && $request->allDay;
            
            // Usar el ID LDAP directamente (ignorando las restricciones de clave foránea)
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // Obtenemos ID del usuario LDAP de la sesión
            $evento->creado_por = session('auth_user.id') ?? 1; // ID del usuario o 1 como fallback
            
            // Guardamos el nombre exacto del usuario LDAP
            $evento->nombre_creador = $nombreLDAP;
            
            Log::debug('Datos del evento antes de guardar: ' . json_encode([
                'titulo' => $evento->titulo,
                'descripcion' => $evento->descripcion,
                'fecha_inicio' => $evento->fecha_inicio,
                'fecha_fin' => $evento->fecha_fin,
                'color' => $evento->color,
                'todo_el_dia' => $evento->todo_el_dia,
                'creado_por' => $evento->creado_por,
                'nombre_creador' => $evento->nombre_creador
            ]));
            
            // Usar consulta SQL directa para insertar el evento sin validación de claves foráneas
            $id = DB::table('eventos')->insertGetId([
                'titulo' => $evento->titulo,
                'descripcion' => $evento->descripcion,
                'fecha_inicio' => $evento->fecha_inicio,
                'fecha_fin' => $evento->fecha_fin,
                'color' => $evento->color,
                'todo_el_dia' => $evento->todo_el_dia ? 1 : 0,
                'creado_por' => $evento->creado_por,
                'nombre_creador' => $evento->nombre_creador,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Verificar que el nombre del creador se guardó correctamente
            $eventoGuardado = DB::table('eventos')->where('id', $id)->first();
            Log::info('VERIFICACIÓN - Evento guardado con nombre: ' . ($eventoGuardado->nombre_creador ?? 'NO GUARDADO'));
            
            // Restablecer la verificación de claves foráneas
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            DB::commit();
            
            Log::info("Evento creado para el usuario LDAP: " . $evento->nombre_creador);
            
            // Redirigir de vuelta al calendario con mensaje de éxito y datos adicionales
            $mensajeDepuracion = "Evento creado por: " . $nombreLDAP;
            return redirect()->route('dashboard.calendario')
                ->with('success', 'Evento creado correctamente. ' . $mensajeDepuracion);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear evento: ' . $e->getMessage() . ' - Traza: ' . $e->getTraceAsString());
            
            // Redirigir de vuelta al calendario con mensaje de error
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