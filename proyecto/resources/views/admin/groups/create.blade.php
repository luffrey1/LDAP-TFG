@extends('layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Crear Nuevo Grupo LDAP</h3>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('admin.groups.store') }}" method="POST">
                        @csrf
                        <div class="form-group text-white">
                            <label for="cn">Nombre del Grupo (CN)</label>
                            <input type="text" class="form-control @error('cn') is-invalid @enderror" id="cn" name="cn" value="{{ old('cn') }}" required>
                            @error('cn')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group text-white">
                            <label for="gidNumber">GID (opcional - se asignará automáticamente si se deja vacío)</label>
                            <input type="number" class="form-control @error('gidNumber') is-invalid @enderror" id="gidNumber" name="gidNumber" value="{{ old('gidNumber') }}">
                            @error('gidNumber')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group text-white">
                            <label for="description">Descripción (opcional)</label>
                            <input type="text" class="form-control @error('description') is-invalid @enderror" id="description" name="description" value="{{ old('description') }}">
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group text-white">
                            <button type="submit" class="btn btn-primary">Crear Grupo</button>
                            <a href="{{ route('admin.groups.index') }}" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 