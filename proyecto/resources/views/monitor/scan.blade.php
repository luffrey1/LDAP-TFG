@extends('layouts.dashboard')

@section('title', 'Escanear Red')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-search me-2"></i> Escanear Red</h4>
                </div>
                <div class="card-body">
                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                        </div>
                    @endif

                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle me-2"></i> Información sobre el escaneo de red</h5>
                        <p>El escaneo de red permite detectar automáticamente equipos conectados dentro del rango IP especificado.</p>
                        <ul>
                            <li>Asegúrese de estar conectado a la <strong>VPN del instituto</strong> o a la red local antes de iniciar el escaneo.</li>
                            <li>El rango DHCP (172.20.200.0 - 172.20.209.255) contiene la mayoría de los equipos de las aulas.</li>
                            <li>Los dispositivos críticos (router, DNS, servidor) se añadirán automáticamente.</li>
                            <li>Los equipos se organizan automáticamente en grupos según su nombre de host.</li>
                            <li>El escaneo puede tardar varios minutos dependiendo del rango seleccionado.</li>
                        </ul>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-network-wired me-2"></i> Verificar Conectividad de Red</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-9">
                                    <div class="alert alert-secondary">
                                        <p class="mb-2">Verifique su conectividad con la red del instituto antes de iniciar el escaneo:</p>
                                        <div class="row mb-2">
                                            <div class="col-md-4">
                                                <button class="btn btn-outline-primary w-100" onclick="setRange('172.20.0', 1, 30)">
                                                    <i class="fas fa-server me-1"></i> Red Administrativa
                                                </button>
                                            </div>
                                            <div class="col-md-4">
                                                <button class="btn btn-outline-success w-100" onclick="setRange('172.20.200', 1, 254)">
                                                    <i class="fas fa-laptop me-1"></i> DHCP 1 (Aulas)
                                                </button>
                                            </div>
                                            <div class="col-md-4">
                                                <button class="btn btn-outline-info w-100" onclick="setRange('172.20.201', 1, 254)">
                                                    <i class="fas fa-desktop me-1"></i> DHCP 2 (Más aulas)
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <button class="btn btn-warning w-100" id="checkNetworkBtn">
                                                <i class="fas fa-plug me-1"></i> Verificar Conectividad
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('monitor.scan.execute') }}" method="POST" id="scanForm" class="mb-5">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="base_ip" class="form-label"><i class="fas fa-network-wired me-1"></i> IP Base</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="base_ip" name="base_ip" value="{{ $baseIp }}" required>
                                        <button class="btn btn-outline-secondary" type="button" id="detectNetwork"><i class="fas fa-magic"></i> Detectar</button>
                                    </div>
                                    <div class="form-text">Primeros 3 octetos (ej: 172.20.200)</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="range_start" class="form-label">Inicio</label>
                                    <input type="number" class="form-control" id="range_start" name="range_start" min="1" max="254" value="1" required>
                                    <div class="form-text">Primer host</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="range_end" class="form-label">Fin</label>
                                    <input type="number" class="form-control" id="range_end" name="range_end" min="1" max="254" value="254" required>
                                    <div class="form-text">Último host</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="group_id" class="form-label"><i class="fas fa-layer-group me-1"></i> Asignar al grupo</label>
                                    <select class="form-select" id="group_id" name="group_id">
                                        <option value="">Sin grupo</option>
                                        @foreach($groups as $group)
                                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Opcional - Grupo para hosts sin aula detectada</div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i> <strong>Importante:</strong> Verifique que está conectado a la VPN o red local del instituto antes de continuar.
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="force_register" name="force_register" value="1">
                                    <label class="form-check-label" for="force_register">
                                        <i class="fas fa-magic me-1"></i> Forzar registro de hosts (incluso si no responden al ping)
                                    </label>
                                    <div class="form-text">Active esta opción para registrar equipos aunque no respondan al ping actualmente.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="scan_additional_ranges" name="scan_additional_ranges" value="1" checked>
                                    <label class="form-check-label" for="scan_additional_ranges">
                                        <i class="fas fa-layer-group me-1"></i> Escanear rangos adicionales (Infraestructura + muestras DHCP)
                                    </label>
                                    <div class="form-text">Incluye automáticamente dispositivos críticos y muestras de otros rangos DHCP del instituto.</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="force_register" name="force_register" value="1">
                                <label class="form-check-label" for="force_register">
                                    <strong>Forzar registro de equipos</strong> aunque no respondan al ping
                                </label>
                                <div class="form-text">Útil cuando los equipos están apagados pero desea registrarlos igualmente</div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="{{ route('monitor.index') }}" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary" id="scanButton">
                                    <i class="fas fa-search me-1"></i> Iniciar Escaneo
                                </button>
                            </div>
                        </div>
                    </form>

                    <div id="progressSection" style="display: none;">
                        <h4 class="mb-3"><i class="fas fa-sync fa-spin me-2"></i> Escaneando red...</h4>
                        <p id="statusText">Iniciando escaneo...</p>
                        <div class="progress mb-3" style="height: 25px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" id="scanProgress" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> El escaneo puede tardar varios minutos. No cierre esta página.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Función para establecer el rango de escaneo
    window.setRange = function(baseIp, start, end) {
        $('#base_ip').val(baseIp);
        $('#range_start').val(start);
        $('#range_end').val(end);
        
        // Desplazar hacia el formulario
        $('html, body').animate({
            scrollTop: $("#scanForm").offset().top - 100
        }, 800);
    };
    
    // Verificación de red del instituto
    $('#detectNetwork').click(function() {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Detectando...');
        
        checkNetworkConnectivity();
    });
    
    // Botón para verificar conectividad
    $('#checkNetworkBtn').click(function() {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Verificando...');
        
        checkNetworkConnectivity();
    });
    
    function checkNetworkConnectivity() {
        $.ajax({
            url: "{{ route('monitor.check-network') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                $('#detectNetwork').prop('disabled', false).html('<i class="fas fa-magic"></i> Detectar');
                $('#checkNetworkBtn').prop('disabled', false).html('<i class="fas fa-plug me-1"></i> Verificar Conectividad');
                
                let message = '';
                let iconClass = '';
                
                if (response.connected) {
                    message = 'Conectado a la red del instituto. Se detectaron los siguientes dispositivos: <ul>';
                    iconClass = 'success';
                    
                    // Mostrar detalles de dispositivos detectados
                    for (let ip in response.details) {
                        let status = response.details[ip].status === 'online' ? 'En línea' : 'Fuera de línea';
                        let statusClass = response.details[ip].status === 'online' ? 'text-success' : 'text-danger';
                        message += `<li>${response.details[ip].name} (${ip}): <span class="${statusClass}"><strong>${status}</strong></span></li>`;
                    }
                    
                    message += '</ul>';
                    // Recomendar configuración
                    message += '<br>Se ha configurado el rango DHCP principal para el escaneo.';
                    
                    // Configurar automáticamente para DHCP1
                    $('#base_ip').val('172.20.200');
                    $('#range_start').val(1);
                    $('#range_end').val(254);
                } else {
                    message = `<strong>¡Atención!</strong> No se detectó una conexión completa a la red del instituto.<br>${response.message}<br><br>Dispositivos detectados: <ul>`;
                    iconClass = 'warning';
                    
                    let anyOnline = false;
                    
                    // Mostrar detalles de dispositivos detectados
                    for (let ip in response.details) {
                        let status = response.details[ip].status === 'online' ? 'En línea' : 'Fuera de línea';
                        let statusClass = response.details[ip].status === 'online' ? 'text-success' : 'text-danger';
                        if (response.details[ip].status === 'online') anyOnline = true;
                        message += `<li>${response.details[ip].name} (${ip}): <span class="${statusClass}"><strong>${status}</strong></span></li>`;
                    }
                    
                    message += '</ul>';
                    
                    if (anyOnline) {
                        message += '<br>Se detectaron algunos dispositivos. Puede intentar el escaneo, pero es posible que no se encuentren todos los equipos.';
                    } else {
                        message += '<br>No se detectó ningún dispositivo en línea. Verifique su conexión VPN o red local antes de continuar.';
                    }
                }
                
                // Mostrar resultado con SweetAlert
                Swal.fire({
                    icon: iconClass,
                    title: response.connected ? 'Conexión detectada' : 'Problemas de conexión',
                    html: message,
                    confirmButtonText: 'Entendido'
                });
            },
            error: function() {
                $('#detectNetwork').prop('disabled', false).html('<i class="fas fa-magic"></i> Detectar');
                $('#checkNetworkBtn').prop('disabled', false).html('<i class="fas fa-plug me-1"></i> Verificar Conectividad');
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error de comunicación',
                    text: 'No se pudo verificar la conexión. Intente nuevamente.',
                    confirmButtonText: 'Entendido'
                });
            }
        });
    }
    
    // Manejar envío del formulario
    $('#scanForm').submit(function(e) {
        var rangeStart = parseInt($('#range_start').val());
        var rangeEnd = parseInt($('#range_end').val());
        var totalIPs = rangeEnd - rangeStart + 1;
        
        if (totalIPs > 254) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Rango demasiado grande',
                text: 'El rango seleccionado tiene más de 254 IPs. Seleccione un rango más pequeño para un mejor rendimiento.',
                confirmButtonText: 'Entendido'
            });
            return false;
        }
        
        // Mostrar sección de progreso
        $('#scanButton').prop('disabled', true);
        $('#progressSection').show();
        
        // Simular progreso durante el escaneo
        simulateProgress(totalIPs);
    });
    
    function simulateProgress(totalIPs) {
        var progress = 0;
        var increment = totalIPs <= 50 ? 5 : (totalIPs <= 100 ? 2 : 1);
        var stepTime = totalIPs <= 50 ? 1000 : (totalIPs <= 100 ? 500 : 300);
        var messages = [
            'Iniciando escaneo...',
            'Verificando dispositivos en línea...',
            'Detectando equipos del instituto...',
            'Analizando respuestas de ping...',
            'Consultando DNS para nombres de host...',
            'Obteniendo direcciones MAC...',
            'Organizando equipos por aulas...',
            'Completando registro de dispositivos...',
            'Finalizando escaneo...'
        ];
        var messageIndex = 0;
        
        var interval = setInterval(function() {
            progress += increment;
            if (progress >= 100) {
                progress = 99; // Mantener en 99% hasta que la página se redirija
                clearInterval(interval);
            }
            
            // Actualizar barra de progreso
            $('#scanProgress').css('width', progress + '%').attr('aria-valuenow', progress).text(progress + '%');
            
            // Cambiar mensaje de estado periódicamente
            if (progress % 15 === 0 && messageIndex < messages.length - 1) {
                messageIndex++;
                $('#statusText').text(messages[messageIndex]);
            }
        }, stepTime);
    }
});
</script>
@endsection 