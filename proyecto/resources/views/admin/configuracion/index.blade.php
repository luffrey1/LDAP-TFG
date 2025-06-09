@extends('layouts.dashboard')

@section('title', 'Configuración del Sistema')

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
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold">Módulos del Sistema</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_calendario_activo" name="modulos[]" value="modulo_calendario_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_calendario_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_calendario_activo')->first()->valor == 'true') checked @endif>
                                <label class="form-check-label" for="modulo_calendario_activo">Calendario</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_mensajeria_activo" name="modulos[]" value="modulo_mensajeria_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_mensajeria_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_mensajeria_activo')->first()->valor == 'true') checked @endif>
                                <label class="form-check-label" for="modulo_mensajeria_activo">Mensajería</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_documentos_activo" name="modulos[]" value="modulo_documentos_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_documentos_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_documentos_activo')->first()->valor == 'true') checked @endif>
                                <label class="form-check-label" for="modulo_documentos_activo">Documentos</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_monitoreo_activo" name="modulos[]" value="modulo_monitoreo_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_monitoreo_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_monitoreo_activo')->first()->valor == 'true') checked @endif>
                                <label class="form-check-label" for="modulo_monitoreo_activo">Monitor de Equipos</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_ssh_activo" name="modulos[]" value="modulo_ssh_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_ssh_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_ssh_activo')->first()->valor == 'true') checked @endif>
                                <label class="form-check-label" for="modulo_ssh_activo">Acceso SSH</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="modulo_clases_activo" name="modulos[]" value="modulo_clases_activo"
                                    @if(isset($configuraciones['modulos']) && $configuraciones['modulos']->where('clave', 'modulo_clases_activo')->first() && 
                                    $configuraciones['modulos']->where('clave', 'modulo_clases_activo')->first()->valor == 'true') checked @endif>
                                <label class="form-check-label" for="modulo_clases_activo">Gestión de Clases</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Telemetría de Equipos -->
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold">Telemetría de Equipos</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="telemetria_intervalo" class="form-label">Intervalo de Monitoreo</label>
                            <select class="form-control" id="telemetria_intervalo" name="telemetria_intervalo">
                                <option value="30" @if(isset($configuraciones['telemetria']) && $configuraciones['telemetria']->where('clave', 'telemetria_intervalo')->first() && 
                                    $configuraciones['telemetria']->where('clave', 'telemetria_intervalo')->first()->valor == '30') selected @endif>30 segundos</option>
                                <option value="60" @if(isset($configuraciones['telemetria']) && $configuraciones['telemetria']->where('clave', 'telemetria_intervalo')->first() && 
                                    $configuraciones['telemetria']->where('clave', 'telemetria_intervalo')->first()->valor == '60') selected @endif>1 minuto</option>
                                <option value="300" @if(isset($configuraciones['telemetria']) && $configuraciones['telemetria']->where('clave', 'telemetria_intervalo')->first() && 
                                    $configuraciones['telemetria']->where('clave', 'telemetria_intervalo')->first()->valor == '300') selected @endif>5 minutos</option>
                            </select>
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