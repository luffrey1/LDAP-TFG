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

                        <form action="{{ route('monitor.store') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="hostname">Nombre del Host <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('hostname') is-invalid @enderror" id="hostname" name="hostname" value="{{ old('hostname') }}" required>
                                @error('hostname')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="form-group">
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
                                <input type="text" class="form-control @error('mac_address') is-invalid @enderror" id="mac_address" name="mac_address" value="{{ old('mac_address') }}" placeholder="00:11:22:33:44:55">
                                @error('mac_address')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <small class="form-text text-muted">Formato: 00:11:22:33:44:55</small>
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

                            <div class="card-footer text-right">
                                <button type="submit" class="btn btn-primary">Guardar</button>
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
                            <p><strong>Tips para agregar hosts:</strong></p>
                            <ul>
                                <li>Asegúrate de que el host esté encendido y conectado a la red.</li>
                                <li>Si no conoces la dirección IP, puedes usar la función de <a href="{{ route('monitor.scan') }}">Escaneo de Red</a>.</li>
                                <li>Para obtener datos de telemetría, necesitarás instalar un agente en el equipo.</li>
                            </ul>
                        </div>

                        <div class="card card-warning">
                            <div class="card-header">
                                <h4>Verificar Conectividad</h4>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="test_ip">Dirección IP a verificar</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="test_ip" name="test_ip" placeholder="192.168.1.10">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button" id="ping_test">Verificar</button>
                                        </div>
                                    </div>
                                </div>
                                <div id="ping_result" class="mt-3 d-none">
                                    <div class="alert" id="ping_alert">
                                        <div id="ping_message"></div>
                                    </div>
                                </div>
                            </div>
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
    // Copiar IP de prueba al formulario
    $('#test_ip').on('input', function() {
        $('#ip_address').val($(this).val());
    });
    
    // Prueba de ping
    $('#ping_test').on('click', function() {
        var ip = $('#test_ip').val();
        if (!ip) {
            alert('Por favor ingresa una dirección IP para verificar');
            return;
        }
        
        $('#ping_result').removeClass('d-none');
        $('#ping_alert').removeClass('alert-success alert-danger').addClass('alert-warning');
        $('#ping_message').html('<i class="fas fa-spinner fa-spin"></i> Verificando conectividad...');
        
        // Simulación de ping (reemplazar con AJAX real)
        setTimeout(function() {
            // Validación simple de IP
            var ipRegex = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
            
            if (!ipRegex.test(ip)) {
                $('#ping_alert').removeClass('alert-warning alert-success').addClass('alert-danger');
                $('#ping_message').html('<i class="fas fa-times"></i> La dirección IP no es válida');
                return;
            }
            
            // Aquí iría una llamada AJAX real para probar la conectividad
            // Por ahora simulamos un resultado aleatorio
            var success = Math.random() > 0.5;
            
            if (success) {
                $('#ping_alert').removeClass('alert-warning alert-danger').addClass('alert-success');
                $('#ping_message').html('<i class="fas fa-check"></i> Host alcanzable. Conectividad correcta.');
                $('#ip_address').val(ip);
            } else {
                $('#ping_alert').removeClass('alert-warning alert-success').addClass('alert-danger');
                $('#ping_message').html('<i class="fas fa-times"></i> No se pudo conectar con el host. Verifica que esté encendido y en la misma red.');
            }
        }, 1500);
    });
});
</script>
@endsection 