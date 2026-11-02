<?php
// Structure-only schema safety checks. This is not a database import test.
$sql = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
if (!is_string($sql) || $sql === '') {
    fwrite(STDERR, "Cannot read database/schema.sql\n");
    exit(1);
}

preg_match_all('/^CREATE TABLE IF NOT EXISTS `([a-z_]+)`/m', $sql, $matches);
$tables = $matches[1];
$required = [
    'action_logs', 'charity_centers', 'claimrequest', 'contact_conversations',
    'contact_messages', 'donations', 'foodoffer', 'notification',
    'password_resets', 'problem_reports', 'reservations', 'restaurants',
    'review', 'saved_offers', 'suggestions', 'users',
];
sort($tables);
sort($required);
if ($tables !== $required) {
    fwrite(STDERR, "Schema table names are unexpected.\n");
    exit(1);
}

// Only check executable top-level statements; ON UPDATE for datetime
// columns and ALTER TABLE for foreign keys are valid DDL.
$uncommented = preg_replace('/^--.*$/m', '', $sql);
if (preg_match('/^\\s*(DROP\\s+TABLE|INSERT\\s+INTO|REPLACE\\s+INTO|DELETE\\s+FROM|UPDATE\\s+[a-z_]+|LOAD\\s+DATA|CREATE\\s+USER|CREATE\\s+DATABASE)\\b/im', $uncommented)) {
    fwrite(STDERR, "Unexpected destructive or row-data SQL.\n");
    exit(1);
}
if (stripos($sql, 'sharebite_test') !== false) {
    fwrite(STDERR, "Unexpected local database identifier.\n");
    exit(1);
}

preg_match_all('/^ALTER TABLE `[a-z_]+`/m', $sql, $fkMatches);
if (count($fkMatches[0]) !== 4) {
    fwrite(STDERR, "Schema foreign-key statements are incomplete.\n");
    exit(1);
}
echo "Structure-only schema checks passed.\n";
