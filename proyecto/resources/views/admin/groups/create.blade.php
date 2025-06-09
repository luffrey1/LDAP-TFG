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
                    <form id="groupForm" method="POST">
                        @csrf
                        <div class="form-group text-white">
                            <label for="cn">Nombre del Grupo</label>
                            <input type="text" name="cn" id="cn" class="form-control" required 
                                   pattern="[a-zA-Z0-9\-_]+" 
                                   title="Solo letras, números, guiones y guiones bajos">
                        </div>

                        <div class="form-group text-white">
                            <label for="description">Descripción</label>
                            <input type="text" name="description" id="description" class="form-control">
                        </div>

                        <div class="form-group text-white">
                            <button type="button" class="btn btn-info" data-bs-toggle="collapse" data-bs-target="#advancedOptions">
                                <i class="fas fa-cog"></i> Opciones Avanzadas
                            </button>
                        </div>

                        <div id="advancedOptions" class="collapse">
                            <div class="card card-body bg-dark">
                                <h5 class="text-white">Tipo de Grupo</h5>
                                <div class="form-check text-white">
                                    <input class="form-check-input" type="checkbox" name="type[]" value="posix" id="posixCheck" checked>
                                    <label class="form-check-label" for="posixCheck">
                                        PosixGroup
                                    </label>
                                </div>
                                <div class="form-check text-white">
                                    <input class="form-check-input" type="checkbox" name="type[]" value="unique" id="uniqueCheck" checked>
                                    <label class="form-check-label" for="uniqueCheck">
                                        GroupOfUniqueNames
                                    </label>
                                </div>

                                <div id="posixOptions" class="mt-3">
                                    <div class="form-group text-white">
                                        <label for="gidNumber">GID Number</label>
                                        <input type="number" name="gidNumber" id="gidNumber" class="form-control" min="1000">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group text-white mt-3">
                            <button type="submit" class="btn btn-primary">Crear Grupo</button>
                            <a href="{{ route('gestion.grupos.index') }}" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Función para validar el formulario
    function validateForm() {
        const posixChecked = $('#posixCheck').is(':checked');
        const uniqueChecked = $('#uniqueCheck').is(':checked');
        const gidNumber = $('#gidNumber').val();

        if (posixChecked && !gidNumber) {
            alert('El GID Number es requerido para grupos PosixGroup');
            return false;
        }

        if (!posixChecked && !uniqueChecked) {
            alert('Debe seleccionar al menos un tipo de grupo');
            return false;
        }

        return true;
    }

    // Manejar el envío del formulario
    $('#groupForm').on('submit', function(e) {
        e.preventDefault();

        if (!validateForm()) {
            return;
        }

        const formData = new FormData(this);
        const types = [];
        
        if ($('#posixCheck').is(':checked')) types.push('posix');
        if ($('#uniqueCheck').is(':checked')) types.push('unique');
        
        formData.set('type', types.join(','));

        $.ajax({
            url: '{{ route("gestion.grupos.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    window.location.href = '{{ route("gestion.grupos.index") }}';
                } else {
                    alert(response.message || 'Error al crear el grupo');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                alert(response.message || 'Error al crear el grupo');
            }
        });
    });

    // Mostrar/ocultar opciones de PosixGroup
    $('#posixCheck').on('change', function() {
        if ($(this).is(':checked')) {
            $('#posixOptions').show();
        } else {
            $('#posixOptions').hide();
            $('#gidNumber').val('');
        }
    });

    // Validar que al menos un tipo esté seleccionado
    $('input[name="type[]"]').on('change', function() {
        const posixChecked = $('#posixCheck').is(':checked');
        const uniqueChecked = $('#uniqueCheck').is(':checked');
        
        if (!posixChecked && !uniqueChecked) {
            $(this).prop('checked', true);
            alert('Debe seleccionar al menos un tipo de grupo');
        }
    });
});
</script>
@endpush

@push('styles')
<style>
.collapse {
    transition: all 0.3s ease;
}
.card-body.bg-dark {
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 4px;
}
</style>
@endpush
@endsection 