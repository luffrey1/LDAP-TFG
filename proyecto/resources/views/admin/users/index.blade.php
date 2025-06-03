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

                    @if (isset($connectionError) && $connectionError)
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i> {{ $errorMessage ?? 'Error de conexión con el servidor LDAP' }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <div class="mb-3 d-flex justify-content-end">
                            <button class="btn btn-primary" onclick="window.location.reload()">
                                <i class="fas fa-sync-alt me-2"></i> Reintentar conexión
                            </button>
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
                                    @foreach($groupList as $group)
                                        <option value="{{ $group }}" {{ $selectedGroup == $group ? 'selected' : '' }}>{{ $group }}</option>
                                    @endforeach
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
                                                    <a href="{{ route('admin.users.index', ['group' => $group]) }}" class="badge bg-info text-decoration-none">{{ $group }}</a>
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
                                                
                                                @if(session('auth_user.is_admin') || session('auth_user.username') === 'ldap-admin')
                                                <form action="{{ route('admin.users.toggle-admin', $encodedDn) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm {{ $isAdmin ? 'btn-warning' : 'btn-secondary' }}" title="{{ $isAdmin ? 'Quitar admin' : 'Hacer admin' }}">
                                                        <i class="fas fa-crown"></i>
                                                    </button>
                                                </form>
                                                @endif
                                                
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
                                                @if (isset($connectionError) && $connectionError)
                                                    <i class="fas fa-exclamation-triangle me-2"></i> {{ __('No se pueden mostrar usuarios debido a un error de conexión') }}
                                                @else
                                                    <i class="fas fa-info-circle me-2"></i> {{ __('No se encontraron usuarios') }}
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination-container mt-4">
                        {{ $users->appends(request()->query())->links('pagination.custom') }}
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <p class="text-muted mb-0">
                                Mostrando {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} de {{ $total ?? 0 }} usuarios
                            </p>
                        </div>
                        <div>
                            <select id="perPageSelector" class="form-select form-select-sm" style="width: auto;">
                                <option value="10" {{ ($perPage ?? 10) == 10 ? 'selected' : '' }}>10 por página</option>
                                <option value="25" {{ ($perPage ?? 10) == 25 ? 'selected' : '' }}>25 por página</option>
                                <option value="50" {{ ($perPage ?? 10) == 50 ? 'selected' : '' }}>50 por página</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    const searchForm = searchInput.closest('form');
    let searchTimeout;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchForm.submit();
        }, 500); // Esperar 500ms después de que el usuario deje de escribir
    });
});
</script>
@endpush

@endsection 