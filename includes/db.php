<?php
require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // TEMPORARY: show real error
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(503);
            header('Content-Type: application/json');
            die(json_encode([
                'success' => false,
                'message' => $e->getMessage(),   // ← real error now visible
                'dsn'     => $dsn,
                'user'    => DB_USER,
            ]));
        }
    }

    return $pdo;
}