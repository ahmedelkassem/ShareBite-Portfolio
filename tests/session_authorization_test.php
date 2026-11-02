<?php
require_once dirname(__DIR__) . '/session_authorization.php';

function expectPrincipal($actual, $expected, string $label): void
{
    if ($actual !== $expected) {
        fwrite(STDERR, "FAILED: " . $label . PHP_EOL);
        exit(1);
    }
}

$record = [
    'user_id' => 25,
    'role' => 'Volunteer',
    'full_name' => 'Demo Account',
    'email' => 'demo@example.com'
];

$principal = [
    'user_id' => 25,
    'role' => 'Volunteer',
    'full_name' => 'Demo Account',
    'email' => 'demo@example.com'
];

expectPrincipal(sharebiteCurrentAccount(25, $record), $principal, 'valid owner session');
expectPrincipal(sharebiteCurrentAccount('25', $record), $principal, 'numeric session id');

$promoted = $record;
$promoted['role'] = 'Admin';
$adminPrincipal = $principal;
$adminPrincipal['role'] = 'Admin';
expectPrincipal(sharebiteCurrentAccount(25, $promoted), $adminPrincipal, 'role refreshed after admin promotion');

$demoted = $record;
$demoted['role'] = 'Donor';
$donorPrincipal = $principal;
$donorPrincipal['role'] = 'Donor';
expectPrincipal(sharebiteCurrentAccount(25, $demoted), $donorPrincipal, 'old Admin session must adopt current role');

expectPrincipal(sharebiteCurrentAccount(25, null), null, 'deleted account rejected');
expectPrincipal(sharebiteCurrentAccount(26, $record), null, 'mismatched account ID rejected');
expectPrincipal(sharebiteCurrentAccount(0, $record), null, 'zero ID rejected');
expectPrincipal(sharebiteCurrentAccount('abc', $record), null, 'invalid ID rejected');

$invalidRole = $record;
$invalidRole['role'] = 'Unknown';
expectPrincipal(sharebiteCurrentAccount(25, $invalidRole), null, 'unknown role rejected');

$connection = file_get_contents(dirname(__DIR__) . '/db_connection.php');
if (!is_string($connection) ||
    !str_contains($connection, 'sharebiteCurrentAccount(') ||
    !str_contains($connection, 'foreach ($principal as $key => $value)')) {
    fwrite(STDERR, "The database session-refresh integration is missing.\n");
    exit(1);
}

echo "Current-account authorization tests passed.\n";
