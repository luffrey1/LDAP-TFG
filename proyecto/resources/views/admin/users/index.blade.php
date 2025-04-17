@extends('layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ __('Gestión de Usuarios') }}</h5>
                        <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-light">
                            <i class="fas fa-plus"></i> {{ __('Crear Usuario') }}
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('admin.users.index') }}" method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" value="{{ $search }}" placeholder="{{ __('Buscar por nombre, apellido o email...') }}">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select name="group" class="form-select" onchange="this.form.submit()">
                                    <option value="">{{ __('Todos los grupos') }}</option>
                                    <option value="ldapadmins" {{ $selectedGroup == 'ldapadmins' ? 'selected' : '' }}>{{ __('Administradores') }}</option>
                                    <option value="profesores" {{ $selectedGroup == 'profesores' ? 'selected' : '' }}>{{ __('Profesores') }}</option>
                                    <option value="alumnos" {{ $selectedGroup == 'alumnos' ? 'selected' : '' }}>{{ __('Alumnos') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2 text-end">
                                @if ($search || $selectedGroup)
                                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                                        {{ __('Limpiar') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>{{ __('UID') }}</th>
                                    <th>{{ __('Nombre') }}</th>
                                    <th>{{ __('Apellidos') }}</th>
                                    <th>{{ __('Email') }}</th>
                                    <th>{{ __('Grupos') }}</th>
                                    <th class="text-center">{{ __('Acciones') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    <tr>
                                        <td>
                                            @if (is_array($user))
                                                {{ $user['uid'][0] ?? 'N/A' }}
                                            @else
                                                {{ $user->getFirstAttribute('uid') ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if (is_array($user))
                                                {{ $user['givenname'][0] ?? 'N/A' }}
                                            @else
                                                {{ $user->getFirstAttribute('givenname') ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if (is_array($user))
                                                {{ $user['sn'][0] ?? 'N/A' }}
                                            @else
                                                {{ $user->getFirstAttribute('sn') ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if (is_array($user))
                                                {{ $user['mail'][0] ?? 'N/A' }}
                                            @else
                                                {{ $user->getFirstAttribute('mail') ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $uid = is_array($user) ? ($user['uid'][0] ?? '') : $user->getFirstAttribute('uid');
                                            @endphp
                                            @if (isset($userGroups[$uid]))
                                                @foreach ($userGroups[$uid] as $group)
                                                    <span class="badge bg-info">{{ $group }}</span>
                                                @endforeach
                                            @else
                                                <span class="badge bg-secondary">{{ __('Sin grupos') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $encodedDn = is_array($user) ? ($user['encoded_dn'] ?? '') : $user->encoded_dn;
                                                $userDn = is_array($user) ? ($user['dn'] ?? '') : $user->getDn();
                                                $isAdmin = in_array($userDn, $adminUsers ?? []);
                                                
                                                // Si encodedDn está vacío, intentar generarlo nuevamente
                                                if (empty($encodedDn) && !empty($userDn)) {
                                                    $encodedDn = base64_encode($userDn);
                                                }
                                            @endphp
                                            
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.users.edit', $encodedDn) }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <form action="{{ route('admin.users.toggle-admin', $encodedDn) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm {{ $isAdmin ? 'btn-warning' : 'btn-secondary' }}" title="{{ $isAdmin ? 'Quitar admin' : 'Hacer admin' }}">
                                                        <i class="fas fa-crown"></i>
                                                    </button>
                                                </form>
                                                
                                                <form action="{{ route('admin.users.destroy', $encodedDn) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de que desea eliminar este usuario?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="alert alert-info mb-0">
                                                <i class="fas fa-info-circle me-2"></i> {{ __('No se encontraron usuarios') }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 