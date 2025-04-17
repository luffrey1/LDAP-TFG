<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\Documento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocumentoController extends Controller
{
    /**
     * Mostrar la lista de documentos
     */
    public function index()
    {
        Log::info('DocumentoController@index iniciado - IP: ' . request()->ip());
        Log::info('Sesión actual: ' . json_encode(session()->all()));
        Log::info('URL previa: ' . url()->previous());
        Log::info('URL actual: ' . url()->current());
        
        // Verificar si estamos siendo redirigidos desde otra parte
        $referer = request()->headers->get('referer');
        Log::info('Referer: ' . ($referer ? $referer : 'No hay referer'));
        
        try {
            // Comprobar si el usuario está autenticado
            if (!Auth::check() && !session()->has('auth_user')) {
                Log::warning('Usuario no autenticado intentando acceder a documentos');
                return redirect()->route('login')
                    ->with('error', 'Debes iniciar sesión para acceder a esta sección.');
            }
            
            // Crear una respuesta que evite la redirección automática
            session()->put('documentos_access', true);
            
            // Intentar obtener documentos reales
            try {
                $documents = Documento::where('activo', true)
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function($doc) {
                        return [
                            'id' => $doc->id,
                            'nombre' => $doc->nombre,
                            'extension' => $doc->extension,
                            'fecha_subida' => $doc->created_at->format('Y-m-d H:i:s'),
                            'subido_por' => $doc->usuario ? $doc->usuario->id : 'Unknown',
                            'subido_por_nombre' => $doc->usuario ? $doc->usuario->name : 'Usuario desconocido',
                            'tamaño' => $doc->tamaño_formateado,
                            'carpeta' => $doc->carpeta
                        ];
                    });
                
                Log::info('DocumentoController: Encontrados ' . count($documents) . ' documentos');
            } catch (\Exception $e) {
                Log::warning('Error al obtener documentos: ' . $e->getMessage() . ' - Usando array vacío');
                $documents = [];
            }
            
            $folders = $this->getFolders();
            
            Log::info('DocumentoController: Renderizando vista dashboard.documentos');
            
            // Usar response()->view() para mayor control sobre la respuesta
            return response()->view('dashboard.documentos', compact('documents', 'folders'));
        } catch (\Exception $e) {
            Log::error('Error en DocumentoController@index: ' . $e->getMessage());
            Log::error('Traza: ' . $e->getTraceAsString());
            
            // En lugar de redirigir, mostramos un mensaje de error en la misma vista
            return response()->view('dashboard.documentos', [
                'documents' => [],
                'folders' => $this->getFolders(),
                'error' => 'Error al cargar documentos: ' . $e->getMessage()
            ]);
        }
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
            ]
        ];
    }
    
    /**
     * Subir un nuevo documento
     */
    public function store(Request $request)
    {
        $request->validate([
            'documento' => 'required|file|max:10240', // Máximo 10MB
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'categoria' => 'required|string'
        ]);

        try {
            $file = $request->file('documento');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileSize = $file->getSize();
            $fileName = time() . '_' . Str::slug($request->nombre) . '.' . $extension;
            
            // Asegurar que el directorio existe
            $dirPath = 'documentos/' . $request->categoria;
            $fullPath = public_path($dirPath);
            
            // Log para depuración
            Log::info('Intentando guardar en: ' . $fullPath);
            
            if (!file_exists($fullPath)) {
                Log::info('El directorio no existe, intentando crearlo');
                if (!mkdir($fullPath, 0777, true)) {
                    $error = error_get_last();
                    throw new \Exception('No se pudo crear el directorio: ' . $fullPath . ' - Error: ' . ($error['message'] ?? 'Desconocido'));
                }
                // Establecer permisos explícitamente después de crear
                chmod($fullPath, 0777);
            }
            
            // Guardar el archivo
            $filePath = $fullPath . '/' . $fileName;
            
            if (!$file->move($fullPath, $fileName)) {
                throw new \Exception('No se pudo mover el archivo al directorio: ' . $fullPath);
            }
            
            // Dar permisos al archivo creado
            chmod($filePath, 0666);
            
            // Guardar en la base de datos
            $documento = new Documento();
            $documento->nombre = $request->nombre;
            $documento->nombre_original = $originalName;
            $documento->descripcion = $request->descripcion;
            $documento->carpeta = $request->categoria;
            $documento->extension = $extension;
            $documento->tipo = $file->getMimeType();
            $documento->tamaño = $fileSize;
            $documento->ruta = $dirPath . '/' . $fileName;
            $documento->subido_por = session('auth_user')['id'] ?? Auth::id() ?? 1; // Fallback a ID 1 si no hay sesión
            $documento->activo = true;
            $documento->save();
            
            Log::info('Documento guardado en BD: ' . $documento->id);
            
            return redirect()->route('dashboard.gestion-documental')
                ->with('success', 'Documento subido correctamente.');
                
        } catch (\Exception $e) {
            Log::error('Error al subir documento: ' . $e->getMessage());
            Log::error('Traza: ' . $e->getTraceAsString());
            return back()->with('error', 'Error al subir el documento: ' . $e->getMessage());
        }
    }
    
    /**
     * Mostrar un documento específico
     */
    public function show($id)
    {
        try {
            // Buscar el documento en la base de datos
            $doc = Documento::findOrFail($id);
            
            if (!$doc || !$doc->activo) {
                Log::warning('Intentando acceder a documento inactivo o eliminado: ' . $id);
                return redirect()->route('dashboard.gestion-documental')
                    ->with('error', 'El documento no existe o ha sido desactivado.');
            }
            
            $documento = [
                'id' => $doc->id,
                'nombre' => $doc->nombre,
                'nombre_original' => $doc->nombre_original,
                'descripcion' => $doc->descripcion,
                'carpeta' => $doc->carpeta,
                'extension' => $doc->extension,
                'tipo' => $doc->tipo,
                'tamaño' => $doc->tamaño_formateado,
                'subido_por' => $doc->usuario ? $doc->usuario->id : 'Unknown',
                'subido_por_nombre' => $doc->usuario ? $doc->usuario->name : 'Usuario desconocido',
                'fecha_subida' => $doc->created_at->format('Y-m-d H:i:s'),
                'ruta' => $doc->ruta
            ];
            
            Log::info('Mostrando documento: ' . $id);
            return view('dashboard.documentos.ver', compact('documento'));
            
        } catch (\Exception $e) {
            Log::error('Error al mostrar documento: ' . $e->getMessage());
            return redirect()->route('dashboard.gestion-documental')
                ->with('error', 'Error al mostrar el documento: ' . $e->getMessage());
        }
    }
    
    /**
     * Eliminar un documento
     */
    public function destroy($id)
    {
        try {
            // Buscar el documento en la base de datos
            $documento = Documento::findOrFail($id);
            
            // Eliminar el archivo físico si existe
            $rutaArchivo = public_path($documento->ruta);
            if (file_exists($rutaArchivo)) {
                unlink($rutaArchivo);
                Log::info('Archivo físico eliminado: ' . $rutaArchivo);
            }
            
            // Eliminar de la base de datos (o marcar como inactivo)
            $documento->activo = false;
            $documento->save();
            
            Log::info('Documento desactivado en BD: ' . $id);
            
            return redirect()->route('dashboard.gestion-documental')
                ->with('success', 'Documento eliminado correctamente.');
                
        } catch (\Exception $e) {
            Log::error('Error al eliminar documento: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar el documento: ' . $e->getMessage());
        }
    }
    
    /**
     * Descargar un documento
     */
    public function download($id)
    {
        try {
            // Buscar el documento en la base de datos
            $documento = Documento::findOrFail($id);
            
            if (!$documento->activo) {
                Log::warning('Intentando descargar documento inactivo: ' . $id);
                return redirect()->route('dashboard.gestion-documental')
                    ->with('error', 'El documento no está disponible.');
            }
            
            // Ruta al archivo físico
            $rutaDocumento = public_path($documento->ruta);
            
            // Verificar si existe el archivo
            if (file_exists($rutaDocumento)) {
                Log::info('Descargando documento: ' . $id . ' - Ruta: ' . $rutaDocumento);
                return response()->download($rutaDocumento, $documento->nombre_original);
            }
            
            Log::warning('Archivo no encontrado para descarga: ' . $rutaDocumento);
            return redirect()->route('dashboard.gestion-documental')
                ->with('error', 'No se encontró el archivo físico.');
                
        } catch (\Exception $e) {
            Log::error('Error al descargar documento: ' . $e->getMessage());
            return back()->with('error', 'Error al descargar el documento: ' . $e->getMessage());
        }
    }
} 