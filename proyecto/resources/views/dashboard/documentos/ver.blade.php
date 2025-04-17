@extends('layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Detalles del Documento</h1>
        <a href="{{ route('dashboard.gestion-documental') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left fa-sm mr-2"></i> Volver a documentos
        </a>
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

    <!-- Detalles del documento -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">{{ $documento['nombre'] }}</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <h5>Información del Documento</h5>
                        <hr>
                        <p><strong>Nombre original:</strong> {{ $documento['nombre_original'] }}</p>
                        <p><strong>Tipo:</strong> {{ strtoupper($documento['extension']) }}</p>
                        <p><strong>Tamaño:</strong> {{ $documento['tamaño'] }}</p>
                        <p><strong>Subido por:</strong> {{ $documento['subido_por_nombre'] }}</p>
                        <p><strong>Fecha de subida:</strong> {{ date('d/m/Y H:i', strtotime($documento['fecha_subida'])) }}</p>
                        <p><strong>Carpeta:</strong> {{ $documento['carpeta'] }}</p>
                    </div>

                    @if(!empty($documento['descripcion']))
                    <div class="mb-4">
                        <h5>Descripción</h5>
                        <hr>
                        <p>{{ $documento['descripcion'] }}</p>
                    </div>
                    @endif

                    <div class="mb-4">
                        <h5>Acciones</h5>
                        <hr>
                        <a href="{{ route('dashboard.gestion-documental.descargar', $documento['id']) }}" class="btn btn-primary">
                            <i class="fas fa-download mr-1"></i> Descargar
                        </a>
                        <form action="{{ route('dashboard.gestion-documental.eliminar', $documento['id']) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar este documento?')">
                                <i class="fas fa-trash mr-1"></i> Eliminar
                            </button>
                        </form>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="document-preview text-center">
                        @if($documento['extension'] == 'pdf')
                        <div class="embed-responsive embed-responsive-16by9">
                            <iframe class="embed-responsive-item" src="{{ asset($documento['ruta']) }}" allowfullscreen></iframe>
                        </div>
                        @elseif(in_array($documento['extension'], ['jpg', 'jpeg', 'png', 'gif']))
                        <img src="{{ asset($documento['ruta']) }}" alt="{{ $documento['nombre'] }}" class="img-fluid">
                        @elseif(in_array($documento['extension'], ['docx', 'doc']))
                        <div class="document-placeholder p-5">
                            <i class="fas fa-file-word fa-5x text-primary"></i>
                            <p class="mt-3">Documento de Word</p>
                        </div>
                        @elseif(in_array($documento['extension'], ['xlsx', 'xls']))
                        <div class="document-placeholder p-5">
                            <i class="fas fa-file-excel fa-5x text-success"></i>
                            <p class="mt-3">Hoja de cálculo de Excel</p>
                        </div>
                        @else
                        <div class="document-placeholder p-5">
                            <i class="fas fa-file fa-5x text-gray-500"></i>
                            <p class="mt-3">{{ strtoupper($documento['extension']) }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.document-placeholder {
    background-color: #f8f9fc;
    border-radius: 0.5rem;
    padding: 3rem;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
</style>
@endsection 