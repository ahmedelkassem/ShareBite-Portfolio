<?php
// Release guard: checks that selected previously discovered privacy/XSS risks
// stay fixed. This is NOT a comprehensive code/security scanner.

$root = dirname(__DIR__);
$reset = file_get_contents($root . '/reset_password.php');
if ($reset === false ||
    !str_contains($reset, 'value="<?php echo escapeHtml($prefillEmail); ?>"')) {
    fwrite(STDERR, "Reset email is not escaped in its HTML attribute.\n");
    exit(1);
}
if (str_contains($reset, 'value="<?php echo $prefillEmail; ?>"')) {
    fwrite(STDERR, "Unsafe reset email output is still present.\n");
    exit(1);
}

$expected = ['charity-dashboard.jpg', 'donor-dashboard.jpg'];
$actual = array_map('basename', glob($root . '/screenshots/*') ?: []);
sort($actual);
if ($actual !== $expected) {
    fwrite(STDERR, "Unexpected portfolio screenshot assets.\n");
    exit(1);
}

$excluded = [
    '.env', 'mail_config.php', 'db_backup.sql',
    'database/private', 'Imges/food_donation.jpg',
];
foreach ($excluded as $path) {
    if (file_exists($root . '/' . $path)) {
        fwrite(STDERR, "Private or unreviewed file detected: {$path}\n");
        exit(1);
    }
}

$ignore = file_get_contents($root . '/.gitignore');
if ($ignore === false ||
    !str_contains($ignore, 'mail_config.php') ||
    !str_contains($ignore, 'Imges/food_*')) {
    fwrite(STDERR, "Required .gitignore rules are missing.\n");
    exit(1);
}

echo "Portfolio privacy and reset-output regression checks passed.\n";
