<?php

try {
    $pdo = new PDO('mysql:host=127.0.0.1', 'root', '');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS stockify_multishop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS stockify_multishop_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo "Databases created\n";
} catch (Throwable $e) {
    echo 'Error: '.$e->getMessage()."\n";
    exit(1);
}
