@extends('layouts.dashboard')

@section('title', 'Escanear Red')

@section('content')
<div class="container-fluid">
    <!-- Encabezado de página -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Escanear Red Local</h1>
        <a href="{{ route('dashboard.monitor') }}" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Volver al Monitor
        </a>
    </div>

    <!-- Tarjeta del formulario -->
    <div class="card shadow mb-4" data-aos="fade-up">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Parámetros de Escaneo</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('dashboard.monitor.scan') }}" method="POST" id="scan-form">
                @csrf
                
                @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
                @endif
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="base_ip">Dirección Base IP</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="base_ip" name="base_ip" value="{{ $baseIp }}" required>
                                <span class="input-group-text">.X</span>
                            </div>
                            <small class="form-text text-muted">Primeros 3 octetos de la red (ej. 192.168.1)</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="range_start">Inicio del Rango</label>
                            <input type="number" class="form-control" id="range_start" name="range_start" value="1" min="1" max="254" required>
                            <small class="form-text text-muted">Primer IP a escanear (1-254)</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="range_end">Fin del Rango</label>
                            <input type="number" class="form-control" id="range_end" name="range_end" value="254" min="1" max="254" required>
                            <small class="form-text text-muted">Última IP a escanear (1-254)</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mt-3">
                    <label for="group_id">Asignar Grupo</label>
                    <select class="form-control" id="group_id" name="group_id">
                        <option value="0">Sin grupo</option>
                        <option value="1">Laboratorio</option>
                        <option value="2">Administración</option>
                        <option value="3">Aulas</option>
                        <option value="4">Servidores</option>
                    </select>
                    <small class="form-text text-muted">Asignar automáticamente los equipos encontrados a este grupo</small>
                </div>
                
                <div class="alert alert-info">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fa-2x me-3"></i>
                        <div>
                            <p class="mb-0">El escaneo puede tardar varios minutos dependiendo del rango de IPs seleccionado. Se añadirán automáticamente los equipos que respondan al ping.</p>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary" id="start-scan-btn">
                        <i class="fas fa-search mr-1"></i> Iniciar Escaneo
                    </button>
                    <a href="{{ route('dashboard.monitor') }}" class="btn btn-secondary ml-2">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </a>
                </div>
            </form>
            
            <!-- Div para mostrar progreso -->
            <div id="scan-progress-container" class="mt-4 d-none">
                <h5>Escaneando red...</h5>
                <div class="progress mb-3">
                    <div id="scan-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                </div>
                <p id="scan-status" class="text-muted">Preparando escaneo...</p>
            </div>
        </div>
    </div>
    
    <!-- Información adicional -->
    <div class="card shadow mb-4" data-aos="fade-up" data-aos-delay="200">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Información sobre el Escaneo de Red</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-info-circle text-info mr-2"></i> ¿Cómo funciona?</h5>
                    <p>El escáner envía un ping a cada dirección IP en el rango especificado. Si un dispositivo responde, se añade automáticamente a la lista de equipos monitoreados.</p>
                    <ul>
                        <li>Se utilizan pings con tiempos cortos para agilizar el proceso.</li>
                        <li>Se intenta detectar el nombre del host mediante consultas DNS inversas.</li>
                        <li>Los equipos ya existentes en la base de datos serán omitidos.</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5><i class="fas fa-lightbulb text-warning mr-2"></i> Recomendaciones</h5>
                    <ul>
                        <li>Escanee pequeños rangos si solo necesita detectar algunos equipos.</li>
                        <li>El rango completo (1-254) puede tardar varios minutos en completarse.</li>
                        <li>Algunos equipos podrían no responder a ping por configuración del firewall.</li>
                        <li>Para información completa del sistema, considere instalar el agente en los equipos detectados.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Validación para la dirección IP base
    $('#base_ip').on('input', function() {
        const baseIpRegex = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;
        const value = $(this).val();
        let isValid = baseIpRegex.test(value);
        
        if (isValid) {
            const matches = value.match(baseIpRegex);
            const oct1 = parseInt(matches[1]);
            const oct2 = parseInt(matches[2]);
            const oct3 = parseInt(matches[3]);
            
            isValid = oct1 >= 0 && oct1 <= 255 && 
                      oct2 >= 0 && oct2 <= 255 && 
                      oct3 >= 0 && oct3 <= 255;
        }
        
        if (value && !isValid) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).parent().append('<div class="invalid-feedback">Formato inválido. Use el formato: xxx.xxx.xxx</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).parent().find('.invalid-feedback').remove();
        }
    });
    
    // Validación para el rango
    $('#range_start, #range_end').on('input', function() {
        const rangeStart = parseInt($('#range_start').val());
        const rangeEnd = parseInt($('#range_end').val());
        
        if (rangeStart > rangeEnd) {
            $('#range_end').addClass('is-invalid');
            if (!$('#range_end').next('.invalid-feedback').length) {
                $('#range_end').after('<div class="invalid-feedback">El fin del rango debe ser mayor que el inicio</div>');
            }
        } else {
            $('#range_end').removeClass('is-invalid');
            $('#range_end').next('.invalid-feedback').remove();
        }
    });
    
    // Manejo del formulario de escaneo
    $('#scan-form').on('submit', function(e) {
        const rangeStart = parseInt($('#range_start').val());
        const rangeEnd = parseInt($('#range_end').val());
        const ipBase = $('#base_ip').val();
        
        // Validar IP base
        const baseIpRegex = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;
        if (!baseIpRegex.test(ipBase)) {
            e.preventDefault();
            $('#base_ip').addClass('is-invalid');
            return false;
        }
        
        // Validar rango
        if (rangeStart > rangeEnd) {
            e.preventDefault();
            $('#range_end').addClass('is-invalid');
            return false;
        }
        
        // Mostrar barra de progreso
        $('#scan-progress-container').removeClass('d-none');
        $('#start-scan-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Escaneando...');
        
        // Simulación de progreso (el escaneo real ocurre en el servidor)
        const rangeSize = rangeEnd - rangeStart + 1;
        const increment = 100 / rangeSize;
        let progress = 0;
        let currentIp = rangeStart;
        
        const interval = setInterval(function() {
            if (progress >= 100) {
                clearInterval(interval);
                return;
            }
            
            progress += increment;
            $('#scan-progress-bar').css('width', Math.min(progress, 100) + '%');
            $('#scan-status').text('Escaneando ' + ipBase + '.' + currentIp);
            currentIp++;
            
        }, 200);
    });
});
</script>
@endsection 