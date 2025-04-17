@extends('layouts.dashboard')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ __('Logs de Actividad LDAP') }}</span>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-light">
                            <i class="fas fa-users"></i> {{ __('Volver a Usuarios') }}
                        </a>
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
                                        <td>{{ $log['id'] }}</td>
                                        <td>{{ $log['fecha'] }}</td>
                                        <td>
                                            <span class="badge bg-{{ $log['nivel'] == 'ERROR' ? 'danger' : ($log['nivel'] == 'WARNING' ? 'warning' : ($log['nivel'] == 'INFO' ? 'info' : ($log['nivel'] == 'DEBUG' ? 'secondary' : 'primary'))) }}">
                                                {{ $log['nivel'] }}
                                            </span>
                                        </td>
                                        <td>{{ $log['usuario'] }}</td>
                                        <td>
                                            <span class="badge bg-{{ $log['accion'] == 'Error' ? 'danger' : ($log['accion'] == 'Advertencia' ? 'warning' : ($log['accion'] == 'Información' ? 'info' : 'secondary')) }}">
                                                {{ $log['accion'] }}
                                            </span>
                                        </td>
                                        <td class="text-truncate" style="max-width: 400px;" title="{{ $log['descripcion'] }}">
                                            {{ \Illuminate\Support\Str::limit($log['descripcion'], 100) }}
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
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 