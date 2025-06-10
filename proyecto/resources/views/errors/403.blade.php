@extends('layouts.dashboard')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Acceso Denegado</div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        No tienes permiso para acceder a esta página.
                    </div>
                    <a href="{{ route('dashboard.index') }}" class="btn btn-primary">
                        Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
