@extends('layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Editar Grupo LDAP: {{ $groupData['cn'] }}</h3>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger text-white">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('gestion.grupos.update', $groupData['cn']) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group text-white">
                            <label for="cn">Nombre del Grupo (CN)</label>
                            <input type="text" class="form-control text-black" id="cn" value="{{ $groupData['cn'] }}" disabled>
                            <small class="form-text text-white">El nombre del grupo no se puede modificar.</small>
                        </div>

                        <div class="form-group text-white">
                            <label for="gidNumber">GID</label>
                            <input type="number" class="form-control @error('gidNumber') is-invalid @enderror" id="gidNumber" name="gidNumber" value="{{ old('gidNumber', $groupData['gidNumber']) }}" required>
                            @error('gidNumber')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group text-white">
                            <label for="description">Descripción (opcional)</label>
                            <input type="text" class="form-control @error('description') is-invalid @enderror" id="description" name="description" value="{{ old('description', $groupData['description']) }}">
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group text-white">
                            <button type="submit" class="btn btn-primary">Actualizar Grupo</button>
                            <a href="{{ route('gestion.grupos.index') }}" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 