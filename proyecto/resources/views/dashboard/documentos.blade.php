@extends('layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Documentos</h1>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#uploadDocumentModal">
            <i class="fas fa-upload fa-sm text-white-50 mr-1"></i> Subir Documento
        </button>
    </div>

    <!-- Mensajes de alerta -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    <!-- Filtros -->
    <div class="card shadow mb-4 bg-white">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('dashboard.gestion-documental') }}" method="GET" class="row">
                <div class="col-md-3 mb-3">
                    <label for="search">Buscar:</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Nombre del documento" value="{{ request('search') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="type">Tipo:</label>
                    <select name="type" id="type" class="form-control">
                        <option value="">Todos los tipos</option>
                        <option value="pdf" {{ request('type') == 'pdf' ? 'selected' : '' }}>PDF</option>
                        <option value="doc" {{ request('type') == 'doc' ? 'selected' : '' }}>Word</option>
                        <option value="xls" {{ request('type') == 'xls' ? 'selected' : '' }}>Excel</option>
                        <option value="other" {{ request('type') == 'other' ? 'selected' : '' }}>Otros</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="date">Fecha:</label>
                    <input type="date" name="date" id="date" class="form-control" value="{{ request('date') }}">
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary mr-2">Filtrar</button>
                    <a href="{{ route('dashboard.gestion-documental') }}" class="btn btn-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de documentos -->
    <div class="card shadow mb-4 bg-white">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-primary">Documentos ({{ isset($documents) ? count($documents) : 0 }})</h6>
        </div>
        <div class="card-body">
            @if(isset($documents) && count($documents) > 0)
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Subido por</th>
                            <th>Fecha</th>
                            <th>Tamaño</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documents as $doc)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($doc['extension'] == 'pdf')
                                    <i class="fas fa-file-pdf text-danger mr-2"></i>
                                    @elseif(in_array($doc['extension'], ['docx', 'doc']))
                                    <i class="fas fa-file-word text-primary mr-2"></i>
                                    @elseif(in_array($doc['extension'], ['xlsx', 'xls']))
                                    <i class="fas fa-file-excel text-success mr-2"></i>
                                    @else
                                    <i class="fas fa-file text-gray-500 mr-2"></i>
                                    @endif
                                    {{ $doc['nombre'] }}
                                </div>
                            </td>
                            <td>{{ strtoupper($doc['extension']) }}</td>
                            <td>{{ $doc['subido_por_nombre'] }}</td>
                            <td>{{ date('d/m/Y', strtotime($doc['fecha_subida'])) }}</td>
                            <td>{{ $doc['tamaño'] }}</td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('dashboard.gestion-documental.descargar', $doc['id']) }}" class="btn btn-sm btn-primary" title="Descargar">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-info" title="Ver" data-toggle="modal" data-target="#viewDocumentModal{{ $doc['id'] }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <form action="{{ route('dashboard.gestion-documental.eliminar', $doc['id']) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Estás seguro de que deseas eliminar este documento?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p class="text-center py-3">No se encontraron documentos.</p>
            @endif
        </div>
    </div>
</div>

<!-- Modal para subir documento -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1" role="dialog" aria-labelledby="uploadDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadDocumentModalLabel">Subir Documento</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('dashboard.gestion-documental.subir') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="documentFile">Archivo:</label>
                        <div class="custom-file">
                            <input type="file" name="documento" class="custom-file-input" id="documentFile" required>
                            <label class="custom-file-label" for="documentFile">Seleccionar archivo</label>
                        </div>
                        <small class="form-text text-muted">Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP, RAR (Máximo 10MB)</small>
                    </div>
                    <div class="form-group">
                        <label for="documentTitle">Título del documento:</label>
                        <input type="text" name="nombre" class="form-control" id="documentTitle" placeholder="Título del documento" required>
                    </div>
                    <div class="form-group">
                        <label for="documentCategory">Categoría:</label>
                        <select name="categoria" id="documentCategory" class="form-control" required>
                            <option value="">Seleccionar categoría</option>
                            @foreach($folders as $folder)
                            <option value="{{ $folder['clave'] }}">{{ $folder['nombre'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="documentDescription">Descripción (opcional):</label>
                        <textarea name="descripcion" id="documentDescription" class="form-control" rows="3" placeholder="Descripción del documento"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Subir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modals para visualizar documentos -->
@foreach($documents as $doc)
<div class="modal fade" id="viewDocumentModal{{ $doc['id'] }}" tabindex="-1" role="dialog" aria-labelledby="viewDocumentModalLabel{{ $doc['id'] }}" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewDocumentModalLabel{{ $doc['id'] }}">{{ $doc['nombre'] }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Subido por:</strong> {{ $doc['subido_por_nombre'] }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Fecha:</strong> {{ date('d/m/Y', strtotime($doc['fecha_subida'])) }}</p>
                    </div>
                </div>
                
                <div class="document-preview text-center">
                    @if($doc['extension'] == 'pdf')
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i> Vista previa disponible solo para documentos PDF.
                    </div>
                    <div class="embed-responsive embed-responsive-16by9">
                        <iframe class="embed-responsive-item" src="#" allowfullscreen></iframe>
                    </div>
                    @elseif(in_array($doc['extension'], ['docx', 'doc']))
                    <div class="document-placeholder">
                        <i class="fas fa-file-word fa-5x text-primary"></i>
                        <p class="mt-3">Documento de Word</p>
                    </div>
                    @elseif(in_array($doc['extension'], ['xlsx', 'xls']))
                    <div class="document-placeholder">
                        <i class="fas fa-file-excel fa-5x text-success"></i>
                        <p class="mt-3">Hoja de cálculo de Excel</p>
                    </div>
                    @else
                    <div class="document-placeholder">
                        <i class="fas fa-file fa-5x text-gray-500"></i>
                        <p class="mt-3">{{ strtoupper($doc['extension']) }}</p>
                    </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <a href="{{ route('dashboard.gestion-documental.descargar', $doc['id']) }}" class="btn btn-primary">
                    <i class="fas fa-download mr-1"></i> Descargar
                </a>
            </div>
        </div>
    </div>
</div>
@endforeach

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Mostrar el nombre del archivo seleccionado en el input file
    $(".custom-file-input").on("change", function() {
        var fileName = $(this).val().split("\\").pop();
        $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
    });
});
</script>
@endsection

@section('styles')
<style>
.document-placeholder {
    padding: 3rem;
    background-color: #f8f9fc;
    border-radius: 0.5rem;
}
</style>
@endsection 