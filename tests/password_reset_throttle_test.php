<?php
require_once dirname(__DIR__) . '/password_reset_throttle.php';

$path = sys_get_temp_dir() . DIRECTORY_SEPARATOR .
    'sharebite-reset-test-' . bin2hex(random_bytes(8)) . '.json';

function assertResetLimit(bool $actual, bool $expected, string $label): void
{
    if ($actual !== $expected) {
        fwrite(STDERR, 'FAILED: ' . $label . PHP_EOL);
        exit(1);
    }
}

try {
    assertResetLimit(
        sharebitePasswordResetThrottle('User@Example.Com', '192.0.2.10', 'check', $path),
        true, 'first request allowed'
    );
    for ($n = 1; $n <= 3; $n++) {
        assertResetLimit(
            sharebitePasswordResetThrottle('user@example.com', '192.0.2.10', 'record', $path),
            true, 'email request ' . $n
        );
    }
    assertResetLimit(
        sharebitePasswordResetThrottle('USER@example.com', '192.0.2.11', 'record', $path),
        false, 'email quota applies across IP addresses and case variations'
    );
    assertResetLimit(
        sharebitePasswordResetThrottle('different@example.com', '192.0.2.10', 'record', $path),
        true, 'different account still allowed'
    );

    $ip = '198.51.100.55';
    for ($n = 0; $n < 20; $n++) {
        assertResetLimit(
            sharebitePasswordResetThrottle('acct' . $n . '@example.com', $ip, 'record', $path),
            true, 'IP request ' . $n
        );
    }
    assertResetLimit(
        sharebitePasswordResetThrottle('newaccount@example.com', $ip, 'record', $path),
        false, 'IP quota blocks next account'
    );
    assertResetLimit(
        sharebitePasswordResetThrottle('newaccount@example.com', '198.51.100.56', 'record', $path),
        true, 'different IP is unaffected'
    );
    assertResetLimit(
        sharebitePasswordResetThrottle('somebody@example.com', '203.0.113.20', 'bogus', $path),
        false, 'unknown action rejected'
    );
    echo "Password reset request limiter tests passed." . PHP_EOL;
} finally {
    @unlink($path);
}
