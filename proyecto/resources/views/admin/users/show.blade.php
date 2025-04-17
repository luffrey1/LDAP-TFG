@extends('layouts.dashboard')

@section('title', 'Detalles de Usuario')

@section('content')
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Detalles del Usuario: {{ $user['name'] }}</h6>
            <div>
                <a href="{{ route('ldap.users.edit', urlencode($user['dn'])) }}" class="btn btn-sm btn-primary me-2">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="{{ route('ldap.users.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Información principal -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Información Personal</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <img src="{{ asset('img/undraw_profile.svg') }}" class="img-profile rounded-circle mb-3" width="100">
                                <h5 class="font-weight-bold">{{ $user['name'] }}</h5>
                                <span class="badge bg-primary">{{ $roles[$currentRole] ?? 'Sin rol' }}</span>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-sm-4 font-weight-bold">Usuario:</div>
                                <div class="col-sm-8">{{ $user['username'] }}</div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-sm-4 font-weight-bold">Email:</div>
                                <div class="col-sm-8">{{ $user['email'] }}</div>
                            </div>
                            @if(isset($user['created_at']))
                            <div class="row mt-2">
                                <div class="col-sm-4 font-weight-bold">Creado:</div>
                                <div class="col-sm-8">{{ $user['created_at'] }}</div>
                            </div>
                            @endif
                            @if(isset($user['updated_at']))
                            <div class="row mt-2">
                                <div class="col-sm-4 font-weight-bold">Actualizado:</div>
                                <div class="col-sm-8">{{ $user['updated_at'] }}</div>
                            </div>
                            @endif
                            @if(isset($user['last_login']))
                            <div class="row mt-2">
                                <div class="col-sm-4 font-weight-bold">Último acceso:</div>
                                <div class="col-sm-8">{{ $user['last_login'] }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Grupos LDAP -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Grupos LDAP</h6>
                        </div>
                        <div class="card-body">
                            @if(count($userGroups) > 0)
                                <ul class="list-group">
                                    @foreach($userGroups as $groupDn)
                                        @php
                                            $groupName = 'Grupo desconocido';
                                            foreach($ldapGroups as $group) {
                                                if($group['dn'] == $groupDn) {
                                                    $groupName = $group['name'];
                                                    break;
                                                }
                                            }
                                        @endphp
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            {{ $groupName }}
                                            <span class="badge bg-secondary rounded-pill">
                                                <i class="fas fa-users"></i>
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i> Este usuario no pertenece a ningún grupo LDAP.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Permisos y acceso -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Permisos y Acceso</h6>
                        </div>
                        <div class="card-body">
                            <h6 class="font-weight-bold">Rol en la aplicación:</h6>
                            <div class="mb-3">
                                @if($currentRole == 'admin')
                                    <div class="alert alert-primary">
                                        <i class="fas fa-crown me-2"></i> <strong>Administrador</strong>
                                        <p class="mb-0 small mt-1">Acceso completo a todas las funciones del sistema.</p>
                                    </div>
                                @elseif($currentRole == 'profesor')
                                    <div class="alert alert-success">
                                        <i class="fas fa-chalkboard-teacher me-2"></i> <strong>Profesor</strong>
                                        <p class="mb-0 small mt-1">Acceso a gestión de documentos y mensajes.</p>
                                    </div>
                                @elseif($currentRole == 'alumno')
                                    <div class="alert alert-info">
                                        <i class="fas fa-user-graduate me-2"></i> <strong>Alumno</strong>
                                        <p class="mb-0 small mt-1">Acceso limitado solo a funciones de visualización.</p>
                                    </div>
                                @else
                                    <div class="alert alert-secondary">
                                        <i class="fas fa-user me-2"></i> <strong>Sin rol específico</strong>
                                        <p class="mb-0 small mt-1">Acceso muy limitado a la aplicación.</p>
                                    </div>
                                @endif
                            </div>
                            
                            <h6 class="font-weight-bold mt-4">Acciones rápidas:</h6>
                            <div class="d-grid gap-2">
                                <a href="{{ route('ldap.users.reset-password', urlencode($user['dn'])) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-key me-2"></i> Restablecer contraseña
                                </a>
                                @if($currentRole != 'admin' && in_array('ldapadmins', array_map(function($g) { return explode(',', $g)[0]; }, $userGroups)))
                                    <a href="{{ route('ldap.users.revoke-admin', urlencode($user['dn'])) }}" class="btn btn-danger btn-sm">
                                        <i class="fas fa-user-shield me-2"></i> Revocar permisos de administrador
                                    </a>
                                @elseif($currentRole == 'profesor')
                                    <a href="{{ route('ldap.users.make-admin', urlencode($user['dn'])) }}" class="btn btn-success btn-sm">
                                        <i class="fas fa-user-shield me-2"></i> Convertir en administrador
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actividad reciente -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Actividad Reciente</h6>
                        </div>
                        <div class="card-body">
                            @if(isset($userActivities) && count($userActivities) > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Tipo</th>
                                                <th>Descripción</th>
                                                <th>IP</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($userActivities as $activity)
                                            <tr>
                                                <td>{{ $activity->created_at }}</td>
                                                <td>
                                                    @if($activity->type == 'login')
                                                        <span class="badge bg-success">Inicio de sesión</span>
                                                    @elseif($activity->type == 'logout')
                                                        <span class="badge bg-secondary">Cierre de sesión</span>
                                                    @elseif($activity->type == 'document')
                                                        <span class="badge bg-info">Documento</span>
                                                    @else
                                                        <span class="badge bg-primary">{{ $activity->type }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $activity->description }}</td>
                                                <td>{{ $activity->ip_address }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> No hay actividad reciente registrada para este usuario.
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