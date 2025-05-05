@extends('layouts.dashboard')

@section('title', 'Añadir Equipo')

@section('content')
<div class="container-fluid">
    <!-- Encabezado de página -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Añadir Nuevo Equipo</h1>
        <a href="{{ route('dashboard.monitor') }}" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Volver al Monitor
        </a>
    </div>

    <!-- Tarjeta del formulario -->
    <div class="card shadow mb-4" data-aos="fade-up">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Información del Equipo</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('dashboard.monitor.store') }}" method="POST">
                @csrf
                
                @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <h5 class="alert-heading">Se encontraron errores</h5>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="hostname">Nombre del equipo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('hostname') is-invalid @enderror" id="hostname" name="hostname" value="{{ old('hostname') }}" required>
                            <small class="form-text text-muted">Nombre identificativo del equipo (ej. PC-Laboratorio1)</small>
                            @error('hostname')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ip_address">Dirección IP <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('ip_address') is-invalid @enderror" id="ip_address" name="ip_address" value="{{ old('ip_address') }}" placeholder="192.168.1.100" required>
                            <small class="form-text text-muted">Dirección IP del equipo en la red local</small>
                            @error('ip_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mac_address">Dirección MAC</label>
                            <input type="text" class="form-control @error('mac_address') is-invalid @enderror" id="mac_address" name="mac_address" value="{{ old('mac_address') }}" placeholder="00:1A:2B:3C:4D:5E">
                            <small class="form-text text-muted">Dirección MAC del equipo (opcional)</small>
                            @error('mac_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="group_id">Grupo</label>
                            <select class="form-control @error('group_id') is-invalid @enderror" id="group_id" name="group_id">
                                <option value="0" {{ old('group_id') == 0 ? 'selected' : '' }}>Sin grupo</option>
                                <option value="1" {{ old('group_id') == 1 ? 'selected' : '' }}>Laboratorio</option>
                                <option value="2" {{ old('group_id') == 2 ? 'selected' : '' }}>Administración</option>
                                <option value="3" {{ old('group_id') == 3 ? 'selected' : '' }}>Aulas</option>
                                <option value="4" {{ old('group_id') == 4 ? 'selected' : '' }}>Servidores</option>
                            </select>
                            <small class="form-text text-muted">Categoría o grupo al que pertenece el equipo</small>
                            @error('group_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Descripción</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    <small class="form-text text-muted">Información adicional sobre el equipo (ubicación, características, etc.)</small>
                    @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <hr>
                
                <div class="form-group text-center">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Guardar Equipo
                    </button>
                    <a href="{{ route('dashboard.monitor') }}" class="btn btn-secondary ml-2">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Información adicional -->
    <div class="card shadow mb-4" data-aos="fade-up" data-aos-delay="200">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Consideraciones</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-info-circle text-info mr-2"></i> Información Importante</h5>
                    <ul>
                        <li>Asegúrese de que la dirección IP sea correcta y accesible desde el servidor.</li>
                        <li>El nombre del equipo debe ser único y fácilmente identificable.</li>
                        <li>Los equipos se pueden agrupar para facilitar su gestión.</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5><i class="fas fa-lightbulb text-warning mr-2"></i> Recomendaciones</h5>
                    <ul>
                        <li>Utilice direcciones IP estáticas o reservas DHCP para evitar cambios de IP.</li>
                        <li>Incluya información sobre la ubicación física en la descripción.</li>
                        <li>Para monitoreo avanzado, considere instalar un agente en el equipo remoto.</li>
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
    // Validador de IP en tiempo real
    $('#ip_address').on('input', function() {
        const ipRegex = /^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
        const isValid = ipRegex.test($(this).val());
        
        if ($(this).val() && !isValid) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Por favor, introduzca una dirección IP válida.</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });
    
    // Validador de MAC en tiempo real
    $('#mac_address').on('input', function() {
        const macRegex = /^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/;
        const value = $(this).val();
        
        if (value && !macRegex.test(value)) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Formato de MAC inválido. Use XX:XX:XX:XX:XX:XX</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });
    
    // Autoformateo de MAC
    $('#mac_address').on('keyup', function() {
        let value = $(this).val().replace(/[^0-9a-f]/gi, '');
        let formatted = '';
        
        for (let i = 0; i < value.length && i < 12; i++) {
            if (i > 0 && i % 2 === 0) {
                formatted += ':';
            }
            formatted += value[i];
        }
        
        $(this).val(formatted.toUpperCase());
    });
});
</script>
@endsection 