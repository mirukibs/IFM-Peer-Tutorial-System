<?php
/**
 * Database Configuration
 * 
 * Supports both MySQL and SQLite
 * Change DB_TYPE to switch between databases
 */

// ========================================
// CONFIGURATION - EDIT HERE
// ========================================
define('DB_TYPE', 'sqlite'); // 'mysql' or 'sqlite'

// MySQL Configuration (used when DB_TYPE = 'mysql')
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'peer_tutoring');

// SQLite Configuration (used when DB_TYPE = 'sqlite')
define('DB_PATH', __DIR__ . '/../peer_tutoring.db');

// ========================================
// DATABASE CONNECTION
// ========================================
try {
    if (DB_TYPE === 'mysql') {
        // Use native mysqli for MySQL (no compatibility layer needed)
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Database Connection Failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        
    } else {
        // Use PDO with mysqli compatibility layer for SQLite
        $pdo = new PDO("sqlite:" . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Create mysqli-compatible wrapper for SQLite
        $conn = new MysqliCompat($pdo);
    }
} catch (Exception $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// ========================================
// MYSQLI COMPATIBILITY LAYER (for SQLite only)
// ========================================
// This wrapper is only used when DB_TYPE = 'sqlite'
// It provides mysqli-style methods for PDO/SQLite
// When using MySQL, native mysqli is used directly (no wrapper needed)
class MysqliCompat {
    private $pdo;
    public $connect_error = null;
    public $error = '';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function query($sql) {
        try {
            $stmt = $this->pdo->query($sql);
            $this->error = '';
            return new ResultWrapper($stmt);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function prepare($sql) {
        // Convert mysqli ? placeholders (compatible with both)
        try {
            $stmt = $this->pdo->prepare($sql);
            $this->error = '';
            return new StatementWrapper($stmt, $this->pdo);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function real_escape_string($str) {
        return str_replace("'", "''", $str);
    }
    
    public function close() {
        $this->pdo = null;
    }
}

class StatementWrapper {
    private $stmt;
    private $pdo;
    private $params = [];
    private $types = '';
    public $insert_id = 0;
    public $affected_rows = 0;
    
    public function __construct($stmt, $pdo) {
        $this->stmt = $stmt;
        $this->pdo = $pdo;
    }
    
    public function bind_param($types, &...$params) {
        $this->types = $types;
        $this->params = $params;
        return true;
    }
    
    public function execute() {
        try {
            $result = false;
            if (!empty($this->params)) {
                $result = $this->stmt->execute($this->params);
            } else {
                $result = $this->stmt->execute();
            }
            if ($result) {
                $this->insert_id = $this->pdo->lastInsertId();
                $this->affected_rows = $this->stmt->rowCount();
            }
            return $result;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function get_result() {
        return new ResultWrapper($this->stmt);
    }
    
    public function close() {
        $this->stmt = null;
    }
}

class ResultWrapper {
    private $stmt;
    private $data;
    private $position = 0;
    public $num_rows = 0;
    
    public function __construct($stmt) {
        $this->stmt = $stmt;
        if ($stmt) {
            $this->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->num_rows = count($this->data);
        }
    }
    
    public function fetch_assoc() {
        if ($this->stmt && $this->position < count($this->data)) {
            return $this->data[$this->position++];
        }
        return null;
    }
}

$conn = new MysqliCompat($pdo);
?>

