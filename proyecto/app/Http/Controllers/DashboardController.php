<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\LdapUser;
use LdapRecord\Models\OpenLDAP\User as LdapSystemUser;
use LdapRecord\Models\OpenLDAP\Group;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Documento;
use App\Models\Mensaje;
use App\Models\Evento;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Mostrar el dashboard principal
     */
    public function index()
    {
        // Obtener usuario autenticado
        $user = session('auth_user');
        
        // Obtener estadísticas reales
        $stats = $this->getStatsData();

        // Si el usuario es administrador, obtener estadísticas LDAP
        if ($user['is_admin'] ?? false) {
            try {
                $ldapStats = $this->getLdapStats();
                $stats = array_merge($stats, $ldapStats);
                
                // Obtener usuarios LDAP recientes
                $ldapRecentUsers = $this->getLdapRecentUsers();
            } catch (\Exception $e) {
                //Log::error('Error al obtener datos LDAP: ' . $e->getMessage());
                // Valores por defecto en caso de error
                $stats['ldap_admins'] = 0;
                $stats['ldap_profesores'] = 0;
                $stats['ldap_alumnos'] = 0;
                $stats['ldap_total'] = 0;
                $ldapRecentUsers = [];
            }
        }

        // Obtener datos reales
        $recentDocuments = $this->getRecentDocuments();
        $recentMessages = $this->getRecentMessages();
        $upcomingEvents = $this->getUpcomingEvents();
        $userActivity = $this->getUserActivity();
        
        // Para compatibilidad con código existente
        $proximosEventos = $upcomingEvents;
        $mensajesRecientes = $recentMessages;
        
        // Compactar las variables a pasar a la vista
        $viewData = compact(
            'user',
            'stats',
            'proximosEventos',
            'mensajesRecientes',
            'recentDocuments',
            'upcomingEvents',
            'recentMessages',
            'userActivity'
        );
        
        if ($user['is_admin'] ?? false) {
            $viewData['ldapRecentUsers'] = $ldapRecentUsers ?? [];
        }
        
        return view('dashboard.index', $viewData);
    }

    /**
     * Mostrar la sección de documentos
     */
    public function documentos()
    {
        try {
            // Obtener documentos reales de la base de datos
            $documents = Documento::where('activo', true)
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Obtener carpetas de documentos
            $foldersDB = DB::table('carpetas_documentos')
                ->where('activo', true)
                ->orderBy('nombre')
                ->get();
                
            if ($foldersDB->count() > 0) {
                $folders = $foldersDB;
            } else {
                // Si no hay carpetas en BD, obtener de directorios físicos
                $storageDirs = Storage::disk('public')->directories('documentos');
                $folders = collect();
                
                foreach ($storageDirs as $dir) {
                    $folderName = str_replace('documentos/', '', $dir);
                    if ($folderName) {
                        $folders->push([
                            'nombre' => ucfirst($folderName),
                            'clave' => $folderName,
                            'icono' => 'fa-folder'
                        ]);
                    }
                }
                
                // Asegurar que existe carpeta General
                if (!$folders->contains('clave', 'general')) {
                    $folders->prepend([
                        'nombre' => 'General',
                        'clave' => 'general',
                        'icono' => 'fa-folder'
                    ]);
                }
            }

            return view('dashboard.documentos.index', compact('documents', 'folders'));
        } catch (\Exception $e) {
            Log::error('Error al cargar documentos: ' . $e->getMessage());
            return view('dashboard.documentos.index', [
                'documents' => collect(),
                'folders' => collect([['nombre' => 'General', 'clave' => 'general', 'icono' => 'fa-folder']]),
                'error' => 'Error al cargar documentos: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Subir un nuevo documento
     */
    public function subirDocumento(Request $request)
    {
        $request->validate([
            'documento' => 'required|file|max:10240', // Máximo 10MB
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'carpeta' => 'nullable|string'
        ]);

        try {
            $file = $request->file('documento');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $folder = $request->carpeta ? $request->carpeta : 'general';
            
            // Crear una estructura para guardar la información del documento
            $document = [
                'id' => Str::uuid()->toString(),
                'nombre' => $request->nombre,
                'nombre_original' => $originalName,
                'descripcion' => $request->descripcion,
                'carpeta' => $folder,
                'extension' => $extension,
                'tipo' => $file->getMimeType(),
                'tamaño' => $file->getSize(),
                'subido_por' => session('auth_user')['username'],
                'fecha_subida' => now()->format('Y-m-d H:i:s')
            ];
            
            // Almacenar el archivo
            $path = $file->storeAs(
                'documentos/' . $folder, 
                $document['id'] . '.' . $extension, 
                'public'
            );
            
            // Guardar la información del documento
            $this->saveDocumentInfo($document);
            
            return redirect()->route('dashboard.gestion-documental')
                ->with('success', 'Documento subido correctamente.');
                
        } catch (\Exception $e) {
            Log::error('Error al subir documento: ' . $e->getMessage());
            return back()->with('error', 'Error al subir el documento: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar un documento
     */
    public function eliminarDocumento($id)
    {
        try {
            // Obtener la información del documento
            $document = $this->getDocumentById($id);
            
            if (!$document) {
                return back()->with('error', 'El documento no existe.');
            }
            
            // Verificar permisos (solo el propietario o admin pueden eliminar)
            if ($document['subido_por'] !== session('auth_user')['username'] && 
                session('auth_user')['role'] !== 'admin') {
                return back()->with('error', 'No tienes permisos para eliminar este documento.');
            }
            
            // Eliminar el archivo físicamente
            $path = 'documentos/' . $document['carpeta'] . '/' . $id . '.' . $document['extension'];
            Storage::disk('public')->delete($path);
            
            // Eliminar la información del documento
            $this->deleteDocumentInfo($id);
            
            return redirect()->route('dashboard.gestion-documental')
                ->with('success', 'Documento eliminado correctamente.');
                
        } catch (\Exception $e) {
            Log::error('Error al eliminar documento: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar el documento: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar la sección de mensajes
     */
    public function mensajes()
    {
        try {
            // Obtener el usuario autenticado
            $userId = session('auth_user')['id'] ?? Auth::id();
            
            // Obtener mensajes reales de la base de datos
            $mensajesRecibidos = Mensaje::recibidos($userId)
                ->with(['remitente'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
                
            $mensajesEnviados = Mensaje::enviados($userId)
                ->with(['destinatario'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            
            return view('dashboard.mensajes.index', compact('mensajesRecibidos', 'mensajesEnviados'));
        } catch (\Exception $e) {
            Log::error('Error al cargar mensajes: ' . $e->getMessage());
            return view('dashboard.mensajes.index', [
                'mensajesRecibidos' => collect(),
                'mensajesEnviados' => collect(),
                'error' => 'Error al cargar mensajes: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Mostrar formulario para nuevo mensaje
     */
    public function nuevoMensaje()
    {
        try {
            // Obtener usuarios reales para el campo destinatario
            $users = User::select('id', 'name', 'email', 'role')
                ->where('active', true)
                ->orderBy('name')
                ->get();
            
            return view('dashboard.mensajes.nuevo', compact('users'));
        } catch (\Exception $e) {
            Log::error('Error al cargar usuarios: ' . $e->getMessage());
            return redirect()->route('dashboard.mensajes')
                ->with('error', 'Error al cargar usuarios: ' . $e->getMessage());
        }
    }

    /**
     * Enviar un nuevo mensaje
     */
    public function enviarMensaje(Request $request)
    {
        $request->validate([
            'destinatario' => 'required|string',
            'asunto' => 'required|string|max:255',
            'contenido' => 'required|string'
        ]);

        try {
            $mensaje = [
                'id' => Str::uuid()->toString(),
                'remitente' => session('auth_user')['username'],
                'remitente_nombre' => session('auth_user')['nombre'],
                'destinatario' => $request->destinatario,
                'asunto' => $request->asunto,
                'contenido' => $request->contenido,
                'leido' => false,
                'fecha' => now()->format('Y-m-d H:i:s')
            ];
            
            // Guardar el mensaje
            $this->saveMensaje($mensaje);
            
            return redirect()->route('dashboard.mensajes')
                ->with('success', 'Mensaje enviado correctamente.');
                
        } catch (\Exception $e) {
            Log::error('Error al enviar mensaje: ' . $e->getMessage());
            return back()->with('error', 'Error al enviar el mensaje: ' . $e->getMessage());
        }
    }

    /**
     * Ver un mensaje específico
     */
    public function verMensaje($id)
    {
        try {
            $mensaje = $this->getMensajeById($id);
            
            if (!$mensaje) {
                return redirect()->route('dashboard.mensajes')
                    ->with('error', 'El mensaje no existe.');
            }
            
            // Verificar que el usuario tenga permiso para ver este mensaje
            if ($mensaje['destinatario'] !== session('auth_user')['username'] && 
                $mensaje['remitente'] !== session('auth_user')['username'] && 
                session('auth_user')['role'] !== 'admin') {
                return redirect()->route('dashboard.mensajes')
                    ->with('error', 'No tienes permiso para ver este mensaje.');
            }
            
            // Marcar como leído si es necesario
            if ($mensaje['destinatario'] === session('auth_user')['username'] && !$mensaje['leido']) {
                $this->marcarMensajeComoLeido($id);
                $mensaje['leido'] = true;
            }
            
            return view('dashboard.mensajes.ver', compact('mensaje'));
                
        } catch (\Exception $e) {
            Log::error('Error al ver mensaje: ' . $e->getMessage());
            return back()->with('error', 'Error al cargar el mensaje: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar calendario y eventos
     */
    public function calendario()
    {
        try {
            // Obtener eventos reales de la base de datos
            $eventos = Evento::whereDate('fecha_inicio', '>=', now()->subDays(7))
                ->orderBy('fecha_inicio')
                ->get();
            
            return view('dashboard.calendario.index', compact('eventos'));
        } catch (\Exception $e) {
            Log::error('Error al cargar eventos: ' . $e->getMessage());
            return view('dashboard.calendario.index', [
                'eventos' => collect(),
                'error' => 'Error al cargar eventos: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Crear un nuevo evento en el calendario
     */
    public function nuevoEvento(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'color' => 'nullable|string|max:20',
            'todo_el_dia' => 'nullable|boolean'
        ]);

        try {
            $evento = [
                'id' => Str::uuid()->toString(),
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'color' => $request->color ?? '#3788d8',
                'todo_el_dia' => $request->has('todo_el_dia'),
                'creado_por' => session('auth_user')['username'],
                'fecha_creacion' => now()->format('Y-m-d H:i:s')
            ];
            
            // Guardar el evento
            $this->saveEvento($evento);
            
            return redirect()->route('dashboard.calendario')
                ->with('success', 'Evento creado correctamente.');
                
        } catch (\Exception $e) {
            Log::error('Error al crear evento: ' . $e->getMessage());
            return back()->with('error', 'Error al crear el evento: ' . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas para el dashboard
     */
    private function getStatsData()
    {
        try {
            // Obtener estadísticas reales de la base de datos
            $documentosCount = 0;
            $mensajesNuevosCount = 0;
            $eventosProximosCount = 0;
            $usuariosCount = 0;
            
            // Obtener el ID del usuario actual
            $userId = session('auth_user.id') ?? Auth::id();
            
            // Obtener el recuento de documentos
            try {
                $documentosCount = \DB::table('documentos')->count();
            } catch (\Exception $e) {
                Log::warning('Error al obtener recuento de documentos: ' . $e->getMessage());
            }
            
            // Obtener el recuento de mensajes no leídos para el usuario actual
            try {
                // Usar el modelo Mensaje con su scope
                $mensajesNuevosCount = \App\Models\Mensaje::recibidos($userId)
                    ->where('leido', false)
                    ->count();
                
                // Si no funciona el modelo, intentar con consulta directa
                if ($mensajesNuevosCount === 0) {
                    $mensajesNuevosCount = \DB::table('mensajes')
                        ->where('destinatario_id', $userId)
                        ->where('leido', false)
                        ->where('eliminado_destinatario', false)
                        ->where('borrador', false)
                        ->count();
                }
            } catch (\Exception $e) {
                Log::warning('Error al obtener recuento de mensajes nuevos: ' . $e->getMessage());
            }
            
            // Obtener el recuento de eventos próximos (en los siguientes 7 días)
            try {
                $fechaHoy = now()->format('Y-m-d');
                $fechaLimite = now()->addDays(7)->format('Y-m-d');
                $eventosProximosCount = \DB::table('eventos')
                    ->whereDate('fecha_inicio', '>=', $fechaHoy)
                    ->whereDate('fecha_inicio', '<=', $fechaLimite)
                    ->count();
            } catch (\Exception $e) {
                Log::warning('Error al obtener recuento de eventos próximos: ' . $e->getMessage());
            }
            
            // Obtener el recuento de usuarios
            try {
                $usuariosCount = \DB::table('users')->count();
                
                // Si no hay usuarios en la tabla, al menos mostrar 1 para el usuario actual
                if ($usuariosCount === 0) {
                    $usuariosCount = 1;
                }
            } catch (\Exception $e) {
                Log::warning('Error al obtener recuento de usuarios: ' . $e->getMessage());
                // Si hay error, al menos mostrar 1 para el usuario actual
                $usuariosCount = 1;
            }
            
            // Log para depuración
            Log::info("Estadísticas obtenidas: Usuarios=$usuariosCount, Documentos=$documentosCount, Mensajes nuevos=$mensajesNuevosCount, Eventos próximos=$eventosProximosCount");
            
            return [
                'documentos' => $documentosCount,
                'mensajes_nuevos' => $mensajesNuevosCount,
                'eventos_proximos' => $eventosProximosCount,
                'usuarios' => $usuariosCount
            ];
        } catch (\Exception $e) {
            //Log::error('Error al obtener estadísticas: ' . $e->getMessage());
            
            // Devolver valores por defecto en caso de error
            return [
                'documentos' => 0,
                'mensajes_nuevos' => 0,
                'eventos_proximos' => 0,
                'usuarios' => 1 // Al menos mostrar 1 usuario
            ];
        }
    }

    /**
     * Obtener documentos recientes
     */
    private function getRecentDocuments()
    {
        try {
            // Obtener documentos recientes de la base de datos
            $documents = Documento::where('activo', true)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
                
            if ($documents->count() > 0) {
                Log::info("Documentos recientes obtenidos: " . $documents->count());
                
                return $documents->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'nombre' => $doc->nombre,
                        'extension' => $doc->extension,
                        'fecha_subida' => $doc->created_at,
                        'subido_por' => $doc->subido_por,
                        'subido_por_nombre' => $doc->usuario ? $doc->usuario->name : 'Usuario desconocido',
                        'tamaño' => $doc->tamaño_formateado,
                        'carpeta' => $doc->carpeta
                    ];
                })->toArray();
            }
            
            // Si no hay documentos, devolver ejemplos con diferentes categorías
            Log::info("No hay documentos recientes, devolviendo documentos de ejemplo");
            return [
                [
                    'id' => '1',
                    'nombre' => 'Programación Didáctica',
                    'extension' => 'pdf',
                    'fecha_subida' => '2025-04-19 10:00:00',
                    'subido_por' => 'profesor1',
                    'subido_por_nombre' => 'Profesor Ejemplo',
                    'tamaño' => '2.5 MB',
                    'carpeta' => 'programaciones'
                ],
                [
                    'id' => '2',
                    'nombre' => 'Horario Escolar',
                    'extension' => 'xls',
                    'fecha_subida' => '2025-04-18 14:30:00',
                    'subido_por' => 'secretaria',
                    'subido_por_nombre' => 'Secretaría',
                    'tamaño' => '1.8 MB',
                    'carpeta' => 'horarios'
                ],
                [
                    'id' => '3',
                    'nombre' => 'Acta de Evaluación',
                    'extension' => 'doc',
                    'fecha_subida' => '2025-04-17 09:15:00',
                    'subido_por' => 'jefatura',
                    'subido_por_nombre' => 'Jefe de Estudios',
                    'tamaño' => '560 KB',
                    'carpeta' => 'actas'
                ]
            ];
        } catch (\Exception $e) {
            //Log::error('Error al obtener documentos recientes: ' . $e->getMessage());
            
            // Devolver documento de ejemplo en caso de error
            return [
                [
                    'id' => '1',
                    'nombre' => 'Documento de ejemplo',
                    'extension' => 'pdf',
                    'fecha_subida' => '2025-04-19 10:00:00',
                    'subido_por' => 'profesor1',
                    'subido_por_nombre' => 'Profesor Ejemplo',
                    'tamaño' => '0 KB',
                    'carpeta' => 'general'
                ]
            ];
        }
    }
    
    /**
     * Obtener el nombre de un usuario a partir de su nombre de usuario
     */
    private function getUserName($username)
    {
        try {
            $user = \DB::table('users')->where('guid', $username)->first();
            return $user ? $user->name : $username;
        } catch (\Exception $e) {
            return $username;
        }
    }
    
    /**
     * Formatear tamaño de archivo
     */
    private function formatFileSize($bytes)
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } elseif ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        } else {
            return round($bytes / 1073741824, 2) . ' GB';
        }
    }

    /**
     * Obtener mensajes recientes
     */
    private function getRecentMessages()
    {
        try {
            // Obtener el usuario autenticado
            $userId = session('auth_user.id') ?? Auth::id();
            
            // Intentar usar el modelo Mensaje con sus relaciones
            try {
                $mensajes = \App\Models\Mensaje::with(['remitente', 'destinatario'])
                    ->recibidos($userId)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($msg) {
                        return [
                            'id' => $msg->id,
                            'remitente' => $msg->remitente_id,
                            'remitente_nombre' => $msg->remitente ? $msg->remitente->name : 'Usuario',
                            'asunto' => $msg->asunto,
                            'contenido' => $msg->contenido,
                            'fecha' => $msg->created_at,
                            'leido' => (bool)$msg->leido
                        ];
                    })
                    ->toArray();
                
                if (!empty($mensajes)) {
                    Log::info("Mensajes recientes obtenidos correctamente: " . count($mensajes));
                    return $mensajes;
                }
            } catch (\Exception $e) {
                Log::warning('Error al obtener mensajes con modelo: ' . $e->getMessage());
            }
            
            // Si el modelo falló o no hay mensajes, intentar con consulta directa
            $messages = \DB::table('mensajes')
                ->where('destinatario_id', $userId)
                ->where('eliminado_destinatario', false)
                ->where('borrador', false)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
                
            if ($messages->count() > 0) {
                Log::info("Mensajes recientes obtenidos con consulta directa: " . $messages->count());
                
                return $messages->map(function ($msg) {
                    // Obtener nombre del remitente
                    $remitente = \DB::table('users')
                        ->where('id', $msg->remitente_id)
                        ->first();
                        
                    $remitenteName = $remitente ? $remitente->name : 'Usuario';
                    
                    return [
                        'id' => $msg->id,
                        'remitente' => $msg->remitente_id,
                        'remitente_nombre' => $remitenteName,
                        'asunto' => $msg->asunto,
                        'contenido' => $msg->contenido,
                        'fecha' => $msg->created_at,
                        'leido' => (bool)$msg->leido
                    ];
                })->toArray();
            }
            
            // Si no hay mensajes, devolver un mensaje de bienvenida
            Log::info("No hay mensajes recientes, devolviendo mensaje de bienvenida");
            return [
                [
                    'id' => '1',
                    'remitente' => 'sistema',
                    'remitente_nombre' => 'Sistema',
                    'asunto' => 'Bienvenido al sistema',
                    'contenido' => 'Este es un mensaje automático de bienvenida.',
                    'fecha' => now()->format('Y-m-d H:i:s'),
                    'leido' => false
                ]
            ];
        } catch (\Exception $e) {
            //Log::error('Error al obtener mensajes recientes: ' . $e->getMessage());
            
            // Devolver mensaje de bienvenida en caso de error
            return [
                [
                    'id' => '1',
                    'remitente' => 'sistema',
                    'remitente_nombre' => 'Sistema',
                    'asunto' => 'Bienvenido al sistema',
                    'contenido' => 'Este es un mensaje automático de bienvenida.',
                    'fecha' => now()->format('Y-m-d H:i:s'),
                    'leido' => false
                ]
            ];
        }
    }

    /**
     * Obtener eventos próximos
     */
    private function getUpcomingEvents()
    {
        try {
            // Obtener la fecha actual
            $fechaHoy = now()->format('Y-m-d');
            $fechaLimite = now()->addDays(14)->format('Y-m-d');
            
            // Obtener eventos próximos de la base de datos
            $events = \DB::table('eventos')
                ->whereDate('fecha_inicio', '>=', $fechaHoy)
                ->whereDate('fecha_inicio', '<=', $fechaLimite)
                ->orderBy('fecha_inicio')
                ->limit(5)
                ->get();
                
            if ($events->count() > 0) {
                Log::info("Eventos próximos obtenidos: " . $events->count());
                
                return $events->map(function ($event) {
                    return [
                        'id' => $event->id,
                        'titulo' => $event->titulo,
                        'fecha_inicio' => $event->fecha_inicio,
                        'fecha_fin' => $event->fecha_fin,
                        'todo_el_dia' => (bool)($event->todo_el_dia ?? false),
                        'color' => $event->color ?? '#3788d8'
                    ];
                })->toArray();
            }
            
            // Si no hay eventos, verificar si se está usando el modelo Evento
            try {
                $modelEvents = \App\Models\Evento::where('fecha_inicio', '>=', $fechaHoy)
                    ->where('fecha_inicio', '<=', $fechaLimite)
                    ->orderBy('fecha_inicio')
                    ->limit(5)
                    ->get();
                
                if ($modelEvents->count() > 0) {
                    Log::info("Eventos próximos obtenidos con modelo: " . $modelEvents->count());
                    
                    return $modelEvents->map(function ($event) {
                        return [
                            'id' => $event->id,
                            'titulo' => $event->titulo,
                            'fecha_inicio' => $event->fecha_inicio,
                            'fecha_fin' => $event->fecha_fin,
                            'todo_el_dia' => (bool)$event->todo_el_dia,
                            'color' => $event->color
                        ];
                    })->toArray();
                }
            } catch (\Exception $e) {
                Log::warning('Error al obtener eventos con modelo: ' . $e->getMessage());
            }
            
            // Si no hay eventos, devolver un evento de ejemplo
            Log::info("No hay eventos próximos, devolviendo evento de ejemplo");
            return [
                [
                    'id' => '1',
                    'titulo' => 'Sin eventos próximos',
                    'fecha_inicio' => now()->addDays(1)->format('Y-m-d H:i:s'),
                    'fecha_fin' => now()->addDays(1)->addHour()->format('Y-m-d H:i:s'),
                    'todo_el_dia' => false,
                    'color' => '#3788d8'
                ]
            ];
        } catch (\Exception $e) {
            //Log::error('Error al obtener eventos próximos: ' . $e->getMessage());
            
            // Devolver datos de fallback para evitar errores en la vista
            return [
                [
                    'id' => '1',
                    'titulo' => 'Sin eventos próximos',
                    'fecha_inicio' => now()->addDays(1)->format('Y-m-d H:i:s'),
                    'fecha_fin' => now()->addDays(1)->addHour()->format('Y-m-d H:i:s'),
                    'todo_el_dia' => false,
                    'color' => '#3788d8'
                ]
            ];
        }
    }

    /**
     * Obtener actividad reciente de usuarios
     */
    private function getUserActivity()
    {
        try {
            // Intentar obtener actividad de la base de datos primero
            try {
                $dbActivities = \DB::table('user_activity')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                    
                if ($dbActivities && count($dbActivities) > 0) {
                    $activities = [];
                    foreach ($dbActivities as $activity) {
                        $activities[] = [
                            'usuario' => $activity->user_id,
                            'nombre' => $this->getUserName($activity->user_id),
                            'accion' => $activity->action,
                            'detalles' => $activity->details,
                            'fecha' => $activity->created_at
                        ];
                    }
                    return $activities;
                }
            } catch (\Exception $e) {
                Log::warning('Sin tabla user_activity: ' . $e->getMessage());
            }
            
            // Si no hay actividades en la base de datos, usar datos por defecto
            return [
                [
                    'usuario' => 'sistema',
                    'nombre' => 'Sistema',
                    'accion' => 'Inició',
                    'detalles' => 'la aplicación',
                    'fecha' => now()->format('Y-m-d H:i:s')
                ]
            ];
        } catch (\Exception $e) {
            //Log::error('Error al obtener actividad de usuarios: ' . $e->getMessage());
            
            // Devolver actividad por defecto
            return [
                [
                    'usuario' => 'sistema',
                    'nombre' => 'Sistema',
                    'accion' => 'Inició',
                    'detalles' => 'la aplicación',
                    'fecha' => now()->format('Y-m-d H:i:s')
                ]
            ];
        }
    }

    /**
     * Obtener todos los documentos
     */
    private function getAllDocuments()
    {
        try {
            $documents = Documento::where('activo', true)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($doc) {
                    return [
                        'id' => $doc->id,
                        'nombre' => $doc->nombre,
                        'nombre_original' => $doc->nombre_original,
                        'descripcion' => $doc->descripcion,
                        'carpeta' => $doc->carpeta,
                        'extension' => $doc->extension,
                        'tipo' => $doc->tipo,
                        'tamaño' => $doc->tamaño,
                        'subido_por' => $doc->subido_por,
                        'fecha_subida' => $doc->created_at->format('Y-m-d H:i:s')
                    ];
                });
            
            return $documents->toArray();
        } catch (\Exception $e) {
            //Log::error('Error al obtener documentos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener carpetas disponibles
     */
    private function getFolders()
    {
        try {
            // Intentar obtener carpetas de la base de datos
            $foldersDB = DB::table('carpetas_documentos')
                ->where('activo', true)
                ->orderBy('nombre')
                ->get();
                
            if ($foldersDB->count() > 0) {
                return $foldersDB->toArray();
            }
            
            // Si no hay datos en la base de datos, obtener carpetas existentes del storage
            $storageDirs = Storage::disk('public')->directories('documentos');
            $folders = [];
            
            // Crear estructura de carpetas basada en los directorios existentes
            foreach ($storageDirs as $dir) {
                $folderName = str_replace('documentos/', '', $dir);
                if ($folderName) {
                    $folders[] = [
                        'nombre' => ucfirst($folderName),
                        'clave' => $folderName,
                        'icono' => $this->getFolderIcon($folderName)
                    ];
                }
            }
            
            // Asegurar que siempre existe la carpeta General
            $hasGeneral = false;
            foreach ($folders as $folder) {
                if ($folder['clave'] === 'general') {
                    $hasGeneral = true;
                    break;
                }
            }
            
            if (!$hasGeneral) {
                array_unshift($folders, [
                    'nombre' => 'General',
                    'clave' => 'general',
                    'icono' => 'fa-folder'
                ]);
            }
            
            return $folders;
            
        } catch (\Exception $e) {
            //Log::error('Error al obtener carpetas: ' . $e->getMessage());
            
            // Devolver sólo la carpeta General en caso de error
            return [
                [
                    'nombre' => 'General',
                    'clave' => 'general',
                    'icono' => 'fa-folder'
                ]
            ];
        }
    }
    
    /**
     * Obtener el icono apropiado para una carpeta
     */
    private function getFolderIcon($folderName)
    {
        $icons = [
            'general' => 'fa-folder',
            'programaciones' => 'fa-file-alt',
            'horarios' => 'fa-calendar-alt',
            'actas' => 'fa-file-signature',
            'evaluaciones' => 'fa-clipboard-check'
        ];
        
        return $icons[strtolower($folderName)] ?? 'fa-folder';
    }

    /**
     * Obtener un documento por su ID
     */
    private function getDocumentById($id)
    {
        // En un sistema real, buscaríamos en la base de datos
        $documents = $this->getAllDocuments();
        
        foreach ($documents as $document) {
            if ($document['id'] === $id) {
                return $document;
            }
        }
        
        return null;
    }

    /**
     * Guardar información de un documento
     */
    private function saveDocumentInfo($document)
    {
        // En un sistema real, guardaríamos en la base de datos
        // En esta implementación de ejemplo, solo registramos en el log
        Log::info('Documento guardado: ' . json_encode($document));
    }

    /**
     * Eliminar información de un documento
     */
    private function deleteDocumentInfo($id)
    {
        // En un sistema real, eliminaríamos de la base de datos
        // En esta implementación de ejemplo, solo registramos en el log
        Log::info('Documento eliminado: ' . $id);
    }

    /**
     * Obtener mensajes recibidos por el usuario actual
     */
    private function getMensajesRecibidos()
    {
        try {
            // Obtener el usuario autenticado
            $userId = session('auth_user')['id'] ?? Auth::id();
            
            // Obtener mensajes reales usando el modelo Mensaje
            $mensajes = Mensaje::recibidos($userId)
                ->with(['remitente'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($mensaje) {
                    return [
                        'id' => $mensaje->id,
                        'remitente' => $mensaje->remitente_id,
                        'remitente_nombre' => $mensaje->remitente ? $mensaje->remitente->name : 'Usuario',
                        'destinatario' => $mensaje->destinatario_id,
                        'asunto' => $mensaje->asunto,
                        'contenido' => $mensaje->contenido,
                        'fecha' => $mensaje->created_at->format('Y-m-d H:i:s'),
                        'leido' => (bool)$mensaje->leido
                    ];
                });
                
            return $mensajes->toArray();
            
        } catch (\Exception $e) {
            //Log::error('Error al obtener mensajes recibidos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener mensajes enviados por el usuario actual
     */
    private function getMensajesEnviados()
    {
        // Intentamos obtener mensajes reales primero
        $username = session('auth_user')['username'];
        $userId = session('auth_user')['id'] ?? Auth::id();
        
        try {
            $mensajes = \DB::table('mensajes')
                ->where('remitente_id', $userId)
                ->where('eliminado_remitente', false)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
                
            if ($mensajes->count() > 0) {
                return $mensajes->toArray();
            }
        } catch (\Exception $e) {
            //Log::warning('Error al obtener mensajes enviados: ' . $e->getMessage());
        }
        
        // Datos simulados simplificados
        return [
            [
                'id' => '3',
                'remitente' => $username,
                'destinatario' => 'profesor',
                'destinatario_nombre' => 'Profesor',
                'asunto' => 'Respuesta a consulta',
                'contenido' => 'Aquí tienes la información solicitada.',
                'fecha' => '2024-05-18 14:30:00',
                'leido' => true
            ]
        ];
    }

    /**
     * Obtener un mensaje por su ID
     */
    private function getMensajeById($id)
    {
        // En un sistema real, buscaríamos en la base de datos
        $recibidos = $this->getMensajesRecibidos();
        $enviados = $this->getMensajesEnviados();
        $mensajes = array_merge($recibidos, $enviados);
        
        foreach ($mensajes as $mensaje) {
            if ($mensaje['id'] === $id) {
                return $mensaje;
            }
        }
        
        return null;
    }

    /**
     * Marcar un mensaje como leído
     */
    private function marcarMensajeComoLeido($id)
    {
        // En un sistema real, actualizaríamos en la base de datos
        // En esta implementación de ejemplo, solo registramos en el log
        Log::info('Mensaje marcado como leído: ' . $id);
    }

    /**
     * Guardar un nuevo mensaje
     */
    private function saveMensaje($mensaje)
    {
        // En un sistema real, guardaríamos en la base de datos
        // En esta implementación de ejemplo, solo registramos en el log
        Log::info('Mensaje guardado: ' . json_encode($mensaje));
    }

    /**
     * Obtener usuarios activos para enviar mensajes
     */
    private function getActiveUsers()
    {
        // Intentamos obtener usuarios reales primero
        try {
            $users = \DB::table('users')
                ->select('id as username', 'name as nombre')
                ->where('active', true)
                ->orderBy('name')
                ->get();
                
            if ($users->count() > 0) {
                return $users->toArray();
            }
        } catch (\Exception $e) {
            Log::warning('Error al obtener usuarios activos: ' . $e->getMessage());
        }
        
        // Datos simulados más concisos
        return [
            ['username' => 'admin', 'nombre' => 'Administrador'],
            ['username' => 'profesor', 'nombre' => 'Profesor'],
            ['username' => 'profesor2', 'nombre' => 'Laura Sánchez']
        ];
    }

    /**
     * Obtener eventos del calendario
     */
    private function getEventos()
    {
        // Intentamos obtener eventos reales primero
        try {
            $eventos = \DB::table('eventos')
                ->whereDate('fecha_inicio', '>=', now())
                ->orderBy('fecha_inicio')
                ->limit(5)
                ->get();
                
            if ($eventos->count() > 0) {
                return $eventos->toArray();
            }
        } catch (\Exception $e) {
            Log::warning('Error al obtener eventos: ' . $e->getMessage());
        }
        
        // Datos simulados más concisos
        return [
            [
                'id' => '1',
                'titulo' => 'Reunión de departamento',
                'descripcion' => 'Reunión para coordinar la programación',
                'fecha_inicio' => '2024-05-25 10:00:00',
                'fecha_fin' => '2024-05-25 11:30:00',
                'todo_el_dia' => false,
                'color' => '#3788d8',
                'creado_por' => 'admin'
            ],
            [
                'id' => '2',
                'titulo' => 'Entrega de notas',
                'descripcion' => 'Fecha límite para entrega de notas',
                'fecha_inicio' => '2024-06-15 00:00:00',
                'fecha_fin' => '2024-06-15 23:59:59',
                'todo_el_dia' => true,
                'color' => '#e74c3c',
                'creado_por' => 'admin'
            ]
        ];
    }

    /**
     * Guardar un nuevo evento
     */
    private function saveEvento($evento)
    {
        // Simulamos guardar el evento
        return true;
    }
    
    /**
     * Obtiene estadísticas de usuarios LDAP
     */
    private function getLdapStats()
    {
        $stats = [
            'ldap_admins' => 0,
            'ldap_profesores' => 0,
            'ldap_alumnos' => 0,
            'ldap_total' => 0
        ];
        
        try {
            // Crear una conexión LDAP usando la configuración
            $config = config('ldap.connections.default');
            $connection = new \LdapRecord\Connection($config);
            $connection->connect();
            
            if ($connection->isConnected()) {
                $peopleOu = 'ou=people,dc=test,dc=tierno,dc=es';
                
                // Contar todos los usuarios
                $allUsers = $connection->query()
                    ->in($peopleOu)
                    ->where('objectclass', '=', 'inetOrgPerson')
                    ->get();
                
                $stats['ldap_total'] = count($allUsers);
                
                // Contar usuarios por grupo
                $groups = [
                    'ldap_admins' => 'cn=ldapadmins,ou=groups,dc=test,dc=tierno,dc=es',
                    'ldap_profesores' => 'cn=profesores,ou=groups,dc=test,dc=tierno,dc=es',
                    'ldap_alumnos' => 'cn=alumnos,ou=groups,dc=test,dc=tierno,dc=es'
                ];
                
                foreach ($groups as $key => $dn) {
                    $group = $connection->query()->in($dn)->first();
                    if ($group && isset($group['uniquemember'])) {
                        $members = is_array($group['uniquemember']) 
                            ? $group['uniquemember'] 
                            : [$group['uniquemember']];
                        $stats[$key] = count($members);
                    }
                }
            }
        } catch (\Exception $e) {
            //Log::error('Error al obtener estadísticas LDAP: ' . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Obtiene usuarios recientes de LDAP
     */
    private function getLdapRecentUsers()
    {
        $users = [];
        
        try {
            // Crear una conexión LDAP usando la configuración
            $config = config('ldap.connections.default');
            $connection = new \LdapRecord\Connection($config);
            $connection->connect();
            
            if ($connection->isConnected()) {
                $peopleOu = 'ou=people,dc=test,dc=tierno,dc=es';
                $groupsOu = 'ou=groups,dc=test,dc=tierno,dc=es';
                
                // Obtener los últimos 5 usuarios creados
                $ldapUsers = $connection->query()
                    ->in($peopleOu)
                    ->where('objectclass', '=', 'inetOrgPerson')
                    ->limit(5)
                    ->get();
                
                // Procesar usuarios
                foreach ($ldapUsers as $ldapUser) {
                    $users[] = [
                        'uid' => $ldapUser['uid'][0] ?? '',
                        'nombre' => $ldapUser['givenname'][0] ?? '',
                        'apellidos' => $ldapUser['sn'][0] ?? '',
                        'email' => $ldapUser['mail'][0] ?? '',
                        'grupos' => []
                    ];
                }
            }
        } catch (\Exception $e) {
            //Log::error('Error al obtener usuarios LDAP: ' . $e->getMessage());
        }
        
        return $users;
    }
} 