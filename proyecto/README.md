<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development/)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# Sistema de Gestión Integrado

## Configuración del Nodo de Ejecución Remota

Para mejorar la funcionalidad de monitoreo de equipos, el sistema puede usar un nodo de ejecución remota en Linux. Esto permite:

1. Ejecutar comandos de ping consistentes desde Linux, independientemente del sistema operativo del servidor web
2. Detectar correctamente todos los dispositivos de red, incluyendo routers y switches
3. Ejecutar comandos en equipos remotos de forma masiva (característica futura)

### Pasos para configurar un nodo de ejecución:

1. Configure un servidor Linux (puede ser una máquina virtual pequeña)
2. Asegúrese de que sea accesible por SSH
3. Configure un usuario específico con permisos para ejecutar ping y arp
4. Actualice el archivo `.env` con la información de este servidor:

```
SSH_EXECUTION_HOST=192.168.1.100
SSH_EXECUTION_USER=executor
SSH_EXECUTION_PASSWORD=contraseña
SSH_EXECUTION_PORT=22
```

5. Asegúrese de tener instalada la biblioteca phpseclib:

```
composer require phpseclib/phpseclib:^3.0
```

### Ventajas del nodo de ejecución remota:

- Permite escaneos de red consistentes desde cualquier sistema operativo
- Facilita el monitoreo de dispositivos en la red institucional
- Habilita la ejecución futura de comandos remotos en aulas informáticas

### Operación sin nodo de ejecución:

Si no configura un nodo de ejecución, el sistema funcionará con capacidades reducidas:
- Utilizará los comandos nativos del sistema donde se ejecuta la aplicación web
- Los comandos de ping pueden variar según el sistema operativo
- Algunos dispositivos como routers podrían no ser detectados correctamente
