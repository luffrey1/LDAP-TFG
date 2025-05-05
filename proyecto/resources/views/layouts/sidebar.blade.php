<li class="nav-item dropdown {{ request()->is('monitor*') ? 'active' : '' }}">
    <a href="#" class="nav-link has-dropdown" data-toggle="dropdown">
        <i class="fas fa-server"></i>
        <span>Monitoreo</span>
    </a>
    <ul class="dropdown-menu">
        <li class="{{ request()->is('monitor') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('monitor.index') }}">
                <i class="fas fa-desktop"></i> Panel de Hosts
            </a>
        </li>
        <li class="{{ request()->is('monitor/create') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('monitor.create') }}">
                <i class="fas fa-plus"></i> Añadir Host
            </a>
        </li>
        <li class="{{ request()->is('monitor/scan') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('monitor.scan') }}">
                <i class="fas fa-search"></i> Escanear Red
            </a>
        </li>
    </ul>
</li> 