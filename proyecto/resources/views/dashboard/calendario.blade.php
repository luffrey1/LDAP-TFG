@extends('layouts.dashboard')

@section('title', 'Calendario de Eventos')

@section('styles')
<!-- FullCalendar CSS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet" />
<style>
    /* Estilos generales del calendario */
    #calendar {
        width: 100%;
        min-height: 800px;
        margin: 0 auto;
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Estilos de la barra de herramientas */
    .fc-toolbar {
        margin-bottom: 2em !important;
        padding: 1em;
        background: #f8fafc;
        border-radius: 8px;
    }

    .fc-toolbar-title {
        font-size: 1.5rem !important;
        font-weight: 600 !important;
        color: #1a202c;
    }

    .fc-button {
        background-color: #4f46e5 !important;
        border-color: #4f46e5 !important;
        padding: 0.5em 1em !important;
        font-weight: 500 !important;
        text-transform: capitalize !important;
        border-radius: 6px !important;
        transition: all 0.2s ease !important;
    }

    .fc-button:hover {
        background-color: #3730a3 !important;
        border-color: #3730a3 !important;
        transform: translateY(-1px);
    }

    .fc-button-primary:not(:disabled).fc-button-active {
        background-color: #3730a3 !important;
        border-color: #3730a3 !important;
    }

    /* Estilos de la cuadrícula */
    .fc-view-harness {
        background: white;
        border-radius: 8px;
        overflow: hidden;
    }

    .fc-daygrid-day {
        height: 160px !important;
        border: 1px solid #e2e8f0 !important;
    }

    .fc-daygrid-day-frame {
        padding: 8px !important;
    }

    .fc-daygrid-day-top {
        justify-content: center !important;
        padding: 4px 0 !important;
    }

    .fc-daygrid-day-number {
        font-size: 1.1em !important;
        font-weight: 500 !important;
        color: #4a5568 !important;
        text-decoration: none !important;
    }

    /* Estilos de los eventos */
    .fc-event {
        cursor: pointer;
        min-height: 55px !important;
        padding: 10px 14px !important;
        border-radius: 8px !important;
        margin: 3px 4px !important;
        border: none !important;
        background-color: var(--event-color, #4f46e5) !important;
        color: white !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        transition: all 0.2s ease !important;
    }

    .fc-event:hover {
        box-shadow: 0 4px 6px rgba(0,0,0,0.1) !important;
        transform: translateY(-2px) !important;
    }

    .fc-event-title {
        font-weight: 500 !important;
        font-size: 0.95em !important;
        line-height: 1.4 !important;
    }

    .fc-event-time {
        font-size: 0.85em !important;
        opacity: 0.9 !important;
        margin-bottom: 4px !important;
    }

    /* Estilos para el botón "más eventos" */
    .fc-daygrid-more-link {
        color: #4f46e5 !important;
        font-weight: 500 !important;
        background: #f8fafc !important;
        padding: 4px 8px !important;
        border-radius: 6px !important;
        margin-top: 4px !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
    }

    /* Estilos para la vista de semana */
    .fc-timegrid-event {
        min-height: 50px !important;
        padding: 8px 12px !important;
        margin: 2px 0 !important;
    }

    /* Estilos para el encabezado de la semana */
    .fc-col-header-cell {
        padding: 10px 0 !important;
        background: #f8fafc !important;
    }

    .fc-col-header-cell-cushion {
        color: #4a5568 !important;
        font-weight: 600 !important;
        text-decoration: none !important;
    }

    /* Estilos para el día actual */
    .fc-day-today {
        background-color: #f0f9ff !important;
    }

    .fc-day-today .fc-daygrid-day-number {
        background: #4f46e5 !important;
        color: white !important;
        width: 30px !important;
        height: 30px !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0 auto !important;
    }

    /* Estilos para el modal de eventos */
    .event-modal-content {
        padding: 20px;
    }

    .form-label {
        font-weight: 500;
        color: #4a5568;
        margin-bottom: 0.5rem;
    }

    .form-control {
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        padding: 0.5rem 0.75rem;
    }

    .form-control:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Calendario de Eventos</h1>
        <button id="crearEvento" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm mr-2"></i>Crear Evento
        </button>
    </div>

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

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Eventos del Centro</h6>
        </div>
        <div class="card-body">
            <div id="calendar"></div>
        </div>
    </div>
</div>

<!-- Modal para crear eventos -->
<div class="modal fade" id="eventoModal" tabindex="-1" role="dialog" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nuevo Evento</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="closeModal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body event-modal-content">
                <form id="eventoForm" method="POST" action="{{ route('dashboard.calendario.evento') }}">
                    @csrf
                    
                    <div class="form-group">
                        <label class="form-label" for="titulo">Título del evento</label>
                        <input type="text" id="titulo" name="titulo" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="fecha_inicio">Fecha inicio</label>
                                <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="fecha_fin">Fecha fin</label>
                                <input type="datetime-local" id="fecha_fin" name="fecha_fin" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="color">Tipo de evento</label>
                                <select id="color" name="color" class="form-control">
                                    <option value="#3788d8">Reunión</option>
                                    <option value="#e74c3c">Fecha límite</option>
                                    <option value="#2ecc71">Formación</option>
                                    <option value="#9b59b6">Vacaciones</option>
                                    <option value="#f39c12">Claustro</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label d-block">&nbsp;</label>
                                <div class="custom-control custom-checkbox mt-2">
                                    <input type="checkbox" id="todo_el_dia" name="todo_el_dia" class="custom-control-input" value="1">
                                    <label class="custom-control-label" for="todo_el_dia">Todo el día</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Verificar que FullCalendar está disponible
    if (typeof FullCalendar === 'undefined') {
        console.error('FullCalendar no se ha cargado correctamente');
        alert('Error: No se pudo cargar el calendario. Por favor, recarga la página.');
        return;
    }
    
    // Inicializar el calendario
    var calendarEl = document.getElementById('calendar');
    if (!calendarEl) {
        console.error('No se encontró el elemento calendar');
        return;
    }
    
    // Configuración del calendario
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        locale: 'es',
        events: {!! json_encode($eventosFormateados ?? []) !!},
        height: 800,
        contentHeight: 800,
        aspectRatio: 1.5,
        editable: false,
        selectable: true,
        dayMaxEvents: true,
        dateClick: function(info) {
            // Abrir el modal para crear un nuevo evento
            $('#modalTitle').text('Nuevo Evento');
            $('#eventoForm')[0].reset();
            
            // Establecer la fecha seleccionada
            var fechaSeleccionada = info.dateStr;
            $('#fecha_inicio').val(fechaSeleccionada + 'T00:00');
            $('#fecha_fin').val(fechaSeleccionada + 'T23:59');
            
            // Mostrar el modal
            $('#eventoModal').modal('show');
        }
    });
    
    calendar.render();

    // Manejar el envío del formulario
    $('#eventoForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        $.ajax({
            url: '{{ route("dashboard.calendario.evento") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#eventoModal').modal('hide');
                showNotification('Evento guardado correctamente', 'success');
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            },
            error: function(xhr) {
                var errorMessage = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error desconocido';
                showNotification('Error al guardar el evento: ' + errorMessage, 'error');
            }
        });
    });
});
</script>
@endpush 