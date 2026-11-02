<?php
// Ensure the problem-report insert matches the checked-in schema.
$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
$source = file_get_contents(dirname(__DIR__) . '/report_problem.php');
if (!is_string($schema) || !is_string($source)) {
    fwrite(STDERR, "Missing required project files.\n");
    exit(1);
}

if (!preg_match('/CREATE TABLE IF NOT EXISTS `problem_reports`\s*\((.*?)\) ENGINE=/s', $schema, $table)) {
    fwrite(STDERR, "problem_reports schema not found.\n");
    exit(1);
}
preg_match_all('/^\s*`([a-zA-Z_]+)`/m', $table[1], $columns);
$allowed = array_map('strtolower', $columns[1]);

if (!preg_match('/INSERT INTO problem_reports\s*\(([^)]+)\)/s', $source, $insert)) {
    fwrite(STDERR, "Problem-report insert not found.\n");
    exit(1);
}
$used = array_map(static function ($value): string {
    return strtolower(trim($value, " `\t\r\n"));
}, explode(',', $insert[1]));

foreach ($used as $column) {
    if (!in_array($column, $allowed, true)) {
        fwrite(STDERR, "Unknown column in report insert: " . $column . "\n");
        exit(1);
    }
}
if ($used !== ['userid', 'offerid', 'message']) {
    fwrite(STDERR, "Unexpected problem report field mapping.\n");
    exit(1);
}
if (!str_contains($source, 'escapeHtml($details)')) {
    fwrite(STDERR, "Report textarea escaping is missing.\n");
    exit(1);
}
echo "Problem report schema compatibility checks passed.\n";
