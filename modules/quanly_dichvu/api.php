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
    
    $controller = new DichVuController($conn);
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    switch ($method) {
        case 'GET':
            if ($action === 'getAll') {
                echo json_encode($controller->getAll());
            } elseif ($action === 'getById' && isset($_GET['id'])) {
                echo json_encode($controller->getById((int)$_GET['id']));
            } elseif ($action === 'getByDanhMuc' && isset($_GET['maDanhMuc'])) {
                echo json_encode($controller->getByDanhMuc((int)$_GET['maDanhMuc']));
            } elseif ($action === 'search' && isset($_GET['keyword'])) {
                echo json_encode($controller->search($_GET['keyword']));
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Action không hợp lệ']);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'create') {
                echo json_encode($controller->create($data));
            } elseif ($action === 'updateSoLuong' && isset($_GET['id'])) {
                if (!isset($data['soLuong']) || !is_numeric($data['soLuong'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'soLuong phải là số']);
                } else {
                    echo json_encode($controller->updateSoLuong((int)$_GET['id'], (float)$data['soLuong']));
                }
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
