<?php
/**
 * Database Migration Runner
 * Run migrations in the migrations folder
 */

require_once __DIR__ . '/../includes/db.php';

function runMigration($pdo, $file) {
    echo "Running migration: " . basename($file) . "\n";

    $sql = file_get_contents($file);

    if (empty($sql)) {
        echo "  ⚠️  Empty migration file\n";
        return false;
    }

    try {
        // Split by delimiter to handle triggers and procedures
        $statements = preg_split('/;\s*$/m', $sql);

        $pdo->beginTransaction();

        foreach ($statements as $statement) {
            $statement = trim($statement);

            if (empty($statement) || substr($statement, 0, 2) === '--') {
                continue;
            }

            $pdo->exec($statement);
        }

        $pdo->commit();
        echo "  ✅ Migration completed successfully\n";
        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "  ❌ Migration failed: " . $e->getMessage() . "\n";
        return false;
    }
}

// Get all migration files
$migrationDir = __DIR__ . '/migrations';
$files = glob($migrationDir . '/*.sql');
sort($files);

echo "========================================\n";
echo "Database Migration Runner\n";
echo "========================================\n\n";

if (empty($files)) {
    echo "No migration files found in {$migrationDir}\n";
    exit(1);
}

echo "Found " . count($files) . " migration(s)\n\n";

$success = 0;
$failed = 0;

foreach ($files as $file) {
    if (runMigration($pdo, $file)) {
        $success++;
    } else {
        $failed++;
    }
    echo "\n";
}

echo "========================================\n";
echo "Summary: {$success} succeeded, {$failed} failed\n";
echo "========================================\n";

exit($failed > 0 ? 1 : 0);
