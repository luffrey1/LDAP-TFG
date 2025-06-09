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
    // Inicializar DataTables con configuración básica
    var table = $('#logsTable').DataTable({
        "paging": false,
        "ordering": true,
        "info": false,
        "searching": false,
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            }
        }
    });

    // Función mejorada para determinar el tipo de log
    function getLogType(description) {
        if (!description) {
            console.log('Descripción vacía o nula');
            return 'all';
        }
        
        description = description.toLowerCase();
        console.log('Analizando descripción:', description);
        
        // Patrones de detección mejorados
        const patterns = {
            users: [
                'usuario ldap creado',
                'usuario ldap actualizado',
                'usuario ldap eliminado',
                'usuario actualizado',
                'usuario creado',
                'usuario eliminado',
                'nuevo usuario',
                'modificación de usuario'
            ],
            groups: [
                'grupo ldap creado',
                'grupo ldap actualizado',
                'grupo ldap eliminado',
                'grupo actualizado',
                'grupo creado',
                'grupo eliminado',
                'nuevo grupo',
                'modificación de grupo',
                'miembro añadido al grupo',
                'miembro eliminado del grupo',
                'grupo modificado'
            ],
            access: [
                'intento de acceso',
                'acceso exitoso',
                'acceso fallido',
                'desde',
                'ip:',
                'user agent',
                'login',
                'logout',
                'sesión'
            ]
        };

        // Verificar cada patrón
        for (const [type, patternList] of Object.entries(patterns)) {
            const matches = patternList.filter(pattern => description.includes(pattern));
            if (matches.length > 0) {
                console.log('Tipo detectado:', type, 'para descripción:', description);
                console.log('Patrones coincidentes:', matches);
                return type;
            }
        }

        // Si no coincide con ningún patrón, intentar determinar por contexto
        if (description.includes('grupo') || description.includes('group')) {
            console.log('Tipo detectado por contexto: groups');
            return 'groups';
        }

        console.log('No se detectó tipo específico, usando "all"');
        return 'all';
    }

    // Función para asignar tipos a las filas
    function assignTypesToRows() {
        console.log('Iniciando asignación de tipos a filas...');
        let typeCount = { users: 0, groups: 0, access: 0, all: 0 };
        let rowDetails = [];

        table.rows().every(function() {
            const data = this.data();
            console.log('Datos de la fila:', data);
            
            const description = data[2]; // Descripción en la tercera columna
            console.log('Descripción encontrada:', description);
            
            const type = getLogType(description);
            
            // Asignar tipo a la fila
            const $row = $(this.node());
            $row.attr('data-type', type);
            typeCount[type]++;

            const rowDetail = {
                description: description,
                type: type,
                rowIndex: this.index(),
                dataType: $row.attr('data-type')
            };
            rowDetails.push(rowDetail);

            console.log('Fila asignada:', rowDetail);
        });

        console.log('Conteo de tipos:', typeCount);
        console.log('Detalles de todas las filas:', rowDetails);
        return typeCount;
    }

    // Función mejorada para filtrar logs
    function filterLogs(type) {
        console.log('Iniciando filtrado para tipo:', type);
        
        // Obtener todas las filas
        const $rows = $('#logsTable tbody tr');
        let visibleCount = 0;
        let rowDetails = [];

        $rows.each(function(index) {
            const $row = $(this);
            const rowType = $row.attr('data-type');
            const description = $row.find('td:eq(2)').text();
            
            const rowDetail = {
                index: index,
                tipo: rowType,
                esperado: type,
                descripcion: description,
                visible: type === 'all' || rowType === type
            };
            rowDetails.push(rowDetail);
            
            console.log(`Fila ${index}:`, rowDetail);
            
            // Aplicar visibilidad
            $row.toggle(rowDetail.visible);
            if (rowDetail.visible) visibleCount++;
        });

        console.log('Filtrado completado:', {
            tipo: type,
            filasVisibles: visibleCount,
            totalFilas: $rows.length,
            filasPorTipo: {
                users: $rows.filter('[data-type="users"]').length,
                groups: $rows.filter('[data-type="groups"]').length,
                access: $rows.filter('[data-type="access"]').length,
                all: $rows.filter('[data-type="all"]').length
            },
            detalles: rowDetails
        });

        // Actualizar contador en la interfaz
        updateVisibleCount(visibleCount);
    }

    // Función para actualizar el contador de filas visibles
    function updateVisibleCount(count) {
        const $counter = $('#visibleCount');
        if ($counter.length === 0) {
            $('.card-header').append('<span id="visibleCount" class="ml-3 badge badge-info">Filas visibles: ' + count + '</span>');
        } else {
            $counter.text('Filas visibles: ' + count);
        }
    }

    // Asignar tipos iniciales
    const typeCount = assignTypesToRows();
    console.log('Tipos asignados inicialmente:', typeCount);

    // Manejar cambios de pestaña
    $('#logTabs a').on('click', function(e) {
        e.preventDefault();
        $(this).tab('show');
        
        const type = $(this).attr('id').replace('-tab', '');
        console.log('Pestaña clickeada:', type);
        filterLogs(type);
    });

    // Búsqueda de usuario mejorada
    $('#userSearch').on('keyup', function() {
        const searchText = $(this).val().toLowerCase();
        console.log('Buscando:', searchText);

        const $rows = $('#logsTable tbody tr');
        let visibleCount = 0;

        $rows.each(function() {
            const $row = $(this);
            const userText = $row.find('td:first').text().toLowerCase();
            const type = $row.attr('data-type');
            const currentTab = $('#logTabs .active').attr('id').replace('-tab', '');
            
            const matchesSearch = userText.includes(searchText);
            const matchesType = currentTab === 'all' || type === currentTab;
            
            $row.toggle(matchesSearch && matchesType);
            if (matchesSearch && matchesType) visibleCount++;
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
    $('#logsTable tbody').on('click', 'tr', function() {
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