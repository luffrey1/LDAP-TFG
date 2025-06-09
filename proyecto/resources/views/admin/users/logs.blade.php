@extends('layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-history mr-2"></i>Logs del Sistema
                    </h3>
                    <div class="card-tools">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <input type="text" id="userSearch" class="form-control float-right" placeholder="Buscar usuario...">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-light" id="clearSearch">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Pestañas de filtro -->
                    <ul class="nav nav-tabs nav-fill mb-4" id="logTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="all-tab" data-toggle="tab" href="#all" role="tab">
                                <i class="fas fa-list mr-1"></i> Todos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="users-tab" data-toggle="tab" href="#users" role="tab">
                                <i class="fas fa-users mr-1"></i> Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="groups-tab" data-toggle="tab" href="#groups" role="tab">
                                <i class="fas fa-user-friends mr-1"></i> Grupos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="access-tab" data-toggle="tab" href="#access" role="tab">
                                <i class="fas fa-sign-in-alt mr-1"></i> Accesos
                            </a>
                        </li>
                    </ul>

                    <!-- Contenido de las pestañas -->
                    <div class="tab-content" id="logTabsContent">
                        <div class="tab-pane fade show active" id="all" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover" id="logsTable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Usuario</th>
                                            <th>Acción</th>
                                            <th>Descripción</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($logs as $log)
                                        <tr class="log-row" data-id="{{ $log->id }}" data-type="{{ $log->type }}">
                                            <td>
                                                <span class="badge badge-info text-black">
                                                    <i class="fas fa-user mr-1"></i>{{ $log->user }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $log->level === 'WARNING' ? 'warning' : 'success' }} text-black">
                                                    {{ $log->action }}
                                                </span>
                                            </td>
                                            <td class="text-black">{{ $log->description }}</td>
                                            <td>
                                                <span class="text-muted">
                                                    <i class="far fa-clock mr-1"></i>
                                                    {{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4">
                                {{ $logs->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para detalles del log -->
<div class="modal fade" id="logDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle mr-2"></i>Detalles del Log
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="font-weight-bold">ID:</label>
                    <p id="logId" class="text-muted"></p>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Fecha:</label>
                    <p id="logDate" class="text-muted"></p>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Usuario:</label>
                    <p id="logUser" class="text-muted"></p>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Acción:</label>
                    <p id="logAction" class="text-muted"></p>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Descripción:</label>
                    <p id="logDescription" class="text-muted"></p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Función para determinar el tipo de log
    function getLogType(action, description) {
        if (!action && !description) return 'all';
        
        action = (action || '').toLowerCase();
        description = (description || '').toLowerCase();
        
        console.log('Analizando:', { action, description });
        
        // Patrones de detección
        const patterns = {
            users: [
                // Patrones en action
                'crear usuario',
                'actualizar usuario',
                'eliminar usuario',
                'modificar usuario',
                'nuevo usuario',
                // Patrones en description
                'usuario ldap creado',
                'usuario ldap actualizado',
                'usuario ldap eliminado',
                'usuario actualizado',
                'usuario creado',
                'usuario eliminado',
                'usuario modificado',
                'nuevo usuario',
                'modificación de usuario'
            ],
            groups: [
                // Patrones en action
                'crear grupo',
                'actualizar grupo',
                'eliminar grupo',
                'modificar grupo',
                'nuevo grupo',
                // Patrones en description
                'grupo ldap creado',
                'grupo ldap actualizado',
                'grupo ldap eliminado',
                'grupo actualizado',
                'grupo creado',
                'grupo eliminado',
                'grupo modificado',
                'nuevo grupo',
                'modificación de grupo',
                'miembro añadido al grupo',
                'miembro eliminado del grupo',
                'grupo modificado',
                'memberuid',
                'uniquemember',
                'member'
            ],
            access: [
                // Patrones en action
                'intento de acceso',
                'acceso exitoso',
                'acceso fallido',
                'login',
                'logout',
                // Patrones en description
                'desde',
                'ip:',
                'user agent',
                'sesión iniciada',
                'sesión cerrada',
                'autenticación',
                'authentication',
                'failed login',
                'successful login'
            ]
        };

        // Verificar cada patrón en ambos campos
        for (const [type, patternList] of Object.entries(patterns)) {
            if (patternList.some(pattern => 
                action.includes(pattern) || description.includes(pattern)
            )) {
                console.log('Tipo detectado:', type, 'para:', { action, description });
                return type;
            }
        }

        // Si no coincide con ningún patrón, intentar determinar por contexto
        if (description.includes('grupo') || description.includes('group') || 
            action.includes('grupo') || action.includes('group')) {
            console.log('Tipo detectado por contexto: groups');
            return 'groups';
        }

        if (description.includes('acceso') || description.includes('access') || 
            action.includes('acceso') || action.includes('access')) {
            console.log('Tipo detectado por contexto: access');
            return 'access';
        }

        if (description.includes('usuario') || description.includes('user') || 
            action.includes('usuario') || action.includes('user')) {
            console.log('Tipo detectado por contexto: users');
            return 'users';
        }

        console.log('No se detectó tipo específico, usando "all"');
        return 'all';
    }

    // Asignar tipos a las filas
    $('.log-row').each(function() {
        const $row = $(this);
        const action = $row.find('td:eq(1)').text();
        const description = $row.find('td:eq(2)').text();
        const type = getLogType(action, description);
        $row.attr('data-type', type);
        console.log('Fila asignada:', { action, description, type });
    });

    // Función para filtrar logs
    function filterLogs(type) {
        console.log('Filtrando por tipo:', type);
        let visibleCount = 0;

        $('.log-row').each(function() {
            const $row = $(this);
            const rowType = $row.attr('data-type');
            const shouldShow = type === 'all' || rowType === type;
            
            if (shouldShow) {
                $row.show();
                visibleCount++;
            } else {
                $row.hide();
            }
        });

        console.log('Filas visibles:', visibleCount);
        updateVisibleCount(visibleCount);
    }

    // Función para actualizar el contador
    function updateVisibleCount(count) {
        const $counter = $('#visibleCount');
        if ($counter.length === 0) {
            $('.card-header').append('<span id="visibleCount" class="ml-3 badge badge-info">Filas visibles: ' + count + '</span>');
        } else {
            $counter.text('Filas visibles: ' + count);
        }
    }

    // Manejar cambios de pestaña
    $('#logTabs a').on('click', function(e) {
        e.preventDefault();
        $(this).tab('show');
        
        const type = $(this).attr('id').replace('-tab', '');
        console.log('Pestaña clickeada:', type);
        filterLogs(type);
    });

    // Búsqueda de usuario
    $('#userSearch').on('keyup', function() {
        const searchText = $(this).val().toLowerCase();
        const currentTab = $('#logTabs .active').attr('id').replace('-tab', '');
        let visibleCount = 0;

        $('.log-row').each(function() {
            const $row = $(this);
            const userText = $row.find('td:first').text().toLowerCase();
            const rowType = $row.attr('data-type');
            
            const matchesSearch = userText.includes(searchText);
            const matchesType = currentTab === 'all' || rowType === currentTab;
            
            if (matchesSearch && matchesType) {
                $row.show();
                visibleCount++;
            } else {
                $row.hide();
            }
        });

        updateVisibleCount(visibleCount);
    });

    // Limpiar búsqueda
    $('#clearSearch').on('click', function() {
        $('#userSearch').val('');
        const currentTab = $('#logTabs .active').attr('id').replace('-tab', '');
        filterLogs(currentTab);
    });

    // Mostrar detalles del log
    $('.log-row').on('click', function() {
        const id = $(this).data('id');
        showLogDetails(id);
    });

    function showLogDetails(id) {
        $.get('/admin/logs/' + id, function(data) {
            $('#logId').text(data.id);
            $('#logDate').text(new Date(data.created_at).toLocaleString());
            $('#logUser').text(data.user);
            $('#logAction').text(data.action);
            $('#logDescription').text(data.description);
            $('#logDetailsModal').modal('show');
        });
    }

    // Aplicar filtro inicial
    filterLogs('all');
});
</script>
@endpush

@push('styles')
<style>
.log-row {
    cursor: pointer;
    transition: all 0.2s ease;
}
.log-row:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
}
.nav-tabs .nav-link {
    color: #495057;
    border: none;
    border-bottom: 2px solid transparent;
    padding: 0.75rem 1rem;
    transition: all 0.2s ease;
}
.nav-tabs .nav-link:hover {
    border-bottom-color: #007bff;
    color: #007bff;
}
.nav-tabs .nav-link.active {
    color: #007bff;
    font-weight: bold;
    border-bottom: 2px solid #007bff;
    background: none;
}
.badge {
    padding: 0.5em 0.75em;
    font-weight: 500;
    background-color: #e9ecef;
    color: #000 !important;
}
.badge-info {
    background-color: #e9ecef !important;
}
.badge-warning {
    background-color: #ffeeba !important;
}
.badge-success {
    background-color: #d4edda !important;
}
.card {
    border: none;
    border-radius: 0.5rem;
}
.card-header {
    border-radius: 0.5rem 0.5rem 0 0 !important;
}
.table th {
    border-top: none;
    font-weight: 600;
}
.pagination {
    justify-content: center;
}
.page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
}
.modal-content {
    border-radius: 0.5rem;
    border: none;
}
.modal-header {
    border-radius: 0.5rem 0.5rem 0 0;
}
</style>
@endpush 