<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Mensaje;
use App\Models\User;
use App\Models\MensajeAdjunto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MensajeController extends Controller
{
    /**
     * Mostrar la bandeja de entrada y enviados
     */
    public function index(Request $request)
    {
        // Obtener el usuario autenticado
        $userId = session('auth_user')['id'] ?? Auth::id();
        
        // Tipo por defecto: recibidos
        $tipo = $request->query('tipo', 'recibidos');
        
        // Obtener mensajes según el tipo seleccionado
        try {
            $mensajes = $this->getMensajes($tipo, $userId);
            
            // Obtener contadores para la barra lateral
            $contadores = $this->getContadores($userId);
            
            return view('dashboard.mensajes', compact('mensajes', 'contadores', 'tipo'));
        } catch (\Exception $e) {
            Log::error('Error al cargar mensajes: ' . $e->getMessage());
            return view('dashboard.mensajes', ['mensajes' => [], 'contadores' => [], 'tipo' => $tipo])
                ->with('error', 'Error al cargar los mensajes: ' . $e->getMessage());
        }
    }
    
    /**
     * Mostrar formulario para crear un nuevo mensaje
     */
    public function create()
    {
        // Obtener lista de usuarios para destinatarios
        try {
            // Obtenemos todos los usuarios
            $usuarios = User::select('id', 'name', 'email', 'role')
                ->orderBy('name')
                ->get();
            
            // Agrupar usuarios por roles
            $profesores = $usuarios->where('role', 'profesor')->all();
            $alumnos = $usuarios->where('role', 'alumno')->all();
            $admins = $usuarios->where('role', 'admin')->all();
            $otros = $usuarios->whereNotIn('role', ['profesor', 'alumno', 'admin'])->all();
            
            // Generamos un array para los grupos
            $grupos = [
                [
                    'id' => 'grupo_profesores',
                    'nombre' => 'Todos los profesores',
                    'tipo' => 'grupo'
                ],
                [
                    'id' => 'grupo_alumnos',
                    'nombre' => 'Todos los alumnos',
                    'tipo' => 'grupo'
                ],
                [
                    'id' => 'grupo_todos',
                    'nombre' => 'Todos los usuarios',
                    'tipo' => 'grupo'
                ]
            ];
            
            // Organizamos usuarios
            $usuariosAgrupados = [
                'grupos' => $grupos,
                'profesores' => $profesores,
                'alumnos' => $alumnos,
                'admins' => $admins,
                'otros' => $otros
            ];
            
            return view('dashboard.redactar_mensaje', compact('usuarios', 'usuariosAgrupados'));
        } catch (\Exception $e) {
            Log::error('Error al cargar usuarios para mensaje: ' . $e->getMessage());
            return redirect()->route('dashboard.mensajes')
                ->with('error', 'Error al cargar la página de redacción: ' . $e->getMessage());
        }
    }
    
    /**
     * Almacenar un nuevo mensaje
     */
    public function store(Request $request)
    {
        $request->validate([
            'destinatario' => 'required',
            'asunto' => 'required|string|max:255',
            'contenido' => 'required|string',
            'adjuntos.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpeg,jpg,png,gif,zip,rar',
        ], [
            'adjuntos.*.max' => 'Cada archivo adjunto debe ser menor a 10MB.',
            'adjuntos.*.mimes' => 'Solo se permiten archivos PDF, Word, Excel, PowerPoint, imágenes y ZIP/RAR.',
        ]);

        try {
            DB::beginTransaction();
            
            // Obtener el ID del remitente
            $remitenteId = session('auth_user')['id'] ?? Auth::id();
            
            // Procesar destinatario
            $destinatarioId = $request->destinatario;
            $esGrupo = false;
            
            // Verificar si es un grupo
            if (substr($destinatarioId, 0, 6) === 'grupo_') {
                $esGrupo = true;
                $tipoGrupo = substr($destinatarioId, 6);
                
                // Obtenemos los usuarios según el grupo
                $usuariosGrupo = [];
                
                if ($tipoGrupo === 'profesores') {
                    $usuariosGrupo = User::where(function($query) {
                        $query->where('role', 'profesor')
                              ->orWhere('role', 'admin');
                    })->pluck('id')->toArray();
                } elseif ($tipoGrupo === 'alumnos') {
                    // Ignoramos la validación por tipo de rol y simplemente enviamos a todos los usuarios
                    // que no son el remitente actual ni profesores
                    $usuariosGrupo = User::where('id', '!=', $remitenteId)
                        ->where(function($query) {
                            $query->where('role', '!=', 'profesor')
                                  ->where('role', '!=', 'admin');
                        })
                        ->pluck('id')
                        ->toArray();
                } elseif ($tipoGrupo === 'todos') {
                    $usuariosGrupo = User::where('id', '!=', $remitenteId)->pluck('id')->toArray();
                }
                
                // Verificar que hay destinatarios
                if (empty($usuariosGrupo)) {
                    // Si no hay usuarios específicos, enviar a todos los usuarios excepto al remitente
                    $usuariosGrupo = User::where('id', '!=', $remitenteId)->pluck('id')->toArray();
                    
                    // Si aún no hay usuarios, entonces no hay nadie más en el sistema
                    if (empty($usuariosGrupo)) {
                        throw new \Exception('No hay otros usuarios en el sistema para enviar mensajes');
                    }
                }
                
                // Enviar mensaje a cada usuario del grupo
                foreach ($usuariosGrupo as $userId) {
                    $mensaje = Mensaje::create([
                        'remitente_id' => $remitenteId,
                        'destinatario_id' => $userId,
                        'asunto' => $request->asunto,
                        'contenido' => $request->contenido,
                        'leido' => false,
                        'destacado' => false,
                        'borrador' => false,
                        'eliminado_remitente' => false,
                        'eliminado_destinatario' => false
                    ]);
                    // Guardar adjuntos para cada mensaje
                    if ($request->hasFile('adjuntos')) {
                        if (!Storage::disk('public')->exists('adjuntos/mensajes')) {
                            Storage::disk('public')->makeDirectory('adjuntos/mensajes');
                        }
                        foreach ($request->file('adjuntos') as $file) {
                            $path = $file->store('adjuntos/mensajes', 'public');
                            MensajeAdjunto::create([
                                'mensaje_id' => $mensaje->id,
                                'nombre' => $file->getClientOriginalName(),
                                'nombre_original' => $file->getClientOriginalName(),
                                'extension' => $file->getClientOriginalExtension(),
                                'tipo' => $file->getClientMimeType(),
                                'tamaño' => $file->getSize(),
                                'ruta' => $path,
                            ]);
                        }
                    }
                }
                Log::info('Mensaje enviado por usuario ' . $remitenteId . ' al grupo ' . $tipoGrupo . ' (' . count($usuariosGrupo) . ' destinatarios)');
            } else {
                // Mensaje individual
                // Crear el mensaje
                $mensaje = Mensaje::create([
                    'remitente_id' => $remitenteId,
                    'destinatario_id' => $destinatarioId,
                    'asunto' => $request->asunto,
                    'contenido' => $request->contenido,
                    'leido' => false,
                    'destacado' => false,
                    'borrador' => false,
                    'eliminado_remitente' => false,
                    'eliminado_destinatario' => false
                ]);
                
                // Si hay archivos adjuntos, procesarlos
                if ($request->hasFile('adjuntos')) {
                    // Asegura que la carpeta existe
                    if (!Storage::disk('public')->exists('adjuntos/mensajes')) {
                        Storage::disk('public')->makeDirectory('adjuntos/mensajes');
                    }
                    foreach ($request->file('adjuntos') as $file) {
                        $path = $file->store('adjuntos/mensajes', 'public');
                        MensajeAdjunto::create([
                            'mensaje_id' => $mensaje->id,
                            'nombre' => $file->getClientOriginalName(),
                            'nombre_original' => $file->getClientOriginalName(),
                            'extension' => $file->getClientOriginalExtension(),
                            'tipo' => $file->getClientMimeType(),
                            'tamaño' => $file->getSize(),
                            'ruta' => $path,
                        ]);
                    }
                }
                
                Log::info('Mensaje enviado por usuario ' . $remitenteId . ' a usuario ' . $destinatarioId);
            }
            
            DB::commit();
            
            return redirect()->route('dashboard.mensajes')
                ->with('success', 'Mensaje enviado correctamente.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al enviar mensaje: ' . $e->getMessage());
            return back()->with('error', 'Error al enviar el mensaje: ' . $e->getMessage());
        }
    }
    
    /**
     * Mostrar un mensaje específico
     */
    public function show($id)
    {
        try {
            // Obtener el usuario autenticado
            $userId = session('auth_user')['id'] ?? Auth::id();
            
            // Buscar el mensaje
            $mensajeObj = Mensaje::with(['remitente', 'destinatario', 'adjuntos'])
                ->findOrFail($id);
            
            // Verificar permiso para ver el mensaje
            if ($mensajeObj->destinatario_id != $userId && 
                $mensajeObj->remitente_id != $userId && 
                !$this->isAdmin()) {
                return redirect()->route('dashboard.mensajes')
                    ->with('error', 'No tienes permiso para ver este mensaje.');
            }
            
            // Marcar como leído si es el destinatario y no lo ha leído aún
            if ($mensajeObj->destinatario_id == $userId && !$mensajeObj->leido) {
                $mensajeObj->leido = true;
                $mensajeObj->save();
                Log::info('Mensaje marcado como leído: ' . $id);
            }
            
            // Transformar el objeto Mensaje en un array con la estructura necesaria para la vista
            $mensaje = [
                'id' => $mensajeObj->id,
                'asunto' => $mensajeObj->asunto,
                'contenido' => $mensajeObj->contenido,
                'fecha' => $mensajeObj->created_at,
                'destacado' => $mensajeObj->destacado,
                'leido' => $mensajeObj->leido,
                'en_papelera' => ($mensajeObj->eliminado_remitente || $mensajeObj->eliminado_destinatario),
                'remitente' => [
                    'id' => $mensajeObj->remitente->id,
                    'nombre' => $mensajeObj->remitente->name,
                    'email' => $mensajeObj->remitente->email,
                    'departamento' => $mensajeObj->remitente->departamento ?? null,
                    'avatar' => $mensajeObj->remitente->avatar ?? null,
                ],
                'destinatarios' => [
                    [
                        'id' => $mensajeObj->destinatario->id,
                        'nombre' => $mensajeObj->destinatario->name,
                        'email' => $mensajeObj->destinatario->email,
                    ]
                ],
                'archivos' => $mensajeObj->adjuntos->map(function($adjunto) {
                    return [
                        'id' => $adjunto->id,
                        'nombre' => $adjunto->nombre,
                        'ruta' => $adjunto->ruta,
                        'tipo' => pathinfo($adjunto->nombre, PATHINFO_EXTENSION),
                        'tamano' => $this->formatoTamano($adjunto->tamaño)
                    ];
                })->toArray(),
                'cc' => [],
                'historial' => []
            ];
            
            return view('dashboard.mensaje_detalle', compact('mensaje'));
        } catch (\Exception $e) {
            Log::error('Error al mostrar mensaje: ' . $e->getMessage());
            return redirect()->route('dashboard.mensajes')
                ->with('error', 'Error al cargar el mensaje: ' . $e->getMessage());
        }
    }
    
    /**
     * Eliminar un mensaje
     */
    public function destroy($id)
    {
        try {
            // Obtener el usuario autenticado
            $userId = session('auth_user')['id'] ?? Auth::id();
            
            // Buscar el mensaje
            $mensaje = Mensaje::findOrFail($id);
            
            // Verificar permiso para eliminar el mensaje
            if ($mensaje->destinatario_id != $userId && 
                $mensaje->remitente_id != $userId && 
                !$this->isAdmin()) {
                return redirect()->route('dashboard.mensajes')
                    ->with('error', 'No tienes permiso para eliminar este mensaje.');
            }
            
            // Marcar como eliminado según el rol del usuario
            if ($mensaje->remitente_id == $userId) {
                $mensaje->eliminado_remitente = true;
            }
            
            if ($mensaje->destinatario_id == $userId) {
                $mensaje->eliminado_destinatario = true;
            }
            
            // Si ambos usuarios han eliminado el mensaje, eliminarlo físicamente
            if ($mensaje->eliminado_remitente && $mensaje->eliminado_destinatario) {
                // Eliminar adjuntos si existen
                foreach ($mensaje->adjuntos as $adjunto) {
                    $this->eliminarArchivoAdjunto($adjunto);
                    $adjunto->delete();
                }
                
                $mensaje->delete();
                Log::info('Mensaje eliminado permanentemente: ' . $id);
            } else {
                $mensaje->save();
                Log::info('Mensaje movido a papelera: ' . $id);
            }
            
            return redirect()->route('dashboard.mensajes')
                ->with('success', 'Mensaje eliminado correctamente.');
                
        } catch (\Exception $e) {
            Log::error('Error al eliminar mensaje: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar el mensaje: ' . $e->getMessage());
        }
    }
    
    /**
     * Marcar un mensaje como leído/no leído
     */
    public function toggleRead($id)
    {
        try {
            // Obtener el usuario autenticado
            $userId = session('auth_user')['id'] ?? Auth::id();
            
            // Buscar el mensaje
            $mensaje = Mensaje::findOrFail($id);
            
            // Verificar permiso
            if ($mensaje->destinatario_id != $userId && !$this->isAdmin()) {
                if (request()->ajax()) {
                    return response()->json(['success' => false, 'message' => 'No tienes permiso para modificar este mensaje.'], 403);
                }
                return redirect()->route('dashboard.mensajes')
                    ->with('error', 'No tienes permiso para modificar este mensaje.');
            }
            
            // Cambiar estado
            $mensaje->leido = !$mensaje->leido;
            $mensaje->save();
            
            $estado = $mensaje->leido ? 'leído' : 'no leído';
            Log::info('Mensaje marcado como ' . $estado . ': ' . $id);
            
            if (request()->ajax()) {
                return response()->json(['success' => true, 'message' => 'Mensaje marcado como ' . $estado, 'estado' => $mensaje->leido]);
            }
            
            return redirect()->back()
                ->with('success', 'Mensaje marcado como ' . $estado . '.');
                
        } catch (\Exception $e) {
            Log::error('Error al actualizar estado de lectura: ' . $e->getMessage());
            
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Error al actualizar el mensaje: ' . $e->getMessage()], 500);
            }
            
            return back()->with('error', 'Error al actualizar el mensaje: ' . $e->getMessage());
        }
    }

    /**
     * Marcar un mensaje como destacado/no destacado
     */
    public function toggleStarred($id)
    {
        try {
            // Obtener el usuario autenticado
            $userId = session('auth_user')['id'] ?? Auth::id();
            
            // Buscar el mensaje
            $mensaje = Mensaje::findOrFail($id);
            
            // Verificar permiso
            if ($mensaje->destinatario_id != $userId && 
                $mensaje->remitente_id != $userId && 
                !$this->isAdmin()) {
                return redirect()->route('dashboard.mensajes')
                    ->with('error', 'No tienes permiso para modificar este mensaje.');
            }
            
            // Cambiar estado de destacado
            $mensaje->destacado = !$mensaje->destacado;
            $mensaje->save();
            
            $estado = $mensaje->destacado ? 'destacado' : 'no destacado';
            Log::info('Mensaje marcado como ' . $estado . ': ' . $id);
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => true, 
                    'message' => 'Mensaje marcado como ' . $estado,
                    'estado' => $mensaje->destacado
                ]);
            }
            
            return redirect()->back()
                ->with('success', 'Mensaje marcado como ' . $estado . '.');
                
        } catch (\Exception $e) {
            Log::error('Error al marcar mensaje como destacado: ' . $e->getMessage());
            return back()->with('error', 'Error al modificar el mensaje: ' . $e->getMessage());
        }
    }

    /**
     * Restaurar un mensaje de la papelera
     */
    public function restore($id)
    {
        try {
            // Obtener el usuario autenticado
            $userId = session('auth_user')['id'] ?? Auth::id();
            
            // Buscar el mensaje
            $mensaje = Mensaje::findOrFail($id);
            
            // Verificar permiso
            if ($mensaje->destinatario_id != $userId && 
                $mensaje->remitente_id != $userId && 
                !$this->isAdmin()) {
                return redirect()->route('dashboard.mensajes')
                    ->with('error', 'No tienes permiso para restaurar este mensaje.');
            }
            
            // Restaurar según el rol del usuario
            if ($mensaje->remitente_id == $userId) {
                $mensaje->eliminado_remitente = false;
            }
            
            if ($mensaje->destinatario_id == $userId) {
                $mensaje->eliminado_destinatario = false;
            }
            
            $mensaje->save();
            Log::info('Mensaje restaurado de papelera: ' . $id);
            
            return redirect()->route('dashboard.mensajes', ['tipo' => 'papelera'])
                ->with('success', 'Mensaje restaurado correctamente.');
                
        } catch (\Exception $e) {
            Log::error('Error al restaurar mensaje: ' . $e->getMessage());
            return back()->with('error', 'Error al restaurar el mensaje: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener contadores para la barra lateral
     */
    private function getContadores($userId)
    {
        try {
            return [
                'recibidos' => Mensaje::recibidos($userId)->count(),
                'no_leidos' => Mensaje::recibidos($userId)->where('leido', false)->count(),
                'destacados' => Mensaje::destacados($userId)->count(),
                'enviados' => Mensaje::enviados($userId)->count(),
                'papelera' => Mensaje::papelera($userId)->count(),
                'recientes' => Mensaje::recibidos($userId)->where('created_at', '>=', now()->subDays(5))->count()
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener contadores de mensajes: ' . $e->getMessage());
            return [
                'recibidos' => 0,
                'no_leidos' => 0,
                'destacados' => 0,
                'enviados' => 0,
                'papelera' => 0,
                'recientes' => 0
            ];
        }
    }
    
    /**
     * Obtener mensajes según el tipo
     */
    private function getMensajes($tipo, $userId)
    {
        $query = null;
        
        switch($tipo) {
            case 'enviados':
                $query = Mensaje::enviados($userId);
                break;
            case 'destacados':
                $query = Mensaje::destacados($userId);
                break;
            case 'papelera':
                $query = Mensaje::papelera($userId);
                break;
            case 'recibidos':
            default:
                $query = Mensaje::recibidos($userId);
                break;
        }
        
        // Aplicar filtros adicionales
        $filtro = request('filter');
        if ($filtro) {
            switch($filtro) {
                case 'no_leidos':
                    $query->where('leido', false);
                    break;
                case 'recientes':
                    $query->where('created_at', '>=', now()->subDays(5));
                    break;
            }
        }
        
        return $query->with(['remitente', 'destinatario'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }
    
    /**
     * Verificar si el usuario actual es administrador
     */
    private function isAdmin()
    {
        if (Auth::check()) {
            $user = Auth::user();
            return ($user->role === 'admin' || $user->is_admin);
        }
        
        return session('auth_user')['is_admin'] ?? false;
    }
    
    /**
     * Eliminar archivo adjunto
     */
    private function eliminarArchivoAdjunto($adjunto)
    {
        try {
            if ($adjunto->ruta && Storage::disk('public')->exists($adjunto->ruta)) {
                Storage::disk('public')->delete($adjunto->ruta);
            }
        } catch (\Exception $e) {
            Log::error('Error al eliminar archivo adjunto: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario para responder a un mensaje
     */
    public function reply($id)
    {
        try {
            // Obtener el usuario autenticado
            $userId = session('auth_user')['id'] ?? Auth::id();
            
            // Buscar el mensaje original
            $mensaje = Mensaje::with(['remitente', 'destinatario', 'adjuntos'])
                ->findOrFail($id);
            
            // Verificar permiso
            if ($mensaje->destinatario_id != $userId && !$this->isAdmin()) {
                return redirect()->route('dashboard.mensajes')
                    ->with('error', 'No tienes permiso para responder a este mensaje.');
            }
            
            // Obtener lista de usuarios para destinatarios
            $usuarios = User::select('id', 'name', 'email', 'role')
                ->orderBy('name')
                ->get();
            
            // Agrupar usuarios por roles
            $profesores = $usuarios->where('role', 'profesor')->all();
            $alumnos = $usuarios->where('role', 'alumno')->all();
            $admins = $usuarios->where('role', 'admin')->all();
            $otros = $usuarios->whereNotIn('role', ['profesor', 'alumno', 'admin'])->all();
            
            // Generamos un array para los grupos
            $grupos = [
                [
                    'id' => 'grupo_profesores',
                    'nombre' => 'Todos los profesores',
                    'tipo' => 'grupo'
                ],
                [
                    'id' => 'grupo_alumnos',
                    'nombre' => 'Todos los alumnos',
                    'tipo' => 'grupo'
                ],
                [
                    'id' => 'grupo_todos',
                    'nombre' => 'Todos los usuarios',
                    'tipo' => 'grupo'
                ]
            ];
            
            // Organizamos usuarios
            $usuariosAgrupados = [
                'grupos' => $grupos,
                'profesores' => $profesores,
                'alumnos' => $alumnos,
                'admins' => $admins,
                'otros' => $otros
            ];
            
            // Preparar datos para la vista
            $destinatario = $mensaje->remitente_id;
            $asunto = 'RE: ' . $mensaje->asunto;
            $contenido = '<br><br><hr><p>En ' . $mensaje->created_at->format('d/m/Y H:i') . ', ' . $mensaje->remitente->name . ' escribió:</p><blockquote>' . $mensaje->contenido . '</blockquote>';
            
            // Preparar el mensaje original para la vista
            $mensajeOriginal = [
                'remitente' => [
                    'nombre' => $mensaje->remitente->name,
                    'email' => $mensaje->remitente->email
                ],
                'destinatarios' => [
                    [
                        'nombre' => $mensaje->destinatario->name,
                        'email' => $mensaje->destinatario->email
                    ]
                ],
                'fecha' => $mensaje->created_at,
                'asunto' => $mensaje->asunto,
                'contenido' => $mensaje->contenido
            ];
            
            return view('dashboard.redactar_mensaje', compact(
                'usuarios', 
                'usuariosAgrupados',
                'destinatario', 
                'asunto', 
                'contenido', 
                'mensajeOriginal',
                'mensaje'
            ));
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de respuesta: ' . $e->getMessage());
            return redirect()->route('dashboard.mensajes')
                ->with('error', 'Error al cargar el formulario de respuesta: ' . $e->getMessage());
        }
    }
    
    /**
     * Mostrar formulario para reenviar un mensaje
     */
    public function forward($id)
    {
        try {
            // Obtener el usuario autenticado
            $userId = session('auth_user')['id'] ?? Auth::id();
            
            // Buscar el mensaje original
            $mensaje = Mensaje::with(['remitente', 'destinatario', 'adjuntos'])
                ->findOrFail($id);
            
            // Verificar permiso
            if ($mensaje->destinatario_id != $userId && 
                $mensaje->remitente_id != $userId && 
                !$this->isAdmin()) {
                return redirect()->route('dashboard.mensajes')
                    ->with('error', 'No tienes permiso para reenviar este mensaje.');
            }
            
            // Obtener lista de usuarios para destinatarios
            $usuarios = User::select('id', 'name', 'email', 'role')
                ->orderBy('name')
                ->get();
            
            // Agrupar usuarios por roles
            $profesores = $usuarios->where('role', 'profesor')->all();
            $alumnos = $usuarios->where('role', 'alumno')->all();
            $admins = $usuarios->where('role', 'admin')->all();
            $otros = $usuarios->whereNotIn('role', ['profesor', 'alumno', 'admin'])->all();
            
            // Generamos un array para los grupos
            $grupos = [
                [
                    'id' => 'grupo_profesores',
                    'nombre' => 'Todos los profesores',
                    'tipo' => 'grupo'
                ],
                [
                    'id' => 'grupo_alumnos',
                    'nombre' => 'Todos los alumnos',
                    'tipo' => 'grupo'
                ],
                [
                    'id' => 'grupo_todos',
                    'nombre' => 'Todos los usuarios',
                    'tipo' => 'grupo'
                ]
            ];
            
            // Organizamos usuarios
            $usuariosAgrupados = [
                'grupos' => $grupos,
                'profesores' => $profesores,
                'alumnos' => $alumnos,
                'admins' => $admins,
                'otros' => $otros
            ];
            
            // Preparar datos para la vista
            $asunto = 'FW: ' . $mensaje->asunto;
            $contenido = '<br><br><hr><p>Mensaje original:</p><p><strong>De:</strong> ' . $mensaje->remitente->name . '<br><strong>Para:</strong> ' . $mensaje->destinatario->name . '<br><strong>Enviado:</strong> ' . $mensaje->created_at->format('d/m/Y H:i') . '<br><strong>Asunto:</strong> ' . $mensaje->asunto . '</p>' . $mensaje->contenido;
            
            // Preparar adjuntos originales
            $adjuntosOriginales = [];
            foreach ($mensaje->adjuntos as $adjunto) {
                $adjuntosOriginales[] = [
                    'id' => $adjunto->id,
                    'nombre' => $adjunto->nombre,
                    'tipo' => $adjunto->tipo,
                    'tamano' => $this->formatoTamano($adjunto->tamaño)
                ];
            }
            
            return view('dashboard.redactar_mensaje', compact(
                'usuarios', 
                'usuariosAgrupados',
                'asunto', 
                'contenido', 
                'adjuntosOriginales',
                'mensaje'
            ));
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de reenvío: ' . $e->getMessage());
            return redirect()->route('dashboard.mensajes')
                ->with('error', 'Error al cargar el formulario de reenvío: ' . $e->getMessage());
        }
    }
    
    /**
     * Formatear el tamaño de archivo a una forma legible
     */
    private function formatoTamano($bytes)
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        } else {
            return round($bytes / 1048576, 1) . ' MB';
        }
    }

    /**
     * Ver un archivo adjunto de un mensaje
     */
    public function verAdjunto($id, $adjuntoId)
    {
        try {
            // Obtener el usuario autenticado
            $userId = session('auth_user')['id'] ?? Auth::id();
            
            if (!$userId) {
                return redirect()->route('login')->with('error', 'Debe iniciar sesión para acceder a este recurso.');
            }
            
            // Buscar el mensaje y el adjunto
            $mensaje = Mensaje::with('adjuntos')->findOrFail($id);
            $adjunto = $mensaje->adjuntos()->findOrFail($adjuntoId);
            
            // Verificar permiso para ver el archivo
            if ($mensaje->destinatario_id != $userId && 
                $mensaje->remitente_id != $userId && 
                !$this->isAdmin()) {
                return redirect()->route('dashboard.mensajes')
                    ->with('error', 'No tienes permiso para ver este archivo.');
            }
            
            // Verificar si el archivo existe
            if (!Storage::disk('public')->exists($adjunto->ruta)) {
                return redirect()->route('dashboard.mensajes')
                    ->with('error', 'El archivo no existe.');
            }
            
            // Obtener el tipo MIME del archivo
            $mimeType = Storage::disk('public')->mimeType($adjunto->ruta);
            
            // Si es una imagen o PDF, mostrarla en el navegador
            if (strpos($mimeType, 'image/') === 0 || $mimeType === 'application/pdf') {
                return Storage::disk('public')->response($adjunto->ruta, $adjunto->nombre_original, [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => 'inline; filename="' . $adjunto->nombre_original . '"'
                ]);
            }
            
            // Para otros tipos de archivo, forzar la descarga
            return Storage::disk('public')->download($adjunto->ruta, $adjunto->nombre_original, [
                'Content-Type' => $mimeType
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al acceder al archivo adjunto: ' . $e->getMessage());
            return redirect()->route('dashboard.mensajes')
                ->with('error', 'Error al acceder al archivo: ' . $e->getMessage());
        }
    }

    /**
     * Buscar destinatarios para el autocompletado
     */
    public function buscarDestinatarios(Request $request)
    {
        try {
            // Verificar autenticación de manera más robusta
            $user = Auth::user() ?? session('auth_user');
            if (!$user) {
                Log::warning('Intento de búsqueda de destinatarios sin autenticación');
                return response()->json(['error' => 'No autenticado'], 401);
            }

            $query = trim($request->get('query', ''));
            Log::debug("Búsqueda de destinatarios iniciada", [
                'query' => $query,
                'user_id' => $user['id'] ?? $user->id
            ]);
            
            if (strlen($query) < 2) {
                Log::debug("Query demasiado corta", ['query' => $query]);
                return response()->json([]);
            }
            
            $usuarios = User::where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->where('active', true)
            ->select('id', 'name', 'email', 'role')
            ->orderBy('name')
            ->limit(10)
            ->get();
            
            Log::info('Búsqueda de destinatarios exitosa', [
                'query' => $query,
                'resultados' => $usuarios->count(),
                'user_id' => $user['id'] ?? $user->id
            ]);
            
            return response()->json($usuarios->toArray());
            
        } catch (\Exception $e) {
            Log::error('Error al buscar destinatarios: ' . $e->getMessage(), [
                'query' => $query ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }
} 