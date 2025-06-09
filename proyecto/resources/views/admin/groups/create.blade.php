@extends('layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Crear Nuevo Grupo</h4>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="groupTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="posix-tab" data-bs-toggle="tab" href="#posix" role="tab" aria-controls="posix" aria-selected="true">
                                PosixGroup
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="unique-tab" data-bs-toggle="tab" href="#unique" role="tab" aria-controls="unique" aria-selected="false">
                                GroupOfUniqueNames
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="combined-tab" data-bs-toggle="tab" href="#combined" role="tab" aria-controls="combined" aria-selected="false">
                                Combinado
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="groupTabsContent">
                        <!-- PosixGroup -->
                        <div class="tab-pane fade show active" id="posix" role="tabpanel" aria-labelledby="posix-tab">
                            <form id="posixForm" class="group-form" method="POST" action="{{ route('gestion.grupos.store') }}">
                                @csrf
                                <input type="hidden" name="type" value="posix">
                                <div class="form-group text-white">
                                    <label>Nombre del Grupo</label>
                                    <input type="text" name="cn" class="form-control" required 
                                           pattern="[a-zA-Z0-9\-_]+" 
                                           title="Solo letras, números, guiones y guiones bajos">
                                </div>
                                <div class="form-group text-white">
                                    <label>GID Number (opcional)</label>
                                    <input type="number" name="gidNumber" class="form-control" min="1000">
                                </div>
                                <div class="form-group text-white">
                                    <label>Descripción</label>
                                    <input type="text" name="description" class="form-control">
                                </div>
                                <button type="submit" class="btn btn-primary">Crear Grupo</button>
                            </form>
                        </div>

                        <!-- GroupOfUniqueNames -->
                        <div class="tab-pane fade" id="unique" role="tabpanel" aria-labelledby="unique-tab">
                            <form id="uniqueForm" class="group-form" method="POST" action="{{ route('gestion.grupos.store') }}">
                                @csrf
                                <input type="hidden" name="type" value="unique">
                                <div class="form-group text-white">
                                    <label>Nombre del Grupo</label>
                                    <input type="text" name="cn" class="form-control" required 
                                           pattern="[a-zA-Z0-9\-_]+" 
                                           title="Solo letras, números, guiones y guiones bajos">
                                </div>
                                <div class="form-group text-white">
                                    <label>Descripción</label>
                                    <input type="text" name="description" class="form-control">
                                </div>
                                <button type="submit" class="btn btn-primary">Crear Grupo</button>
                            </form>
                        </div>

                        <!-- Combinado -->
                        <div class="tab-pane fade" id="combined" role="tabpanel" aria-labelledby="combined-tab">
                            <form id="combinedForm" class="group-form" method="POST" action="{{ route('gestion.grupos.store') }}">
                                @csrf
                                <input type="hidden" name="type" value="combined">
                                <div class="form-group text-white">
                                    <label>Nombre del Grupo</label>
                                    <input type="text" name="cn" class="form-control" required 
                                           pattern="[a-zA-Z0-9\-_]+" 
                                           title="Solo letras, números, guiones y guiones bajos">
                                </div>
                                <div class="form-group text-white">
                                    <label>GID Number (opcional)</label>
                                    <input type="number" name="gidNumber" class="form-control" min="1000">
                                </div>
                                <div class="form-group text-white">
                                    <label>Descripción</label>
                                    <input type="text" name="description" class="form-control">
                                </div>
                                <button type="submit" class="btn btn-primary">Crear Grupo</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Inicializar Select2
    $('.select2').select2({
        placeholder: 'Selecciona los miembros',
        allowClear: true
    });
});
</script>
@endpush

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.select2-container {
    width: 100% !important;
}
</style>
@endpush
@endsection 