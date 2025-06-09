<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Restringido - IES Tierno Galván</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #2c3e50;
        }

        .warning-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 800px;
            width: 90%;
            text-align: center;
        }

        .logo {
            max-width: 200px;
            margin-bottom: 2rem;
        }

        .title {
            color: #e74c3c;
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .message {
            font-size: 1.2rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            color: #34495e;
        }

        .details {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }

        .details p {
            margin: 0.5rem 0;
            font-size: 1rem;
            color: #7f8c8d;
        }

        .details strong {
            color: #2c3e50;
        }

        .footer {
            font-size: 0.9rem;
            color: #95a5a6;
            margin-top: 2rem;
        }

        .security-icon {
            font-size: 4rem;
            color: #e74c3c;
            margin-bottom: 1rem;
        }

        .contact-info {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #ecf0f1;
        }

        .contact-info p {
            margin: 0.5rem 0;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="warning-container">
        <img src="{{ asset('images/logo.png') }}" alt="IES Tierno Galván" class="logo">
        
        <div class="security-icon">🔒</div>
        
        <h1 class="title">Acceso Restringido</h1>
        
        <div class="message">
            Estimado estudiante,<br>
            Has intentado acceder a una sección restringida del sistema. 
            Este acceso está limitado exclusivamente al personal autorizado del centro.
        </div>

        <div class="details">
            <p><strong>Detalles del intento de acceso:</strong></p>
            <p>• Usuario: {{ session('auth_user.username') }}</p>
            <p>• Nombre: {{ session('auth_user.nombre') }}</p>
            <p>• Origen: {{ gethostbyaddr(request()->ip()) }}</p>
            <p>• Fecha y hora: {{ now()->format('d/m/Y H:i:s') }}</p>
            <p>• ID de sesión: {{ session()->getId() }}</p>
        </div>


        <div class="footer">
            <p>© {{ date('Y') }} IES Tierno Galván - Todos los derechos reservados</p>
            <p>Este incidente ha sido registrado y notificado al personal autorizado.</p>
        </div>
    </div>

    <script>
        // Registrar el intento de acceso
        fetch('/api/log-access-attempt', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                username: '{{ session('auth_user.username') }}',
                hostname: '{{ gethostbyaddr(request()->ip()) }}'
            })
        });
    </script>
</body>
</html> 