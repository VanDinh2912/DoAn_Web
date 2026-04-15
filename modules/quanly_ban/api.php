<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Load database connection
    require_once '../../config/db.php';
    require_once './controller.php';
    
    $controller = new BanController($conn);
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    switch ($method) {
        case 'GET':
            if ($action === 'getAll') {
                echo json_encode($controller->getAll());
            } elseif ($action === 'getById' && isset($_GET['id'])) {
                echo json_encode($controller->getById((int)$_GET['id']));
            } elseif ($action === 'getByKhuVuc' && isset($_GET['maKhuVuc'])) {
                echo json_encode($controller->getByKhuVuc((int)$_GET['maKhuVuc']));
            } elseif ($action === 'getLoaiBan') {
                echo json_encode($controller->getLoaiBan());
            } elseif ($action === 'getKhuVuc') {
                echo json_encode($controller->getKhuVuc());
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Action không hợp lệ']);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!is_array($data)) {
                $data = [];
            }
            
            if ($action === 'create') {
                echo json_encode($controller->create($data));
            } elseif ($action === 'updateStatus' && isset($_GET['id'])) {
                if (empty($data['status'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Thiếu dữ liệu status']);
                } else {
                    echo json_encode($controller->updateStatus((int)$_GET['id'], $data['status']));
                }
            } elseif ($action === 'createLoaiBan') {
                echo json_encode($controller->createLoaiBan($data));
            } elseif ($action === 'createKhuVuc') {
                echo json_encode($controller->createKhuVuc($data));
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Action không hợp lệ']);
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'update' && isset($_GET['id'])) {
                echo json_encode($controller->update((int)$_GET['id'], $data));
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Action không hợp lệ']);
            }
            break;
            
        case 'DELETE':
            if ($action === 'delete' && isset($_GET['id'])) {
                echo json_encode($controller->delete((int)$_GET['id']));
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Action không hợp lệ']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method không được hỗ trợ']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
