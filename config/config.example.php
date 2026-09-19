<?php

declare(strict_types=1);

/**
 * MODELLO di configurazione — solo segnaposto, nessun segreto.
 *
 * Il file vero lo genera deploy/00-bootstrap.sh in
 *   /etc/orangeroad/config.php
 * L'app lo cerca, in ordine: $ORANGEROAD_CONFIG, /etc/orangeroad/config.php,
 * /etc/orangeroad/config.php, <progetto>/config/config.php.
 */

return [
    'app' => [
        'name'        => 'Cento Gradini',
        'env'         => 'production',
        'debug'       => false,
        'timezone'    => 'Europe/Rome',
        'pretty_urls' => true,
        'base_path'   => null,
        'public_url'  => 'https://example.com/orangeroad',
    ],

    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'kor_orangeroad',
        'user'    => 'kor_orangeroad',
        'pass'    => 'CAMBIAMI',
        'charset' => 'utf8mb4',
    ],

    'security' => [
        'session_name' => 'orangeroad_sess',
        'session_ttl'  => 60 * 60 * 8,
    ],

    // 'log' scrive su storage/logs/app.log invece di spedire davvero.
    'mail' => [
        'transport'   => 'log',
        'smtp_host'   => 'smtp-relay.brevo.com',
        'smtp_port'   => 587,
        'smtp_secure' => 'tls',
        'smtp_user'   => 'CAMBIAMI',
        'smtp_pass'   => 'CAMBIAMI',
        'from_email'  => 'CAMBIAMI',   // mittente verificato dal provider
        'from_name'   => 'Cento Gradini',
        'timeout'     => 15,
    ],

    'notify' => [
        'new_registration' => true,
        'admin_email'      => 'admin@example.com',
    ],
];
