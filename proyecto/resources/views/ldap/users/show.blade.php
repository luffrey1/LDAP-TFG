@extends('layouts.departamento')

@section('title', 'Detalles de Usuario LDAP')

@section('content')
<div class="container mx-auto px-4 py-8 animated-fade-in">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-blue-900">Detalles de Usuario LDAP</h1>
        <div class="flex space-x-2">
            <a href="{{ route('ldap.users.edit', ['dn' => urlencode($user->getDn())]) }}" class="bg-yellow-600 text-white py-2 px-4 rounded-lg hover:bg-yellow-700 transition duration-300">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
            <a href="{{ route('ldap.users.index') }}" class="bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition duration-300">
                <i class="fas fa-arrow-left mr-2"></i> Volver
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-4 bg-gray-50 border-b border-gray-200">
            <h2 class="font-semibold text-lg">Información del Usuario</h2>
            <p class="text-sm text-gray-600">DN: {{ $user->getDn() }}</p>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-md font-medium text-gray-800 mb-3">Información General</h3>
                    <dl class="space-y-3">
                        <div class="flex flex-wrap">
                            <dt class="w-1/3 text-sm font-medium text-gray-600">UID:</dt>
                            <dd class="w-2/3 text-sm text-gray-900">{{ $user->getAttribute('uid')[0] ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex flex-wrap">
                            <dt class="w-1/3 text-sm font-medium text-gray-600">Nombre:</dt>
                            <dd class="w-2/3 text-sm text-gray-900">{{ $user->getAttribute('cn')[0] ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex flex-wrap">
                            <dt class="w-1/3 text-sm font-medium text-gray-600">Apellido:</dt>
                            <dd class="w-2/3 text-sm text-gray-900">{{ $user->getAttribute('sn')[0] ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex flex-wrap">
                            <dt class="w-1/3 text-sm font-medium text-gray-600">Email:</dt>
                            <dd class="w-2/3 text-sm text-gray-900">
                                @if(isset($user->getAttribute('mail')[0]))
                                    <a href="mailto:{{ $user->getAttribute('mail')[0] }}" class="text-blue-600 hover:underline">
                                        {{ $user->getAttribute('mail')[0] }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-md font-medium text-gray-800 mb-3">Información de Cuenta</h3>
                    <dl class="space-y-3">
                        <div class="flex flex-wrap">
                            <dt class="w-1/3 text-sm font-medium text-gray-600">UID Number:</dt>
                            <dd class="w-2/3 text-sm text-gray-900">{{ $user->getAttribute('uidNumber')[0] ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex flex-wrap">
                            <dt class="w-1/3 text-sm font-medium text-gray-600">GID Number:</dt>
                            <dd class="w-2/3 text-sm text-gray-900">{{ $user->getAttribute('gidNumber')[0] ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex flex-wrap">
                            <dt class="w-1/3 text-sm font-medium text-gray-600">Directorio Home:</dt>
                            <dd class="w-2/3 text-sm text-gray-900">{{ $user->getAttribute('homeDirectory')[0] ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex flex-wrap">
                            <dt class="w-1/3 text-sm font-medium text-gray-600">Shell:</dt>
                            <dd class="w-2/3 text-sm text-gray-900">{{ $user->getAttribute('loginShell')[0] ?? 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg md:col-span-2">
                    <h3 class="text-md font-medium text-gray-800 mb-3">Grupos</h3>
                    @if(count($userGroups) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            @foreach($userGroups as $group)
                                <div class="bg-blue-100 text-blue-800 py-2 px-3 rounded-lg flex items-center">
                                    <i class="fas fa-users-cog mr-2"></i>
                                    {{ $group->getName() }}
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-600">Este usuario no pertenece a ningún grupo.</p>
                    @endif
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg md:col-span-2">
                    <h3 class="text-md font-medium text-gray-800 mb-3">Clases de Objeto</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($user->getAttribute('objectClass') as $class)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-800">
                                {{ $class }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-8 border-t border-gray-200 pt-4 flex justify-between">
                <form action="{{ route('ldap.users.destroy', ['dn' => urlencode($user->getDn())]) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition duration-300">
                        <i class="fas fa-trash mr-2"></i> Eliminar Usuario
                    </button>
                </form>
                
                <div class="flex space-x-2">
                    <a href="{{ route('ldap.users.edit', ['dn' => urlencode($user->getDn())]) }}" class="bg-yellow-600 text-white py-2 px-4 rounded-lg hover:bg-yellow-700 transition duration-300">
                        <i class="fas fa-edit mr-2"></i> Editar Usuario
                    </a>
                    <a href="{{ route('ldap.users.index') }}" class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-300">
                        <i class="fas fa-list mr-2"></i> Ver Todos los Usuarios
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 