@extends('layouts.dashboard')

@section('title', 'Calendario de Eventos')

@section('styles')
<!-- FullCalendar CSS -->
<link href="https://unpkg.com/fullcalendar@6.1.10/main.min.css" rel="stylesheet" />
<link href="https://unpkg.com/@fullcalendar/daygrid@6.1.10/main.min.css" rel="stylesheet" />
<link href="https://unpkg.com/@fullcalendar/timegrid@6.1.10/main.min.css" rel="stylesheet" />
<style>
    /* Hacer las celdas del calendario más altas */
    .fc-daygrid-day {
        height: 160px !important;
    }

    /* Estilo base para todos los eventos */
    .fc-event {
        cursor: pointer;
        min-height: 55px !important;
        padding: 10px 14px !important;
        border-radius: 8px !important;
        margin: 3px 4px !important;
        border: none !important;
        background-color: #1a365d !important; /* Azul más oscuro */
        color: white !important;
        transition: all 0.3s ease !important;
    }

    /* Eventos en la vista de mes */
    .fc-daygrid-event {
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        padding: 10px 14px !important;
        margin: 4px !important;
        line-height: 1.4 !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
    }

    /* Eventos que duran múltiples días */
    .fc-daygrid-block-event {
        min-height: 60px !important;
        padding: 12px 14px !important;
    }

    /* Título del evento */
    .fc-event-title {
        font-weight: 500 !important;
        font-size: 1em !important;
        line-height: 1.5 !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    /* Hora del evento */
    .fc-event-time {
        font-size: 0.9em !important;
        opacity: 0.9 !important;
        margin-bottom: 6px !important;
    }

    /* Efecto hover en eventos */
    .fc-event:hover {
        background-color: #2c5282 !important; /* Azul más claro al hover */
        box-shadow: 0 4px 8px rgba(0,0,0,0.3) !important;
        transform: translateY(-2px) !important;
    }

    /* Colores específicos para tipos de eventos */
    .fc-event[style*="background-color: #3788d8"] {
        background: linear-gradient(45deg, #1a365d, #2c5282) !important;
    }
    .fc-event[style*="background-color: #e74c3c"] {
        background: linear-gradient(45deg, #9b2c2c, #c53030) !important;
    }
    .fc-event[style*="background-color: #2ecc71"] {
        background: linear-gradient(45deg, #22543d, #276749) !important;
    }
    .fc-event[style*="background-color: #9b59b6"] {
        background: linear-gradient(45deg, #553c9a, #6b46c1) !important;
    }
    .fc-event[style*="background-color: #f39c12"] {
        background: linear-gradient(45deg, #744210, #975a16) !important;
    }

    /* Más espacio para los eventos en la vista de semana */
    .fc-timegrid-event {
        min-height: 50px !important;
        padding: 8px 12px !important;
        margin: 2px 0 !important;
    }

    /* Ajustar el contenedor de eventos para mostrar más */
    .fc-daygrid-day-events {
        margin-top: 6px !important;
        padding: 3px !important;
        min-height: 120px !important;
    }

    /* Estilo para el botón "más eventos" */
    .fc-daygrid-more-link {
        color: #4f46e5 !important;
        font-weight: 500 !important;
        background: #f8fafc !important;
        padding: 4px 8px !important;
        border-radius: 6px !important;
        margin-top: 6px !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
    }

    /* Ajustar el número del día para dar más espacio a los eventos */
    .fc-daygrid-day-top {
        margin-bottom: 4px !important;
    }

    /* Resto de estilos sin cambios */
    .fc-toolbar-title {
        font-size: 1.5rem !important;
        font-weight: bold;
    }

    .fc-button {
        background-color: #4f46e5 !important;
        border-color: #4f46e5 !important;
    }

    .fc-button:hover {
        background-color: #3730a3 !important;
        border-color: #3730a3 !important;
    }

    .event-tooltip {
        background-color: white;
        border: none;
        border-radius: 8px;
        padding: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        max-width: 300px;
    }

    .event-type-indicator {
        display: inline-flex;
        align-items: center;
        margin-right: 15px;
        margin-bottom: 10px;
    }

    .event-type-indicator .color-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 8px;
    }

    #calendar {
        width: 100%;
        min-height: 800px;
        margin: 0 auto;
        background: white;
        padding: 20px;
        border-radius: 8px;
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
                        <label class="form-label text-black" for="titulo">Título del evento</label>
                        <input type="text" id="titulo" name="titulo" class="form-control text-white" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label text-black" for="descripcion">Descripción</label>
                            <textarea id="descripcion" name="descripcion" class="form-control text-white" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label text-black" for="fecha_inicio">Fecha inicio</label>
                                <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" class="form-control text-white" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label text-black" for="fecha_fin">Fecha fin</label>
                                <input type="datetime-local" id="fecha_fin" name="fecha_fin" class="form-control text-white" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label text-black" for="color">Tipo de evento</label>
                                <select id="color" name="color" class="form-control text-white">
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
                                    <label class="custom-control-label text-black" for="todo_el_dia">Todo el día</label>
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
        eventClick: function(info) {
            // Mostrar información del evento
            var modal = new bootstrap.Modal(document.getElementById('eventoModal'));
            $('#modalTitle').text('Detalles del Evento');
            
            // Rellenar el formulario con los datos del evento
            $('#titulo').val(info.event.title);
            $('#descripcion').val(info.event.extendedProps.description || '');
            $('#fecha_inicio').val(info.event.start.toISOString().slice(0, 16));
            $('#fecha_fin').val(info.event.end.toISOString().slice(0, 16));
            $('#color').val(info.event.backgroundColor);
            $('#todo_el_dia').prop('checked', info.event.allDay);
            
            // Deshabilitar los campos para solo lectura
            $('#eventoForm input, #eventoForm textarea, #eventoForm select').prop('disabled', true);
            $('.modal-footer').html('<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>');
            
            modal.show();
        },
        dateClick: function(info) {
            abrirModalCrearEvento(info.dateStr);
        }
    });
    
    calendar.render();

    // Función para abrir el modal de creación de evento
    function abrirModalCrearEvento(fechaSeleccionada) {
        $('#modalTitle').text('Nuevo Evento');
        $('#eventoForm')[0].reset();
        
        // Establecer la fecha seleccionada
        if (fechaSeleccionada) {
            $('#fecha_inicio').val(fechaSeleccionada + 'T00:00');
            $('#fecha_fin').val(fechaSeleccionada + 'T23:59');
        }
        
        // Habilitar los campos
        $('#eventoForm input, #eventoForm textarea, #eventoForm select').prop('disabled', false);
        $('.modal-footer').html(`
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar</button>
        `);
        
        // Mostrar el modal
        $('#eventoModal').modal('show');
    }

    // Manejar el clic en el botón de crear evento
    document.getElementById('crearEvento').addEventListener('click', function() {
        abrirModalCrearEvento();
    });

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