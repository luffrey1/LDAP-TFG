@extends('layouts.dashboard')

@section('title', 'Configuración del Sistema')

@section('styles')
<style>
    .card-configuracion {
        margin-bottom: 30px;
        transition: all 0.3s;
    }
    .card-configuracion:hover {
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
    .form-check-input {
        height: 1.2rem;
        width: 2.4rem;
    }
    .form-switch .form-check-input {
        margin-left: 0;
    }
    .panel-descripcion {
        font-size: 0.9rem;
        color: #6c757d;
    }
    .badge-vpn {
        font-family: monospace;
        font-size: 1rem;
        padding: 10px;
        letter-spacing: 1px;
        background-color: #f8f9fa;
        border: 1px dashed #ced4da;
    }
    .seguridad-item {
        padding: 10px;
        border: 1px solid #f1f1f1;
        border-radius: 8px;
        margin-bottom: 15px;
        transition: all 0.2s;
    }
    .seguridad-item:hover {
        background-color: #f8f9fa;
    }
    .modulo-card {
        padding: 15px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        margin-bottom: 15px;
        position: relative;
        transition: all 0.2s;
    }
    .modulo-card:hover {
        background-color: #f8f9fa;
    }
    .modulo-titulo {
        font-weight: 600;
        margin-bottom: 5px;
    }
    .modulo-descripcion {
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 15px;
    }
    .modulo-switch {
        position: absolute;
        top: 15px;
        right: 15px;
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
                        <p class="panel-descripcion mt-1 mb-0">Activa o desactiva los módulos del sistema</p>
                    </div>
                    <div class="card-body">
                        <div class="modulo-card">
                            <div class="modulo-titulo">Calendario</div>
                            <div class="modulo-descripcion">Permite gestionar eventos y reuniones del centro</div>
                            <div class="form-check form-switch modulo-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_calendario_activo" name="modulos[]" value="modulo_calendario_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_calendario_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_calendario_activo')->first()->valor == 'true') checked @endif>
                            </div>
                        </div>
                        
                        <div class="modulo-card">
                            <div class="modulo-titulo">Mensajería</div>
                            <div class="modulo-descripcion">Sistema de mensajería interna entre usuarios</div>
                            <div class="form-check form-switch modulo-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_mensajeria_activo" name="modulos[]" value="modulo_mensajeria_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_mensajeria_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_mensajeria_activo')->first()->valor == 'true') checked @endif>
                            </div>
                        </div>
                        
                        <div class="modulo-card">
                            <div class="modulo-titulo">Documentos</div>
                            <div class="modulo-descripcion">Gestión de documentos compartidos</div>
                            <div class="form-check form-switch modulo-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_documentos_activo" name="modulos[]" value="modulo_documentos_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_documentos_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_documentos_activo')->first()->valor == 'true') checked @endif>
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
                        
                        <div class="seguridad-item">
                            <label for="dias_aviso_password" class="form-label">Días de aviso</label>
                            <input type="number" class="form-control" id="dias_aviso_password" name="dias_aviso_password" min="1" max="30" 
                                value="{{ isset($configuraciones['general']) ? $configuraciones['general']->where('clave', 'dias_aviso_password')->first()->valor : '7' }}">
                            <small class="form-text text-muted">Días de aviso antes de forzar cambio de contraseña</small>
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
        </div>
        
        <div class="text-center mb-5">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save mr-2"></i>Guardar Configuración
            </button>
        </div>
    </form>
</div>
@endsection 