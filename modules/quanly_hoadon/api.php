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
    
    $controller = new HoaDonController($conn);
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    switch ($method) {
        case 'GET':
            if ($action === 'getAll') {
                echo json_encode($controller->getAll());
            } elseif ($action === 'getById' && isset($_GET['id'])) {
                echo json_encode($controller->getById((int)$_GET['id']));
            } elseif ($action === 'getByBan' && isset($_GET['maBan'])) {
                echo json_encode($controller->getHoaDonByBan((int)$_GET['maBan']));
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Action không hợp lệ']);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'create') {
                echo json_encode($controller->create($data));
            } elseif ($action === 'addChiTiet' && isset($_GET['id'])) {
                echo json_encode($controller->addChiTiet((int)$_GET['id'], $data));
            } elseif ($action === 'complete' && isset($_GET['id'])) {
                echo json_encode($controller->completeHoaDon((int)$_GET['id']));
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Action không hợp lệ']);
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'update' && isset($_GET['id'])) {
                echo json_encode($controller->update((int)$_GET['id'], $data));
            } elseif ($action === 'updateChiTiet' && isset($_GET['id'])) {
                echo json_encode($controller->updateChiTiet((int)$_GET['id'], $data));
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Action không hợp lệ']);
            }
            break;
            
        case 'DELETE':
            if ($action === 'delete' && isset($_GET['id'])) {
                echo json_encode($controller->delete((int)$_GET['id']));
            } elseif ($action === 'deleteChiTiet' && isset($_GET['id'])) {
                echo json_encode($controller->deleteChiTiet((int)$_GET['id']));
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
