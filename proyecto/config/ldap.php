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
            'hosts' => [env('LDAP_DEFAULT_HOSTS', env('LDAP_HOST', '172.19.0.4'))],
            'username' => env('LDAP_DEFAULT_USERNAME', env('LDAP_USERNAME', 'cn=admin,dc=test,dc=tierno,dc=es')),
            'password' => env('LDAP_DEFAULT_PASSWORD', env('LDAP_PASSWORD', 'admin')),
            'port' => env('LDAP_DEFAULT_PORT', env('LDAP_PORT', 389)),
            'base_dn' => env('LDAP_DEFAULT_BASE_DN', env('LDAP_BASE_DN', 'dc=test,dc=tierno,dc=es')),
            'timeout' => env('LDAP_DEFAULT_TIMEOUT', env('LDAP_TIMEOUT', 5)),
            'use_ssl' => env('LDAP_DEFAULT_SSL', env('LDAP_SSL', false)),
            'use_tls' => env('LDAP_DEFAULT_TLS', env('LDAP_TLS', false)),
            'use_sasl' => env('LDAP_SASL', false),
            'version' => env('LDAP_VERSION', 3),
            'options' => [
                LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,
                LDAP_OPT_REFERRALS => 0,
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