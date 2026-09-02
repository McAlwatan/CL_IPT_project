<?php

$dbconfig = require __DIR__ . '/../config/database.php';

try{
    $dsn = "mysql:host={$dbconfig['host']};dbname={$dbconfig['dbname']};charset={$dbconfig['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $dbconfig['user'], $dbconfig['password'], $options);
} catch (PDOException $e){
    die("Database connection failed: " . $e ->getMessage());
}
