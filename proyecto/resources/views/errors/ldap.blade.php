@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Error de Conexión LDAP</div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        {{ $message }}
                    </div>
                    <div class="text-center">
                        <a href="{{ url()->previous() }}" class="btn btn-primary">Volver atrás</a>
                        <a href="{{ route('login') }}" class="btn btn-secondary">Ir al login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 