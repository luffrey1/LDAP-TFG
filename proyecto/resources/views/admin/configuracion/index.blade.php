@extends('layouts.dashboard')

@section('title', 'Configuración del Sistema')

@section('styles')
<style>
body, .container-fluid {
    background: #181c24 !important;
}
.card-configuracion {
    box-shadow: 0 4px 24px rgba(25,118,237,0.08), 0 1.5px 4px rgba(25,118,237,0.03);
    border-radius: 0.7rem;
    margin-bottom: 20px;
    transition: all 0.3s;
    border: none;
}
.card-header h6 {
    color: #fff !important;
    font-size: 1.18rem;
    font-weight: 800 !important;
    letter-spacing: 0.01em;
}
.card-body {
    background: transparent !important;
    padding: 1rem;
}
.modulo-card {
    color: #fff !important;
    border: 1.5px solid #dbeafe;
    border-radius: 10px;
    margin-bottom: 10px;
    transition: all 0.2s;
    box-shadow: 0 1px 4px rgba(25,118,237,0.04);
    padding: 0.75rem 1rem;
}
.modulo-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(25,118,237,0.08);
}
.modulo-titulo {
    color: #fff !important;
    font-size: 1.1rem;
    font-weight: 700 !important;
    letter-spacing: 0.01em;
    margin-bottom: 0.25rem;
}
.modulo-descripcion {
    color: rgba(255, 255, 255, 0.8) !important;
    font-size: 0.9rem !important;
    margin-bottom: 0.5rem;
}
.form-check-input:checked {
    background-color: #4e73df !important;
    border-color: #4e73df !important;
}
input.form-control, select.form-control {
    color: #222 !important;
    border: 1.5px solid #b3c6e6 !important;
    border-radius: 0.4rem !important;
    font-size: 1rem !important;
    font-weight: 500;
    box-shadow: none !important;
    transition: border-color 0.2s;
}
.alert {
    font-size: 1.08rem;
    border-radius: 0.5rem;
    border: none;
}
.btn-primary {
    padding: 0.75rem 2rem;
    font-size: 1.1rem;
    font-weight: 600;
    letter-spacing: 0.01em;
}
.panel-descripcion {
    color: rgba(255, 255, 255, 0.8) !important;
    font-size: 0.9rem !important;
    margin-top: 0.25rem !important;
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Encabezado de página -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Configuración del Sistema</h1>
    </div>

    <!-- Mensajes de estado -->
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

    <!-- Nueva contraseña de VPN generada -->
    @if(session('vpn_password'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <strong>¡Nueva contraseña de VPN generada!</strong>
        <p class="mt-2 mb-0">Contraseña: <span class="badge badge-vpn">{{ session('vpn_password') }}</span></p>
        <p class="mt-2 mb-0"><small>Guárdala en un lugar seguro, no la volverás a ver.</small></p>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    <form action="{{ route('admin.configuracion.guardar') }}" method="POST">
        @csrf
        <div class="row">
            <!-- Configuración de Módulos -->
            <div class="col-lg-6">
                <div class="card shadow card-configuracion">
                    <div class="card-header py-2">
                        <h6 class="m-0 font-weight-bold text-primary">Módulos del Sistema</h6>
                        <p class="panel-descripcion mb-0">Activa o desactiva los módulos del sistema</p>
                    </div>
                    <div class="card-body">
                        <div class="modulo-card">
                            <div class="modulo-titulo">Calendario</div>
                            <div class="modulo-descripcion">Permite gestionar eventos y reuniones del centro</div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_calendario_activo" name="modulos[]" value="modulo_calendario_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_calendario_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_calendario_activo')->first()->valor == 'true') checked @endif>
                            </div>
                        </div>
                        
                        <div class="modulo-card">
                            <div class="modulo-titulo">Mensajería</div>
                            <div class="modulo-descripcion">Sistema de mensajería interna entre usuarios</div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_mensajeria_activo" name="modulos[]" value="modulo_mensajeria_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_mensajeria_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_mensajeria_activo')->first()->valor == 'true') checked @endif>
                            </div>
                        </div>
                        
                        <div class="modulo-card">
                            <div class="modulo-titulo">Documentos</div>
                            <div class="modulo-descripcion">Gestión de documentos compartidos</div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_documentos_activo" name="modulos[]" value="modulo_documentos_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_documentos_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_documentos_activo')->first()->valor == 'true') checked @endif>
                            </div>
                        </div>
                        
                        <div class="modulo-card">
                            <div class="modulo-titulo">Monitor de Equipos</div>
                            <div class="modulo-descripcion">Monitoreo y gestión de equipos por aulas</div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_monitoreo_activo" name="modulos[]" value="modulo_monitoreo_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_monitoreo_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_monitoreo_activo')->first()->valor == 'true') checked @endif>
                            </div>
                        </div>

                        <div class="modulo-card">
                            <div class="modulo-titulo">Acceso SSH</div>
                            <div class="modulo-descripcion">Gestión de acceso SSH a equipos</div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_ssh_activo" name="modulos[]" value="modulo_ssh_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_ssh_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_ssh_activo')->first()->valor == 'true') checked @endif>
                            </div>
                        </div>

                        <div class="modulo-card">
                            <div class="modulo-titulo">Gestión de Clases</div>
                            <div class="modulo-descripcion">Activa o desactiva el módulo de gestión de clases</div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_clases_activo" name="modulos[]" value="modulo_clases_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_clases_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_clases_activo')->first()->valor == 'true') checked @endif>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Telemetría de Equipos -->
            <div class="col-lg-6">
                <div class="card shadow card-configuracion">
                    <div class="card-header py-2">
                        <h6 class="m-0 font-weight-bold text-primary">Telemetría de Equipos</h6>
                        <p class="panel-descripcion mb-0">Configura cada cuánto tiempo los agentes deben enviar datos automáticamente (en minutos). Si usas modo bajo demanda, este valor es solo informativo.</p>
                    </div>
                    <div class="card-body">
                        <div class="modulo-card">
                            <label for="telemetria_intervalo_minutos" class="form-label text-white">Intervalo de telemetría (minutos)</label>
                            <input type="number" class="form-control" id="telemetria_intervalo_minutos" name="telemetria_intervalo_minutos" min="1" max="1440" 
                                value="{{ isset($configuraciones['general']) && $configuraciones['general']->where('clave', 'telemetria_intervalo_minutos')->first() ? $configuraciones['general']->where('clave', 'telemetria_intervalo_minutos')->first()->valor : 60 }}">
                            <small class="form-text text-white-50">Ejemplo: 60 = cada hora, 10 = cada 10 minutos, 1440 = una vez al día.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botón de guardar -->
        <div class="text-center mb-4">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save mr-2"></i>Guardar Configuración
            </button>
        </div>
    </form>
</div>
@endsection 