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
                            <button type="button" class="btn btn-info" data-bs-toggle="collapse" data-bs-target="#advancedOptions" aria-expanded="false" aria-controls="advancedOptions">
                                <i class="fas fa-cog"></i> Opciones Avanzadas
                            </button>
                        </div>

                        <div id="advancedOptions" class="collapse show">
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
                                    <div class="form-group text-white">
                                        <label for="memberUid">Miembros (UID)</label>
                                        <select name="memberUid[]" id="memberUid" class="form-control select2" multiple>
                                            <!-- Se llenará con AJAX -->
                                        </select>
                                    </div>
                                </div>

                                <div id="uniqueOptions" class="mt-3">
                                    <div class="form-group text-white">
                                        <label for="uniqueMember">Miembros (DN)</label>
                                        <select name="uniqueMember[]" id="uniqueMember" class="form-control select2" multiple>
                                            <!-- Se llenará con AJAX -->
                                        </select>
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
    // Inicializar Select2
    $('.select2').select2({
        placeholder: 'Selecciona los miembros',
        allowClear: true,
        theme: 'bootstrap4'
    });

    // Cargar usuarios para los selectores
    function loadUsers() {
        $.get('{{ route("admin.users.list") }}', function(data) {
            const users = data.users || [];
            
            // Limpiar y llenar los selectores
            $('#memberUid').empty();
            $('#uniqueMember').empty();
            
            users.forEach(user => {
                const uid = user.uid;
                const dn = user.dn;
                const displayName = `${user.givenname} ${user.sn} (${uid})`;
                
                // Añadir al selector de UID
                $('#memberUid').append(new Option(displayName, uid));
                
                // Añadir al selector de DN
                $('#uniqueMember').append(new Option(displayName, dn));
            });
        });
    }

    // Cargar usuarios al inicio
    loadUsers();

    // Función para validar el formulario
    function validateForm() {
        const posixChecked = $('#posixCheck').is(':checked');
        const uniqueChecked = $('#uniqueCheck').is(':checked');
        const gidNumber = $('#gidNumber').val();
        const memberUid = $('#memberUid').val();
        const uniqueMember = $('#uniqueMember').val();

        if (posixChecked && !gidNumber) {
            alert('El GID Number es requerido para grupos PosixGroup');
            return false;
        }

        if (!posixChecked && !uniqueChecked) {
            alert('Debe seleccionar al menos un tipo de grupo');
            return false;
        }

        if (posixChecked && (!memberUid || memberUid.length === 0)) {
            alert('Debe seleccionar al menos un miembro para el grupo PosixGroup');
            return false;
        }

        if (uniqueChecked && (!uniqueMember || uniqueMember.length === 0)) {
            alert('Debe seleccionar al menos un miembro para el grupo GroupOfUniqueNames');
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

    // Mostrar/ocultar opciones según el tipo de grupo
    function toggleOptions() {
        const posixChecked = $('#posixCheck').is(':checked');
        const uniqueChecked = $('#uniqueCheck').is(':checked');

        if (posixChecked) {
            $('#posixOptions').show();
        } else {
            $('#posixOptions').hide();
            $('#gidNumber').val('');
            $('#memberUid').val(null).trigger('change');
        }

        if (uniqueChecked) {
            $('#uniqueOptions').show();
        } else {
            $('#uniqueOptions').hide();
            $('#uniqueMember').val(null).trigger('change');
        }
    }

    // Inicializar visibilidad de opciones
    toggleOptions();

    // Manejar cambios en los checkboxes
    $('input[name="type[]"]').on('change', function() {
        const posixChecked = $('#posixCheck').is(':checked');
        const uniqueChecked = $('#uniqueCheck').is(':checked');
        
        if (!posixChecked && !uniqueChecked) {
            $(this).prop('checked', true);
            alert('Debe seleccionar al menos un tipo de grupo');
        }
        
        toggleOptions();
    });
});
</script>
@endpush

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css" rel="stylesheet">
<style>
.collapse {
    transition: all 0.3s ease;
}
.card-body.bg-dark {
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 4px;
}
.select2-container {
    width: 100% !important;
}
.select2-container--bootstrap4 .select2-selection {
    background-color: #343a40;
    border-color: #6c757d;
    color: #fff;
}
.select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
    background-color: #495057;
    border-color: #6c757d;
    color: #fff;
}
.select2-container--bootstrap4 .select2-dropdown {
    background-color: #343a40;
    border-color: #6c757d;
}
.select2-container--bootstrap4 .select2-results__option {
    color: #fff;
}
.select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected] {
    background-color: #495057;
}
</style>
@endpush
@endsection 