@extends('layouts.dashboard')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ __('Logs de Actividad LDAP') }}</span>
                        <div>
                            <div class="btn-group me-2">
                                <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-trash"></i> {{ __('Borrar últimos') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('admin.logs.delete', ['count' => 500]) }}" onclick="return confirm('¿Estás seguro de borrar los últimos 500 logs?')">Últimos 500</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.logs.delete', ['count' => 1000]) }}" onclick="return confirm('¿Estás seguro de borrar los últimos 1000 logs?')">Últimos 1000</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.logs.delete', ['count' => 2000]) }}" onclick="return confirm('¿Estás seguro de borrar los últimos 2000 logs?')">Últimos 2000</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.logs.delete', ['count' => 5000]) }}" onclick="return confirm('¿Estás seguro de borrar los últimos 5000 logs?')">Últimos 5000</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="{{ route('admin.logs.delete', ['count' => 'all']) }}" onclick="return confirm('¿Estás seguro de borrar TODOS los logs? Esta acción no se puede deshacer.')">Borrar todos</a></li>
                                </ul>
                            </div>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-light">
                                <i class="fas fa-users"></i> {{ __('Volver a Usuarios') }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('status') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Fecha') }}</th>
                                    <th>{{ __('Nivel') }}</th>
                                    <th>{{ __('Usuario') }}</th>
                                    <th>{{ __('Acción') }}</th>
                                    <th>{{ __('Descripción') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr>
                                        <td>{{ $log->id }}</td>
                                        <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $log->level == 'ERROR' ? 'danger' : ($log->level == 'WARNING' ? 'warning' : ($log->level == 'INFO' ? 'info' : ($log->level == 'DEBUG' ? 'secondary' : 'primary'))) }}">
                                                {{ $log->level }}
                                            </span>
                                        </td>
                                        <td>{{ $log->user ?? 'Sistema' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $log->action == 'Error' ? 'danger' : ($log->action == 'Advertencia' ? 'warning' : ($log->action == 'Información' ? 'info' : 'secondary')) }}">
                                                {{ $log->action }}
                                            </span>
                                        </td>
                                        <td class="text-truncate" style="max-width: 400px;" title="{{ $log->description }}">
                                            {{ \Illuminate\Support\Str::limit($log->description, 100) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="alert alert-info mb-0">
                                                <i class="fas fa-info-circle me-2"></i> {{ __('No hay registros de actividad disponibles') }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($logs->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $logs->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 