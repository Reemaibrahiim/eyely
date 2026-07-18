<?php
// Load database credentials from external text file
$lines = file(__DIR__ . '/Database.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$credentials = [];
foreach ($lines as $line) {
    list($key, $value) = explode('=', $line, 2);
    $credentials[$key] = trim($value);
}

// Connect to the database using loaded credentials
$connection = new mysqli(
    $credentials['DB_HOST'],
    $credentials['DB_USER'],
    $credentials['DB_PASS'],
    $credentials['DB_NAME']
);

// Check connection
if ($connection->connect_error) {
    die("Database connection failed: " . $connection->connect_error);
}

// Set character encoding
$connection->set_charset("utf8mb4");
?>
