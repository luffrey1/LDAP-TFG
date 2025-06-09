@extends('layouts.dashboard')

@section('title', 'Configuración del Sistema')

@section('styles')
<style>
body, .container-fluid {
    background: #181c24 !important;
}
.card-configuracion {
    background: #f4f7fb !important;
    box-shadow: 0 4px 24px rgba(25,118,237,0.08), 0 1.5px 4px rgba(25,118,237,0.03);
    border-radius: 0.7rem;
    margin-bottom: 30px;
    transition: all 0.3s;
    border: none;
}
.card-header {
    background: #1976ed !important;
    color: #fff !important;
    border-radius: 0.7rem 0.7rem 0 0;
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
}
.modulo-card, .seguridad-item {
    background: #fafdff !important;
    color: #222 !important;
    border: 1.5px solid #dbeafe;
    border-radius: 10px;
    margin-bottom: 15px;
    transition: all 0.2s;
    box-shadow: 0 1px 4px rgba(25,118,237,0.04);
}
.modulo-card:hover, .seguridad-item:hover {
    background: #e3edfa !important;
    border-color: #1976ed;
}
.modulo-titulo, .form-label, h6.text-gray-800 {
    color: #1976ed !important;
    font-size: 1.13rem;
    font-weight: 700 !important;
    letter-spacing: 0.01em;
}
.panel-descripcion, .modulo-descripcion, .form-text, .form-check-label, small {
    color: #444 !important;
    font-size: 1rem !important;
    opacity: 0.92;
}
input.form-control, select.form-control, .form-check-input {
    background: #f4f7fb !important;
    color: #222 !important;
    border: 1.5px solid #b3c6e6 !important;
    border-radius: 0.4rem !important;
    font-size: 1.08rem !important;
    font-weight: 500;
    box-shadow: none !important;
    transition: border-color 0.2s;
}
input.form-control:focus, select.form-control:focus {
    border-color: #1976ed !important;
    box-shadow: 0 0 0 2px #1976ed22 !important;
}
.form-check-input:checked {
    background-color: #1976ed !important;
    border-color: #1976ed !important;
}
.btn-primary, .btn-secondary {
    font-size: 1.08rem;
    font-weight: 700;
    border-radius: 0.4rem;
    background: #1976ed !important;
    border: none;
    color: #fff !important;
    transition: background 0.2s;
}
.btn-primary:hover, .btn-secondary:hover {
    background: #1251a3 !important;
}
.alert {
    font-size: 1.08rem;
    border-radius: 0.5rem;
    border: none;
}
.badge-vpn {
    background: #e3edfa !important;
    color: #1976ed !important;
    border: 1.5px dashed #1976ed !important;
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
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Módulos del Sistema</h6>
                        <p class="panel-descripcion mt-1 mb-0 text-white">Activa o desactiva los módulos del sistema</p>
                    </div>
                    <div class="card-body">
                        <div class="modulo-card text-white">
                            <div class="modulo-titulo">Calendario</div>
                            <div class="modulo-descripcion">Permite gestionar eventos y reuniones del centro</div>
                            <div class="form-check form-switch modulo-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_calendario_activo" name="modulos[]" value="modulo_calendario_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_calendario_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_calendario_activo')->first()->valor == 'true') checked @endif>
                            </div>
                        </div>
                        
                        <div class="modulo-card text-white">
                            <div class="modulo-titulo">Mensajería</div>
                            <div class="modulo-descripcion">Sistema de mensajería interna entre usuarios</div>
                            <div class="form-check form-switch modulo-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_mensajeria_activo" name="modulos[]" value="modulo_mensajeria_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_mensajeria_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_mensajeria_activo')->first()->valor == 'true') checked @endif>
                            </div>
                        </div>
                        
                        <div class="modulo-card text-white">
                            <div class="modulo-titulo">Documentos</div>
                            <div class="modulo-descripcion">Gestión de documentos compartidos</div>
                            <div class="form-check form-switch modulo-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_documentos_activo" name="modulos[]" value="modulo_documentos_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_documentos_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_documentos_activo')->first()->valor == 'true') checked @endif>
                            </div>
                        </div>
                        
                        <div class="modulo-card text-white">
                            <div class="modulo-titulo">Monitor de Equipos</div>
                            <div class="modulo-descripcion">Monitoreo y gestión de equipos por aulas</div>
                            <div class="form-check form-switch modulo-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_monitoreo_activo" name="modulos[]" value="modulo_monitoreo_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_monitoreo_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_monitoreo_activo')->first()->valor == 'true') checked @endif>
                            </div>
                        </div>

                        <div class="modulo-card text-white">
                            <div class="modulo-titulo">Acceso SSH</div>
                            <div class="modulo-descripcion">Gestión de acceso SSH a equipos</div>
                            <div class="form-check form-switch modulo-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_ssh_activo" name="modulos[]" value="modulo_ssh_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_ssh_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_ssh_activo')->first()->valor == 'true') checked @endif>
                            </div>
                        </div>

                        <div class="modulo-card text-white">
                            <div class="modulo-titulo">Gestión de Clases</div>
                            <div class="modulo-descripcion">Activa o desactiva el módulo de gestión de clases</div>
                            <div class="form-check form-switch modulo-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_clases_activo" name="modulos[]" value="modulo_clases_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_clases_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_clases_activo')->first()->valor == 'true') checked @endif>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuración de Seguridad -->
            <div class="col-lg-6">
                <div class="card shadow card-configuracion">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Políticas de Seguridad</h6>
                        <p class="panel-descripcion mt-1 mb-0">Configura los requisitos de seguridad del sistema</p>
                    </div>
                    <div class="card-body">
                        <h6 class="text-gray-800 mb-3">Requisitos de Contraseñas</h6>
                        
                        <div class="seguridad-item">
                            <label for="politica_password_longitud" class="form-label">Longitud mínima</label>
                            <input type="number" class="form-control" id="politica_password_longitud" name="politica_password_longitud" min="6" max="20" 
                                value="{{ isset($configuraciones['seguridad']) ? $configuraciones['seguridad']->where('clave', 'politica_password_longitud')->first()->valor : '8' }}">
                            <small class="form-text text-muted">Número mínimo de caracteres requeridos</small>
                        </div>
                        
                        <div class="seguridad-item">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="politica_password_mayusculas" name="politica_password_mayusculas" value="1"
                                    @if(isset($configuraciones['seguridad']) && $configuraciones['seguridad']->where('clave', 'politica_password_mayusculas')->first() && 
                                    $configuraciones['seguridad']->where('clave', 'politica_password_mayusculas')->first()->valor == 'true') checked @endif>
                                <label class="form-check-label" for="politica_password_mayusculas">Requerir letras mayúsculas</label>
                            </div>
                            <small class="form-text text-muted">Al menos una letra mayúscula (A-Z)</small>
                        </div>
                        
                        <div class="seguridad-item">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="politica_password_numeros" name="politica_password_numeros" value="1"
                                    @if(isset($configuraciones['seguridad']) && $configuraciones['seguridad']->where('clave', 'politica_password_numeros')->first() && 
                                    $configuraciones['seguridad']->where('clave', 'politica_password_numeros')->first()->valor == 'true') checked @endif>
                                <label class="form-check-label" for="politica_password_numeros">Requerir números</label>
                            </div>
                            <small class="form-text text-muted">Al menos un número (0-9)</small>
                        </div>
                        
                        <div class="seguridad-item">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="politica_password_especiales" name="politica_password_especiales" value="1"
                                    @if(isset($configuraciones['seguridad']) && $configuraciones['seguridad']->where('clave', 'politica_password_especiales')->first() && 
                                    $configuraciones['seguridad']->where('clave', 'politica_password_especiales')->first()->valor == 'true') checked @endif>
                                <label class="form-check-label" for="politica_password_especiales">Requerir caracteres especiales</label>
                            </div>
                            <small class="form-text text-muted">Al menos un carácter especial (!@#$%^&*)</small>
                        </div>
                        
                        <h6 class="text-gray-800 mb-3 mt-4">Configuración de VPN</h6>
                        <div class="d-grid">
                            <button type="submit" name="generar_vpn_password" value="1" class="btn btn-secondary">
                                <i class="fas fa-key mr-2"></i>Generar Nueva Contraseña VPN
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuración de Telemetría -->
            <div class="col-lg-6">
                <div class="card shadow card-configuracion">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Telemetría de Equipos</h6>
                        <p class="panel-descripcion mt-1 mb-0">Configura cada cuánto tiempo los agentes deben enviar datos automáticamente (en minutos). Si usas modo bajo demanda, este valor es solo informativo.</p>
                    </div>
                    <div class="card-body">
                        <div class="seguridad-item">
                            <label for="telemetria_intervalo_minutos" class="form-label text-white">Intervalo de telemetría (minutos)</label>
                            <input type="number" class="form-control" id="telemetria_intervalo_minutos" name="telemetria_intervalo_minutos" min="1" max="1440" value="{{ isset($configuraciones['general']) && $configuraciones['general']->where('clave', 'telemetria_intervalo_minutos')->first() ? $configuraciones['general']->where('clave', 'telemetria_intervalo_minutos')->first()->valor : 60 }}">
                            <small class="form-text text-white">Ejemplo: 60 = cada hora, 10 = cada 10 minutos, 1440 = una vez al día.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mb-5">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save mr-2"></i>Guardar Configuración
            </button>
        </div>
    </form>
</div>
@endsection 