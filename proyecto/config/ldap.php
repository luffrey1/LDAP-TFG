<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default LDAP Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the LDAP connections below you wish
    | to use as your default connection for all LDAP operations. Of
    | course you may use many connections at once.
    |
    */

    'default' => env('LDAP_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | LDAP Connections
    |--------------------------------------------------------------------------
    |
    | Below you may configure each LDAP connection your application requires
    | access to. Be sure to include a valid base DN - otherwise you may
    | not receive any results when performing LDAP search operations.
    |
    */

    'connections' => [
        'default' => [
            'hosts' => [env('LDAP_DEFAULT_HOSTS', env('LDAP_HOST', 'openldap-osixia'))],
            'username' => env('LDAP_DEFAULT_USERNAME', env('LDAP_USERNAME', 'cn=admin,dc=tierno,dc=es')),
            'password' => env('LDAP_DEFAULT_PASSWORD', env('LDAP_PASSWORD', 'admin')),
            'port' => 389,
            'base_dn' => env('LDAP_DEFAULT_BASE_DN', env('LDAP_BASE_DN', 'dc=tierno,dc=es')),
            'timeout' => env('LDAP_DEFAULT_TIMEOUT', env('LDAP_TIMEOUT', 5)),
            'use_ssl' => false,
            'use_tls' => false,
            'use_sasl' => false,
            'version' => 3,
            'options' => [
                LDAP_OPT_REFERRALS => 0,
                LDAP_OPT_PROTOCOL_VERSION => 3,
                LDAP_OPT_NETWORK_TIMEOUT => 5,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | LDAP Logging
    |--------------------------------------------------------------------------
    |
    | When LDAP logging is enabled, all LDAP search and modification operations are
    | logged using the default application logging stack. This can be useful for
    | debugging connectivity issues to your LDAP server.
    |
    */

    'logging' => env('LDAP_LOGGING', true),

    /*
    |--------------------------------------------------------------------------
    | LDAP Authentication
    |--------------------------------------------------------------------------
    |
    | Here you may configure the LDAP authentication settings for your application.
    | This is used by the built-in Laravel authentication provider for verifying
    | user credentials in your database.
    |
    */

    'auth' => [
        'provider' => env('LDAP_AUTH_PROVIDER', 'eloquent'),
        'model' => env('LDAP_AUTH_MODEL', App\Models\User::class),
        'rules' => [
            'username' => env('LDAP_AUTH_USERNAME_RULE', 'uid'),
            'password' => env('LDAP_AUTH_PASSWORD_RULE', 'userpassword'),
        ],
        'scopes' => [
            'username' => env('LDAP_AUTH_USERNAME_SCOPE', 'uid'),
        ],
        'identifiers' => [
            'ldap' => env('LDAP_AUTH_IDENTIFIER', 'guid'),
            'database' => env('LDAP_AUTH_DATABASE_IDENTIFIER', 'guid'),
        ],
        'passwords' => [
            'sync' => env('LDAP_AUTH_PASSWORD_SYNC', true),
            'column' => env('LDAP_AUTH_PASSWORD_COLUMN', 'password'),
        ],
        'login_fallback' => env('LDAP_AUTH_LOGIN_FALLBACK', true),
        'sync_attributes' => [
            'name' => 'cn',
            'email' => 'mail',
        ],
    ],

]; 