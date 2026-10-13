<?php

return [
    /*
     | Dónde se escribe la bitácora de auditoría:
     |  - mongodb: colección "auditoria" en MongoDB (producción y desarrollo)
     |  - log:     archivo de log local (respaldo si no hay MongoDB)
     |  - array:   en memoria, solo para las pruebas automatizadas
     */
    'driver' => env('AUDIT_DRIVER', 'mongodb'),

    'canal_log' => env('AUDIT_LOG_CHANNEL', 'daily'),

    'mongodb_uri' => env('MONGODB_URI', 'mongodb://127.0.0.1:27017'),

    'mongodb_database' => env('MONGODB_DATABASE', 'farmacia_auditoria'),
];
