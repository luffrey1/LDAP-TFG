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
            $usuarios = User::select('id', 'name', 'email')
                ->orderBy('name')
                ->get();
            
            return view('dashboard.redactar_mensaje', compact('usuarios'));
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
            'destinatario' => 'required|exists:users,id',
            'asunto' => 'required|string|max:255',
            'contenido' => 'required|string'
        ]);

        try {
            DB::beginTransaction();
            
            // Obtener el ID del remitente
            $remitenteId = session('auth_user')['id'] ?? Auth::id();
            
            // Crear el mensaje
            $mensaje = Mensaje::create([
                'remitente_id' => $remitenteId,
                'destinatario_id' => $request->destinatario,
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
                foreach ($request->file('adjuntos') as $file) {
                    $path = $file->store('adjuntos/mensajes', 'public');
                    
                    MensajeAdjunto::create([
                        'mensaje_id' => $mensaje->id,
                        'nombre' => $file->getClientOriginalName(),
                        'ruta' => $path,
                        'tipo' => $file->getMimeType(),
                        'tamaño' => $file->getSize()
                    ]);
                }
            }
            
            DB::commit();
            
            Log::info('Mensaje enviado por usuario ' . $remitenteId . ' a usuario ' . $request->destinatario);
            
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
            $mensaje = Mensaje::with(['remitente', 'destinatario', 'adjuntos'])
                ->findOrFail($id);
            
            // Verificar permiso para ver el mensaje
            if ($mensaje->destinatario_id != $userId && 
                $mensaje->remitente_id != $userId && 
                !$this->isAdmin()) {
                return redirect()->route('dashboard.mensajes')
                    ->with('error', 'No tienes permiso para ver este mensaje.');
            }
            
            // Marcar como leído si es el destinatario y no lo ha leído aún
            if ($mensaje->destinatario_id == $userId && !$mensaje->leido) {
                $mensaje->leido = true;
                $mensaje->save();
                Log::info('Mensaje marcado como leído: ' . $id);
            }
            
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
                return redirect()->route('dashboard.mensajes')
                    ->with('error', 'No tienes permiso para modificar este mensaje.');
            }
            
            // Cambiar estado
            $mensaje->leido = !$mensaje->leido;
            $mensaje->save();
            
            $estado = $mensaje->leido ? 'leído' : 'no leído';
            Log::info('Mensaje marcado como ' . $estado . ': ' . $id);
            
            return redirect()->back()
                ->with('success', 'Mensaje marcado como ' . $estado . '.');
                
        } catch (\Exception $e) {
            Log::error('Error al actualizar estado de lectura: ' . $e->getMessage());
            return back()->with('error', 'Error al actualizar el mensaje: ' . $e->getMessage());
        }
    }

    /**
     * Marcar un mensaje como destacado/no destacado
     */
    public function toggleFavorite($id)
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
            
            // Cambiar estado
            $mensaje->destacado = !$mensaje->destacado;
            $mensaje->save();
            
            $estado = $mensaje->destacado ? 'destacado' : 'no destacado';
            Log::info('Mensaje marcado como ' . $estado . ': ' . $id);
            
            return redirect()->back()
                ->with('success', 'Mensaje marcado como ' . $estado . '.');
                
        } catch (\Exception $e) {
            Log::error('Error al actualizar estado destacado: ' . $e->getMessage());
            return back()->with('error', 'Error al actualizar el mensaje: ' . $e->getMessage());
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
                'papelera' => Mensaje::papelera($userId)->count()
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener contadores de mensajes: ' . $e->getMessage());
            return [
                'recibidos' => 0,
                'no_leidos' => 0,
                'destacados' => 0,
                'enviados' => 0,
                'papelera' => 0
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
            return Auth::user()->hasRole('admin');
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
} 