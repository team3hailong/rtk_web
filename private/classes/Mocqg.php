<?php
/**
 * Class Mocqg - Xử lý các chức năng liên quan đến mốc quốc gia
 * File: private/classes/Mocqg.php
 */

class Mocqg {
    
    /**
     * Tìm các mốc quốc gia trong bán kính từ một điểm
     * 
     * @param PDO $pdo Database connection
     * @param float $lat Vĩ độ điểm tìm kiếm
     * @param float $lng Kinh độ điểm tìm kiếm
     * @param float $radius Bán kính tìm kiếm (km)
     * @return array Danh sách mốc tìm được
     */
    public static function findNearbyMocqg($pdo, $lat, $lng, $radius = 50) {
        // Validate input
        if (!is_numeric($lat) || !is_numeric($lng) || !is_numeric($radius)) {
            throw new InvalidArgumentException('Tọa độ và bán kính phải là số');
        }
        
        $lat = floatval($lat);
        $lng = floatval($lng);
        $radius = floatval($radius);
        
        // Validate coordinates
        if ($lat < -90 || $lat > 90) {
            throw new InvalidArgumentException('Vĩ độ phải trong khoảng -90 đến 90');
        }
        
        if ($lng < -180 || $lng > 180) {
            throw new InvalidArgumentException('Kinh độ phải trong khoảng -180 đến 180');
        }
        
        if ($radius <= 0 || $radius > 1000) {
            throw new InvalidArgumentException('Bán kính phải trong khoảng 1-1000 km');
        }
        
        // Sử dụng công thức Haversine để tính khoảng cách
        // distance = 6371 * acos(cos(radians(lat1)) * cos(radians(lat2)) * 
        //            cos(radians(lng2) - radians(lng1)) + sin(radians(lat1)) * sin(radians(lat2)))
        
        $sql = "SELECT 
                    id,
                    ten_moc,
                    lat,
                    `long` as lng,
                    height,
                    vn2000_x,
                    vn2000_y,
                    vn2000_z,
                    status,
                    created_at,
                    (6371 * acos(
                        cos(radians(?)) * cos(radians(lat)) * 
                        cos(radians(`long`) - radians(?)) + 
                        sin(radians(?)) * sin(radians(lat))
                    )) AS distance_km
                FROM mocqg
                WHERE status = 1
                HAVING distance_km <= ?
                ORDER BY distance_km ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lat, $lng, $lat, $radius]);
        
        $mocqg_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format kết quả
        foreach ($mocqg_list as &$mocqg) {
            $mocqg['distance_km'] = round(floatval($mocqg['distance_km']), 2);
            $mocqg['lat'] = round(floatval($mocqg['lat']), 6);
            $mocqg['lng'] = round(floatval($mocqg['lng']), 6);
            $mocqg['id'] = intval($mocqg['id']);
            $mocqg['status'] = intval($mocqg['status']);
        }
        
        return $mocqg_list;
    }
    
    /**
     * Lấy tất cả mốc quốc gia đang hoạt động
     * 
     * @param PDO $pdo Database connection
     * @return array Danh sách tất cả mốc
     */
    public static function getAllActiveMocqg($pdo) {
        $sql = "SELECT 
                    id,
                    ten_moc,
                    lat,
                    `long` as lng,
                    status,
                    created_at,
                    updated_at
                FROM mocqg
                WHERE status = 1
                ORDER BY created_at DESC";
        
        $stmt = $pdo->query($sql);
        $mocqg_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format kết quả
        foreach ($mocqg_list as &$mocqg) {
            $mocqg['lat'] = round(floatval($mocqg['lat']), 6);
            $mocqg['lng'] = round(floatval($mocqg['lng']), 6);
            $mocqg['id'] = intval($mocqg['id']);
            $mocqg['status'] = intval($mocqg['status']);
        }
        
        return $mocqg_list;
    }
    
    /**
     * Lấy thông tin một mốc theo ID
     * 
     * @param PDO $pdo Database connection
     * @param int $id ID của mốc
     * @return array|null Thông tin mốc hoặc null nếu không tìm thấy
     */
    public static function getMocqgById($pdo, $id) {
        $sql = "SELECT 
                    id,
                    ten_moc,
                    lat,
                    `long` as lng,
                    status,
                    created_at,
                    updated_at
                FROM mocqg
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        $mocqg = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($mocqg) {
            $mocqg['lat'] = round(floatval($mocqg['lat']), 6);
            $mocqg['lng'] = round(floatval($mocqg['lng']), 6);
            $mocqg['id'] = intval($mocqg['id']);
            $mocqg['status'] = intval($mocqg['status']);
        }
        
        return $mocqg;
    }
    
    /**
     * Tính khoảng cách giữa 2 điểm sử dụng công thức Haversine
     * 
     * @param float $lat1 Vĩ độ điểm 1
     * @param float $lng1 Kinh độ điểm 1
     * @param float $lat2 Vĩ độ điểm 2
     * @param float $lng2 Kinh độ điểm 2
     * @return float Khoảng cách (km)
     */
    public static function calculateDistance($lat1, $lng1, $lat2, $lng2) {
        $earthRadius = 6371; // Bán kính trái đất (km)
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        $distance = $earthRadius * $c;
        
        return round($distance, 2);
    }
}
