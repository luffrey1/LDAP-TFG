@extends('layouts.dashboard')

@section('title', 'Bandeja de entrada')

@section('content')
<div class="container-fluid">
    <!-- Encabezado con botones de acción -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Bandeja de entrada</h1>
        <div>
            <a href="{{ route('mensajes.redactar') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="fas fa-pen fa-sm text-white-50 mr-1"></i> Redactar mensaje
            </a>
            <button class="btn btn-danger btn-sm shadow-sm ml-2" id="btn-eliminar-seleccionados" disabled>
                <i class="fas fa-trash fa-sm text-white-50 mr-1"></i> Eliminar seleccionados
            </button>
        </div>
    </div>

    <!-- Alertas -->
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

    <!-- Tarjeta principal -->
    <div class="card shadow mb-4">
        <!-- Filtros y buscador -->
        <div class="card-header py-3">
            <div class="row align-items-center">
                <div class="col-md-6 mb-2 mb-md-0">
                    <form action="{{ route('mensajes.index') }}" method="GET" class="form-inline">
                        <div class="input-group">
                            <input type="text" class="form-control" name="buscar" placeholder="Buscar mensajes..." value="{{ request('buscar') }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-md-end">
                        <div class="dropdown mr-2">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-filter mr-1"></i> Filtrar
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                                <a class="dropdown-item {{ request('filtro') == 'todos' || !request('filtro') ? 'active' : '' }}" href="{{ route('mensajes.index', ['filtro' => 'todos']) }}">Todos</a>
                                <a class="dropdown-item {{ request('filtro') == 'leidos' ? 'active' : '' }}" href="{{ route('mensajes.index', ['filtro' => 'leidos']) }}">Leídos</a>
                                <a class="dropdown-item {{ request('filtro') == 'no-leidos' ? 'active' : '' }}" href="{{ route('mensajes.index', ['filtro' => 'no-leidos']) }}">No leídos</a>
                                <a class="dropdown-item {{ request('filtro') == 'con-adjuntos' ? 'active' : '' }}" href="{{ route('mensajes.index', ['filtro' => 'con-adjuntos']) }}">Con adjuntos</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item {{ request('filtro') == 'favoritos' ? 'active' : '' }}" href="{{ route('mensajes.index', ['filtro' => 'favoritos']) }}">Favoritos</a>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownSortButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-sort mr-1"></i> Ordenar
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownSortButton">
                                <a class="dropdown-item {{ request('orden') == 'reciente' || !request('orden') ? 'active' : '' }}" href="{{ route('mensajes.index', ['orden' => 'reciente']) }}">Más recientes primero</a>
                                <a class="dropdown-item {{ request('orden') == 'antiguo' ? 'active' : '' }}" href="{{ route('mensajes.index', ['orden' => 'antiguo']) }}">Más antiguos primero</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item {{ request('orden') == 'remitente' ? 'active' : '' }}" href="{{ route('mensajes.index', ['orden' => 'remitente']) }}">Por remitente</a>
                                <a class="dropdown-item {{ request('orden') == 'asunto' ? 'active' : '' }}" href="{{ route('mensajes.index', ['orden' => 'asunto']) }}">Por asunto</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lista de mensajes -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <form id="form-mensajes" action="{{ route('mensajes.accion-masiva') }}" method="POST">
                    @csrf
                    <input type="hidden" name="accion" id="accion-masiva" value="">
                    
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-3 py-2">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="selectAll">
                                        <label class="custom-control-label" for="selectAll"></label>
                                    </div>
                                </th>
                                <th class="py-2" width="30"></th>
                                <th class="py-2" width="30"></th>
                                <th class="py-2">Remitente</th>
                                <th class="py-2">Asunto</th>
                                <th class="py-2" width="40"></th>
                                <th class="py-2">Fecha</th>
                                <th class="py-2 text-center" width="80">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mensajes as $mensaje)
                                <tr class="{{ !$mensaje['leido'] ? 'font-weight-bold bg-light-hover' : '' }}" data-id="{{ $mensaje['id'] }}">
                                    <td class="px-3 py-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input mensaje-check" id="mensaje{{ $mensaje['id'] }}" name="mensajes[]" value="{{ $mensaje['id'] }}">
                                            <label class="custom-control-label" for="mensaje{{ $mensaje['id'] }}"></label>
                                        </div>
                                    </td>
                                    <td class="text-center py-2">
                                        <a href="#" class="toggle-favorito" data-id="{{ $mensaje['id'] }}">
                                            <i class="fas fa-star {{ $mensaje['favorito'] ? 'text-warning' : 'text-muted' }}"></i>
                                        </a>
                                    </td>
                                    <td class="text-center py-2">
                                        @if(!$mensaje['leido'])
                                            <span class="badge badge-primary badge-dot"></span>
                                        @endif
                                    </td>
                                    <td class="py-2">
                                        <a href="{{ route('mensajes.ver', $mensaje['id']) }}" class="text-decoration-none text-gray-800">
                                            {{ $mensaje['remitente']['nombre'] }}
                                        </a>
                                    </td>
                                    <td class="py-2">
                                        <a href="{{ route('mensajes.ver', $mensaje['id']) }}" class="text-decoration-none text-gray-800">
                                            {{ $mensaje['asunto'] }}
                                            <small class="text-muted d-block d-md-none">{{ \Carbon\Carbon::parse($mensaje['fecha'])->format('d/m/Y H:i') }}</small>
                                        </a>
                                    </td>
                                    <td class="text-center py-2">
                                        @if($mensaje['tiene_adjuntos'])
                                            <i class="fas fa-paperclip text-muted"></i>
                                        @endif
                                    </td>
                                    <td class="py-2 d-none d-md-table-cell">
                                        <span class="text-muted">{{ \Carbon\Carbon::parse($mensaje['fecha'])->format('d/m/Y H:i') }}</span>
                                    </td>
                                    <td class="text-center py-2">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-icon" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v text-gray-500"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item" href="{{ route('mensajes.ver', $mensaje['id']) }}">
                                                    <i class="fas fa-eye fa-sm fa-fw text-gray-600 mr-2"></i>
                                                    Ver mensaje
                                                </a>
                                                <a class="dropdown-item" href="{{ route('mensajes.responder', $mensaje['id']) }}">
                                                    <i class="fas fa-reply fa-sm fa-fw text-gray-600 mr-2"></i>
                                                    Responder
                                                </a>
                                                <a class="dropdown-item" href="{{ route('mensajes.reenviar', $mensaje['id']) }}">
                                                    <i class="fas fa-share fa-sm fa-fw text-gray-600 mr-2"></i>
                                                    Reenviar
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                @if(!$mensaje['leido'])
                                                    <a class="dropdown-item marcar-leido" href="#" data-id="{{ $mensaje['id'] }}">
                                                        <i class="fas fa-check-double fa-sm fa-fw text-gray-600 mr-2"></i>
                                                        Marcar como leído
                                                    </a>
                                                @else
                                                    <a class="dropdown-item marcar-no-leido" href="#" data-id="{{ $mensaje['id'] }}">
                                                        <i class="fas fa-check fa-sm fa-fw text-gray-600 mr-2"></i>
                                                        Marcar como no leído
                                                    </a>
                                                @endif
                                                <a class="dropdown-item toggle-favorito-menu" href="#" data-id="{{ $mensaje['id'] }}">
                                                    <i class="fas fa-star fa-sm fa-fw {{ $mensaje['favorito'] ? 'text-warning' : 'text-gray-600' }} mr-2"></i>
                                                    {{ $mensaje['favorito'] ? 'Quitar de favoritos' : 'Añadir a favoritos' }}
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item text-danger eliminar-mensaje" href="#" data-id="{{ $mensaje['id'] }}">
                                                    <i class="fas fa-trash fa-sm fa-fw text-danger mr-2"></i>
                                                    Eliminar
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="py-4">
                                            <i class="fas fa-inbox fa-3x text-gray-300 mb-3"></i>
                                            <p class="text-gray-500 mb-0">No se encontraron mensajes</p>
                                            @if(request('buscar') || request('filtro'))
                                                <a href="{{ route('mensajes.index') }}" class="btn btn-sm btn-outline-primary mt-2">
                                                    <i class="fas fa-undo-alt mr-1"></i> Limpiar filtros
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
        
        <!-- Paginación -->
        @if($mensajes->count() > 0)
            <div class="card-footer bg-white border-top-0 py-3">
                <div class="row align-items-center">
                    <div class="col-md-6 small text-muted">
                        Mostrando {{ $mensajes->firstItem() ?? 0 }} a {{ $mensajes->lastItem() ?? 0 }} de {{ $mensajes->total() }} mensajes
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-md-end">
                            {{ $mensajes->links() }}
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Modal de confirmación de eliminación -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar eliminación</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea eliminar los mensajes seleccionados? Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirm-delete">Eliminar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    /* Estilo para el punto de no leído */
    .badge-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }
    
    /* Estilo para hover en la tabla */
    .bg-light-hover {
        background-color: rgba(248, 249, 252, 0.5);
    }
    
    /* Estilo para filas al pasar el mouse */
    .table-hover tbody tr:hover {
        background-color: rgba(224, 228, 246, 0.15);
    }
    
    /* Estilo para los botones de acción */
    .btn-icon {
        background: transparent;
        border: none;
        padding: 0.25rem 0.5rem;
    }
    
    .btn-icon:hover {
        background-color: rgba(78, 115, 223, 0.1);
        border-radius: 0.25rem;
    }
    
    /* Estilo para la paginación */
    .pagination {
        margin-bottom: 0;
    }
