@extends('layouts.dashboard')

@section('title', 'Agregar Nuevo Host')

@section('content')
<section class="section">
    <div class="section-header">
        <h1>Agregar Nuevo Host</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard.index') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('monitor.index') }}">Monitoreo</a></div>
            <div class="breadcrumb-item">Agregar Host</div>
        </div>
    </div>

    <div class="section-body">
        <h2 class="section-title">Información del Host</h2>
        <p class="section-lead">Completa los detalles para agregar un nuevo equipo al sistema de monitoreo.</p>

        <div class="row">
            <div class="col-12 col-md-6 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Datos del Host</h4>
                    </div>
                    <div class="card-body">
                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible show fade">
                                <div class="alert-body">
                                    <button class="close" data-dismiss="alert">
                                        <span>&times;</span>
                                    </button>
                                    {{ session('error') }}
                                </div>
                            </div>
                        @endif

                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible show fade">
                                <div class="alert-body">
                                    <button class="close" data-dismiss="alert">
                                        <span>&times;</span>
                                    </button>
                                    {{ session('success') }}
                                </div>
                            </div>
                        @endif

                        <div id="detection-alert" class="alert alert-info d-none">
                            <div class="alert-body">
                                <i class="fas fa-spinner fa-spin mr-2"></i> <span id="detection-message">Detectando información del host...</span>
                            </div>
                        </div>

                        <form id="host-form" action="{{ route('monitor.store') }}" method="POST">
                            @csrf
                            
                            <div class="form-group">
                                <label>Tipo de Configuración</label>
                                <div class="selectgroup w-100">
                                    <label class="selectgroup-item">
                                        <input type="radio" name="tipo_host" value="fija" class="selectgroup-input" checked>
                                        <span class="selectgroup-button">IP Fija</span>
                                    </label>
                                    <label class="selectgroup-item">
                                        <input type="radio" name="tipo_host" value="dhcp" class="selectgroup-input">
                                        <span class="selectgroup-button">DHCP (Automático)</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="hostname">Nombre del Host <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control @error('hostname') is-invalid @enderror" id="hostname" name="hostname" value="{{ old('hostname') }}" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="button" id="btn-detect-host">
                                            <i class="fas fa-search"></i> Detectar
                                        </button>
                                    </div>
                                </div>
                                @error('hostname')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <small class="form-text text-muted">Ejemplo: B27-A1 o B27-A1.tierno.es</small>
                            </div>

                            <div class="form-group ip-field">
                                <label for="ip_address">Dirección IP <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text">
                                            <i class="fas fa-network-wired"></i>
                                        </div>
                                    </div>
                                    <input type="text" class="form-control @error('ip_address') is-invalid @enderror" id="ip_address" name="ip_address" value="{{ old('ip_address') }}" placeholder="192.168.1.10">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="button" id="btn-detect-ip">
                                            <i class="fas fa-search"></i> Detectar
                                        </button>
                                    </div>
                                </div>
                                @error('ip_address')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <small class="form-text text-muted">Ejemplo: 192.168.1.10</small>
                            </div>

                            <div class="form-group">
                                <label for="mac_address">Dirección MAC</label>
                                <div class="input-group">
                                    <input type="text" class="form-control @error('mac_address') is-invalid @enderror" id="mac_address" name="mac_address" value="{{ old('mac_address') }}" placeholder="00:11:22:33:44:55" readonly>
                                    <div class="input-group-append">
                                        <span class="input-group-text"><i class="fas fa-ethernet"></i></span>
                                    </div>
                                </div>
                                @error('mac_address')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <small class="form-text text-muted">La MAC se detectará automáticamente</small>
                            </div>

                            <div class="form-group">
                                <label for="description">Descripción</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="group_id">Grupo</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-layer-group"></i></span>
                                    <select class="form-select" id="group_id" name="group_id">
                                        <option value="">-- Sin grupo --</option>
                                        @foreach($groups as $group)
                                            <option value="{{ $group->id }}" {{ old('group_id', $groupId ?? '') == $group->id ? 'selected' : '' }}>
                                                {{ $group->name }} - {{ $group->description ?? 'Sin descripción' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-text text-muted">Seleccione el grupo/aula al que pertenece este equipo</div>
                            </div>

                            <div class="card-footer text-right">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar
                                </button>
                                <a href="{{ route('monitor.index') }}" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-md-6 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Información Adicional</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <p><strong>Tipos de configuración:</strong></p>
                            <ul>
                                <li><strong>IP Fija:</strong> Se especifica manualmente la IP y el hostname.</li>
                                <li><strong>DHCP:</strong> Solo se especifica el hostname y la IP se detecta automáticamente.</li>
                                <li>En ambos casos, la dirección MAC se detectará automáticamente.</li>
                            </ul>
                        </div>

                        <div class="alert alert-warning">
                            <p><strong>Importante:</strong></p>
                            <ul>
                                <li>El equipo debe estar encendido y conectado a la red para detectar su información.</li>
                                <li>Si no puede detectarse, puede guardar la información básica sin verificar.</li>
                                <li>Para equipos con DHCP, asegúrese de usar el nombre de host completo (incluyendo dominio si es necesario).</li>
                            </ul>
                        </div>

                        <div class="alert alert-success">
                            <p><strong>Nuevo: Detección automática</strong></p>
                            <ul>
                                <li>Haz clic en el botón "Detectar" junto al hostname para obtener automáticamente la IP y MAC.</li>
                                <li>También puedes detectar por IP haciendo clic en el botón "Detectar" junto al campo IP.</li>
                                <li>Si el hostname sigue un patrón como B27-A1, se asignará automáticamente al grupo/aula correspondiente.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Configuración global de AJAX para incluir token CSRF
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Función para actualizar el estado del campo IP
    function updateIpField() {
        var tipo = $('input[name="tipo_host"]:checked').val();
        if (tipo === 'dhcp') {
            $('.ip-field').hide();
            $('#ip_address').prop('required', false);
            $('#ip_address').val('');
        } else {
            $('.ip-field').show();
            $('#ip_address').prop('required', true);
        }
    }

    // Ejecutar al cargar la página
    updateIpField();

    // Cambiar modo según tipo de host
    $('input[name="tipo_host"]').on('change', function() {
        updateIpField();
    });

    // Mostrar alerta de detección
    function showDetectionAlert(message) {
        $('#detection-message').text(message || 'Detectando información del host...');
        $('#detection-alert').removeClass('d-none alert-danger alert-success').addClass('alert-info');
        $('#detection-alert .fa-spinner').show();
    }

    // Actualizar alerta de detección con éxito
    function showDetectionSuccess(message) {
        $('#detection-message').text(message || 'Host detectado correctamente');
        $('#detection-alert').removeClass('alert-info alert-danger').addClass('alert-success');
        $('#detection-alert .fa-spinner').hide();
    }

    // Actualizar alerta de detección con error
    function showDetectionError(message) {
        $('#detection-message').text(message || 'No se pudo detectar el host');
        $('#detection-alert').removeClass('alert-info alert-success').addClass('alert-danger');
        $('#detection-alert .fa-spinner').hide();
    }

    // Función para detectar host por hostname
    function detectHostByHostname() {
        var hostname = $('#hostname').val().trim();
        if (!hostname) {
            showDetectionError('Debe ingresar un nombre de host para detectar');
            return;
        }

        showDetectionAlert('Detectando información para el host: ' + hostname);
        
        var tipo = $('input[name="tipo_host"]:checked').val();
        
        $.ajax({
            url: '{{ route("monitor.detect-host") }}',
            type: 'POST',
            data: {
                hostname: hostname,
                tipo: tipo
            },
            dataType: 'json',
            success: function(response) {
                console.log("Respuesta de detección por hostname:", response);
                if (response.success) {
                    // Actualizar campos con la información detectada
                    if (response.data.ip_address) {
                        $('#ip_address').val(response.data.ip_address);
                    }
                    if (response.data.mac_address) {
                        $('#mac_address').val(response.data.mac_address);
                    }
                    
                    showDetectionSuccess('Host detectado correctamente. IP: ' + 
                        (response.data.ip_address || 'No detectada') + 
                        ', MAC: ' + (response.data.mac_address || 'No detectada'));
                } else {
                    showDetectionError(response.message || 'No se pudo detectar el host');
                }
            },
            error: function(xhr, status, error) {
                console.error("Error en detección por hostname:", error, xhr.responseText);
                var errorMsg = 'Error al detectar el host';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showDetectionError(errorMsg);
            }
        });
    }

    // Función para detectar host por IP
    function detectHostByIp() {
        var ip = $('#ip_address').val().trim();
        if (!ip) {
            showDetectionError('Debe ingresar una dirección IP para detectar');
            return;
        }

        showDetectionAlert('Detectando información para la IP: ' + ip);
        
        var tipo = $('input[name="tipo_host"]:checked').val();
        
        $.ajax({
            url: '{{ route("monitor.detect-host") }}',
            type: 'POST',
            data: {
                ip_address: ip,
                tipo: tipo
            },
            dataType: 'json',
            success: function(response) {
                console.log("Respuesta de detección por IP:", response);
                if (response.success) {
                    // Actualizar campos con la información detectada
                    if (response.data.hostname) {
                        $('#hostname').val(response.data.hostname.replace('.tierno.es', ''));
                    }
                    if (response.data.mac_address) {
                        $('#mac_address').val(response.data.mac_address);
                    }
                    
                    showDetectionSuccess('Host detectado correctamente. Hostname: ' + 
                        (response.data.hostname || 'No detectado') + 
                        ', MAC: ' + (response.data.mac_address || 'No detectada'));
                } else {
                    showDetectionError(response.message || 'No se pudo detectar el host');
                }
            },
            error: function(xhr, status, error) {
                console.error("Error en detección por IP:", error, xhr.responseText);
                var errorMsg = 'Error al detectar el host';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showDetectionError(errorMsg);
            }
        });
    }

    // Asignar eventos a los botones de detección
    $('#btn-detect-host').on('click', function(e) {
        e.preventDefault(); // Evitar que el botón envíe el formulario
        detectHostByHostname();
    });
    
    $('#btn-detect-ip').on('click', function(e) {
        e.preventDefault(); // Evitar que el botón envíe el formulario
        detectHostByIp();
    });

    // También intentar detectar al perder el foco en los campos
    $('#hostname').on('blur', function() {
        if ($(this).val().trim().length > 3) {
            detectHostByHostname();
        }
    });

    $('#ip_address').on('blur', function() {
        if ($(this).val().trim().length > 7) {
            detectHostByIp();
        }
    });
});
</script>
@endsection
