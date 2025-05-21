@extends('layouts.dashboard')
@section('title', 'Estado global de los equipos')
@section('content')
@php
    $encendidos8h = collect($equipos)->where('encendido_8h', true);
    $graves = collect($equipos)->where('estado', 'grave')->where('encendido_8h', false);
    $criticos = collect($equipos)->where('estado', 'critico')->where('encendido_8h', false);
    $saludables = collect($equipos)->where('estado', 'saludable')->where('encendido_8h', false);
@endphp
<div class="container my-4">
    <h1 class="mb-4"><i class="fas fa-server"></i> Estado global de los equipos</h1>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center border-danger mb-2">
                <div class="card-body">
                    <h5 class="card-title text-danger"><i class="fas fa-skull-crossbones"></i> Graves</h5>
                    <span class="display-6 fw-bold">{{ $summary['grave'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-warning mb-2">
                <div class="card-body">
                    <h5 class="card-title text-warning"><i class="fas fa-exclamation-triangle"></i> Críticos</h5>
                    <span class="display-6 fw-bold">{{ $summary['critico'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-success mb-2">
                <div class="card-body">
                    <h5 class="card-title text-success"><i class="fas fa-check-circle"></i> Saludables</h5>
                    <span class="display-6 fw-bold">{{ $summary['saludable'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-info mb-2">
                <div class="card-body">
                    <h5 class="card-title text-info"><i class="fas fa-clock"></i> Encendidos &gt; 8h</h5>
                    <span class="display-6 fw-bold">{{ $summary['encendidos_8h'] }}</span>
                </div>
            </div>
        </div>
    </div>
    @if($encendidos8h->count())
    <div class="card mb-4 border-info">
        <div class="card-header bg-info text-white"><i class="fas fa-clock"></i> Equipos encendidos &gt; 8h <span class="ms-2 small">(Recomendado apagar)</span></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Estado</th>
                            <th>Hostname</th>
                            <th>IP</th>
                            <th>CPU (%)</th>
                            <th>Memoria (%)</th>
                            <th>Disco (%)</th>
                            <th>Uptime</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($encendidos8h as $eq)
                        <tr>
                            <td>
                                @if($eq['estado'] === 'saludable')
                                    <span class="badge bg-success">Saludable</span>
                                @elseif($eq['estado'] === 'critico')
                                    <span class="badge bg-warning text-dark">Crítico</span>
                                @else
                                    <span class="badge bg-danger">Grave</span>
                                @endif
                            </td>
                            <td>{{ $eq['hostname'] }}</td>
                            <td>{{ $eq['ip_address'] }}</td>
                            <td class="fw-bold @if($eq['cpu']>=70) text-danger @elseif($eq['cpu']>=50) text-warning @else text-success @endif">{{ $eq['cpu'] }}%</td>
                            <td class="fw-bold @if($eq['mem']>=70) text-danger @elseif($eq['mem']>=50) text-warning @else text-success @endif">{{ $eq['mem'] }}%</td>
                            <td class="fw-bold @if($eq['disk']>=70) text-danger @elseif($eq['disk']>=50) text-warning @else text-success @endif">{{ $eq['disk'] }}%</td>
                            <td>{{ $eq['uptime'] ?? 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
    @if($graves->count())
    <div class="card mb-4 border-danger">
        <div class="card-header bg-danger text-white"><i class="fas fa-skull-crossbones"></i> Equipos en estado grave</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Estado</th>
                            <th>Hostname</th>
                            <th>IP</th>
                            <th>CPU (%)</th>
                            <th>Memoria (%)</th>
                            <th>Disco (%)</th>
                            <th>Uptime</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($graves as $eq)
                        <tr>
                            <td><span class="badge bg-danger">Grave</span></td>
                            <td>{{ $eq['hostname'] }}</td>
                            <td>{{ $eq['ip_address'] }}</td>
                            <td class="fw-bold @if($eq['cpu']>=70) text-danger @elseif($eq['cpu']>=50) text-warning @else text-success @endif">{{ $eq['cpu'] }}%</td>
                            <td class="fw-bold @if($eq['mem']>=70) text-danger @elseif($eq['mem']>=50) text-warning @else text-success @endif">{{ $eq['mem'] }}%</td>
                            <td class="fw-bold @if($eq['disk']>=70) text-danger @elseif($eq['disk']>=50) text-warning @else text-success @endif">{{ $eq['disk'] }}%</td>
                            <td>{{ $eq['uptime'] ?? 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
    @if($criticos->count())
    <div class="card mb-4 border-warning">
        <div class="card-header bg-warning text-dark"><i class="fas fa-exclamation-triangle"></i> Equipos en estado crítico</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Estado</th>
                            <th>Hostname</th>
                            <th>IP</th>
                            <th>CPU (%)</th>
                            <th>Memoria (%)</th>
                            <th>Disco (%)</th>
                            <th>Uptime</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($criticos as $eq)
                        <tr>
                            <td><span class="badge bg-warning text-dark">Crítico</span></td>
                            <td>{{ $eq['hostname'] }}</td>
                            <td>{{ $eq['ip_address'] }}</td>
                            <td class="fw-bold @if($eq['cpu']>=70) text-danger @elseif($eq['cpu']>=50) text-warning @else text-success @endif">{{ $eq['cpu'] }}%</td>
                            <td class="fw-bold @if($eq['mem']>=70) text-danger @elseif($eq['mem']>=50) text-warning @else text-success @endif">{{ $eq['mem'] }}%</td>
                            <td class="fw-bold @if($eq['disk']>=70) text-danger @elseif($eq['disk']>=50) text-warning @else text-success @endif">{{ $eq['disk'] }}%</td>
                            <td>{{ $eq['uptime'] ?? 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
    @if($saludables->count())
    <div class="card mb-4 border-success">
        <div class="card-header bg-success text-white"><i class="fas fa-check-circle"></i> Equipos saludables</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Estado</th>
                            <th>Hostname</th>
                            <th>IP</th>
                            <th>CPU (%)</th>
                            <th>Memoria (%)</th>
                            <th>Disco (%)</th>
                            <th>Uptime</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($saludables as $eq)
                        <tr>
                            <td><span class="badge bg-success">Saludable</span></td>
                            <td>{{ $eq['hostname'] }}</td>
                            <td>{{ $eq['ip_address'] }}</td>
                            <td class="fw-bold @if($eq['cpu']>=70) text-danger @elseif($eq['cpu']>=50) text-warning @else text-success @endif">{{ $eq['cpu'] }}%</td>
                            <td class="fw-bold @if($eq['mem']>=70) text-danger @elseif($eq['mem']>=50) text-warning @else text-success @endif">{{ $eq['mem'] }}%</td>
                            <td class="fw-bold @if($eq['disk']>=70) text-danger @elseif($eq['disk']>=50) text-warning @else text-success @endif">{{ $eq['disk'] }}%</td>
                            <td>{{ $eq['uptime'] ?? 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection 