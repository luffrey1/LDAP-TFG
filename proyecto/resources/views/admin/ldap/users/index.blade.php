@extends('layouts.dashboard')

@section('title', 'Gestión de Usuarios LDAP')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Gestión de Usuarios LDAP</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Usuarios LDAP</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-users me-1"></i>
                    Usuarios registrados en LDAP
                </div>
                <div>
                    <a href="{{ route('ldap.users.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> Nuevo Usuario
                    </a>
                    <a href="{{ route('profesor.alumnos.import') }}" class="btn btn-success btn-sm ms-2">
                        <i class="fas fa-file-import me-1"></i> Importar CSV
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="usersTable">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Grupos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td>{{ $user->getFullName() }}</td>
                            <td>{{ $user->getUsername() }}</td>
                            <td>{{ $user->getEmail() }}</td>
                            <td>
                                @php
                                    $userGroups = $user->getGroups();
                                    $groupNames = [];
                                    foreach($userGroups as $group) {
                                        $groupNames[] = $group->getName();
                                    }
                                    echo implode(', ', $groupNames);
                                @endphp
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('ldap.users.show', base64_encode($user->getDn())) }}" class="btn btn-sm btn-info me-1" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('ldap.users.edit', base64_encode($user->getDn())) }}" class="btn btn-sm btn-warning me-1" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('ldap.users.destroy', base64_encode($user->getDn())) }}" method="POST" class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar este usuario?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No se encontraron usuarios LDAP.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#usersTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
            }
        });
    });
</script>
@endsection 