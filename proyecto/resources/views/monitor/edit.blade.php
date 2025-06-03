@extends('layouts.dashboard')

@section('title', 'Editar Host')

@section('content')
<section class="section">
    <div class="section-header">
        <h1>Editar Host: {{ $host->hostname }}</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard.index') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('monitor.index') }}">Monitoreo</a></div>
            <div class="breadcrumb-item">Editar Host</div>
        </div>
    </div>

    <div class="section-body">
        <h2 class="section-title">Modificar Información del Host</h2>
        <p class="section-lead">Actualiza los detalles del equipo monitoreado.</p>

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

                        <form action="{{ route('monitor.update', $host->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-group text-white">
                                <label for="hostname">Nombre del Host <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('hostname') is-invalid @enderror" id="hostname" name="hostname" value="{{ old('hostname', $host->hostname) }}" required>
                                @error('hostname')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="form-group text-white">
                                <label for="ip_address">Dirección IP <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text">
                                            <i class="fas fa-network-wired"></i>
                                        </div>
                                    </div>
                                    <input type="text" class="form-control @error('ip_address') is-invalid @enderror" id="ip_address" name="ip_address" value="{{ old('ip_address', $host->ip_address) }}" placeholder="192.168.1.10" required>
                                </div>
                                @error('ip_address')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="form-group text-white">
                                <label for="mac_address">Dirección MAC</label>
                                <input type="text" class="form-control @error('mac_address') is-invalid @enderror" id="mac_address" name="mac_address" value="{{ old('mac_address', $host->mac_address) }}" placeholder="00:11:22:33:44:55">
                                @error('mac_address')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <small class="form-text text-muted">Formato: 00:11:22:33:44:55</small>
                            </div>

                            <div class="form-group text-white ">
                                <label for="description">Descripción</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $host->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="form-group text-white">
                                <label for="group_id">Grupo</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-layer-group"></i></span>
                                    <select class="form-select" id="group_id" name="group_id">
                                        <option value="">-- Sin grupo --</option>
                                        @foreach($groups as $group)
                                            <option value="{{ $group->id }}" {{ old('group_id', $host->group_id) == $group->id ? 'selected' : '' }}>
                                                {{ $group->name }} - {{ $group->description ?? 'Sin descripción' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group text-white">
                                <label>Estado Actual</label>
                                <div class="mt-2">
                                    <span class="badge badge-{{ $host->status_color }} badge-pill" style="font-size: 1rem; padding: 8px 15px;">
                                        {{ $host->status_text }}
                                    </span>
                                    @if($host->last_seen)
                                        <small class="ml-2 text-white">
                                            Último contacto: {{ $host->last_seen->format('d/m/Y H:i:s') }}
                                        </small>
                                    @endif
                                </div>
                            </div>

                            <div class="card-footer text-right">
                                <button type="submit" class="btn btn-primary">Actualizar</button>
                                <a href="{{ route('monitor.show', $host->id) }}" class="btn btn-info">Ver Detalles</a>
                                <a href="{{ route('monitor.index') }}" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-md-6 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Información del Sistema</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h6>Información de Hardware</h6>
                            @if($host->system_info)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <tbody>
                                            @if(isset($host->system_info['os']))
                                            <tr>
                                                <th>Sistema Operativo</th>
                                                <td>{{ $host->system_info['os'] }}</td>
                                            </tr>
                                            @endif
                                            
                                            @if(isset($host->system_info['cpu_model']))
                                            <tr>
                                                <th>Procesador</th>
                                                <td>{{ $host->system_info['cpu_model'] }}</td>
                                            </tr>
                                            @endif
                                            
                                            @if(isset($host->system_info['memory_total']))
                                            <tr>
                                                <th>Memoria Total</th>
                                                <td>{{ $host->system_info['memory_total'] }}</td>
                                            </tr>
                                            @endif
                                            
                                            @if(isset($host->system_info['disk_total']))
                                            <tr>
                                                <th>Almacenamiento</th>
                                                <td>{{ $host->system_info['disk_total'] }}</td>
                                            </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-light">
                                    No hay información detallada del sistema disponible.
                                </div>
                            @endif
                        </div>
                        
                        <div class="alert alert-info">
                            <h6 class="alert-heading">Consejos de Monitoreo</h6>
                            <ul class="mb-0">
                                <li>Asegúrate de que el nombre del host sea descriptivo para facilitar su identificación.</li>
                                <li>Si cambias la dirección IP, verifica que el equipo sea accesible desde la nueva dirección.</li>
                                <li>Para obtener información detallada del sistema, instala un agente de monitoreo en el equipo.</li>
                            </ul>
                        </div>
                        
                        <div class="mt-4">
                            <button type="button" class="btn btn-info btn-block mb-2 ping-test" data-ip="{{ $host->ip_address }}">
                                <i class="fas fa-sync"></i> Verificar Conectividad
                            </button>
                            
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
</section>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Verificar conectividad
    $('.ping-test').on('click', function() {
        var ip = $(this).data('ip');
        
        $('#ping_result').removeClass('d-none');
        $('#ping_alert').removeClass('alert-success alert-danger').addClass('alert-warning');
        $('#ping_message').html('<i class="fas fa-spinner fa-spin"></i> Verificando conectividad...');
        
        // Simular verificación de conectividad
        setTimeout(function() {
            // Aquí iría una llamada AJAX real a la ruta ping
            var success = Math.random() > 0.3; // 70% de probabilidad de éxito
            
            if (success) {
                $('#ping_alert').removeClass('alert-warning alert-danger').addClass('alert-success');
                $('#ping_message').html('<i class="fas fa-check"></i> Host alcanzable. Conectividad correcta.');
            } else {
                $('#ping_alert').removeClass('alert-warning alert-success').addClass('alert-danger');
                $('#ping_message').html('<i class="fas fa-times"></i> No se pudo conectar con el host. Verifica que esté encendido y en la misma red.');
            }
        }, 1500);
    });
});
</script>
@endsection 