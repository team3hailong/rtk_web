<?php

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // Tải env_loader trước để đảm bảo hệ thống biến môi trường hoạt động
        $env_loader_path = __DIR__ . '/../config/env_loader.php';
        if (file_exists($env_loader_path)) {
            require_once $env_loader_path;
        }
        
        // Check if the constants from config/database.php are defined
        if (!defined('DB_SERVER') || !defined('DB_NAME') || !defined('DB_USERNAME') || !defined('DB_PASSWORD')) {
            // Include config file if constants are not defined yet
            $configPath = __DIR__ . '/../config/database.php'; // Ensure this path is correct
            if (file_exists($configPath)) {
                require_once $configPath;
            } else {
                // Handle error: Config file not found
                die("Error: Database configuration file not found or constants not defined.");
            }
        }

        // Check again after attempting to include the config file
        if (!defined('DB_SERVER') || !defined('DB_NAME') || !defined('DB_USERNAME') || !defined('DB_PASSWORD')) {
             die("Error: Database configuration constants (DB_SERVER, DB_NAME, DB_USERNAME, DB_PASSWORD) are not defined even after including the config file.");
        }

        // Sử dụng hàm env() nếu có hoặc sử dụng giá trị constant trực tiếp
        if (function_exists('env')) {
            $this->host = env('DB_SERVER', DB_SERVER);
            $this->db_name = env('DB_NAME', DB_NAME);
            $this->username = env('DB_USERNAME', DB_USERNAME);
            $this->password = env('DB_PASSWORD', DB_PASSWORD);
        } else {
            $this->host = DB_SERVER;
            $this->db_name = DB_NAME;
            $this->username = DB_USERNAME;
            $this->password = DB_PASSWORD;        }
    }

    /**
     * Establishes a database connection using PDO.
     *
     * @return PDO|null Returns the PDO connection object on success, or null on failure.
     */
    public function connect() {
        $this->conn = null;
        $logPrefix = "[Database Connect] "; // Add a prefix for easier log searching

        try {
            // Data Source Name (DSN)
            $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4';

            // PDO Options
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on error
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch results as associative arrays by default
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
            ];            $this->conn = new PDO($dsn, $this->username, $this->password, $options);

        } catch(PDOException $e) {
            // Log the error instead of echoing it in production
            error_log($logPrefix . 'Connection Error: ' . $e->getMessage()); // Use the prefix
            // Optionally, you could throw the exception again or return null/false
            // throw $e; // Re-throw if you want calling code to handle it
            return null; // Indicate connection failure
        }

        return $this->conn;
    }

    /**
     * Closes the database connection.
     */
    public function close() {
        $this->conn = null;
    }

    /**
     * Returns the current PDO connection object.
     *
     * @return PDO|null The active PDO connection or null if not connected.
     */    public function getConnection() {
        if ($this->conn === null) {
            $this->connect();
        }
        return $this->conn;
    }
}
