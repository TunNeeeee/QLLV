<?php
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $dbh;
    private $stmt;
    private $error;
    
    public function __construct() {
        // Set DSN
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8mb4';
        // Set options
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        );
        
        // Create PDO instance
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            die("Database connection failed: " . $this->error);
        }
    }
    
    // Prepare statement with query
    public function query($sql) {
        $this->stmt = $this->dbh->prepare($sql);
    }
    
    // Bind values
    public function bind($param, $value, $type = null) {
        if(is_null($type)) {
            switch(true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }
    
    // Execute the prepared statement
    public function execute() {
        $logFile = __DIR__ . '/db_debug.log';
        try {
            $result = $this->stmt->execute();
            file_put_contents($logFile, "Query executed: " . $this->stmt->queryString . "\nResult: " . ($result ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
            return $result;
        } catch (PDOException $e) {
            file_put_contents($logFile, "Query ERROR: " . $this->stmt->queryString . "\nError: " . $e->getMessage() . "\n", FILE_APPEND);
            throw $e;
        }
    }
    
    // Get result set as array of objects
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }
    
    // Get single record as object
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }
    
    // Get row count
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    // Get last insert ID - THÊM PHƯƠNG THỨC NÀY
    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }

    // Thêm phương thức transaction nếu chưa có
    public function beginTransaction() {
        try {
            // Chỉ bắt đầu transaction nếu chưa có transaction đang hoạt động
            if (!$this->dbh->inTransaction()) {
                return $this->dbh->beginTransaction();
            }
            return true; // Đã có transaction rồi thì coi như thành công
        } catch (PDOException $e) {
            error_log("Begin transaction error: " . $e->getMessage());
            return false;
        }
    }

    public function commit() {
        try {
            if ($this->dbh->inTransaction()) {
                return $this->dbh->commit();
            }
            return true; // Không có transaction thì cũng coi như thành công
        } catch (PDOException $e) {
            error_log("Commit error: " . $e->getMessage());
            return false;
        }
    }

    public function rollback() {
        try {
            // Kiểm tra xem có transaction đang hoạt động không
            if ($this->dbh->inTransaction()) {
                return $this->dbh->rollBack();
            }
            return true; // Không có transaction thì cũng coi như thành công
        } catch (PDOException $e) {
            error_log("Rollback error: " . $e->getMessage());
            return false;
        }
    }

    public function inTransaction() {
        try {
            return $this->dbh->inTransaction();
        } catch (PDOException $e) {
            error_log("inTransaction check error: " . $e->getMessage());
            return false;
        }
    }
}
?>
