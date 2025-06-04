@extends('layouts.dashboard')

@section('title', 'Gestión de Usuarios LDAP')

@section('content')
<div class="container mx-auto px-4 py-8 animated-fade-in">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-blue-900">Gestión de Usuarios LDAP</h1>
        <div class="flex space-x-2">
            <a href="{{ route('ldap.users.create') }}" class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-300">
                <i class="fas fa-user-plus mr-2"></i> Nuevo Usuario
            </a>
            <a href="{{ route('alumnos.import') }}" class="bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition duration-300">
                <i class="fas fa-file-import mr-2"></i> Importar CSV
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
        {{ session('error') }}
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-4 bg-gray-50 border-b border-gray-200">
            <h2 class="font-semibold text-lg">Usuarios en el Sistema</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="py-3 px-4 text-left">Usuario (UID)</th>
                        <th class="py-3 px-4 text-left">Nombre</th>
                        <th class="py-3 px-4 text-left">Email</th>
                        <th class="py-3 px-4 text-left">Grupos</th>
                        <th class="py-3 px-4 text-left">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4">{{ $user->getAttribute('uid')[0] ?? 'N/A' }}</td>
                        <td class="py-3 px-4">{{ $user->getAttribute('cn')[0] ?? 'N/A' }}</td>
                        <td class="py-3 px-4">{{ $user->getAttribute('mail')[0] ?? 'N/A' }}</td>
                        <td class="py-3 px-4">
                            @php
                                $userGroups = $user->groups()->get();
                                $groupNames = [];
                                foreach($userGroups as $group) {
                                    $groupNames[] = $group->getName();
                                }
                            @endphp
                            
                            @foreach($groupNames as $groupName)
                                <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mr-1 mb-1">
                                    {{ $groupName }}
                                </span>
                            @endforeach
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('ldap.users.show', ['dn' => urlencode($user->getDn())]) }}" class="text-blue-600 hover:text-blue-800" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('ldap.users.edit', ['dn' => urlencode($user->getDn())]) }}" class="text-yellow-600 hover:text-yellow-800" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('ldap.users.destroy', ['dn' => urlencode($user->getDn())]) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este usuario?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-6 px-4 text-center text-gray-500">
                            No se encontraron usuarios.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-4 bg-gray-50 border-b border-gray-200">
                <h2 class="font-semibold text-lg">Grupos en el Sistema</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="py-3 px-4 text-left">Nombre del Grupo</th>
                            <th class="py-3 px-4 text-left">GID Number</th>
                            <th class="py-3 px-4 text-left">Miembros</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($groups as $group)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4">{{ $group->getName() }}</td>
                            <td class="py-3 px-4">{{ $group->getAttribute('gidNumber')[0] ?? 'N/A' }}</td>
                            <td class="py-3 px-4">
                                @php
                                    $members = $group->members()->get();
                                    $count = count($members);
                                @endphp
                                {{ $count }} miembro(s)
                                @if($count > 0)
                                <div class="mt-1">
                                    @foreach($members->take(3) as $member)
                                        <span class="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded-full mr-1 mb-1">
                                            {{ $member->getAttribute('uid')[0] ?? $member->getName() }}
                                        </span>
                                    @endforeach
                                    @if($count > 3)
                                        <span class="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded-full">
                                            +{{ $count - 3 }} más
                                        </span>
                                    @endif
                                </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="py-6 px-4 text-center text-gray-500">
                                No se encontraron grupos.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-between items-center">
        <a href="{{ route('dashboard.index') }}" class="text-blue-600 hover:text-blue-800 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Volver al Dashboard
        </a>
        <a href="{{ route('ldap.logs') }}" class="bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition duration-300">
            <i class="fas fa-history mr-2"></i> Ver Registros de Actividad
        </a>
    </div>
</div>
@endsection 