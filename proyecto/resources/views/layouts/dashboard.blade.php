<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Departamento IT</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    
    <!-- AOS - Animate On Scroll -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4e73df;
            --primary-dark: #3a56b0;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #1a1c24;
            --darker-color: #13141a;
            --text-color: #e0e0e0;
            --text-muted: #b3b3b3;
            --border-color: #2e3140;
            --card-bg: #222533;
        }
        
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--dark-color);
            color: var(--text-color);
            transition: all 0.3s ease;
            overflow-x: hidden;
        }
        
        /* Scrollbar personalizado */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--dark-color);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #444;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color);
        }
        
        /* Navbar */
        .navbar {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(0, 0, 0, 0.3);
            background: var(--darker-color);
            padding: 0.5rem 1rem;
            z-index: 1040;
            height: 56px;
        }
        
        .navbar .navbar-brand {
            font-weight: 600;
            font-size: 1.1rem;
            letter-spacing: 0.05rem;
            color: var(--text-color);
        }
        
        .navbar .dropdown-menu {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.2);
            background-color: var(--darker-color);
            min-width: 200px;
        }
        
        .navbar .dropdown-item {
            color: var(--text-color);
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .navbar .dropdown-item:hover {
            background-color: var(--card-bg);
        }
        
        .navbar .dropdown-divider {
            border-top: 1px solid var(--border-color);
            margin: 0.5rem 0;
        }
        
        .navbar .nav-link {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .navbar .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
            margin-left: 0.5rem;
        }
        
        /* Sidebar */
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: var(--darker-color);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(0, 0, 0, 0.3);
            z-index: 1;
            transition: all 0.3s ease-in-out;
        }
        
        .sidebar-brand {
            height: 4.375rem;
            padding: 1.5rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--darker-color);
        }
        
        .sidebar a {
            color: var(--text-muted);
            padding: 0.8rem 1.25rem;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s;
            border-radius: 0.35rem;
            margin: 0.2rem 0.75rem;
        }
        
        .sidebar a i {
            font-size: 0.85rem;
            margin-right: 0.75rem;
            width: 1.5rem;
            text-align: center;
            transition: all 0.3s;
        }
        
        .sidebar a:hover {
            color: var(--primary-color);
            background-color: rgba(78, 115, 223, 0.1);
            transform: translateX(5px);
        }
        
        .sidebar a.active {
            color: white;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .sidebar a.active i {
            transform: rotate(10deg);
        }
        
        .sidebar .nav-item-header {
            padding: 1.2rem 1rem 0.5rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--secondary-color);
            letter-spacing: 0.08rem;
        }
        
        .sidebar-divider {
            border-top: 1px solid var(--border-color);
            margin: 1rem;
        }
        
        /* Contenido principal */
        .content-wrapper {
            min-height: calc(100vh - 56px);
            transition: all 0.3s ease;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(0, 0, 0, 0.15);
            background-color: var(--card-bg);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(0, 0, 0, 0.25);
        }
        
        .card-header {
            border-bottom: 1px solid var(--border-color);
            background-color: rgba(0, 0, 0, 0.1);
            padding: 1rem 1.25rem;
            font-weight: 700;
            color: var(--text-color);
            border-top-left-radius: 0.75rem !important;
            border-top-right-radius: 0.75rem !important;
        }
        
        .card-body {
            color: var(--text-color);
        }
        
        /* Texto */
        h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {
            color: var(--text-color);
        }
        
        .text-muted {
            color: var(--text-muted) !important;
        }
        
        /* Form text */
        .form-text {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-top: 0.25rem;
            font-style: italic;
        }
        
        /* Tablas */
        .table {
            color: var(--text-color);
        }
        
        .table-bordered {
            border-color: var(--border-color);
        }
        
        .table th, .table td {
            border-color: var(--border-color);
        }
        
        /* DataTables */
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter, 
        .dataTables_wrapper .dataTables_info, 
        .dataTables_wrapper .dataTables_processing, 
        .dataTables_wrapper .dataTables_paginate {
            color: var(--text-color);
        }
        
        /* Select2 */
        .select2-container--bootstrap-5 .select2-selection {
            background-color: var(--card-bg);
            border-color: var(--border-color);
            color: var(--text-color);
        }
        
        /* Botones */
        .btn {
            border-radius: 0.5rem;
            padding: 0.45rem 1.2rem;
            font-weight: 500;
            letter-spacing: 0.03rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        /* Formularios */
        .form-control, .form-select {
            background-color: var(--dark-color);
            border-color: var(--border-color);
            color: var(--text-color);
        }
        
        .form-control:focus, .form-select:focus {
            background-color: var(--dark-color);
            color: var(--text-color);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        
        .form-control::placeholder {
            color: var(--text-muted);
        }
        
        /* Badges */
        .badge {
            font-weight: 600;
        }
        
        /* Alertas */
        .alert {
            border: none;
            border-radius: 0.5rem;
        }
        
        /* Loader */
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        
        .loader-hidden {
            opacity: 0;
            visibility: hidden;
        }
        
        .loader {
            width: 48px;
            height: 48px;
            border: 5px solid #e3e6f0;
            border-bottom-color: var(--primary-color);
            border-radius: 50%;
            display: inline-block;
            animation: rotation 1s linear infinite;
        }
        
        @keyframes rotation {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Efecto hover para tarjetas de estadísticas */
        .stat-card {
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0) 50%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
            z-index: -1;
        }
        
        .stat-card:hover::before {
            transform: translateX(0);
        }
        
        /* Timeline para actividad reciente */
        .timeline {
            position: relative;
            padding-left: 2rem;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 0.85rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e3e6f0;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        
        .timeline-marker {
            position: absolute;
            left: -2rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background: var(--primary-color);
            box-shadow: 0 0 0 4px #f8f9fc;
        }
        
        .timeline-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        /* Dropdown personalizado */
        .dropdown-menu {
            padding: 0.5rem 0;
            border-radius: 0.5rem;
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .dropdown-item {
            padding: 0.5rem 1.5rem;
            font-weight: 400;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover, .dropdown-item:focus {
            background-color: #eaecf4;
            color: var(--primary-color);
        }
        
        /* Toast/Notificaciones */
        .toast-container {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 1060;
        }
        
        /* Sidebar toggle para móviles */
        .sidebar-toggle {
            display: none;
            background: transparent;
            border: none;
            font-size: 1.25rem;
            color: white;
            cursor: pointer;
        }
        
        /* Responsive */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                top: 56px;
                width: 85% !important;
                max-width: 250px;
                height: calc(100vh - 56px);
                overflow-y: auto;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .content-wrapper {
                margin-left: 0 !important;
            }
            
            .sidebar-toggle {
                display: block;
            }
            
            .navbar-brand {
                font-size: 1rem;
            }
        }
        
        /* Estilos para el perfil en el sidebar */
        .sidebar-profile {
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(to right, var(--darker-color), var(--card-bg));
        }

        .sidebar-profile .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .sidebar-profile .user-avatar i {
            position: relative;
            top: 1px;
        }

        .sidebar-profile .user-info {
            flex: 1;
            min-width: 0;
            margin-left: 12px;
        }

        .sidebar-profile .user-name {
            font-weight: 600;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--text-color);
            margin-bottom: 2px;
        }

        .sidebar-profile .user-role {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: capitalize;
        }

        .sidebar-profile .dropdown-toggle {
            padding: 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .sidebar-profile .dropdown-toggle::after {
            display: inline-block;
            margin-left: 8px;
            vertical-align: middle;
            content: "\f107";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 0.9rem;
            color: var(--text-muted);
            transition: all 0.3s ease;
        }

        .sidebar-profile .dropdown-toggle:hover::after {
            color: var(--primary-color);
            transform: translateY(2px);
        }

        .sidebar-profile .dropdown-item {
            color: var(--text-color);
            padding: 10px 16px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .sidebar-profile .dropdown-item:hover {
            background-color: var(--card-bg);
            color: var(--primary-color);
            padding-left: 20px;
        }

        .sidebar-profile .dropdown-item i {
            width: 20px;
            text-align: center;
            margin-right: 8px;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .sidebar-profile .dropdown-item:hover i {
            color: var(--primary-color);
        }

        .sidebar-profile .dropdown-divider {
            border-top: 1px solid var(--border-color);
            margin: 8px 0;
        }
    </style>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('styles')
</head>
<body>

    <!-- Loader -->
    <div class="page-loader">
        <span class="loader"></span>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <button class="sidebar-toggle me-2" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard.index') }}">
                <i class="fas fa-school me-2"></i> Departamento IT
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="me-2">
                                @if(session('auth_user'))
                                    {{ session('auth_user')['nombre'] ?? session('auth_user')['username'] ?? session('auth_user')['email'] ?? 'Usuario' }}
                                @else
                                    Usuario
                                @endif
                            </span>
                            <div class="user-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fas fa-user-cog fa-fw me-2 text-gray-400"></i> Ver Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('auth.logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-sign-out-alt fa-fw me-2 text-gray-400"></i> Cerrar Sesión
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar" id="sidebar">
                <div class="position-sticky">
                    <!-- Perfil de usuario -->
                    <div class="sidebar-profile p-3 mb-3">
                        <div class="dropdown">
                            <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="user-avatar me-2">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="user-info">
                                    <div class="user-name">
                                        @if(session('auth_user'))
                                            {{ session('auth_user')['nombre'] ?? session('auth_user')['username'] ?? session('auth_user')['email'] ?? 'Usuario' }}
                                        @else
                                            Usuario
                                        @endif
                                    </div>
                                    <div class="user-role text-muted small">
                                        {{ session('auth_user')['role'] ?? 'Usuario' }}
                                    </div>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fas fa-user-cog fa-fw me-2"></i> Ver Perfil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('auth.logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas fa-sign-out-alt fa-fw me-2"></i> Cerrar Sesión
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard.index') ? 'active' : '' }}" href="{{ route('dashboard.index') }}">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        
                        <!-- Usuarios LDAP - Visible para todos -->
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('gestion/usuarios*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                                <i class="fas fa-users-cog"></i> Usuarios LDAP
                            </a>
                        </li>
                        
                        @if(\App\Models\SistemaConfig::obtenerConfig('modulo_documentos_activo', true))
                        <div class="sidebar-divider"></div>
                        <div class="nav-item-header">Documentos</div>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard.gestion-documental*') ? 'active' : '' }}" href="{{ route('dashboard.gestion-documental') }}">
                                <i class="fas fa-folder"></i> Gestión Documental
                            </a>
                        </li>
                        @endif
                        
                        <div class="sidebar-divider"></div>
                        <div class="nav-item-header">Comunicación</div>
                        
                        @if(\App\Models\SistemaConfig::obtenerConfig('modulo_mensajeria_activo', true))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard.mensajes*') ? 'active' : '' }}" href="{{ route('dashboard.mensajes') }}">
                                <i class="fas fa-envelope"></i> Mensajería Interna
                            </a>
                        </li>
                        @endif
                        
                        @if(\App\Models\SistemaConfig::obtenerConfig('modulo_calendario_activo', true))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard.calendario*') ? 'active' : '' }}" href="{{ route('dashboard.calendario') }}">
                                <i class="fas fa-calendar-alt"></i> Calendario
                            </a>
                        </li>
                        @endif
                        
                        @if(\App\Models\SistemaConfig::obtenerConfig('modulo_monitoreo_activo', true))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('monitor.index') ? 'active' : '' }}" href="{{ route('monitor.index') }}">
                                <i class="fas fa-desktop"></i> Monitor de Equipos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('monitor.health') ? 'active' : '' }}" href="{{ route('monitor.health') }}">
                                <i class="fas fa-heartbeat"></i> Estado global de equipos
                            </a>
                        </li>
                        @endif
                        
                        <!-- Admin Menu -->
                        @if(session('auth_user.is_admin') || session('auth_user.username') === 'ldap-admin')
                        <div class="sidebar-divider"></div>
                        <div class="nav-item-header">Administración</div>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('admin/logs') ? 'active' : '' }}" href="{{ route('admin.logs') }}">
                                <i class="fas fa-clipboard-list"></i> Logs LDAP
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('admin/configuracion') ? 'active' : '' }}" href="{{ route('admin.configuracion.index') }}">
                                <i class="fas fa-cogs"></i> Configuración
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('admin/gestion/grupos*') ? 'active' : '' }}" href="{{ route('admin.groups.index') }}">
                                <i class="fas fa-users-cog"></i>
                                <span>Gestión de Grupos LDAP</span>
                            </a>
                        </li>
                        @endif
                        
                        <!-- Gestión Académica -->
                        <div class="sidebar-divider"></div>
                        <div class="nav-item-header">Gestión Académica</div>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('profesor.clases.*') ? 'active' : '' }}" href="{{ route('profesor.clases.index') }}">
                                <i class="fas fa-chalkboard"></i> Gestión de Clases
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('profesor.clases.mias') || request()->routeIs('profesor.clases.mias.ver') ? 'active' : '' }}" href="{{ route('profesor.clases.mias') }}">
                                <i class="fas fa-chalkboard-teacher"></i> Mis Clases
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('profesor.alumnos.*') ? 'active' : '' }}" href="{{ route('profesor.alumnos.index') }}">
                                <i class="fas fa-user-graduate"></i> Gestión de Alumnos
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper">
                @yield('content')
            </main>
        </div>
    </div>
    
    <!-- Toast container para notificaciones -->
    <div class="toast-container"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- AOS - Animate On Scroll -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    
    <!-- CountUp.js (versión UMD sin export) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/countup.js/2.0.7/countUp.umd.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Inicialización de AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
        
        // Loader
        window.addEventListener('load', function() {
            const loader = document.querySelector('.page-loader');
            loader.classList.add('loader-hidden');
        });
        
        // Toggle sidebar en móviles
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
        
        // Cerrar sidebar en móviles al hacer clic fuera
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            
            if (window.innerWidth < 992 && 
                !sidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target) && 
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
        
        // Inicialización de Select2
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5'
            });
            
            // DataTables con configuración en español
            $('.datatable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                responsive: true
            });
            
            // Tooltips
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        });
        
        // Función para mostrar notificaciones (toast)
        function showNotification(message, type = 'success') {
            const toastContainer = document.querySelector('.toast-container');
            const iconClass = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle';
            
            const toastHtml = `
                <div class="toast align-items-center border-0 bg-${type === 'error' ? 'danger' : type}" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body text-white">
                            <i class="fas fa-${iconClass} me-2"></i> ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            const toastElement = toastContainer.lastElementChild;
            const toast = new bootstrap.Toast(toastElement, {autohide: true, delay: 5000});
            toast.show();
            
            // Remover toast después de ocultarse
            toastElement.addEventListener('hidden.bs.toast', function () {
                toastElement.remove();
            });
        }
    </script>
    
    @section('js')
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Función global para eliminar host
        function eliminarHost(id, nombre) {
            var form = document.getElementById('form-eliminar-' + id);
            if (!form) {
                alert('No se encontró el formulario para eliminar el host.');
                return;
            }
            if (confirm('¿Está seguro que desea eliminar el equipo "' + nombre + '"? Esta acción no se puede deshacer.')) {
                form.submit();
            }
        }
    </script>
    @show
    @stack('scripts')
</body>
</html> 