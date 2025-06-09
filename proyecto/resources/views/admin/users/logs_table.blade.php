@foreach($logs as $log)
<tr class="log-row" data-id="{{ $log->id }}" data-type="{{ $log->type }}">
    <td>
        <span class="badge badge-info text-black">
            <i class="fas fa-user mr-1"></i>{{ $log->user }}
        </span>
    </td>
    <td>
        <span class="badge badge-{{ $log->level === 'WARNING' ? 'warning' : 'success' }} text-black">
            {{ $log->action }}
        </span>
    </td>
    <td class="text-black">{{ $log->description }}</td>
    <td>
        <span class="text-muted">
            <i class="far fa-clock mr-1"></i>
            {{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}
        </span>
    </td>
</tr>
@endforeach 