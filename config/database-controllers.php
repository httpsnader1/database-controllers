<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Package Route Prefix
    |--------------------------------------------------------------------------
    |
    | This value will be used as the route prefix for the package.
    |
    */
    'route_prefix' => env('DB_CTRL_ROUTE_PREFIX', 'database-controllers'),

    /*
    |--------------------------------------------------------------------------
    | Backup File Prefix
    |--------------------------------------------------------------------------
    |
    | This value will be used as the prefix for all generated backup files.
    |
    */
    'backup_prefix' => env('DB_CTRL_BACKUP_PREFIX', 'backup'),

    /*
    |--------------------------------------------------------------------------
    | Excluded Tables
    |--------------------------------------------------------------------------
    |
    | These tables will be excluded from the database table listing and backups.
    |
    */
    'excluded_tables' => [],

    /*
    |--------------------------------------------------------------------------
    | Password Protection
    |--------------------------------------------------------------------------
    |
    | If set, a login screen will appear before accessing the dashboard.
    | Set to null to disable password protection.
    |
    */
    'password' => env('DB_CTRL_PASSWORD', 1234),
];
