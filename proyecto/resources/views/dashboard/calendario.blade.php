@extends('layouts.dashboard')

@section('title', 'Calendario de Eventos')

@section('styles')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css' rel='stylesheet' />
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
        background-color: var(--event-color, #4f46e5) !important;
        color: white !important;
    }

    /* Eventos en la vista de mes */
    .fc-daygrid-event {
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        padding: 10px 14px !important;
        margin: 4px !important;
        line-height: 1.4 !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
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
        box-shadow: 0 6px 12px rgba(0,0,0,0.2) !important;
        transform: translateY(-2px) !important;
        transition: all 0.2s ease !important;
    }

    /* Colores específicos para tipos de eventos con gradientes más pronunciados */
    .fc-event[style*="background-color: #3788d8"] {
        background: linear-gradient(45deg, #3788d8, #5ca3e6) !important;
    }
    .fc-event[style*="background-color: #e74c3c"] {
        background: linear-gradient(45deg, #e74c3c, #f16b5c) !important;
    }
    .fc-event[style*="background-color: #2ecc71"] {
        background: linear-gradient(45deg, #2ecc71, #54d98c) !important;
    }
    .fc-event[style*="background-color: #9b59b6"] {
        background: linear-gradient(45deg, #9b59b6, #b07cc6) !important;
    }
    .fc-event[style*="background-color: #f39c12"] {
        background: linear-gradient(45deg, #f39c12, #f5b043) !important;
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
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Encabezado de página -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Calendario de Eventos</h1>
        <button id="crearEvento" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm mr-2"></i>Crear Evento
        </button>
    </div>

    <!-- Mensajes de éxito y error -->
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

    <!-- Tarjeta de Calendario -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Eventos del Centro</h6>
        </div>
        
        <div class="card-body">
            <!-- Leyenda del calendario -->
            <div class="event-legend mb-4">
                <div class="event-type-indicator">
                    <span class="color-dot" style="background-color: #3788d8;"></span>
                    <span>Reuniones</span>
                </div>
                <div class="event-type-indicator">
                    <span class="color-dot" style="background-color: #e74c3c;"></span>
                    <span>Fechas límite</span>
                </div>
                <div class="event-type-indicator">
                    <span class="color-dot" style="background-color: #2ecc71;"></span>
                    <span>Formación</span>
                </div>
                <div class="event-type-indicator">
                    <span class="color-dot" style="background-color: #9b59b6;"></span>
                    <span>Periodos vacacionales</span>
                </div>
                <div class="event-type-indicator">
                    <span class="color-dot" style="background-color: #f39c12;"></span>
                    <span>Claustros</span>
                </div>
            </div>
            
            <!-- Calendario -->
            <div id='calendar'></div>
        </div>
    </div>
</div>

<!-- Modal para crear/editar eventos -->
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
                    <input type="hidden" id="evento_id" name="id">
                    <input type="hidden" id="method" name="_method" value="POST">
                    
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
                        <button type="button" id="deleteButton" class="btn btn-danger" onclick="eliminarEvento()">Eliminar</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
                
                <!-- Formulario separado para eliminar eventos -->
                <form id="deleteForm" method="POST">
                    @csrf
                    <input type="hidden" name="_method" value="DELETE">
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/locales/es.js'></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        try {
            // Datos de eventos desde el controlador
            const eventos = @json($eventosFormateados ?? []);
            
            var calendarEl = document.getElementById('calendar');
            
            if (!calendarEl) {
                console.error("Elemento calendar no encontrado");
                return;
            }
            
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                locale: 'es',
                events: eventos,
                editable: false, // No permitimos que todos arrastren eventos
                selectable: true,
                dayMaxEvents: true,
                eventClick: function(info) {
                    // Solo permitimos editar si el evento es editable para este usuario
                    if (info.event.extendedProps.editable) {
                        openEventModal(info.event);
                    } else {
                        // Mostrar detalles del evento sin opción a editar
                        showEventDetails(info.event);
                    }
                },
                select: function(info) {
                    openNewEventModal(info);
                },
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                },
                eventDidMount: function(info) {
                    const tooltip = document.createElement('div');
                    tooltip.classList.add('event-tooltip');
                    tooltip.innerHTML = `
                        <strong>${info.event.title}</strong>
                        ${info.event.extendedProps.description ? `<p>${info.event.extendedProps.description}</p>` : ''}
                        <p class="text-muted small">Creado por: ${info.event.extendedProps.creador}</p>
                    `;
                    
                    const eventEl = info.el;
                    eventEl.addEventListener('mouseover', function() {
                        document.body.appendChild(tooltip);
                        const rect = eventEl.getBoundingClientRect();
                        tooltip.style.position = 'fixed';
                        tooltip.style.top = rect.bottom + 'px';
                        tooltip.style.left = rect.left + 'px';
                        tooltip.style.zIndex = 10000;
                    });
                    
                    eventEl.addEventListener('mouseout', function() {
                        if (document.body.contains(tooltip)) {
                            document.body.removeChild(tooltip);
                        }
                    });
                }
            });
            
            calendar.render();
            console.log("Calendario renderizado correctamente");
        
            // Modal management
            const modal = $('#eventoModal');
            const closeModal = document.getElementById('closeModal');
            const form = document.getElementById('eventoForm');
            const crearEventoBtn = document.getElementById('crearEvento');
            const deleteButton = document.getElementById('deleteButton');
            const todoElDiaCheck = document.getElementById('todo_el_dia');
            
            if (todoElDiaCheck) {
                todoElDiaCheck.addEventListener('change', function() {
                    const fechaInicioInput = document.getElementById('fecha_inicio');
                    const fechaFinInput = document.getElementById('fecha_fin');
                    
                    if (this.checked) {
                        // Guardar la hora actual antes de cambiar a solo fecha
                        const inicioDateTime = fechaInicioInput.value;
                        const finDateTime = fechaFinInput.value;
                        
                        // Cambiar a tipo date y mantener solo la fecha
                        fechaInicioInput.type = 'date';
                        fechaFinInput.type = 'date';
                        
                        if (inicioDateTime) {
                            fechaInicioInput.value = inicioDateTime.split('T')[0];
                        }
                        if (finDateTime) {
                            fechaFinInput.value = finDateTime.split('T')[0];
                        }
                    } else {
                        // Guardar las fechas actuales
                        const inicioDate = fechaInicioInput.value;
                        const finDate = fechaFinInput.value;
                        
                        // Cambiar a datetime-local
                        fechaInicioInput.type = 'datetime-local';
                        fechaFinInput.type = 'datetime-local';
                        
                        // Restaurar las fechas y agregar hora por defecto
                        if (inicioDate) {
                            fechaInicioInput.value = `${inicioDate}T00:00`;
                        }
                        if (finDate) {
                            fechaFinInput.value = `${finDate}T23:59`;
                        }
                    }
                });
            }
            
            // Función para mostrar detalles del evento sin edición
            function showEventDetails(event) {
                // Crear un modal de solo lectura
                const detailsHtml = `
                <div class="modal fade" id="eventDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header" style="background-color: ${event.backgroundColor}; color: white;">
                                <h5 class="modal-title">${event.title}</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="event-info">
                                    <p><strong>Creado por:</strong> ${event.extendedProps.creador}</p>
                                    <p><strong>Fecha de inicio:</strong> ${event.start ? new Date(event.start).toLocaleString('es-ES') : 'No definida'}</p>
                                    <p><strong>Fecha de fin:</strong> ${event.end ? new Date(event.end).toLocaleString('es-ES') : 'No definida'}</p>
                                    ${event.extendedProps.description ? `<p><strong>Descripción:</strong><br>${event.extendedProps.description}</p>` : ''}
                                    <p><strong>Tipo de evento:</strong> ${getTipoEvento(event.backgroundColor)}</p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>`;
                
                // Eliminar modal anterior si existe
                if (document.getElementById('eventDetailsModal')) {
                    document.getElementById('eventDetailsModal').remove();
                }
                
                // Agregar al DOM y mostrar
                document.body.insertAdjacentHTML('beforeend', detailsHtml);
                $('#eventDetailsModal').modal('show');
                
                // Eliminar del DOM al cerrar
                $('#eventDetailsModal').on('hidden.bs.modal', function() {
                    document.getElementById('eventDetailsModal').remove();
                });
            }
            
            // Obtener tipo de evento según el color
            function getTipoEvento(color) {
                const tiposEvento = {
                    '#3788d8': 'Reunión',
                    '#e74c3c': 'Fecha límite',
                    '#2ecc71': 'Formación',
                    '#9b59b6': 'Vacaciones',
                    '#f39c12': 'Claustro'
                };
                return tiposEvento[color] || 'Otro';
            }
            
            function openNewEventModal(info) {
                document.getElementById('modalTitle').textContent = 'Nuevo Evento';
                form.reset();
                form.action = "{{ route('dashboard.calendario.evento') }}";
                document.getElementById('method').value = 'POST';
                
                // Set default dates
                const startDate = info.startStr;
                const endDate = info.endStr;
                
                if (info.allDay) {
                    todoElDiaCheck.checked = true;
                    document.getElementById('fecha_inicio').type = 'date';
                    document.getElementById('fecha_fin').type = 'date';
                    document.getElementById('fecha_inicio').value = startDate;
                    document.getElementById('fecha_fin').value = endDate;
                } else {
                    todoElDiaCheck.checked = false;
                    document.getElementById('fecha_inicio').value = formatDateForInput(new Date(startDate));
                    document.getElementById('fecha_fin').value = formatDateForInput(new Date(endDate));
                }
                
                deleteButton.style.display = 'none';
                modal.modal('show');
            }
            
            function openEventModal(event) {
                document.getElementById('modalTitle').textContent = 'Editar Evento';
                form.reset();
                form.action = "{{ route('dashboard.calendario.evento.update', ['id' => '__id__']) }}".replace('__id__', event.id);
                document.getElementById('method').value = 'PUT';
                document.getElementById('evento_id').value = event.id;
                
                document.getElementById('titulo').value = event.title;
                document.getElementById('descripcion').value = event.extendedProps.description || '';
                document.getElementById('color').value = event.backgroundColor || '#3788d8';
                
                if (event.allDay) {
                    todoElDiaCheck.checked = true;
                    document.getElementById('fecha_inicio').type = 'date';
                    document.getElementById('fecha_fin').type = 'date';
                    document.getElementById('fecha_inicio').value = event.startStr.split('T')[0];
                    document.getElementById('fecha_fin').value = event.endStr ? event.endStr.split('T')[0] : event.startStr.split('T')[0];
                } else {
                    todoElDiaCheck.checked = false;
                    document.getElementById('fecha_inicio').type = 'datetime-local';
                    document.getElementById('fecha_fin').type = 'datetime-local';
                    document.getElementById('fecha_inicio').value = formatDateForInput(event.start);
                    document.getElementById('fecha_fin').value = event.end ? formatDateForInput(event.end) : formatDateForInput(event.start);
                }
                
                // Solo mostramos el botón eliminar si el usuario puede eliminar este evento
                deleteButton.style.display = event.extendedProps.editable ? 'block' : 'none';
                modal.modal('show');
            }
            
            function formatDateForInput(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                
                return `${year}-${month}-${day}T${hours}:${minutes}`;
            }
            
            // Event listeners
            modal.on('hidden.bs.modal', function() {
                form.reset();
            });
            
            crearEventoBtn.addEventListener('click', function() {
                const now = new Date();
                const end = new Date(now);
                end.setHours(now.getHours() + 1);
                
                openNewEventModal({
                    startStr: formatDateForInput(now),
                    endStr: formatDateForInput(end),
                    allDay: false
                });
            });
            
            // Añadir controlador de eventos para el envío del formulario
            form.addEventListener('submit', function(e) {
                // No prevenimos el evento predeterminado para permitir el envío normal del formulario
                
                // Solo agregamos logs para depuración
                console.log('Formulario enviado');
                console.log('URL:', form.action);
                console.log('Método:', document.getElementById('method').value);
                
                // Permitimos que el formulario se envíe normalmente
                return true;
            });
            
            deleteButton.addEventListener('click', function() {
                if (confirm('¿Estás seguro de eliminar este evento?')) {
                    const id = document.getElementById('evento_id').value;
                    const deleteForm = document.getElementById('deleteForm');
                    
                    // Construimos la URL utilizando la ruta con nombre y el ID del evento
                    deleteForm.action = "{{ route('dashboard.calendario.eliminar', ['id' => '__id__']) }}".replace('__id__', id);
                    
                    deleteForm.submit();
                }
            });
            
            function eliminarEvento() {
                if (confirm('¿Estás seguro de eliminar este evento?')) {
                    const id = document.getElementById('evento_id').value;
                    const deleteForm = document.getElementById('deleteForm');
                    
                    // Construimos la URL utilizando la ruta con nombre y el ID del evento
                    deleteForm.action = "{{ route('dashboard.calendario.eliminar', ['id' => '__id__']) }}".replace('__id__', id);
                    
                    deleteForm.submit();
                }
            }
        } catch (error) {
            console.error("Error al inicializar el calendario:", error);
        }
    });
</script>
@endsection 