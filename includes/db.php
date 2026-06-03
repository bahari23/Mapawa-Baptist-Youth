<?php
// ================================================================
// includes/db.php — PDO database connection singleton
// ================================================================

require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            if (APP_ENV === 'production') {
                http_response_code(500);
                die(json_encode(['success' => false, 'message' => 'Database unavailable.']));
            }
            die('DB Connection failed: ' . $e->getMessage());
        }
    }
    return $pdo;
}

// ── Convenience query helpers ─────────────────────────────────────

function db_query(string $sql, array $params = []): PDOStatement {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_fetch(string $sql, array $params = []): ?array {
    return db_query($sql, $params)->fetch() ?: null;
}

function db_fetch_all(string $sql, array $params = []): array {
    return db_query($sql, $params)->fetchAll();
}

function db_insert(string $table, array $data): int {
    $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    db_query("INSERT INTO `$table` ($cols) VALUES ($placeholders)", array_values($data));
    return (int) db()->lastInsertId();
}

function db_update(string $table, array $data, string $where, array $where_params = []): int {
    $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
    $stmt = db_query(
        "UPDATE `$table` SET $set WHERE $where",
        array_merge(array_values($data), $where_params)
    );
    return $stmt->rowCount();
}

function db_delete(string $table, string $where, array $params = []): int {
    return db_query("DELETE FROM `$table` WHERE $where", $params)->rowCount();
}

function db_count(string $table, string $where = '1', array $params = []): int {
    $row = db_fetch("SELECT COUNT(*) AS n FROM `$table` WHERE $where", $params);
    return (int)($row['n'] ?? 0);
}
