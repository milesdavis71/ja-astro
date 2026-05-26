<?php

declare(strict_types=1);

function load_smtp_config(string $configPath): array
{
    if (!is_file($configPath)) {
        throw new RuntimeException('Hiányzik az SMTP konfigurációs fájl.');
    }

    $config = require $configPath;
    if (!is_array($config)) {
        throw new RuntimeException('Az SMTP konfigurációs fájl érvénytelen.');
    }

    $requiredKeys = [
        'host',
        'port',
        'username',
        'password',
        'sender_email',
        'sender_name',
    ];

    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $config)) {
            throw new RuntimeException('Hiányzó SMTP beállítás: ' . $key);
        }
    }

    return [
        'host' => trim((string)$config['host']),
        'port' => (int)$config['port'],
        'username' => trim((string)$config['username']),
        'password' => (string)$config['password'],
        'sender_email' => trim((string)$config['sender_email']),
        'sender_name' => trim((string)$config['sender_name']),
    ];
}
