@extends('layouts.dashboard')

@section('title', 'Monitoreo de Equipos')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-desktop me-2"></i> Monitoreo de Equipos</h4>
                        <div>
                            <a href="{{ route('monitor.groups.index') }}" class="btn btn-light btn-sm me-2">
                                <i class="fas fa-layer-group me-1"></i> Grupos de Aulas
                            </a>
                            <a href="{{ route('monitor.scan') }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-search me-1"></i> Escanear Red
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                        </div>
                    @endif

                    <div class="row mb-4 text-white">
                        <div class="col-md-3 mb-3 text-white">
                            <div class="card bg-primary text-white h-100">
                                <div class="card-body text-white text-center">
                                    <h1 class="display-4">{{ count($hosts) }}</h1>
                                    <p class="lead">Total Equipos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3 text-white">
                            <div class="card bg-success text-white h-100">
                                <div class="card-body text-center text-white">
                                    <h1 class="display-4">{{ $hosts->where('status', 'online')->count() }}</h1>
                                    <p class="lead">En Línea</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3 text-white">
                            <div class="card bg-danger text-white h-100">
                                <div class="card-body text-center text-white">
                                    <h1 class="display-4">{{ $hosts->where('status', 'offline')->count() }}</h1>
                                    <p class="lead">Desconectados</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3 text-white">
                            <div class="card bg-info text-white h-100">
                                <div class="card-body text-center text-white">
                                    <h1 class="display-4">{{ $groups->count() }}</h1>
                                    <p class="lead">Grupos de Aulas</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0 text-black"><i class="fas fa-list me-2 text-black"></i> Listado de Equipos</h5>
                                        <div>
                                            <a href="{{ route('monitor.create') }}" class="btn btn-success btn-sm me-2">
                                                <i class="fas fa-plus me-1"></i> Añadir Equipo
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    @if(count($hosts) > 0)
                                        @php
                                            $hostsByGroup = $hosts->groupBy(function($host) {
                                                return $host->group ? $host->group->name : 'Sin grupo';
                                            });
                                        @endphp

                                        @foreach($hostsByGroup as $groupName => $groupHosts)
                                            <div class="mb-4">
                                                <h5 class="bg-light p-2 border-start border-4 border-primary mb-0 d-flex align-items-center justify-content-between">
                                                    <div>
                                                        <i class="fas fa-{{ $groupHosts->first()->group && $groupHosts->first()->group->type == 'classroom' ? 'chalkboard-teacher' : ($groupName == 'Infraestructura' ? 'network-wired' : 'server') }} me-2"></i>
                                                        {{ $groupName }}
                                                        <span class="badge bg-secondary ms-2">{{ $groupHosts->count() }} equipos</span>
                                                    </div>
                                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-group-id="{{ $groupHosts->first()->group ? $groupHosts->first()->group->id : null }}">
                                                        <i class="fas fa-sync-alt me-1"></i> Actualizar Estado
                                                    </button>
                                                </h5>
                                                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                                    <table class="table table-hover mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Estado</th>
                                                                <th>Hostname</th>
                                                                <th>IP</th>
                                                                <th>MAC</th>
                                                                <th>Último visto</th>
                                                                <th>Acciones</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                        @foreach($groupHosts as $host)
                                                            <tr>
                                                                <td>
                                                                    <span class="badge bg-{{ $host->status_color }}">
                                                                        <i class="fas fa-{{ $host->status == 'online' ? 'check-circle' : ($host->status == 'offline' ? 'times-circle' : 'exclamation-circle') }} me-1"></i>
                                                                        {{ $host->status_text }}
                                                                    </span>
                                                                </td>
                                                                <td>{{ $host->hostname }}</td>
                                                                <td>{{ $host->ip_address }}</td>
                                                                <td>
                                                                    @if($host->mac_address)
                                                                        <span class="text-success"><i class="fas fa-check-circle"></i> {{ $host->mac_address }}</span>
                                                                    @else
                                                                        <span class="text-danger"><i class="fas fa-times-circle"></i> Sin MAC</span>
                                                                    @endif
                                                                </td>
                                                                <td>{{ $host->last_seen ? $host->last_seen->format('d/m/Y H:i:s') : 'Nunca' }}</td>
                                                                <td>
                                                                    <div class="btn-group" role="group">
                                                                        <a href="{{ route('monitor.show', $host->id) }}" class="btn btn-sm btn-primary" title="Ver detalles">
                                                                            <i class="fas fa-eye"></i>
                                                                        </a>
                                                                        <button type="button" class="btn btn-sm btn-info btn-ping" 
                                                                                data-host-id="{{ $host->id }}"
                                                                                data-hostname="{{ $host->hostname }}"
                                                                                data-bs-toggle="modal" 
                                                                                data-bs-target="#updateStatusModal"
                                                                                title="Ping">
                                                                            <i class="fas fa-exchange-alt"></i>
                                                                        </button>
                                                                        @if($host->mac_address)
                                                                            <a href="{{ route('monitor.wol', $host->id) }}" 
                                                                               class="btn btn-sm btn-dark" 
                                                                               onclick="return confirm('¿Enviar señal Wake-on-LAN a {{ $host->hostname }}?');" 
                                                                               title="Wake-on-LAN">
                                                                                <i class="fas fa-power-off"></i>
                                                                            </a>
                                                                        @endif
                                                                        <a href="{{ route('monitor.edit', $host->id) }}" class="btn btn-sm btn-warning" title="Editar">
                                                                            <i class="fas fa-edit"></i>
                                                                        </a>
                                                                        <button type="button" class="btn btn-sm btn-danger" 
                                                                                onclick="eliminarHost({{ $host->id }}, '{{ $host->hostname }}')"
                                                                                title="Eliminar">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </div>
                                                                    <form id="form-eliminar-{{ $host->id }}" action="{{ route('monitor.destroy', $host->id) }}" method="POST" style="display: none;">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i> No hay equipos registrados para monitorear.
                                            <a href="{{ route('monitor.create') }}" class="alert-link">Haga clic aquí para añadir uno</a> o
                                            <a href="{{ route('monitor.scan') }}" class="alert-link">ejecute un escaneo de red</a>.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(count($groups) > 0)
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0 text-black"><i class="fas fa-layer-group me-2 text-black"></i> Grupos de Equipos</h5>
                                        <a href="{{ route('monitor.groups.index') }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-external-link-alt me-1"></i> Ver Todos
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @foreach($groups as $group)
                                            <div class="col-md-4 mb-3">
                                                <div class="card h-100 border-{{ $group->is_active ? 'success' : 'secondary' }} text-white">
                                                    <div class="card-header bg-{{ $group->is_active ? 'success' : 'secondary' }} text-white">
                                                        <h5 class="mb-0">
                                                            <i class="fas fa-{{ $group->type == 'classroom' ? 'chalkboard-teacher' : 'building' }} me-2 text-white"></i>
                                                            {{ $group->name }}
                                                            <span class="badge bg-light text-dark float-end">
                                                                {{ $group->total_hosts_count }} equipos
                                                            </span>
                                                        </h5>
                                                    </div>
                                                    <div class="card-body text-white">
                                                        <p>{{ \Illuminate\Support\Str::limit($group->description, 100) ?: 'Sin descripción' }}</p>
                                                        <div class="progress" style="height: 20px;">
                                                            @php
                                                                $online_percentage = $group->total_hosts_count > 0 
                                                                    ? round(($group->online_hosts_count / $group->total_hosts_count) * 100) 
                                                                    : 0;
                                                            @endphp
                                                            <div class="progress-bar bg-success" 
                                                                style="width: {{ $online_percentage }}%;" 
                                                                aria-valuenow="{{ $online_percentage }}" 
                                                                aria-valuemin="0" 
                                                                aria-valuemax="100">
                                                                {{ $online_percentage }}% En Línea
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="card-footer bg-light">
                                                        <a href="{{ route('monitor.groups.show', $group->id) }}" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-eye me-1"></i> Ver
                                                        </a>
                                                        <a href="{{ route('monitor.groups.wol', $group->id) }}" 
                                                           class="btn btn-dark btn-sm float-end" 
                                                           onclick="return confirm('¿Estás seguro que deseas enviar Wake-on-LAN a todos los equipos de este grupo?');">
                                                            <i class="fas fa-power-off me-1"></i> WOL
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    @if($groups->isEmpty())
                                        <div class="alert alert-info mt-3">
                                            <i class="fas fa-info-circle me-2"></i> No hay grupos creados aún. Los grupos se crean automáticamente al añadir hosts con nombres tipo <b>B25-...</b>, <b>B27-...</b>, etc.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de selección de tipo de escaneo -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-black" id="updateStatusModalLabel">Actualizar Estado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-black">Seleccione el tipo de escaneo:</label>
                    <div class="form-check text-black">
                        <input class="form-check-input" type="radio" name="scanType" id="scanByHostname" value="hostname" checked>
                        <label class="form-check-label" for="scanByHostname">
                            Escanear por Hostname (para equipos de aula)
                        </label>
                    </div>
                    <div class="form-check text-black">
                        <input class="form-check-input" type="radio" name="scanType" id="scanByIP" value="ip">
                        <label class="form-check-label" for="scanByIP">
                            Escanear por IP (para equipos de infraestructura)
                        </label>
                    </div>
                </div>
                <div id="scanProgress" style="display: none;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="spinner-border spinner-border-sm me-2" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <span id="scanStatus">Detectando equipos...</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="startScanBtn">
                    <i class="fas fa-sync-alt me-1"></i> Iniciar Escaneo
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configuración global de AJAX para incluir token CSRF
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Cerrar alertas automáticamente después de 5 segundos
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);

        // Inicializar el modal de Bootstrap
        const updateStatusModal = new bootstrap.Modal(document.getElementById('updateStatusModal'));

        let scanInProgress = false;
        const scanProgress = $('#scanProgress');
        const scanStatus = $('#scanStatus');
        const progressBar = $('.progress-bar');
        const startScanBtn = $('#startScanBtn');
        let currentGroupId = null;
        let currentHostId = null;
        let currentHostname = null;

        // Manejador del botón de escaneo por grupo
        $('[data-bs-target="#updateStatusModal"]').on('click', function() {
            currentGroupId = $(this).data('group-id');
            currentHostId = $(this).data('host-id');
            currentHostname = $(this).data('hostname');
            
            // Configurar el modal según si es grupo o host individual
            if (currentHostId) {
                $('#updateStatusModal .modal-title').text('Escanear Host Individual: ' + currentHostname);
            } else if (currentGroupId) {
                $('#updateStatusModal .modal-title').text('Escanear Grupo');
            } else {
                alert('No se pudo determinar el tipo de escaneo');
                return;
            }
        });

        // Manejador del botón de inicio de escaneo
        $('#startScanBtn').on('click', function() {
            if (scanInProgress) return;
            
            const scanType = $('input[name="scanType"]:checked').val();
            
            // Mostrar progreso
            scanProgress.show();
            startScanBtn.prop('disabled', true);
            scanInProgress = true;
            progressBar.css('width', '0%');
            scanStatus.html('<i class="fas fa-spinner fa-spin me-2"></i>Detectando...');
            
            // Determinar la URL y datos según el tipo de escaneo
            let url, data;
            if (currentHostId) {
                url = "{{ route('monitor.ping', ['id' => '__id__']) }}".replace('__id__', currentHostId);
                data = {
                    _token: '{{ csrf_token() }}',
                    scan_type: scanType
                };
            } else {
                url = "{{ route('monitor.ping-all') }}";
                data = {
                    _token: '{{ csrf_token() }}',
                    scan_type: scanType,
                    group: currentGroupId
                };
            }
            
            // Realizar el escaneo
            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                success: function(response) {
                    progressBar.css('width', '100%');
                    scanStatus.html('<i class="fas fa-check-circle me-2 text-success"></i>Escaneo completado');
                    
                    setTimeout(() => {
                        updateStatusModal.hide();
                        location.reload();
                    }, 1000);
                },
                error: function(xhr) {
                    console.error('Error en la petición:', xhr);
                    scanStatus.html('<i class="fas fa-exclamation-circle me-2 text-danger"></i>Error en el escaneo');
                    startScanBtn.prop('disabled', false);
                },
                complete: function() {
                    scanInProgress = false;
                    startScanBtn.prop('disabled', false);
                }
            });
        });

        // Resetear el modal cuando se cierra
        $('#updateStatusModal').on('hidden.bs.modal', function() {
            scanProgress.hide();
            scanStatus.text('Detectando...');
            progressBar.css('width', '0%');
            startScanBtn.prop('disabled', false);
            currentGroupId = null;
            currentHostId = null;
            currentHostname = null;
        });

        // Definir eliminarHost en el scope global
        function eliminarHost(id, nombre) {
            var form = document.getElementById('form-eliminar-' + id);
            if (!form) {
                alert('No se encontró el formulario para eliminar el host.');
                return;
            }
            if (confirm('¿Está seguro que desea eliminar el equipo "' + nombre + '"? Esta acción no se puede deshacer.')) {
                form.submit();
            }
        }
    });
</script>
@endpush
@endsection 