</style>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Seleccionar/deseleccionar todos los mensajes
        $('#selectAll').change(function() {
            $('.mensaje-check').prop('checked', $(this).prop('checked'));
            toggleDeleteButton();
        });
        
        // Habilitar/deshabilitar botón de eliminar según selección
        $('.mensaje-check').change(function() {
            toggleDeleteButton();
            
            // Actualizar checkbox "Seleccionar todos"
            let allChecked = $('.mensaje-check:checked').length === $('.mensaje-check').length;
            $('#selectAll').prop('checked', allChecked && $('.mensaje-check').length > 0);
        });
        
        // Función para habilitar/deshabilitar botón de eliminar
        function toggleDeleteButton() {
            const numChecked = $('.mensaje-check:checked').length;
            $('#btn-eliminar-seleccionados').prop('disabled', numChecked === 0);
        }
        
        // Marcar como leído/no leído
        $('.marcar-leido, .marcar-no-leido').click(function(e) {
            e.preventDefault();
            const mensajeId = $(this).data('id');
            const accion = $(this).hasClass('marcar-leido') ? 'marcar-leido' : 'marcar-no-leido';
            
            $.ajax({
                url: '/mensajes/' + mensajeId + '/' + accion,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    // Actualizar la interfaz sin recargar
                    if (accion === 'marcar-leido') {
                        $('tr[data-id="' + mensajeId + '"]').removeClass('font-weight-bold bg-light-hover');
                        $('tr[data-id="' + mensajeId + '"] .badge-dot').remove();
                        $('tr[data-id="' + mensajeId + '"] .marcar-leido')
                            .removeClass('marcar-leido')
                            .addClass('marcar-no-leido')
                            .html('<i class="fas fa-check fa-sm fa-fw text-gray-600 mr-2"></i> Marcar como no leído');
                    } else {
                        $('tr[data-id="' + mensajeId + '"]').addClass('font-weight-bold bg-light-hover');
                        $('tr[data-id="' + mensajeId + '"] td:nth-child(3)').html('<span class="badge badge-primary badge-dot"></span>');
                        $('tr[data-id="' + mensajeId + '"] .marcar-no-leido')
                            .removeClass('marcar-no-leido')
                            .addClass('marcar-leido')
                            .html('<i class="fas fa-check-double fa-sm fa-fw text-gray-600 mr-2"></i> Marcar como leído');
                    }
                },
                error: function(error) {
                    console.error('Error:', error);
                    alert('No se pudo actualizar el estado del mensaje.');
                }
            });
        });
        
        // Toggle favorito
        $('.toggle-favorito, .toggle-favorito-menu').click(function(e) {
            e.preventDefault();
            const mensajeId = $(this).data('id');
            
            $.ajax({
                url: '/mensajes/' + mensajeId + '/toggle-favorito',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    const isFavorito = response.favorito;
                    
                    // Actualizar icono de estrella
                    $('tr[data-id="' + mensajeId + '"] .toggle-favorito i')
                        .toggleClass('text-warning', isFavorito)
                        .toggleClass('text-muted', !isFavorito);
                    
                    // Actualizar opción del menú
                    const $menuItem = $('tr[data-id="' + mensajeId + '"] .toggle-favorito-menu');
                    $menuItem.find('i').toggleClass('text-warning', isFavorito).toggleClass('text-gray-600', !isFavorito);
                    $menuItem.text(isFavorito ? ' Quitar de favoritos' : ' Añadir a favoritos');
                    $menuItem.prepend($menuItem.find('i').clone());
                },
                error: function(error) {
                    console.error('Error:', error);
                    alert('No se pudo actualizar el estado de favorito.');
                }
            });
        });
        
        // Eliminar mensaje individual
        $('.eliminar-mensaje').click(function(e) {
            e.preventDefault();
            const mensajeId = $(this).data('id');
            
            if (confirm('¿Está seguro de que desea eliminar este mensaje? Esta acción no se puede deshacer.')) {
                $.ajax({
                    url: '/mensajes/' + mensajeId,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('tr[data-id="' + mensajeId + '"]').fadeOut(300, function() {
                            $(this).remove();
                            
                            // Si no quedan mensajes, mostrar mensaje vacío
                            if ($('tbody tr').length === 0) {
                                $('tbody').html(`
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="py-4">
                                                <i class="fas fa-inbox fa-3x text-gray-300 mb-3"></i>
                                                <p class="text-gray-500 mb-0">No se encontraron mensajes</p>
                                                @if(request('buscar') || request('filtro'))
                                                    <a href="{{ route('mensajes.index') }}" class="btn btn-sm btn-outline-primary mt-2">
                                                        <i class="fas fa-undo-alt mr-1"></i> Limpiar filtros
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                `);
                            }
                        });
                    },
                    error: function(error) {
                        console.error('Error:', error);
                        alert('No se pudo eliminar el mensaje.');
                    }
                });
            }
        });
        
        // Eliminar mensajes seleccionados
        $('#btn-eliminar-seleccionados').click(function() {
            if ($('.mensaje-check:checked').length > 0) {
                $('#deleteModal').modal('show');
            }
        });
        
        // Confirmar eliminación masiva
        $('#confirm-delete').click(function() {
            $('#accion-masiva').val('eliminar');
            $('#form-mensajes').submit();
            $('#deleteModal').modal('hide');
        });
        
        // Doble clic en fila para abrir mensaje
        $('tbody tr').dblclick(function() {
            const mensajeId = $(this).data('id');
            window.location.href = '/mensajes/' + mensajeId;
        });
    });
</script>
@endsection 