<?php
$host = 'localhost';
$passwords = ['', 'root', 'password', 'admin'];
$success = false;
$pdo = null;

foreach ($passwords as $pwd) {
    try {
        $pdo = new PDO("mysql:host=$host;port=3307;charset=utf8mb4", 'root', $pwd, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "Connected successfully to MySQL with password: '$pwd'\n";
        $success = true;
        break;
    } catch (PDOException $e) {
        echo "Failed with password '$pwd': " . $e->getMessage() . "\n";
    }
}

if (!$success) {
    die("Could not connect to MySQL.\n");
}

try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS spacecollab");
    $pdo->exec("USE spacecollab");
    $sql = file_get_contents("spacecollab.sql");
    $pdo->exec($sql);
    echo "Database created and spacecollab.sql imported successfully.\n";
} catch (PDOException $e) {
    die("Error during import: " . $e->getMessage() . "\n");
}
