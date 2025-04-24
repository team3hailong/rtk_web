<?php
class Map {
    public static function getAllStations($pdo) {
        $query = "SELECT s.*, m.mountpoint, l.province 
                  FROM station s 
                  LEFT JOIN mount_point m ON s.mountpoint_id = m.id 
                  LEFT JOIN location l ON m.location_id = l.id
                  ORDER BY s.station_name";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getUserAccessibleStations($pdo, $user_id) {
        $user_accessible_stations = [];
        if (!$user_id) return $user_accessible_stations;
        $accessible_query = "SELECT DISTINCT s.id as station_id
                            FROM registration r
                            JOIN location l ON r.location_id = l.id
                            JOIN mount_point m ON m.location_id = l.id
                            JOIN station s ON s.mountpoint_id = m.id
                            WHERE r.user_id = :user_id
                            AND r.status = 'active'";
        $accessible_stmt = $pdo->prepare($accessible_query);
        $accessible_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $accessible_stmt->execute();
        while ($row = $accessible_stmt->fetch(PDO::FETCH_ASSOC)) {
            $user_accessible_stations[] = $row['station_id'];
        }
        return $user_accessible_stations;
    }
}
