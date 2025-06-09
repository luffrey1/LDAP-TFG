@extends('layouts.dashboard')

@section('title', 'Configuración del Sistema')

@section('styles')
<style>
.card-configuracion {
    background: #fff !important;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
    border-radius: 0.35rem;
    margin-bottom: 1.5rem;
    border: none;
}
.card-header {
    background: #4e73df !important;
    color: #fff !important;
    border-radius: 0.35rem 0.35rem 0 0;
    border: none;
    padding: 1rem 1.25rem;
}
.card-header h6 {
    color: #fff !important;
    font-size: 1rem;
    font-weight: 700 !important;
    margin: 0;
}
.card-body {
    padding: 1.25rem;
}
.modulo-card {
    background: #fff !important;
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
    margin-bottom: 1rem;
    padding: 1rem;
    transition: all 0.2s;
}
.modulo-card:hover {
    border-color: #4e73df;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
}
.modulo-titulo {
    color: #5a5c69 !important;
    font-size: 1rem;
    font-weight: 700 !important;
    margin-bottom: 0.5rem;
}
.modulo-descripcion {
    color: #858796 !important;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}
.form-check-input:checked {
    background-color: #4e73df !important;
    border-color: #4e73df !important;
}
.btn-primary {
    background-color: #4e73df !important;
    border-color: #4e73df !important;
}
.btn-primary:hover {
    background-color: #2e59d9 !important;
    border-color: #2653d4 !important;
}
.alert {
    border-radius: 0.35rem;
    margin-bottom: 1.5rem;
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

    <form action="{{ route('admin.configuracion.guardar') }}" method="POST">
        @csrf
        <div class="row">
            <!-- Configuración de Módulos -->
            <div class="col-lg-6">
                <div class="card shadow card-configuracion">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">Módulos del Sistema</h6>
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
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">Telemetría de Equipos</h6>
                    </div>
                    <div class="card-body">
                        <div class="modulo-card">
                            <div class="modulo-titulo">Intervalo de Monitoreo</div>
                            <div class="modulo-descripcion">Tiempo entre cada verificación de estado de los equipos</div>
                            <select class="form-control" name="telemetria_intervalo">
                                <option value="30" @if(isset($configuraciones['telemetria']) && $configuraciones['telemetria']->where('clave', 'telemetria_intervalo')->first() && 
                                    $configuraciones['telemetria']->where('clave', 'telemetria_intervalo')->first()->valor == '30') selected @endif>30 segundos</option>
                                <option value="60" @if(isset($configuraciones['telemetria']) && $configuraciones['telemetria']->where('clave', 'telemetria_intervalo')->first() && 
                                    $configuraciones['telemetria']->where('clave', 'telemetria_intervalo')->first()->valor == '60') selected @endif>1 minuto</option>
                                <option value="300" @if(isset($configuraciones['telemetria']) && $configuraciones['telemetria']->where('clave', 'telemetria_intervalo')->first() && 
                                    $configuraciones['telemetria']->where('clave', 'telemetria_intervalo')->first()->valor == '300') selected @endif>5 minutos</option>
                            </select>
                        </div>

                        <div class="modulo-card">
                            <div class="modulo-titulo">Notificaciones</div>
                            <div class="modulo-descripcion">Activar notificaciones de estado de equipos</div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="telemetria_notificaciones" name="telemetria_notificaciones" value="1"
                                    @if(isset($configuraciones['telemetria']) && $configuraciones['telemetria']->where('clave', 'telemetria_notificaciones')->first() && 
                                    $configuraciones['telemetria']->where('clave', 'telemetria_notificaciones')->first()->valor == 'true') checked @endif>
                            </div>
                        </div>

                        <div class="modulo-card">
                            <div class="modulo-titulo">Registro de Eventos</div>
                            <div class="modulo-descripcion">Mantener un historial de eventos de los equipos</div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="telemetria_registro" name="telemetria_registro" value="1"
                                    @if(isset($configuraciones['telemetria']) && $configuraciones['telemetria']->where('clave', 'telemetria_registro')->first() && 
                                    $configuraciones['telemetria']->where('clave', 'telemetria_registro')->first()->valor == 'true') checked @endif>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botón de guardar -->
        <div class="row mt-4">
            <div class="col-12 text-right">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>Guardar Cambios
                </button>
            </div>
        </div>
    </form>
</div>
@endsection 