<?php

return [
    'switch_connection'   => env('TENANCY_SWITCH_CONNECTION', true),
    'database_prefix'     => env('TENANCY_DATABASE_PREFIX', 'meli_store_'),
    'migrations_path'     => 'database/migrations/tenant',
    'session_key'         => 'store_id',
    'jwt_claim'           => 'store',
];
