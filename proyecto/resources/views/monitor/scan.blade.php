@extends('layouts.dashboard')

@section('title', 'Escanear Red')

@section('content')
<section class="section">
    <div class="section-header">
        <h1>Escanear Red</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard.index') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('monitor.index') }}">Monitoreo</a></div>
            <div class="breadcrumb-item">Escanear Red</div>
        </div>
    </div>

    <div class="section-body">
        <h2 class="section-title">Detección Automática de Hosts</h2>
        <p class="section-lead">Busca equipos conectados en la red local para agregarlos al monitoreo.</p>
        
        <div class="row">
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Configuración del Escaneo</h4>
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

                        <form action="{{ route('monitor.scan.execute') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="base_ip">Subred a Escanear</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="base_ip" name="base_ip" value="{{ $baseIp ?? '192.168.1' }}" placeholder="192.168.1">
                                    <div class="input-group-append">
                                        <span class="input-group-text">.x</span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Ingresa los primeros 3 octetos de la subred. Por ejemplo: 192.168.1</small>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="range_start">Rango Inicial</label>
                                    <input type="number" class="form-control" id="range_start" name="range_start" value="1" min="1" max="254">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="range_end">Rango Final</label>
                                    <input type="number" class="form-control" id="range_end" name="range_end" value="254" min="1" max="254">
                                </div>
                            </div>

                            <div class="alert alert-warning">
                                <p><strong>Nota:</strong> El escaneo de red puede tardar varios minutos dependiendo del rango seleccionado.</p>
                                <p>Para evitar sobrecargar la red, se recomienda escanear rangos pequeños de IP (máximo 50 a la vez).</p>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="add_to_group" name="add_to_group" value="1">
                                    <label class="custom-control-label" for="add_to_group">Agregar los hosts a un grupo específico</label>
                                </div>
                            </div>

                            <div id="group_selector" class="form-group" style="display: none;">
                                <label for="group_id">Seleccionar Grupo</label>
                                <select class="form-control" id="group_id" name="group_id">
                                    <option value="0">Sin grupo</option>
                                    <!-- Aquí se cargarían los grupos desde la base de datos -->
                                </select>
                            </div>

                            <div class="text-right">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Iniciar Escaneo
                                </button>
                                <a href="{{ route('monitor.index') }}" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Información Adicional</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h6>¿Cómo funciona?</h6>
                            <p>El escáner de red utiliza comandos ICMP (ping) para detectar hosts activos en la red especificada. Para cada dirección IP que responde, se intenta obtener su nombre de host y se agrega a la lista de equipos monitoreados.</p>
                        </div>
                        
                        <div class="mb-4">
                            <h6>¿Por qué no se detectan todos los equipos?</h6>
                            <ul>
                                <li>Algunos equipos pueden tener el firewall configurado para bloquear solicitudes ICMP (ping).</li>
                                <li>Equipos que están apagados o en modo de suspensión no responderán.</li>
                                <li>Restricciones de red pueden evitar que ciertos hosts sean detectados.</li>
                            </ul>
                        </div>
                        
                        <div class="mb-4">
                            <h6>Información de tu Red Local</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tr>
                                        <th style="width: 40%;">Tu dirección IP</th>
                                        <td id="client_ip">Detectando...</td>
                                    </tr>
                                    <tr>
                                        <th>Puerta de enlace</th>
                                        <td id="gateway">Detectando...</td>
                                    </tr>
                                    <tr>
                                        <th>Máscara de subred</th>
                                        <td id="subnet_mask">Detectando...</td>
                                    </tr>
                                </table>
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
    // Mostrar/ocultar selector de grupo
    $('#add_to_group').change(function() {
        if($(this).is(':checked')) {
            $('#group_selector').show();
        } else {
            $('#group_selector').hide();
        }
    });
    
    // Obtener información de red (simulada en el cliente)
    setTimeout(function() {
        $('#client_ip').text('192.168.1.100 (Tu equipo)');
        $('#gateway').text('192.168.1.1');
        $('#subnet_mask').text('255.255.255.0');
    }, 1000);
    
    // Validar el formulario antes de enviar
    $('form').on('submit', function(e) {
        var start = parseInt($('#range_start').val());
        var end = parseInt($('#range_end').val());
        
        if (start > end) {
            e.preventDefault();
            alert('El rango inicial no puede ser mayor que el rango final.');
            return false;
        }
        
        if (end - start > 100) {
            return confirm('Has seleccionado un rango amplio de IPs (' + (end - start + 1) + '). El escaneo puede tardar varios minutos. ¿Deseas continuar?');
        }
        
        return true;
    });
});
</script>
@endsection 