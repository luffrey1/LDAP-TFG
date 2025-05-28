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

                        <form id="host-form" action="{{ route('monitor.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="detected" id="detected" value="0">
                            
                            <div class="form-group">
                                <label for="hostname">Nombre del Host <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('hostname') is-invalid @enderror" id="hostname" name="hostname" value="{{ old('hostname') }}" required>
                                @error('hostname')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="form-group ip-field">
                                <label for="ip_address">Dirección IP <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text">
                                            <i class="fas fa-network-wired"></i>
                                        </div>
                                    </div>
                                    <input type="text" class="form-control @error('ip_address') is-invalid @enderror" id="ip_address" name="ip_address" value="{{ old('ip_address') }}" placeholder="192.168.1.10" required>
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

                            <div class="alert alert-info detection-status d-none">
                                <div class="detection-message"></div>
                            </div>

                            <div class="card-footer text-right">
                                <button type="button" id="detect-button" class="btn btn-info">
                                    <i class="fas fa-search"></i> Detectar Host
                                </button>
                                <button type="submit" id="save-button" class="btn btn-primary d-none">
                                    <i class="fas fa-save"></i> Guardar
                                </button>
                                <button type="submit" id="save-anyway-button" class="btn btn-warning d-none">
                                    <i class="fas fa-exclamation-triangle"></i> Guardar Sin Comprobar
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Cambiar modo según tipo de host
    $('input[name="tipo_host"]').on('change', function() {
        var tipo = $(this).val();
        if (tipo === 'dhcp') {
            $('.ip-field').hide();
            $('#ip_address').removeAttr('required');
            $('#ip_address').val('');
        } else {
            $('.ip-field').show();
            $('#ip_address').attr('required', 'required');
        }
        resetDetection();
    });
    
    // Función para resetear estado de detección
    function resetDetection() {
        $('#detected').val('0');
        $('.detection-status').addClass('d-none');
        $('#save-button').addClass('d-none');
        $('#save-anyway-button').addClass('d-none');
        $('#mac_address').val('');
    }
    
    // Cuando se cambian los campos clave, resetear la detección
    $('#hostname, #ip_address').on('input', function() {
        resetDetection();
    });
    
    // Detectar host
    $('#detect-button').on('click', function() {
        var tipo = $('input[name="tipo_host"]:checked').val();
        var hostname = $('#hostname').val();
        var ip = $('#ip_address').val();
        
        // Validar campos según tipo
        if (tipo === 'dhcp' && !hostname) {
            alert('Por favor ingrese el nombre del host');
            return;
        }
        
        if (tipo === 'fija' && !ip) {
            alert('Por favor ingrese la dirección IP');
            return;
        }
        
        // Mostrar estado de detección
        $('.detection-status').removeClass('d-none alert-danger alert-success').addClass('alert-info');
        $('.detection-message').html('<i class="fas fa-spinner fa-spin"></i> Detectando información del host...');
        
        // Llamar a la API para detectar el host
        $.ajax({
            url: '{{ route("monitor.detect-host") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                hostname: hostname,
                ip_address: ip,
                tipo: tipo
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Actualizar campos con la información detectada
                    if (response.data.hostname) {
                        $('#hostname').val(response.data.hostname);
                    }
                    
                    if (response.data.ip_address) {
                        $('#ip_address').val(response.data.ip_address);
                    }
                    
                    if (response.data.mac_address) {
                        $('#mac_address').val(response.data.mac_address);
                    }
                    
                    // Marcar como detectado
                    $('#detected').val('1');
                    $('.detection-status').removeClass('alert-info alert-danger').addClass('alert-success');
                    $('.detection-message').html('<i class="fas fa-check"></i> ' + response.message + '. Host detectado correctamente.');
                    
                    // Mostrar botón de guardar
                    $('#save-button').removeClass('d-none');
                    $('#save-anyway-button').addClass('d-none');
                } else {
                    // Mostrar error
                    $('.detection-status').removeClass('alert-info alert-success').addClass('alert-danger');
                    $('.detection-message').html('<i class="fas fa-times"></i> ' + response.message);
                    
                    // Mostrar botón de guardar sin comprobar
                    $('#save-button').addClass('d-none');
                    $('#save-anyway-button').removeClass('d-none');
                }
            },
            error: function(xhr) {
                var message = 'Error al detectar el host';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                // Mantener el spinner unos segundos antes de mostrar el error
                setTimeout(function() {
                    $('.detection-status').removeClass('alert-info alert-success').addClass('alert-danger');
                    $('.detection-message').html('<i class="fas fa-times"></i> ' + message + '<br><small>¿Está encendido y conectado a la red? Prueba a escribir el hostname completo (ej: B27-A9.tierno.es) o revisa la IP.</small>');
                    // Mostrar botón de guardar sin comprobar
                    $('#save-button').addClass('d-none');
                    $('#save-anyway-button').removeClass('d-none');
                }, 1200);
            }
        });
    });
    
    // Al enviar el formulario, validar si se ha detectado
    $('#host-form').on('submit', function(e) {
        var detected = $('#detected').val();
        var submitButton = $(document.activeElement);
        
        // Si no se ha detectado y no se está usando el botón de guardar sin comprobar
        if (detected !== '1' && !submitButton.is('#save-anyway-button')) {
            e.preventDefault();
            alert('Por favor, detecte la información del host antes de guardar');
            return false;
        }
    });
});
</script>
@endsection 