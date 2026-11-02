<?php
/**
 * Export CREATE TABLE statements without any INSERTs, users, or other row data.
 *
 * Usage from a trusted local command line (run in the project directory):
 *   php tools/export_schema.php > database/schema.sql
 *
 * The script reads the database selected by db_connection.php / DB_NAME.
 * It never writes to the database. Check generated DDL before committing.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Command-line use only.');
}

require_once dirname(__DIR__) . '/db_connection.php';

try {
    $tables = $pdo->query("
        SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
        ORDER BY TABLE_NAME
    ")->fetchAll(PDO::FETCH_COLUMN);

    // Accumulate in memory so a failed query cannot write a partial SQL file.
    $schema = "-- ShareBite database structure only. NO application row data.\n";
    $schema .= "-- Generated from the selected local database; review before publication.\n\n";
    $schema .= "SET NAMES utf8mb4;\n";
    $schema .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

    foreach ($tables as $table) {
        $quotedTable = '`' . str_replace('`', '``', $table) . '`';
        $statement = $pdo->query('SHOW CREATE TABLE ' . $quotedTable)
            ->fetch(PDO::FETCH_NUM);

        if (!$statement || !isset($statement[1])) {
            throw new RuntimeException('Could not read structure of ' . $table);
        }

        // Omit the current counter value, which is not part of the schema design.
        $createSql = preg_replace('/\\sAUTO_INCREMENT=\\d+\\b/i', '', $statement[1]);
        $schema .= "-- Table: " . $table . "\n";
        $schema .= $createSql . ";\n\n";
    }

    $schema .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    fwrite(STDOUT, $schema);
} catch (Throwable $error) {
    error_log('ShareBite schema export failed: ' . $error->getMessage());
    fwrite(STDERR, "Unable to export schema. Check DB settings and permissions.\n");
    exit(1);
}
