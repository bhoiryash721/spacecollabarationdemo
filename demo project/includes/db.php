<?php
// ============================================================
// includes/db.php — Database connection (PDO)
// Update HOST, USER, PASS, NAME to match your MySQL setup.
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // ← change me
define('DB_PASS', '');          // ← change me
define('DB_NAME', 'spacecollab');
define('BASE_URL', '/');  // ← change if deployed elsewhere

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=3307;dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // In production, log this — never expose DB errors to users
    die(json_encode(['error' => 'Database connection failed.']));
}
