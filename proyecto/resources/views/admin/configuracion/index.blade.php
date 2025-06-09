@extends('layouts.dashboard')

@section('title', 'Configuración del Sistema')

@section('styles')
<style>
.card-configuracion {
    background: #181c24 !important;
    box-shadow: 0 4px 24px rgba(25,118,237,0.08), 0 1.5px 4px rgba(25,118,237,0.03);
    border-radius: 0.7rem;
    margin-bottom: 30px;
    transition: all 0.3s;
    border: none;
}
.card-header {
    background: #181c24 !important;
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
.modulo-card {
    background: #181c24 !important;
    color: #fff !important;
    border: 1.5px solid #2a2f3a;
    border-radius: 10px;
    margin-bottom: 15px;
    transition: all 0.2s;
    box-shadow: 0 1px 4px rgba(25,118,237,0.04);
}
.modulo-card:hover {
    border-color: #2a2f3a;
}
.modulo-titulo {
    color: #fff !important;
    font-size: 1.13rem;
    font-weight: 700 !important;
    letter-spacing: 0.01em;
}
.modulo-descripcion {
    color: #fff !important;
    font-size: 1rem !important;
    opacity: 0.92;
}
.form-check-input:checked {
    background-color: #4e73df !important;
    border-color: #4e73df !important;
}
.alert {
    font-size: 1.08rem;
    border-radius: 0.5rem;
    border: none;
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
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Módulos del Sistema</h6>
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

            <!-- Telemetría de Equipos -->
            <div class="col-lg-6">
                <div class="card shadow card-configuracion">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Telemetría de Equipos</h6>
                    </div>
                    <div class="card-body">
                        <div class="modulo-card text-white">
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

                        <div class="modulo-card text-white">
                            <div class="modulo-titulo">Notificaciones</div>
                            <div class="modulo-descripcion">Activar notificaciones de estado de equipos</div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="telemetria_notificaciones" name="telemetria_notificaciones" value="1"
                                    @if(isset($configuraciones['telemetria']) && $configuraciones['telemetria']->where('clave', 'telemetria_notificaciones')->first() && 
                                    $configuraciones['telemetria']->where('clave', 'telemetria_notificaciones')->first()->valor == 'true') checked @endif>
                            </div>
                        </div>

                        <div class="modulo-card text-white">
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
            <div class="col-12 text-center">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>Guardar Cambios
                </button>
            </div>
        </div>
    </form>
</div>
@endsection 