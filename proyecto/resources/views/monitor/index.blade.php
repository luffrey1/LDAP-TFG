@extends('layouts.dashboard')

@section('title', 'Panel de Monitoreo')

@section('content')
<section class="section">
    <div class="section-header">
        <h1>Panel de Monitoreo</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard.index') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Monitoreo</div>
        </div>
    </div>

    <div class="section-body">
        <h2 class="section-title">Monitoreo de Sistemas</h2>
        <p class="section-lead">Gestiona y monitorea equipos en tu red.</p>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Equipos Monitoreados</h4>
                        <div class="card-header-action">
                            <a href="{{ route('monitor.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Agregar Host
                            </a>
                            <a href="{{ route('monitor.scan') }}" class="btn btn-success">
                                <i class="fas fa-search"></i> Escanear Red
                            </a>
                            <a href="{{ route('monitor.ping-all') }}" class="btn btn-info">
                                <i class="fas fa-sync"></i> Actualizar Todo
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible show fade">
                                <div class="alert-body">
                                    <button class="close" data-dismiss="alert">
                                        <span>&times;</span>
                                    </button>
                                    {{ session('success') }}
                                </div>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible show fade">
                                <div class="alert-body">
                                    <button class="close" data-dismiss="alert">
                                        <span>&times;</span>
                                    </button>
                                    {{ session('error') }}
                                </div>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-striped" id="hosts-table">
                                <thead>
                                    <tr>
                                        <th>Estado</th>
                                        <th>Hostname</th>
                                        <th>Dirección IP</th>
                                        <th>CPU</th>
                                        <th>Memoria</th>
                                        <th>Disco</th>
                                        <th>Último Visto</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($hosts as $host)
                                    <tr>
                                        <td>
                                            <div class="badge badge-{{ $host->status_color }}">
                                                {{ $host->status_text }}
                                            </div>
                                        </td>
                                        <td>{{ $host->hostname }}</td>
                                        <td>{{ $host->ip_address }}</td>
                                        <td>
                                            @if($host->cpu_usage !== null)
                                                <div class="progress" data-toggle="tooltip" title="{{ $host->cpu_usage }}%">
                                                    <div class="progress-bar bg-{{ $host->cpu_color }}" role="progressbar" 
                                                        style="width: {{ $host->cpu_usage }}%" 
                                                        aria-valuenow="{{ $host->cpu_usage }}" aria-valuemin="0" aria-valuemax="100">
                                                        {{ $host->cpu_usage }}%
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($host->memory_usage !== null)
                                                <div class="progress" data-toggle="tooltip" title="{{ $host->memory_usage }}%">
                                                    <div class="progress-bar bg-{{ $host->memory_color }}" role="progressbar" 
                                                        style="width: {{ $host->memory_usage }}%" 
                                                        aria-valuenow="{{ $host->memory_usage }}" aria-valuemin="0" aria-valuemax="100">
                                                        {{ $host->memory_usage }}%
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($host->disk_usage !== null)
                                                <div class="progress" data-toggle="tooltip" title="{{ $host->disk_usage }}%">
                                                    <div class="progress-bar bg-{{ $host->disk_color }}" role="progressbar" 
                                                        style="width: {{ $host->disk_usage }}%" 
                                                        aria-valuenow="{{ $host->disk_usage }}" aria-valuemin="0" aria-valuemax="100">
                                                        {{ $host->disk_usage }}%
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($host->last_seen)
                                                <span data-toggle="tooltip" title="{{ $host->last_seen->format('d/m/Y H:i:s') }}">
                                                    {{ $host->last_seen->diffForHumans() }}
                                                </span>
                                            @else
                                                <span class="text-muted">Nunca</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown d-inline">
                                                <button class="btn btn-primary dropdown-toggle" type="button" id="actionDropdown{{ $host->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    Acciones
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a href="{{ route('monitor.show', $host->id) }}" class="dropdown-item">
                                                        <i class="fas fa-eye"></i> Ver Detalles
                                                    </a>
                                                    <a href="javascript:void(0)" class="dropdown-item ping-host" data-id="{{ $host->id }}">
                                                        <i class="fas fa-sync"></i> Ping
                                                    </a>
                                                    <a href="{{ route('monitor.edit', $host->id) }}" class="dropdown-item">
                                                        <i class="fas fa-edit"></i> Editar
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <a href="javascript:void(0)" class="dropdown-item text-danger delete-host" data-id="{{ $host->id }}" data-hostname="{{ $host->hostname }}">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach

                                    @if(count($hosts) == 0)
                                    <tr>
                                        <td colspan="8" class="text-center">No hay hosts registrados. <a href="{{ route('monitor.create') }}">Agregar uno nuevo</a>.</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal de Confirmación para Eliminar -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar <span id="delete-hostname"></span>?</p>
                <p class="text-danger">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Inicializar datatable
    $('#hosts-table').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
        }
    });
    
    // Ping a un host
    $('.ping-host').on('click', function() {
        var hostId = $(this).data('id');
        var row = $(this).closest('tr');
        var statusBadge = row.find('td:first-child div.badge');
        
        $.ajax({
            url: '/monitor/ping/' + hostId,
            type: 'GET',
            dataType: 'json',
            beforeSend: function() {
                statusBadge.removeClass().addClass('badge badge-warning').html('Verificando...');
            },
            success: function(response) {
                if (response.status === 'online') {
                    statusBadge.removeClass().addClass('badge badge-success').html('En línea');
                } else if (response.status === 'offline') {
                    statusBadge.removeClass().addClass('badge badge-danger').html('Desconectado');
                } else {
                    statusBadge.removeClass().addClass('badge badge-warning').html('Error');
                }
                
                iziToast.info({
                    title: 'Ping',
                    message: response.message,
                    position: 'topRight'
                });
            },
            error: function(xhr, status, error) {
                statusBadge.removeClass().addClass('badge badge-danger').html('Error');
                
                iziToast.error({
                    title: 'Error',
                    message: 'No se pudo contactar el host',
                    position: 'topRight'
                });
            }
        });
    });
    
    // Configuración del modal de eliminación
    $('.delete-host').on('click', function() {
        var hostId = $(this).data('id');
        var hostname = $(this).data('hostname');
        
        $('#delete-hostname').text(hostname);
        $('#deleteForm').attr('action', '/monitor/' + hostId);
        
        $('#deleteModal').modal('show');
    });
});
</script>
@endsection 