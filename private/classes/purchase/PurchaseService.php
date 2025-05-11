<?php

// PurchaseService.php
// Service class to handle purchase pages database operations

require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';

class PurchaseService {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * Check if a user has a registration
     * @param int $user_id
     * @return bool
     */
    public function userHasRegistration(int $user_id): bool {
        $stmt = $this->conn->prepare('SELECT 1 FROM registration WHERE user_id = ? LIMIT 1');
        $stmt->execute([$user_id]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Get all active packages
     * @return array
     */
    public function getAllPackages(): array {
        $stmt = $this->conn->prepare('SELECT package_id, name, price, duration_text, features_json, is_recommended, button_text, savings_text FROM package WHERE is_active = 1 ORDER BY display_order ASC');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get package details by varchar ID
     * @param string|null $varchar_id
     * @return array|false
     */
    public function getPackageByVarcharId(?string $varchar_id) {
        $stmt = $this->conn->prepare('SELECT * FROM package WHERE package_id = ? LIMIT 1');
        $stmt->execute([$varchar_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get list of provinces/cities
     * @return array
     */
    public function getAllProvinces(): array {
        require_once PROJECT_ROOT_PATH . '/private/classes/Location.php';
        $loc = new Location();
        $provinces = $loc->getAllProvinces();
        $loc->closeConnection();
        return $provinces;
    }
}
