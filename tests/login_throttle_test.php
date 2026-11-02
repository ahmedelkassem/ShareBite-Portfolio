<?php
// CLI: php tests/login_throttle_test.php
require_once dirname(__DIR__) . '/login_throttle.php';
$email = bin2hex(random_bytes(12)) . '@example.test';
$ip = '203.0.113.' . random_int(1, 254);
for ($i = 0; $i < 5; $i++) {
    if (!sharebiteLoginThrottle($email, $ip, 'check') ||
        !sharebiteLoginThrottle($email, $ip, 'failure')) {
        fwrite(STDERR, 'Failure while recording attempts.' . PHP_EOL);
        exit(1);
    }
}
if (sharebiteLoginThrottle($email, $ip, 'check') ||
    !sharebiteLoginThrottle($email, $ip, 'success') ||
    !sharebiteLoginThrottle($email, $ip, 'check')) {
    fwrite(STDERR, 'Failure in lockout/reset test.' . PHP_EOL);
    exit(1);
}
echo 'Login throttling tests passed.' . PHP_EOL;
