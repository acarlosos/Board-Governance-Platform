<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Retenção (dias)
    |--------------------------------------------------------------------------
    |
    | Ficheiros bgp-*.sql.gz com mtime mais antigo que este limite são removidos
    | pelo comando backup:clean.
    |
    */
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Disco e pasta (relativo ao disco)
    |--------------------------------------------------------------------------
    */
    'disk' => env('BACKUP_DISK', 'local'),

    'path' => env('BACKUP_PATH', 'backups'),

];
