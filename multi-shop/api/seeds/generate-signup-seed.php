<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use App\AccessAudit\Domain\PermissionCatalog;

$perms = PermissionCatalog::all();
$roles = PermissionCatalog::predefinedRoles();
$rolePerms = PermissionCatalog::rolePermissions();

$out = [];
$out[] = '-- Seed Data Plane Stockify Multi-Shop (RBAC essentiel)';
$out[] = '-- DB : stockify_multishop (MariaDB / MySQL)';
$out[] = '--';
$out[] = '-- Prerequis pour tester la creation de comptes (signup) :';
$out[] = '--   - role "gerant" + permissions associees (obligatoire au provisionnement)';
$out[] = '--   - unites de mesure de base (catalogue apres creation de boutique)';
$out[] = '--';
$out[] = '-- Ne cree PAS de shops / users / tenant_accounts : le signup les cree.';
$out[] = '--';
$out[] = '-- Control Plane (sim_saas_admin) : executer aussi seed-dev.sql a la racine SAAS.';
$out[] = '--';
$out[] = '-- Usage :';
$out[] = '--   mysql -h 127.0.0.1 -u root stockify_multishop < seeds/seed-signup-essentials.sql';
$out[] = '--';
$out[] = '-- Idempotent : re-executable (supprime puis reinsere les lignes seedes).';
$out[] = '-- Genere depuis PermissionCatalog via seeds/generate-signup-seed.php';
$out[] = '';
$out[] = 'SET NAMES utf8mb4;';
$out[] = "SET time_zone = '+00:00';";
$out[] = '';

$permIds = [];
$i = 1;
foreach ($perms as $p) {
    $permIds[$p['code']] = sprintf('019a0000-0000-7000-8000-%012d', $i);
    ++$i;
}

$roleOrder = ['admin', 'gerant', 'caissier', 'magasinier', 'comptable', 'consultant'];
$roleIds = [];
$ri = 1;
foreach ($roleOrder as $code) {
    $roleIds[$code] = sprintf('019a0000-0000-7000-9000-%012d', $ri);
    ++$ri;
}

$unitIds = [
    'piece' => '019a0000-0000-7000-a000-000000000001',
    'kg' => '019a0000-0000-7000-a000-000000000002',
    'liter' => '019a0000-0000-7000-a000-000000000003',
    'carton' => '019a0000-0000-7000-a000-000000000004',
];

$esc = static fn (string $s): string => str_replace(["\\", "'"], ["\\\\", "\\'"], $s);
$pVar = static fn (string $code): string => '@p_'.str_replace('.', '_', $code);

$out[] = '-- UUIDs stables (Doctrine uuid = BINARY(16), ordre RFC)';
foreach ($permIds as $code => $hex) {
    $out[] = sprintf("SET %s = UNHEX(REPLACE('%s', '-', ''));", $pVar($code), $hex);
}
foreach ($roleIds as $code => $hex) {
    $out[] = sprintf("SET @role_%s = UNHEX(REPLACE('%s', '-', ''));", $code, $hex);
}
foreach ($unitIds as $code => $hex) {
    $out[] = sprintf("SET @uom_%s = UNHEX(REPLACE('%s', '-', ''));", $code, $hex);
}

$out[] = '';
$out[] = 'SET @now = UTC_TIMESTAMP();';
$out[] = '';
$out[] = 'START TRANSACTION;';
$out[] = '';
$out[] = '-- Nettoyage ciblé (ordre FK)';
$out[] = 'DELETE FROM role_permissions WHERE role_id IN (';
$out[] = '  @role_admin, @role_gerant, @role_caissier, @role_magasinier, @role_comptable, @role_consultant';
$out[] = ") OR role_id IN (SELECT id FROM (SELECT id FROM roles WHERE code IN ('admin', 'gerant', 'caissier', 'magasinier', 'comptable', 'consultant')) AS _r);";
$out[] = 'DELETE FROM roles WHERE id IN (';
$out[] = '  @role_admin, @role_gerant, @role_caissier, @role_magasinier, @role_comptable, @role_consultant';
$out[] = ") OR code IN ('admin', 'gerant', 'caissier', 'magasinier', 'comptable', 'consultant');";

