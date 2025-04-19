<?php
// filepath: e:\Application\laragon\www\surveying_account\private\classes\Location.php
require_once __DIR__ . '/Database.php';

class Location {
    private $conn;
    private $db;
    private $table = 'location';

    public function __construct($db_connection = null) {
        if ($db_connection) {
            $this->conn = $db_connection;
        } else {
            // Fallback to creating a new connection if none provided
            $this->db = new Database();
            $this->conn = $this->db->connect();
        }
    }

    /**
     * Fetches all locations (provinces).
     * Assumes a 'locations' table with 'id' and 'province' columns.
     *
     * @return array An array of locations, each with 'id' and 'province'.
     */
    public function getAllProvinces(): array {
        $provinces = [];
        try {
            $query = "SELECT id, province FROM " . $this->table . " ORDER BY province ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $provinces = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all provinces: " . $e->getMessage());
            // Handle error appropriately, maybe return empty array or throw exception
        }
        return $provinces;
    }

    /**
     * Checks if a location ID exists.
     *
     * @param int|null $location_id The ID of the location to check.
     * @return bool True if the location exists, false otherwise.
     */
    public function locationExists(?int $location_id): bool {
        if ($location_id === null) {
            return false;
        }
        try {
            $query = "SELECT id FROM " . $this->table . " WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $location_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (PDOException $e) {
            error_log("Error checking location existence (ID: " . $location_id . "): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches location details by ID.
     *
     * @param int $id The ID of the location to fetch.
     * @return array|false The location details as an associative array, or false if not found or an error occurs.
     */
    public function getLocationById(int $id) {
        $query = "SELECT id, province FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $location = $stmt->fetch(PDO::FETCH_ASSOC);
            return $location ? $location : false; // Return the associative array or false if not found
        } catch (PDOException $e) {
            error_log("Error fetching location by ID ($id): " . $e->getMessage());
            return false; // Return false on error
        }
    }

    public function closeConnection(): void {
        $this->conn = null;
    }
}
?>