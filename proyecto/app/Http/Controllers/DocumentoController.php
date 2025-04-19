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
    public function index(Request $request)
    {
        Log::info('DocumentoController@index iniciado - IP: ' . request()->ip());
        
        try {
            // Comprobar si el usuario está autenticado
            if (!Auth::check() && !session()->has('auth_user')) {
                Log::warning('Usuario no autenticado intentando acceder a documentos');
                return redirect()->route('login')
                    ->with('error', 'Debes iniciar sesión para acceder a esta sección.');
            }
            
            // Crear una respuesta que evite la redirección automática
            session()->put('documentos_access', true);
            
            // Obtener documentos con filtros
            $query = Documento::where('activo', true);
            
            // Aplicar filtros si existen
            if ($request->has('search') && !empty($request->search)) {
                $query->where('nombre', 'like', '%' . $request->search . '%')
                    ->orWhere('descripcion', 'like', '%' . $request->search . '%');
            }
            
            if ($request->has('type') && !empty($request->type)) {
                $query->where('extension', $request->type);
            }
            
            if ($request->has('date') && !empty($request->date)) {
                $query->whereDate('created_at', $request->date);
            }
            
            if ($request->has('categoria') && !empty($request->categoria)) {
                $query->where('carpeta', $request->categoria);
            }
            
            // Obtener documentos ordenados
            $documents = $query->orderBy('created_at', 'desc')
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
        try {
            // Intentar obtener carpetas de la base de datos
            $foldersDB = DB::table('carpetas_documentos')
                ->where('activo', true)
                ->orderBy('nombre')
                ->get();
                
            if ($foldersDB->count() > 0) {
                return $foldersDB->toArray();
            }
            
            // Si no hay datos en la base de datos, crear categorías predefinidas
            // Añadir categorías predefinidas que siempre deben existir
            $folders = [
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
            
            // Intentar crear los directorios si no existen
            foreach ($folders as $folder) {
                $dirPath = 'documentos/' . $folder['clave'];
                if (!Storage::disk('public')->exists($dirPath)) {
                    try {
                        Storage::disk('public')->makeDirectory($dirPath);
                        Log::info('Directorio creado: ' . $dirPath);
                    } catch (\Exception $e) {
                        Log::error('Error al crear directorio ' . $dirPath . ': ' . $e->getMessage());
                    }
                }
            }
            
            return $folders;
            
        } catch (\Exception $e) {
            Log::error('Error al obtener carpetas: ' . $e->getMessage());
            
            // Devolver categorías básicas en caso de error
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
                    'nombre' => 'Evaluaciones',
                    'clave' => 'evaluaciones',
                    'icono' => 'fa-clipboard-check'
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
            
            // Verificar si el archivo es válido
            if (!$file->isValid()) {
                throw new \Exception('El archivo no es válido: ' . $file->getErrorMessage());
            }
            
            // Verificar que el archivo exista y sea legible
            if (!file_exists($file->getPathname()) || !is_readable($file->getPathname())) {
                throw new \Exception('El archivo temporal no existe o no se puede leer. Intente nuevamente.');
            }
            
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileSize = $file->getSize();
            $fileName = time() . '_' . Str::slug($request->nombre) . '.' . $extension;
            
            // Log para depuración
            Log::info('Procesando archivo: ' . $originalName);
            Log::info('Categoría seleccionada: ' . $request->categoria);
            
            // Asegurar que el directorio existe
            $dirPath = 'documentos/' . $request->categoria;
            
            // Guardar el archivo usando Storage en lugar de move
            $filePath = Storage::disk('public')->putFileAs(
                $dirPath, 
                $file, 
                $fileName
            );
            
            if (!$filePath) {
                throw new \Exception('No se pudo guardar el archivo en el almacenamiento.');
            }
            
            // Guardar en la base de datos
            $documento = new Documento();
            $documento->nombre = $request->nombre;
            $documento->nombre_original = $originalName;
            $documento->descripcion = $request->descripcion;
            $documento->carpeta = $request->categoria;
            $documento->extension = $extension;
            $documento->tipo = $file->getMimeType();
            $documento->tamaño = $fileSize;
            $documento->ruta = 'storage/' . $filePath;
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
            
            // Comprobar si es una ruta de storage
            if (strpos($documento->ruta, 'storage/') === 0) {
                // Quitar el prefijo 'storage/' para obtener la ruta relativa
                $relativePath = str_replace('storage/', '', $documento->ruta);
                
                // Verificar si existe el archivo en el storage público
                if (Storage::disk('public')->exists($relativePath)) {
                    Log::info('Descargando documento desde storage: ' . $id . ' - Ruta: ' . $relativePath);
                    return Storage::disk('public')->download($relativePath, $documento->nombre_original);
                }
            } else {
                // Ruta al archivo físico (para compatibilidad con rutas antiguas)
                $rutaDocumento = public_path($documento->ruta);
                
                // Verificar si existe el archivo
                if (file_exists($rutaDocumento)) {
                    Log::info('Descargando documento: ' . $id . ' - Ruta: ' . $rutaDocumento);
                    return response()->download($rutaDocumento, $documento->nombre_original);
                }
            }
            
            Log::warning('Archivo no encontrado para descarga: ' . $documento->ruta);
            return redirect()->route('dashboard.gestion-documental')
                ->with('error', 'No se encontró el archivo físico.');
                
        } catch (\Exception $e) {
            Log::error('Error al descargar documento: ' . $e->getMessage());
            return back()->with('error', 'Error al descargar el documento: ' . $e->getMessage());
        }
    }
} 