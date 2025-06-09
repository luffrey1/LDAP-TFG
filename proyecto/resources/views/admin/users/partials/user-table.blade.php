@forelse ($users as $user)
    <tr>
        <td>
            @if (is_array($user))
                {{ $user['uid'][0] ?? 'N/A' }}
            @else
                {{ $user->getFirstAttribute('uid') ?? 'N/A' }}
            @endif
        </td>
        <td>
            @if (is_array($user))
                {{ $user['givenname'][0] ?? 'N/A' }}
            @else
                {{ $user->getFirstAttribute('givenname') ?? 'N/A' }}
            @endif
        </td>
        <td>
            @if (is_array($user))
                {{ $user['sn'][0] ?? 'N/A' }}
            @else
                {{ $user->getFirstAttribute('sn') ?? 'N/A' }}
            @endif
        </td>
        <td>
            @if (is_array($user))
                {{ $user['mail'][0] ?? 'N/A' }}
            @else
                {{ $user->getFirstAttribute('mail') ?? 'N/A' }}
            @endif
        </td>
        <td>
            @php
                $uid = is_array($user) ? ($user['uid'][0] ?? '') : $user->getFirstAttribute('uid');
            @endphp
            @if (isset($userGroups[$uid]))
                @foreach ($userGroups[$uid] as $group)
                    <a href="{{ route('admin.users.index', ['group' => $group]) }}" class="badge bg-info text-decoration-none">{{ $group }}</a>
                @endforeach
            @else
                <span class="badge bg-secondary">{{ __('Sin grupos') }}</span>
            @endif
        </td>
        <td class="text-center">
            @php
                $encodedDn = is_array($user) ? ($user['encoded_dn'] ?? '') : $user->encoded_dn;
                $userDn = is_array($user) ? ($user['dn'] ?? '') : $user->getDn();
                $isAdmin = in_array($userDn, $adminUsers ?? []);
                
                if (empty($encodedDn) && !empty($userDn)) {
                    $encodedDn = base64_encode($userDn);
                }
            @endphp
            
            <div class="btn-group" role="group">
                <a href="{{ route('admin.users.edit', $encodedDn) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-edit"></i>
                </a>
                
                @if(session('auth_user.is_admin') || session('auth_user.username') === 'ldap-admin')
                <button type="button" class="btn btn-sm {{ $isAdmin ? 'btn-warning' : 'btn-secondary' }} toggle-admin" 
                        data-dn="{{ $encodedDn }}" 
                        title="{{ $isAdmin ? 'Quitar admin' : 'Hacer admin' }}">
                    <i class="fas fa-crown"></i>
                </button>
                @endif
                
                <form action="{{ route('admin.users.destroy', $encodedDn) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de que desea eliminar este usuario?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="text-center py-4">
            <div class="alert alert-info mb-0">
                @if (isset($connectionError) && $connectionError)
                    <i class="fas fa-exclamation-triangle me-2"></i> {{ __('No se pueden mostrar usuarios debido a un error de conexión') }}
                @else
                    <i class="fas fa-info-circle me-2"></i> {{ __('No se encontraron usuarios') }}
                @endif
            </div>
        </td>
    </tr>
@endforelse

@push('scripts')
<script>
$(document).ready(function() {
    $('.toggle-admin').click(function() {
        const button = $(this);
        const dn = button.data('dn');
        const isCurrentlyAdmin = button.hasClass('btn-warning');
        
        $.ajax({
            url: '{{ route("admin.users.toggle-admin") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                dn: dn
            },
            success: function(response) {
                if (response.success) {
                    // Actualizar el botón
                    if (isCurrentlyAdmin) {
                        button.removeClass('btn-warning').addClass('btn-secondary');
                        button.attr('title', 'Hacer admin');
                    } else {
                        button.removeClass('btn-secondary').addClass('btn-warning');
                        button.attr('title', 'Quitar admin');
                    }
                    
                    // Mostrar mensaje de éxito
                    alert(response.message);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Error al actualizar el estado de administrador'));
            }
        });
    });
});
</script>
@endpush
