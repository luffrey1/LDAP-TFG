<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Depuración de Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Información de Sesión</h4>
            </div>
            <div class="card-body">
                <h5>Datos de auth_user:</h5>
                <pre class="bg-light p-3">{{ json_encode(session('auth_user'), JSON_PRETTY_PRINT) }}</pre>
                
                <h5 class="mt-4">is_admin presente: {{ session('auth_user')['is_admin'] ? 'SÍ' : 'NO' }}</h5>
                <h5>Valor de is_admin: {{ session('auth_user')['is_admin'] ? 'true' : 'false' }}</h5>
                <h5>Rol: {{ session('auth_user')['role'] ?? 'No definido' }}</h5>
                
                <h5 class="mt-4">Todos los datos de sesión:</h5>
                <pre class="bg-light p-3">{{ json_encode(session()->all(), JSON_PRETTY_PRINT) }}</pre>
            </div>
            <div class="card-footer">
                <a href="{{ route('dashboard.index') }}" class="btn btn-primary">Volver al Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html> 