<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "obradearteh";

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$database}",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Error de conexión a la base de datos: ' . $e->getMessage());
}

