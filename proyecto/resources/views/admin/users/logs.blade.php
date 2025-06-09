@extends('layouts.dashboard')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ __('Logs de Actividad LDAP') }}</span>
                        <div>
                            <div class="btn-group me-2">
                                <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-trash"></i> {{ __('Borrar últimos') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('admin.logs.delete', ['count' => 500]) }}" onclick="return confirm('¿Estás seguro de borrar los últimos 500 logs?')">Últimos 500</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.logs.delete', ['count' => 1000]) }}" onclick="return confirm('¿Estás seguro de borrar los últimos 1000 logs?')">Últimos 1000</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.logs.delete', ['count' => 2000]) }}" onclick="return confirm('¿Estás seguro de borrar los últimos 2000 logs?')">Últimos 2000</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.logs.delete', ['count' => 5000]) }}" onclick="return confirm('¿Estás seguro de borrar los últimos 5000 logs?')">Últimos 5000</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="{{ route('admin.logs.delete', ['count' => 'all']) }}" onclick="return confirm('¿Estás seguro de borrar TODOS los logs? Esta acción no se puede deshacer.')">Borrar todos</a></li>
                                </ul>
                            </div>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-light">
                                <i class="fas fa-users"></i> {{ __('Volver a Usuarios') }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('status') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Pestañas de filtro -->
                    <ul class="nav nav-tabs mb-3" id="logTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="true">
                                <i class="fas fa-list"></i> Todos
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="false">
                                <i class="fas fa-users"></i> Usuarios
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="groups-tab" data-bs-toggle="tab" data-bs-target="#groups" type="button" role="tab" aria-controls="groups" aria-selected="false">
                                <i class="fas fa-user-friends"></i> Grupos
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="access-tab" data-bs-toggle="tab" data-bs-target="#access" type="button" role="tab" aria-controls="access" aria-selected="false">
                                <i class="fas fa-sign-in-alt"></i> Accesos
                            </button>
                        </li>
                    </ul>

                    <!-- Búsqueda de usuario -->
                    <div class="mb-3">
                        <div class="input-group">
                            <input type="text" id="userSearch" class="form-control" placeholder="Buscar por usuario...">
                            <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="logsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Fecha') }}</th>
                                    <th>{{ __('Nivel') }}</th>
                                    <th>{{ __('Usuario') }}</th>
                                    <th>{{ __('Acción') }}</th>
                                    <th>{{ __('Descripción') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr data-type="{{ $log->type ?? 'other' }}" data-id="{{ $log->id }}" class="log-row" style="cursor: pointer;">
                                        <td>{{ $log->id }}</td>
                                        <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $log->level == 'ERROR' ? 'danger' : ($log->level == 'WARNING' ? 'warning' : ($log->level == 'INFO' ? 'info' : ($log->level == 'DEBUG' ? 'secondary' : 'primary'))) }}">
                                                {{ $log->level }}
                                            </span>
                                        </td>
                                        <td>{{ $log->user ?? 'Sistema' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $log->action == 'Error' ? 'danger' : ($log->action == 'Advertencia' ? 'warning' : ($log->action == 'Información' ? 'info' : 'secondary')) }}">
                                                {{ $log->action }}
                                            </span>
                                        </td>
                                        <td class="text-truncate" style="max-width: 400px;" title="{{ $log->description }}">
                                            {{ \Illuminate\Support\Str::limit($log->description, 100) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="alert alert-info mb-0">
                                                <i class="fas fa-info-circle me-2"></i> {{ __('No hay registros de actividad disponibles') }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($activityLogs->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $activityLogs->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de detalles -->
<div class="modal fade" id="logDetailsModal" tabindex="-1" aria-labelledby="logDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logDetailsModalLabel">Detalles del Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>ID:</strong> <span id="modal-id"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Fecha:</strong> <span id="modal-date"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Usuario:</strong> <span id="modal-user"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Nivel:</strong> <span id="modal-level"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Acción:</strong> <span id="modal-action"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Descripción:</strong>
                        <p id="modal-description" class="mt-2"></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <strong>Detalles Adicionales:</strong>
                        <pre id="modal-details" class="mt-2 bg-light p-3 rounded"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTables
    const table = $('#logsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
        },
        order: [[1, 'desc']], // Ordenar por fecha descendente
        pageLength: 25,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        processing: true,
        serverSide: false,
        searching: true,
        info: true,
        lengthChange: true,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
        columnDefs: [
            {
                targets: 0,
                visible: false // Ocultar la columna de ID
            }
        ]
    });

    // Función para filtrar por tipo de log
    function filterByType(type) {
        table.column(0).search('').draw(); // Limpiar búsqueda anterior
        
        if (type === 'all') {
            table.column(0).search('').draw();
        } else {
            // Buscar en la columna de tipo (data-type)
            table.rows().every(function() {
                const rowData = this.data();
                const rowType = $(this.node()).data('type');
                if (type === rowType) {
                    $(this.node()).show();
                } else {
                    $(this.node()).hide();
                }
            });
        }
    }

    // Eventos de las pestañas
    document.querySelectorAll('#logTabs button').forEach(button => {
        button.addEventListener('click', function() {
            // Remover clase active de todas las pestañas
            document.querySelectorAll('#logTabs button').forEach(btn => {
                btn.classList.remove('active');
            });
            // Añadir clase active a la pestaña actual
            this.classList.add('active');
            
            const type = this.id.split('-')[0];
            filterByType(type);
        });
    });

    // Búsqueda de usuario
    const userSearch = document.getElementById('userSearch');
    const clearSearch = document.getElementById('clearSearch');

    userSearch.addEventListener('keyup', function() {
        table.column(3).search(this.value).draw();
    });

    clearSearch.addEventListener('click', function() {
        userSearch.value = '';
        table.column(3).search('').draw();
    });

    // Modal de detalles
    const logDetailsModal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
    
    // Función para mostrar detalles del log
    async function showLogDetails(id) {
        try {
            const response = await fetch(`/admin/logs/${id}`);
            if (!response.ok) throw new Error('Log no encontrado');
            
            const log = await response.json();
            
            // Actualizar contenido del modal
            document.getElementById('modal-id').textContent = log.id;
            document.getElementById('modal-date').textContent = new Date(log.created_at).toLocaleString();
            document.getElementById('modal-user').textContent = log.user || 'Sistema';
            document.getElementById('modal-level').innerHTML = `<span class="badge bg-${log.level === 'ERROR' ? 'danger' : (log.level === 'WARNING' ? 'warning' : (log.level === 'INFO' ? 'info' : 'secondary'))}">${log.level}</span>`;
            document.getElementById('modal-action').innerHTML = `<span class="badge bg-${log.action === 'Error' ? 'danger' : (log.action === 'Advertencia' ? 'warning' : (log.action === 'Información' ? 'info' : 'secondary'))}">${log.action}</span>`;
            document.getElementById('modal-description').textContent = log.description;
            document.getElementById('modal-details').textContent = JSON.stringify(log.details, null, 2);
            
            // Mostrar modal
            logDetailsModal.show();
        } catch (error) {
            console.error('Error al cargar detalles del log:', error);
            alert('Error al cargar los detalles del log');
        }
    }

    // Evento de clic en filas de la tabla usando delegación de eventos
    $('#logsTable tbody').on('click', 'tr.log-row', function() {
        const id = $(this).data('id');
        showLogDetails(id);
    });

    // Ocultar la paginación de Laravel ya que usamos DataTables
    document.querySelector('.pagination').style.display = 'none';
});
</script>
@endsection 