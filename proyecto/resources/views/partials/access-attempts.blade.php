@if(isset($recentAccessAttempts) && $recentAccessAttempts->count() > 0)
<div class="access-attempts-alert">
    <div class="alert-content">
        <h3>⚠️ Intentos de acceso recientes</h3>
        <div class="attempts-list">
            @foreach($recentAccessAttempts as $attempt)
            <div class="attempt-item">
                <p><strong>Usuario:</strong> {{ $attempt->username }}</p>
                <p><strong>Nombre:</strong> {{ $attempt->nombre }}</p>
                <p><strong>Origen:</strong> {{ $attempt->hostname }}</p>
                <p><strong>IP:</strong> {{ $attempt->ip }}</p>
                <p><strong>Fecha y hora:</strong> {{ $attempt->created_at->format('d/m/Y H:i:s') }}</p>
            </div>
            @endforeach
        </div>
    </div>
</div>

<style>
.access-attempts-alert {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 400px;
    max-height: 80vh;
    overflow-y: auto;
}

.alert-content {
    background-color: #fff;
    border-left: 4px solid #e74c3c;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 20px;
    border-radius: 4px;
}

.alert-content h3 {
    color: #e74c3c;
    margin-top: 0;
    margin-bottom: 15px;
}

.attempts-list {
    max-height: 60vh;
    overflow-y: auto;
}

.attempt-item {
    border-bottom: 1px solid #eee;
    padding: 10px 0;
}

.attempt-item:last-child {
    border-bottom: none;
}

.attempt-item p {
    margin: 5px 0;
    color: #2c3e50;
    font-size: 0.9rem;
}

.attempt-item strong {
    color: #34495e;
}
</style>
@endif 