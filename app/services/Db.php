<?php
class Db {
  private static ?PDO $pdo = null;

  public static function conn(): PDO {
    if (self::$pdo) return self::$pdo;
    $cfg = require __DIR__ . '/../config/config.php';
    $m = $cfg['db']['mysql'];
    $dsn = "mysql:host={$m['host']};dbname={$m['dbname']};charset={$m['charset']}";
    self::$pdo = new PDO($dsn, $m['user'], $m['pass'], [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    self::ensureSchema();
    return self::$pdo;
  }

  private static function ensureSchema(): void {
    $sql = "
      CREATE TABLE IF NOT EXISTS submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        referencia VARCHAR(64) NOT NULL,
        origen VARCHAR(32) NOT NULL,
        payload JSON NOT NULL,
        email VARCHAR(255),
        created_at DATETIME NOT NULL,
        pdf_path VARCHAR(255),
        ip VARCHAR(64),
        user_agent VARCHAR(255),
        INDEX (referencia),
        INDEX (email)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    self::$pdo->exec($sql);
  }
}