$codesSql = implode(', ', array_map(static fn (string $c): string => "'".$esc($c)."'", array_keys($permIds)));
$out[] = "DELETE FROM permissions WHERE code IN ({$codesSql});";
$out[] = "DELETE FROM units_of_measure WHERE id IN (@uom_piece, @uom_kg, @uom_liter, @uom_carton) OR code IN ('piece', 'kg', 'liter', 'carton');";
$out[] = '';

$out[] = '-- Permissions catalogue';
$out[] = 'INSERT INTO permissions (id, code, label, module, action, is_critical) VALUES';
$rows = [];
foreach ($perms as $p) {
    $rows[] = sprintf(
        "  (%s, '%s', '%s', '%s', '%s', %d)",
        $pVar($p['code']),
        $esc($p['code']),
        $esc($p['label']),
        $esc($p['module']),
        $esc($p['action']),
        $p['is_critical'] ? 1 : 0,
    );
}
$out[] = implode(",\n", $rows).';';
$out[] = '';

$out[] = '-- Rôles système (gerant requis pour signup / provisionnement admin boutique)';
$out[] = 'INSERT INTO roles (id, code, label, description, is_system, is_active, created_at, updated_at) VALUES';
$rrows = [];
foreach ($roles as $r) {
    $rrows[] = sprintf(
        "  (@role_%s, '%s', '%s', '%s', 1, 1, @now, @now)",
        $r['code'],
        $esc($r['code']),
        $esc($r['label']),
        $esc($r['description']),
    );
}
$out[] = implode(",\n", $rrows).';';
$out[] = '';

$out[] = '-- Rôle ↔ permissions';
$out[] = 'INSERT INTO role_permissions (role_id, permission_id) VALUES';
$rp = [];
foreach ($rolePerms as $roleCode => $codes) {
    foreach ($codes as $code) {
        if (!isset($permIds[$code])) {
            continue;
        }
        $rp[] = sprintf('  (@role_%s, %s)', $roleCode, $pVar($code));
    }
}
$out[] = implode(",\n", $rp).';';
$out[] = '';

$out[] = '-- Unites de mesure de base (catalogue apres creation de boutique)';
$out[] = 'INSERT INTO units_of_measure (id, code, label, decimal_places, is_system) VALUES';
$out[] = "  (@uom_piece, 'piece', 'Pièce', 0, 1),";
$out[] = "  (@uom_kg, 'kg', 'Kilogramme', 3, 1),";
$out[] = "  (@uom_liter, 'liter', 'Litre', 3, 1),";
$out[] = "  (@uom_carton, 'carton', 'Carton', 0, 1);";
$out[] = '';
$out[] = 'COMMIT;';
$out[] = '';
$out[] = '-- Verification rapide';
$out[] = "SELECT 'roles' AS entity, code AS k, label AS v, CAST(is_system AS CHAR) AS extra FROM roles WHERE code IN ('admin','gerant','caissier','magasinier','comptable','consultant')";
$out[] = 'UNION ALL';
$out[] = "SELECT 'permissions', CAST(COUNT(*) AS CHAR), '', '' FROM permissions";
$out[] = 'UNION ALL';
$out[] = "SELECT 'role_permissions', CAST(COUNT(*) AS CHAR), '', '' FROM role_permissions";
$out[] = 'UNION ALL';
$out[] = "SELECT 'units', code, label, CAST(decimal_places AS CHAR) FROM units_of_measure WHERE code IN ('piece','kg','liter','carton');";
$out[] = '';

$target = __DIR__.'/seed-signup-essentials.sql';
file_put_contents($target, implode("\n", $out));

fwrite(STDOUT, sprintf(
    "Wrote %s (%d permissions, %d role-permission links)\n",
    $target,
    count($perms),
    count($rp),
));
