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
                Log::error('Error al obtener datos LDAP: ' . $e->getMessage());
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
        // Obtener los documentos almacenados
        $documents = $this->getAllDocuments();
        $folders = $this->getFolders();

        return view('dashboard.documentos.index', compact('documents', 'folders'));
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
        $mensajesRecibidos = $this->getMensajesRecibidos();
        $mensajesEnviados = $this->getMensajesEnviados();
        
        return view('dashboard.mensajes.index', compact('mensajesRecibidos', 'mensajesEnviados'));
    }

    /**
     * Mostrar formulario para nuevo mensaje
     */
    public function nuevoMensaje()
    {
        // Obtener usuarios para el campo destinatario
        $users = $this->getActiveUsers();
        
        return view('dashboard.mensajes.nuevo', compact('users'));
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
        $eventos = $this->getEventos();
        
        return view('dashboard.calendario.index', compact('eventos'));
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
            
            // Intentar obtener el recuento de documentos
            try {
                $documentosCount = \DB::table('documentos')->count();
            } catch (\Exception $e) {
                Log::warning('Error al obtener recuento de documentos: ' . $e->getMessage());
            }
            
            // Intentar obtener el recuento de mensajes no leídos para el usuario actual
            try {
                $username = session('auth_user.username');
                $mensajesNuevosCount = \DB::table('mensajes')
                    ->where('destinatario', $username)
                    ->where('leido', false)
                    ->count();
            } catch (\Exception $e) {
                Log::warning('Error al obtener recuento de mensajes nuevos: ' . $e->getMessage());
            }
            
            // Intentar obtener el recuento de eventos próximos (en los siguientes 7 días)
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
            
            // Intentar obtener el recuento de usuarios
            try {
                $usuariosCount = \DB::table('users')->count();
            } catch (\Exception $e) {
                Log::warning('Error al obtener recuento de usuarios: ' . $e->getMessage());
            }
            
            return [
                'documentos' => $documentosCount,
                'mensajes_nuevos' => $mensajesNuevosCount,
                'eventos_proximos' => $eventosProximosCount,
                'usuarios' => $usuariosCount
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas: ' . $e->getMessage());
            
            // Devolver valores por defecto en caso de error
            return [
                'documentos' => 0,
                'mensajes_nuevos' => 0,
                'eventos_proximos' => 0,
                'usuarios' => 0
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
            $documents = \DB::table('documentos')
                ->orderBy('fecha_subida', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($doc) {
                    // Formatear tamaño del archivo
                    $tamaño = $this->formatFileSize($doc->tamaño ?? 0);
                    
                    return [
                        'id' => $doc->id,
                        'nombre' => $doc->nombre,
                        'extension' => $doc->extension,
                        'fecha_subida' => $doc->fecha_subida,
                        'subido_por' => $doc->subido_por,
                        'subido_por_nombre' => $this->getUserName($doc->subido_por),
                        'tamaño' => $tamaño
                    ];
                })
                ->toArray();
                
            return $documents;
        } catch (\Exception $e) {
            Log::error('Error al obtener documentos recientes: ' . $e->getMessage());
            
            // Devolver datos de fallback para evitar errores en la vista
            return [
                [
                    'id' => '1',
                    'nombre' => 'Documento de ejemplo',
                    'extension' => 'pdf',
                    'fecha_subida' => now()->format('Y-m-d H:i:s'),
                    'subido_por' => session('auth_user.username'),
                    'subido_por_nombre' => session('auth_user.nombre'),
                    'tamaño' => '0 KB'
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
            // Obtener el nombre de usuario actual
            $username = session('auth_user.username');
            
            // Obtener mensajes recientes de la base de datos
            $messages = \DB::table('mensajes')
                ->where('destinatario', $username)
                ->orderBy('fecha', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($msg) {
                    return [
                        'id' => $msg->id,
                        'remitente' => $msg->remitente,
                        'remitente_nombre' => $this->getUserName($msg->remitente),
                        'asunto' => $msg->asunto,
                        'contenido' => $msg->contenido,
                        'fecha' => $msg->fecha,
                        'leido' => (bool)$msg->leido
                    ];
                })
                ->toArray();
                
            return $messages;
        } catch (\Exception $e) {
            Log::error('Error al obtener mensajes recientes: ' . $e->getMessage());
            
            // Devolver datos de fallback para evitar errores en la vista
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
                ->get()
                ->map(function ($event) {
                    return [
                        'id' => $event->id,
                        'titulo' => $event->titulo,
                        'fecha_inicio' => $event->fecha_inicio,
                        'fecha_fin' => $event->fecha_fin,
                        'todo_el_dia' => (bool)($event->todo_el_dia ?? false),
                        'color' => $event->color ?? '#3788d8'
                    ];
                })
                ->toArray();
                
            return $events;
        } catch (\Exception $e) {
            Log::error('Error al obtener eventos próximos: ' . $e->getMessage());
            
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
            // Intentar obtener la actividad de los logs
            $activities = [];
            
            // Usar logs de Laravel como fuente de actividad
            $logPath = storage_path('logs/laravel.log');
            
            if (file_exists($logPath)) {
                // Leer las últimas 100 líneas del log
                $command = "tail -n 100 " . escapeshellarg($logPath);
                $logContent = shell_exec($command);
                
                if ($logContent) {
                    // Dividir el contenido en líneas
                    $logLines = explode("\n", $logContent);
                    
                    // Filtrar por líneas que parezcan actividades
                    $activityCount = 0;
                    foreach ($logLines as $line) {
                        if (empty(trim($line))) continue;
                        
                        // Buscar patrones de actividad (logs de tipo info)
                        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*INFO:(.+)/', $line, $matches)) {
                            $fecha = $matches[1];
                            $mensaje = trim($matches[2]);
                            
                            // Analizar el mensaje para extraer la actividad
                            if (preg_match('/(.*?) (creó|editó|eliminó|subió|envió) (.*)/', $mensaje, $activityMatches)) {
                                $nombre = trim($activityMatches[1]);
                                $accion = trim($activityMatches[2]);
                                $detalles = trim($activityMatches[3]);
                                
                                $activities[] = [
                                    'usuario' => strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nombre)),
                                    'nombre' => $nombre,
                                    'accion' => ucfirst($accion),
                                    'detalles' => $detalles,
                                    'fecha' => $fecha
                                ];
                                
                                $activityCount++;
                                if ($activityCount >= 5) break; // Limitar a 5 actividades
                            }
                        }
                    }
                }
            }
            
            // Si no hay actividades en los logs, intentar obtenerlas de la base de datos
            if (empty($activities)) {
                try {
                    // Intenta buscar en una tabla de actividad o auditoría, si existe
                    $dbActivities = \DB::table('user_activity')
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get();
                        
                    if ($dbActivities && count($dbActivities) > 0) {
                        foreach ($dbActivities as $activity) {
                            $activities[] = [
                                'usuario' => $activity->user_id,
                                'nombre' => $this->getUserName($activity->user_id),
                                'accion' => $activity->action,
                                'detalles' => $activity->details,
                                'fecha' => $activity->created_at
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // Silenciar errores si la tabla no existe
                }
            }
            
            return $activities;
        } catch (\Exception $e) {
            Log::error('Error al obtener actividad de usuarios: ' . $e->getMessage());
            
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
        // Simulamos una lista de documentos
        return [
            [
                'id' => '1',
                'nombre' => 'Programación anual 2024-2025',
                'nombre_original' => 'programacion_anual_2024_2025.pdf',
                'descripcion' => 'Documento de programación anual para el curso 2024-2025',
                'carpeta' => 'programaciones',
                'extension' => 'pdf',
                'tipo' => 'application/pdf',
                'tamaño' => 1254000,
                'subido_por' => 'admin',
                'fecha_subida' => '2024-05-20 14:30:45'
            ],
            [
                'id' => '2',
                'nombre' => 'Horarios primer trimestre',
                'nombre_original' => 'horarios_primer_trimestre.xlsx',
                'descripcion' => 'Horarios de todos los grupos para el primer trimestre',
                'carpeta' => 'horarios',
                'extension' => 'xlsx',
                'tipo' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'tamaño' => 620000,
                'subido_por' => 'profesor',
                'fecha_subida' => '2024-05-19 10:15:22'
            ],
            [
                'id' => '3',
                'nombre' => 'Acta reunión departamento',
                'nombre_original' => 'acta_reunion_departamento.docx',
                'descripcion' => 'Acta de la última reunión de departamento',
                'carpeta' => 'actas',
                'extension' => 'docx',
                'tipo' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'tamaño' => 450000,
                'subido_por' => 'admin',
                'fecha_subida' => '2024-05-18 16:45:10'
            ],
            [
                'id' => '4',
                'nombre' => 'Calendario escolar',
                'nombre_original' => 'calendario_escolar_2024_2025.pdf',
                'descripcion' => 'Calendario oficial para el curso 2024-2025',
                'carpeta' => 'general',
                'extension' => 'pdf',
                'tipo' => 'application/pdf',
                'tamaño' => 980000,
                'subido_por' => 'admin',
                'fecha_subida' => '2024-05-15 09:30:00'
            ]
        ];
    }

    /**
     * Obtener carpetas disponibles
     */
    private function getFolders()
    {
        return [
            [
                'nombre' => 'General',
                'clave' => 'general',
                'icono' => 'fa-folder'
            ],
            [
                'nombre' => 'Programaciones',
                'clave' => 'programaciones',
                'icono' => 'fa-file-alt'
            ],
            [
                'nombre' => 'Horarios',
                'clave' => 'horarios',
                'icono' => 'fa-calendar-alt'
            ],
            [
                'nombre' => 'Actas',
                'clave' => 'actas',
                'icono' => 'fa-file-signature'
            ],
            [
                'nombre' => 'Evaluaciones',
                'clave' => 'evaluaciones',
                'icono' => 'fa-clipboard-check'
            ]
        ];
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
        // En un sistema real, buscaríamos en la base de datos
        $username = session('auth_user')['username'];
        
        // Simulamos mensajes recibidos
        return [
            [
                'id' => '1',
                'remitente' => 'profesor',
                'remitente_nombre' => 'Profesor',
                'destinatario' => $username,
                'asunto' => 'Reunión importante',
                'contenido' => 'Necesitamos reunirnos para hablar sobre los cambios en el programa.',
                'fecha' => '2024-05-20 11:20:30',
                'leido' => false
            ],
            [
                'id' => '2',
                'remitente' => 'admin',
                'remitente_nombre' => 'Administrador',
                'destinatario' => $username,
                'asunto' => 'Modificación de horarios',
                'contenido' => 'Se han realizado cambios en los horarios del próximo trimestre.',
                'fecha' => '2024-05-19 09:45:15',
                'leido' => true
            ]
        ];
    }

    /**
     * Obtener mensajes enviados por el usuario actual
     */
    private function getMensajesEnviados()
    {
        // En un sistema real, buscaríamos en la base de datos
        $username = session('auth_user')['username'];
        
        // Simulamos mensajes enviados
        return [
            [
                'id' => '3',
                'remitente' => $username,
                'destinatario' => 'profesor',
                'destinatario_nombre' => 'Profesor',
                'asunto' => 'Respuesta a consulta',
                'contenido' => 'Aquí tienes la información que solicitaste sobre las evaluaciones.',
                'fecha' => '2024-05-18 14:30:00',
                'leido' => true
            ],
            [
                'id' => '4',
                'remitente' => $username,
                'destinatario' => 'admin',
                'destinatario_nombre' => 'Administrador',
                'asunto' => 'Solicitud de material',
                'contenido' => 'Necesitaría que autorices la compra de nuevo material para el laboratorio.',
                'fecha' => '2024-05-15 10:20:00',
                'leido' => false
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
        // En un sistema real, buscaríamos usuarios de LDAP o base de datos
        // Simulamos una lista de usuarios
        return [
            [
                'username' => 'admin',
                'nombre' => 'Administrador'
            ],
            [
                'username' => 'profesor',
                'nombre' => 'Profesor'
            ],
            [
                'username' => 'profesor2',
                'nombre' => 'Laura Sánchez'
            ],
            [
                'username' => 'profesor3',
                'nombre' => 'Miguel Ángel Pérez'
            ]
        ];
    }

    /**
     * Obtener eventos del calendario
     */
    private function getEventos()
    {
        // En un sistema real, buscaríamos en la base de datos
        // Simulamos eventos
        return [
            [
                'id' => '1',
                'titulo' => 'Reunión de departamento',
                'descripcion' => 'Reunión para coordinar la programación del próximo curso',
                'fecha_inicio' => '2024-05-25 10:00:00',
                'fecha_fin' => '2024-05-25 11:30:00',
                'todo_el_dia' => false,
                'color' => '#3788d8',
                'creado_por' => 'admin'
            ],
            [
                'id' => '2',
                'titulo' => 'Entrega de notas',
                'descripcion' => 'Fecha límite para la entrega de notas del trimestre',
                'fecha_inicio' => '2024-06-15 00:00:00',
                'fecha_fin' => '2024-06-15 23:59:59',
                'todo_el_dia' => true,
                'color' => '#e74c3c',
                'creado_por' => 'admin'
            ],
            [
                'id' => '3',
                'titulo' => 'Jornada de formación',
                'descripcion' => 'Formación sobre nuevas tecnologías educativas',
                'fecha_inicio' => '2024-06-01 09:00:00',
                'fecha_fin' => '2024-06-02 14:00:00',
                'todo_el_dia' => false,
                'color' => '#2ecc71',
                'creado_por' => 'profesor'
            ],
            [
                'id' => '4',
                'titulo' => 'Vacaciones de verano',
                'descripcion' => 'Periodo vacacional',
                'fecha_inicio' => '2024-07-01 00:00:00',
                'fecha_fin' => '2024-08-31 23:59:59',
                'todo_el_dia' => true,
                'color' => '#9b59b6',
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
            // Crear una conexión LDAP usando la configuración del archivo de configuración
            $config = config('ldap.connections.default');
            $connection = new \LdapRecord\Connection($config);
            $connection->connect();
            
            if ($connection->isConnected()) {
                $baseDn = 'dc=test,dc=tierno,dc=es';
                $peopleOu = 'ou=people,dc=test,dc=tierno,dc=es';
                $adminGroupDn = 'cn=ldapadmins,ou=groups,dc=test,dc=tierno,dc=es';
                $profesoresGroupDn = 'cn=profesores,ou=groups,dc=test,dc=tierno,dc=es';
                $alumnosGroupDn = 'cn=alumnos,ou=groups,dc=test,dc=tierno,dc=es';
                
                // Contar todos los usuarios
                $allUsers = $connection->query()
                    ->in($peopleOu)
                    ->where('objectclass', '=', 'inetOrgPerson')
                    ->get();
                
                $stats['ldap_total'] = count($allUsers);
                
                // Obtener grupo de administradores
                $adminGroup = $connection->query()
                    ->in($adminGroupDn)
                    ->first();
                
                if ($adminGroup && isset($adminGroup['uniquemember'])) {
                    $adminMembers = is_array($adminGroup['uniquemember']) 
                        ? $adminGroup['uniquemember'] 
                        : [$adminGroup['uniquemember']];
                    $stats['ldap_admins'] = count($adminMembers);
                }
                
                // Obtener grupo de profesores
                $profesoresGroup = $connection->query()
                    ->in($profesoresGroupDn)
                    ->first();
                
                if ($profesoresGroup && isset($profesoresGroup['uniquemember'])) {
                    $profesoresMembers = is_array($profesoresGroup['uniquemember']) 
                        ? $profesoresGroup['uniquemember'] 
                        : [$profesoresGroup['uniquemember']];
                    $stats['ldap_profesores'] = count($profesoresMembers);
                }
                
                // Obtener grupo de alumnos
                $alumnosGroup = $connection->query()
                    ->in($alumnosGroupDn)
                    ->first();
                
                if ($alumnosGroup && isset($alumnosGroup['uniquemember'])) {
                    $alumnosMembers = is_array($alumnosGroup['uniquemember']) 
                        ? $alumnosGroup['uniquemember'] 
                        : [$alumnosGroup['uniquemember']];
                    $stats['ldap_alumnos'] = count($alumnosMembers);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas LDAP: ' . $e->getMessage());
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
            // Crear una conexión LDAP usando la configuración del archivo de configuración
            $config = config('ldap.connections.default');
            $connection = new \LdapRecord\Connection($config);
            $connection->connect();
            
            if ($connection->isConnected()) {
                $baseDn = 'dc=test,dc=tierno,dc=es';
                $peopleOu = 'ou=people,dc=test,dc=tierno,dc=es';
                $groupsOu = 'ou=groups,dc=test,dc=tierno,dc=es';
                
                // Obtener los últimos 5 usuarios creados
                $ldapUsers = $connection->query()
                    ->in($peopleOu)
                    ->where('objectclass', '=', 'inetOrgPerson')
                    ->get();
                
                // Ordenar por creationTime si existe, de lo contrario usar los primeros 5
                // En implementaciones reales, aquí podríamos ordenar por un atributo como createTimestamp
                $ldapUsers = array_slice($ldapUsers, 0, 5);
                
                // Obtener todos los grupos
                $groups = $connection->query()
                    ->in($groupsOu)
                    ->where('objectclass', '=', 'groupOfUniqueNames')
                    ->get();
                
                // Procesar usuarios
                foreach ($ldapUsers as $ldapUser) {
                    $userGroups = [];
                    
                    // Verificar a qué grupos pertenece el usuario
                    foreach ($groups as $group) {
                        if (isset($group['uniquemember'])) {
                            $members = is_array($group['uniquemember']) 
                                ? $group['uniquemember'] 
                                : [$group['uniquemember']];
                                
                            if (in_array($ldapUser['dn'], $members)) {
                                $userGroups[] = $group['cn'][0];
                            }
                        }
                    }
                    
                    $users[] = [
                        'uid' => $ldapUser['uid'][0] ?? '',
                        'nombre' => $ldapUser['givenname'][0] ?? '',
                        'apellidos' => $ldapUser['sn'][0] ?? '',
                        'email' => $ldapUser['mail'][0] ?? '',
                        'grupos' => $userGroups
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Error al obtener usuarios recientes LDAP: ' . $e->getMessage());
        }
        
        return $users;
    }
} 