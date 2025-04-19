<?php
// filepath: e:\Application\laragon\www\surveying_account\private\classes\Package.php
require_once __DIR__ . '/Database.php';

class Package {
    private $conn;
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->connect();
    }

    /**
     * Fetches a single package by its varchar ID.
     *
     * @param string|null $package_varchar_id The unique varchar ID of the package.
     * @return array|false The package details as an associative array, or false if not found or error.
     */
    public function getPackageByVarcharId(?string $package_varchar_id): array|false {
        if ($package_varchar_id === null) {
            return false;
        }
        try {
            $stmt = $this->conn->prepare("SELECT id, package_id, name, price, duration_text, features_json, is_recommended, button_text, savings_text FROM package WHERE package_id = :package_id AND is_active = 1 LIMIT 1");
            $stmt->bindParam(':package_id', $package_varchar_id, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching package by varchar_id (" . $package_varchar_id . "): " . $e->getMessage());
            return false;
        }
    }

     /**
     * Fetches a single package by its integer primary key ID.
     *
     * @param int|null $package_int_id The integer primary key ID of the package.
     * @return array|false The package details as an associative array, or false if not found or error.
     */
    public function getPackageById(?int $package_int_id): array|false {
        if ($package_int_id === null) {
            return false;
        }
        try {
            // Select only necessary fields for order processing
            $stmt = $this->conn->prepare("SELECT id, name, price, duration_text FROM package WHERE id = :id AND is_active = 1 LIMIT 1");
            $stmt->bindParam(':id', $package_int_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching package by id (" . $package_int_id . "): " . $e->getMessage());
            return false;
        }
    }


    /**
     * Fetches all active packages ordered by display order.
     *
     * @return array An array of package details.
     */
    public function getAllActivePackages(): array {
        $packages = [];
        try {
            $stmt = $this->conn->prepare("SELECT id, package_id, name, price, duration_text, features_json, is_recommended, button_text, savings_text FROM package WHERE is_active = 1 ORDER BY display_order ASC");
            $stmt->execute();
            $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all active packages: " . $e->getMessage());
        }
        return $packages;
    }

    public function closeConnection(): void {
        $this->db->close();
    }
}
?>