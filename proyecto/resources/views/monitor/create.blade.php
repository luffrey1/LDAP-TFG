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
                                            <label for="tipo_host" class="form-label text-black">Tipo de Equipo</label>
                                            <select class="form-select" id="tipo_host" name="tipo_host" required>
                                                <option value="dhcp">DHCP (Automático)</option>
                                                <option value="fija">IP Fija</option>
                                            </select>
                                        </div>

                                        <div id="dhcpFields">
                                            <div class="mb-3">
                                                <label for="hostname" class="form-label text-black">Hostname</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="hostname" name="hostname" 
                                                           placeholder="Ej: B27-A1, B27-B2, etc." required>
                                                    <button type="button" class="btn btn-primary" id="detectHostBtn">
                                                        <i class="fas fa-search me-1"></i> Comprobar Host
                                                    </button>
                                                </div>
                                                <div class="form-text text-black">Escribe el hostname del equipo (ej: B27-A1) y haz clic en "Comprobar Host"</div>
                                            </div>
                                        </div>

                                        <div id="fijaFields" style="display: none;">
                                            <div class="mb-3">
                                                <label for="ip_address" class="form-label text-black">Dirección IP</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="ip_address" name="ip_address" 
                                                           placeholder="Ej: 172.20.200.100">
                                                    <button type="button" class="btn btn-primary" id="detectIpBtn">
                                                        <i class="fas fa-search me-1"></i> Comprobar IP
                                                    </button>
                                                </div>
                                                <div class="form-text text-black">Escribe la IP del equipo y haz clic en "Comprobar IP"</div>
                                            </div>
                                        </div>

                                        <div id="detectionResult" class="alert" style="display: none;">
                                            <div class="d-flex align-items-center">
                                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                                    <span class="visually-hidden">Cargando...</span>
                                                </div>
                                                <span id="detectionMessage">Detectando equipo...</span>
                                            </div>
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
                                            <label for="mac_address" class="form-label text-black">Dirección MAC</label>
                                            <input type="text" class="form-control" id="mac_address" name="mac_address" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label text-black">Descripción</label>
                                            <textarea class="form-control" id="description" name="description" rows="3" 
                                                      placeholder="Descripción opcional del equipo"></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label for="group_id" class="form-label text-black">Grupo</label>
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


<script>
$(document).ready(function() {
    // Configuración global de AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Cambiar campos según tipo de host
    $('#tipo_host').change(function() {
        if ($(this).val() === 'dhcp') {
            $('#dhcpFields').show();
            $('#fijaFields').hide();
            $('#ip_address').prop('required', false);
            $('#hostname').prop('required', true);
        } else {
            $('#dhcpFields').hide();
            $('#fijaFields').show();
            $('#ip_address').prop('required', true);
            $('#hostname').prop('required', false);
        }
        resetForm();
    });

    // Función para resetear el formulario
    function resetForm() {
        $('#mac_address').val('');
        $('#detectionResult').hide();
        $('#submitBtn').hide();
        $('#hostname').val('');
        $('#ip_address').val('');
    }

    // Función para mostrar mensaje de detección
    function showDetectionMessage(message, type = 'info') {
        $('#detectionResult')
            .removeClass('alert-info alert-success alert-danger')
            .addClass('alert-' + type)
            .show();
        $('#detectionMessage').html(message);
    }

    // Función para detectar host por hostname
    $('#detectHostBtn').click(function() {
        const hostname = $('#hostname').val();
        if (!hostname) {
            showDetectionMessage('Por favor, introduce un hostname', 'danger');
            return;
        }

        showDetectionMessage('Detectando equipo...', 'info');
        $('#detectionResult').show();
        $('#submitBtn').hide();

        $.ajax({
            url: "{{ route('monitor.detect-host') }}",
            type: 'POST',
            data: {
                hostname: hostname,
                tipo: 'dhcp'
            },
            success: function(response) {
                if (response.success) {
                    $('#mac_address').val(response.data.mac_address);
                    $('#ip_address').val(response.data.ip_address);
                    showDetectionMessage('Equipo detectado correctamente', 'success');
                    $('#submitBtn').show();
                } else {
                    showDetectionMessage(response.message, 'danger');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                showDetectionMessage(response?.message || 'Error al detectar el equipo', 'danger');
            }
        });
    });

    // Función para detectar host por IP
    $('#detectIpBtn').click(function() {
        const ip = $('#ip_address').val();
        if (!ip) {
            showDetectionMessage('Por favor, introduce una dirección IP', 'danger');
            return;
        }

        showDetectionMessage('Detectando equipo...', 'info');
        $('#detectionResult').show();
        $('#submitBtn').hide();

        $.ajax({
            url: "{{ route('monitor.detect-host') }}",
            type: 'POST',
            data: {
                ip_address: ip,
                tipo: 'fija'
            },
            success: function(response) {
                if (response.success) {
                    $('#mac_address').val(response.data.mac_address);
                    $('#hostname').val(response.data.hostname);
                    showDetectionMessage('Equipo detectado correctamente', 'success');
                    $('#submitBtn').show();
                } else {
                    showDetectionMessage(response.message, 'danger');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                showDetectionMessage(response?.message || 'Error al detectar el equipo', 'danger');
            }
        });
    });

    // Validar formulario antes de enviar
    $('#createHostForm').submit(function(e) {
        if (!$('#mac_address').val()) {
            e.preventDefault();
            showDetectionMessage('Por favor, detecta el equipo antes de guardar', 'danger');
            return false;
        }
    });
});
</script>

