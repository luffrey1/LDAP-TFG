@extends('layouts.dashboard')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title ">Detalles del Grupo: {{ $group['cn'] }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('gestion.grupos.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 text-white">
                            <h4>Información General</h4>
                            <table class="table">
                                <tr>
                                    <th>Nombre:</th>
                                    <td>{{ $group['cn'] }}</td>
                                </tr>
                                <tr>
                                    <th>Tipo:</th>
                                    <td>
                                        @if($group['type'] === 'posix')
                                            <button class="btn btn-sm btn-info text-white">Posix</button>
                                        @elseif($group['type'] === 'unique')
                                            <button class="btn btn-sm btn-success text-white">Unique Names</button>
                                        @elseif($group['type'] === 'combined')
                                            <button class="btn btn-sm btn-warning text-white">Combinado</button>
                                        @endif
                                    </td>
                                </tr>
                                @if($group['gidNumber'])
                                <tr>
                                    <th>GID:</th>
                                    <td>{{ $group['gidNumber'] }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <th class="text-black"> Descripción:</th>
                                    <td class="text-black">{{ $group['description'] ?? 'Sin descripción' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6 text-white">
                            <h4>Miembros del Grupo</h4>
                            @if(count($group['members']) > 0)
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-bordered table-striped">
                                        <thead class="sticky-top bg-white">
                                            <tr>
                                                <th>Usuario (UID)</th>
                                                <th>UID Number</th>
                                                <th>Nombre</th>
                                                <th>Apellidos</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($group['members'] as $member)
                                                <tr>
                                                    <td>{{ $member['uid'] }}</td>
                                                    <td>{{ $member['uidNumber'] ?? '' }}</td>
                                                    <td>{{ $member['givenname'] ?? '' }}</td>
                                                    <td>{{ $member['sn'] ?? '' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    Este grupo no tiene miembros.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 