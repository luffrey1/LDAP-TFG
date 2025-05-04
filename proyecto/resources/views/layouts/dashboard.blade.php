<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            padding: 0.75rem 1rem;
            z-index: 1040;
        }
        
        .navbar .navbar-brand {
            font-weight: 700;
            font-size: 1.2rem;
            letter-spacing: 0.05rem;
            color: var(--text-color);
        }
        
        .navbar .dropdown-menu {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.2);
            background-color: var(--darker-color);
        }
        
        .navbar .dropdown-item {
            color: var(--text-color);
        }
        
        .navbar .dropdown-item:hover {
            background-color: var(--card-bg);
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
    </style>
    
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
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <span class="me-2 d-none d-lg-inline">{{ session('auth_user')['nombre'] ?? 'Usuario' }}</span>
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fas fa-user text-primary"></i>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user-cog fa-fw me-2 text-gray-400"></i> Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('auth.logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-sign-out-alt fa-fw me-2 text-gray-400"></i> Cerrar sesión
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
                    <ul class="nav flex-column">
                        @php
                            $moduloMensajeriaActivo = App\Models\SistemaConfig::obtenerConfig('modulo_mensajeria_activo', true);
                            $moduloCalendarioActivo = App\Models\SistemaConfig::obtenerConfig('modulo_calendario_activo', true);
                            $moduloDocumentosActivo = App\Models\SistemaConfig::obtenerConfig('modulo_documentos_activo', true);
                        @endphp
                        
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard.index') ? 'active' : '' }}" href="{{ route('dashboard.index') }}">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        
                        @if($moduloDocumentosActivo || session('auth_user.is_admin') || session('auth_user.username') === 'ldap-admin')
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
                        
                        @if($moduloMensajeriaActivo || session('auth_user.is_admin') || session('auth_user.username') === 'ldap-admin')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard.mensajes*') ? 'active' : '' }}" href="{{ route('dashboard.mensajes') }}">
                                <i class="fas fa-envelope"></i> Mensajería Interna
                            </a>
                        </li>
                        @endif
                        
                        @if($moduloCalendarioActivo || session('auth_user.is_admin') || session('auth_user.username') === 'ldap-admin')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard.calendario*') ? 'active' : '' }}" href="{{ route('dashboard.calendario') }}">
                                <i class="fas fa-calendar-alt"></i> Calendario
                            </a>
                        </li>
                        @endif
                        
                        <!-- Admin Menu -->
                        @if(session('auth_user.is_admin') || session('auth_user.username') === 'ldap-admin')
                        <div class="sidebar-divider"></div>
                        <div class="nav-item-header">Administración</div>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('admin/usuarios*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                                <i class="fas fa-users-cog"></i> Usuarios LDAP
                            </a>
                        </li>
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
                        @endif
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
    
    @yield('scripts')
    @stack('scripts')
</body>
</html> 