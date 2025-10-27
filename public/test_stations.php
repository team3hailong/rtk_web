<?php
require_once dirname(__DIR__) . '/private/config/config.php';
init_session();
require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';
require_once PROJECT_ROOT_PATH . '/private/classes/Map.php';

$db = new Database();
$pdo = $db->getConnection();
$stations = Map::getAllStations($pdo);
$user_accessible_stations = Map::getUserAccessibleStations($pdo, 1); // test with user 1

header('Content-Type: application/json');
echo json_encode([
    'stations' => $stations,
    'user_accessible' => $user_accessible_stations
]);
?>