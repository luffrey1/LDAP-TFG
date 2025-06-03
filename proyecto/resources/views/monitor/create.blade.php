@extends('layouts.dashboard')

@section('title', 'Añadir Equipo')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i> Añadir Nuevo Equipo</h4>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                        </div>
                    @endif

                    <form id="createHostForm" action="{{ route('monitor.store') }}" method="POST">
                        @csrf
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0 text-black"><i class="fas fa-search me-2 text-black"></i> Detección del Equipo</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="tipo_host" class="form-label text-white">Tipo de Equipo</label>
                                            <select class="form-select" id="tipo_host" name="tipo_host" required>
                                                <option value="dhcp">DHCP (Automático)</option>
                                                <option value="fija">IP Fija</option>
                                            </select>
                                        </div>

                                        <div id="dhcpFields">
                                            <div class="mb-3">
                                                <label for="hostname" class="form-label text-white">Hostname</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="hostname" name="hostname" 
                                                           placeholder="Ej: B27-A1, B27-B2, etc." required>
                                                    <button type="button" class="btn btn-primary" id="detectHostBtn">
                                                        <i class="fas fa-search me-1"></i> Comprobar Host
                                                    </button>
                                                </div>
                                                <div class="form-text text-white">Escribe el hostname del equipo (ej: B27-A1) y haz clic en "Comprobar Host"</div>
                                            </div>
                                        </div>

                                        <div id="fijaFields" style="display: none;">
                                            <div class="mb-3">
                                                <label for="ip_address" class="form-label text-white">Dirección IP</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="ip_address" name="ip_address" 
                                                           placeholder="Ej: 172.20.200.100">
                                                    <button type="button" class="btn btn-primary" id="detectIpBtn">
                                                        <i class="fas fa-search me-1"></i> Comprobar IP
                                                    </button>
                                                </div>
                                                <div class="form-text text-white">Escribe la IP del equipo y haz clic en "Comprobar IP"</div>
                                                <div class="alert alert-info mt-2" id="retryMessage" style="display: none;">
                                                    <i class="fas fa-info-circle me-2"></i>Si quiere volver a intentarlo, le dé de nuevo a: Comprobar
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="hostname_fija" class="form-label text-white">Hostname (opcional)</label>
                                                <input type="text" class="form-control" id="hostname_fija" name="hostname_fija" 
                                                       placeholder="Ej: B27-A1, B27-B2, etc.">
                                                <div class="form-text text-white">Si conoce el hostname, puede escribirlo para una detección más precisa</div>
                                            </div>
                                        </div>

                                        <div id="detectionResult" class="alert" style="display: none;">
                                            <div id="detectionMessage"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0 text-black"><i class="fas fa-info-circle me-2 text-black"></i> Información del Equipo</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="mac_address" class="form-label text-white">Dirección MAC</label>
                                            <input type="text" class="form-control" id="mac_address" name="mac_address" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label for="ip_address_display" class="form-label text-white">Dirección IP</label>
                                            <input type="text" class="form-control" id="ip_address_display" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label text-white">Descripción</label>
                                            <textarea class="form-control" id="description" name="description" rows="3" 
                                                      placeholder="Descripción opcional del equipo"></textarea>
                                        </div>

                                        <div id="groupSelector" class="mb-3" style="display: none;">
                                                <label for="group_id" class="form-label text-white">Grupo</label>
                                            <select class="form-select" id="group_id" name="group_id">
                                                <option value="">Sin grupo</option>
                                                @foreach($groups as $group)
                                                    <option value="{{ $group->id }}" {{ $groupId == $group->id ? 'selected' : '' }}>
                                                        {{ $group->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">
                                                <i class="fas fa-save me-1"></i> Guardar Equipo
                                            </button>
                                            <button type="submit" class="btn btn-warning" id="submitWithoutCheckBtn" style="display: none;">
                                                <i class="fas fa-save me-1"></i> Guardar Sin Comprobar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Verificar que jQuery está disponible
    if (typeof jQuery === 'undefined') {
        console.error('jQuery no está cargado');
        return;
    }

    // Configuración global de AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Cambiar campos según tipo de host
    $('#tipo_host').on('change', function() {
        if ($(this).val() === 'dhcp') {
            $('#dhcpFields').show();
            $('#fijaFields').hide();
            $('#ip_address').prop('required', false);
            $('#hostname').prop('required', true);
            $('#groupSelector').hide();
        } else {
            $('#dhcpFields').hide();
            $('#fijaFields').show();
            $('#ip_address').prop('required', true);
            $('#hostname').prop('required', false);
            $('#groupSelector').show();
        }
        resetForm();
    });

    // Función para resetear el formulario
    function resetForm() {
        $('#mac_address').val('');
        $('#ip_address_display').val('');
        $('#detectionResult').hide();
        $('#submitBtn').hide();
        $('#submitWithoutCheckBtn').hide();
        $('#retryMessage').hide();
        $('#hostname').val('');
        $('#ip_address').val('');
        $('#hostname_fija').val('');
    }

    // Función para mostrar mensaje de detección
    function showDetectionMessage(message, type = 'info') {
        $('#detectionResult')
            .removeClass('alert-info alert-success alert-danger')
            .addClass('alert-' + type)
            .show();
        
        if (type === 'success') {
            $('#detectionMessage').html('<i class="fas fa-check-circle me-2"></i>' + message);
        } else if (type === 'danger') {
            $('#detectionMessage').html('<i class="fas fa-exclamation-circle me-2"></i>' + message);
        } else {
            $('#detectionMessage').html('<div class="d-flex align-items-center"><div class="spinner-border spinner-border-sm me-2" role="status"><span class="visually-hidden">Cargando...</span></div>' + message + '</div>');
        }
    }

    // Función para detectar host por hostname
    $('#detectHostBtn').on('click', function() {
        console.log('Botón detectHostBtn clickeado');
        const hostname = $('#hostname').val();
        if (!hostname) {
            showDetectionMessage('Por favor, introduce un hostname', 'danger');
            return;
        }

        showDetectionMessage('Detectando equipo...', 'info');
        $('#detectionResult').show();
        $('#submitBtn').hide();
        $('#submitWithoutCheckBtn').hide();
        $('#retryMessage').hide();

        $.ajax({
            url: "{{ route('monitor.detect-host') }}",
            type: 'POST',
            data: {
                hostname: hostname,
                tipo: 'dhcp',
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log('Respuesta exitosa:', response);
                if (response.success) {
                    $('#mac_address').val(response.data.mac_address || 'No detectada');
                    $('#ip_address_display').val(response.data.ip_address || 'No detectada');
                    $('#ip_address').val(response.data.ip_address || '');
                    showDetectionMessage('Equipo detectado correctamente', 'success');
                    $('#submitBtn').show();
                    $('#retryMessage').show();
                } else {
                    showDetectionMessage(response.message, 'danger');
                    $('#submitWithoutCheckBtn').show();
                    $('#retryMessage').show();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la petición AJAX:', {xhr, status, error});
                const response = xhr.responseJSON;
                showDetectionMessage(response?.message || 'Error al detectar el equipo', 'danger');
                $('#submitWithoutCheckBtn').show();
                $('#retryMessage').show();
            }
        });
    });

    // Función para detectar host por IP
    $('#detectIpBtn').on('click', function() {
        console.log('Botón detectIpBtn clickeado');
        const ip = $('#ip_address').val();
        const hostname = $('#hostname_fija').val();
        
        if (!ip) {
            showDetectionMessage('Por favor, introduce una dirección IP', 'danger');
            return;
        }

        showDetectionMessage('Detectando equipo...', 'info');
        $('#detectionResult').show();
        $('#submitBtn').hide();
        $('#submitWithoutCheckBtn').hide();
        $('#retryMessage').hide();

        $.ajax({
            url: "{{ route('monitor.detect-host') }}",
            type: 'POST',
            data: {
                ip_address: ip,
                hostname: hostname,
                tipo: 'fija',
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log('Respuesta exitosa:', response);
                if (response.success) {
                    $('#mac_address').val(response.data.mac_address || 'No detectada');
                    $('#ip_address_display').val(response.data.ip_address || 'No detectada');
                    $('#ip_address').val(response.data.ip_address || ip);
                    if (response.data.hostname) {
                        $('#hostname_fija').val(response.data.hostname);
                    }
                    showDetectionMessage('Equipo detectado correctamente', 'success');
                    $('#submitBtn').show();
                    $('#retryMessage').show();
                } else {
                    showDetectionMessage(response.message, 'danger');
                    $('#submitWithoutCheckBtn').show();
                    $('#retryMessage').show();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la petición AJAX:', {xhr, status, error});
                const response = xhr.responseJSON;
                showDetectionMessage(response?.message || 'Error al detectar el equipo', 'danger');
                $('#submitWithoutCheckBtn').show();
                $('#retryMessage').show();
            }
        });
    });

    // Validar formulario antes de enviar
    $('#createHostForm').on('submit', function(e) {
        // Si el botón "Guardar Equipo" está visible, permitir guardar
        if ($('#submitBtn').is(':visible')) {
            return true;
        }
        
        // Si estamos usando "Guardar Sin Comprobar", permitir guardar
        if ($(e.target.activeElement).attr('id') === 'submitWithoutCheckBtn') {
            return true;
        }
        
        // En cualquier otro caso, mostrar error
        e.preventDefault();
        showDetectionMessage('Por favor, detecta el equipo antes de guardar', 'danger');
        return false;
    });

    // Verificar que los botones existen
    console.log('Botón detectHostBtn existe:', $('#detectHostBtn').length > 0);
    console.log('Botón detectIpBtn existe:', $('#detectIpBtn').length > 0);
});
</script>
@endpush

