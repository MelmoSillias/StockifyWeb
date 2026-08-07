<?php

declare(strict_types=1);

/**
 * Apply seed-signup-essentials.sql to stockify_multishop (requires migrated schema).
 * Usage: php scripts/run-signup-seed.php
 */

$seedFile = dirname(__DIR__).'/seeds/seed-signup-essentials.sql';

if (!is_file($seedFile)) {
    fwrite(STDERR, "Missing seed file: {$seedFile}\nRun: php seeds/generate-signup-seed.php\n");
    exit(1);
}

$sql = file_get_contents($seedFile);
if (false === $sql) {
    fwrite(STDERR, "Unable to read {$seedFile}\n");
    exit(1);
}

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=stockify_multishop;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec($sql);
    fwrite(STDOUT, "Applied seed-signup-essentials.sql to stockify_multishop\n");
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: '.$e->getMessage()."\n");
    exit(1);
}
