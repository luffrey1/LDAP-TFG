<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - Panel de Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body {
            height: 100vh;
            background: linear-gradient(135deg, #0061f2 0%, #1245a8 100%);
            display: flex;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .login-form {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.15);
            overflow: hidden;
        }
        .login-header {
            background: #0061f2;
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 5px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background-color: #0061f2;
            border-color: #0061f2;
        }
        .btn-login:hover {
            background-color: #0053d9;
            border-color: #0053d9;
        }
        .login-footer {
            background-color: #f8f9fa;
            padding: 1rem;
            text-align: center;
            border-top: 1px solid #e3e6ec;
        }
        .input-group-text {
            background-color: #f8f9fa;
        }
        .alert {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-form">
            <div class="login-header">
                <h1 class="h3 mb-3 fw-normal"><i class="fas fa-school me-2"></i> Panel de Gestión</h1>
                <p class="mb-0">Departamento IT</p>
            </div>
            
            <div class="login-body">
                @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif
                
                <form method="POST" action="{{ route('auth.login') }}">
                    @csrf
                    
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Nombre de usuario" value="{{ old('username') }}" required autofocus>
                    </div>
                    
                    <div class="input-group mb-4">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                    </div>
                    
                    <div class="d-grid">
                        <button class="btn btn-primary btn-login" type="submit">Iniciar sesión</button>
                    </div>
                </form>
            </div>
            
            <div class="login-footer">
                <p class="mb-0">Para acceder como administrador: <br>usuario: <strong>ldap-admin</strong>, contraseña: <strong>admin(o la que tengas configurada)</strong></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 