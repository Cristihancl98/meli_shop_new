<?php

return [
    'switch_connection'      => env('TENANCY_SWITCH_CONNECTION', true),
    'database_prefix'        => env('TENANCY_DATABASE_PREFIX', 'meli_store_'),
    'create_database'        => env('TENANCY_CREATE_DATABASE', true),
    'username_from_database' => env('TENANCY_DB_USERNAME_FROM_DATABASE', false),
    'migrations_path'        => 'database/migrations/tenant',
    'session_key'            => 'store_id',
    'jwt_claim'              => 'store',
];
