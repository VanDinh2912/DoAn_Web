<?php
declare(strict_types=1);

class KhachHangController {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    /**
     * Lấy tất cả khách hàng
     */
    public function getAll(): array {
        try {
            $query = "SELECT * FROM KhachHang ORDER BY MaKhachHang DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll()
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Lấy khách hàng theo ID
     */
    public function getById(int $id): array {
        try {
            $query = "SELECT * FROM KhachHang WHERE MaKhachHang = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return ['success' => false, 'error' => 'Khách hàng không tồn tại'];
            }
            
            return ['success' => true, 'data' => $result];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Tạo khách hàng mới
     */
    public function create(array $data): array {
        try {
            // Validate
            if (empty($data['HoTen'])) {
                return ['success' => false, 'error' => 'Tên khách hàng không được trống'];
            }
            
            // Check duplicate phone
            if (!empty($data['SDT'])) {
                $checkQuery = "SELECT MaKhachHang FROM KhachHang WHERE SDT = ?";
                $checkStmt = $this->conn->prepare($checkQuery);
                $checkStmt->execute([$data['SDT']]);
                
                if ($checkStmt->fetch()) {
                    return ['success' => false, 'error' => 'Số điện thoại đã tồn tại'];
                }
            }
            
            $query = "INSERT INTO KhachHang (HoTen, SDT, DiemTichLuy, Hang, IsStaff)
                      VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $data['HoTen'],
                $data['SDT'] ?? null,
                $data['DiemTichLuy'] ?? 0,
                $data['Hang'] ?? 'Thường',
                $data['IsStaff'] ?? 0
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Tạo khách hàng thành công',
                    'id' => $this->conn->lastInsertId()
                ];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Cập nhật khách hàng
     */
    public function update(int $id, array $data): array {
        try {
            $fields = [];
            $values = [];
            
            foreach (['HoTen', 'SDT', 'DiemTichLuy', 'Hang', 'IsStaff'] as $field) {
                if (isset($data[$field])) {
                    // Check duplicate phone if updating SDT
                    if ($field === 'SDT' && !empty($data[$field])) {
                        $checkQuery = "SELECT MaKhachHang FROM KhachHang WHERE SDT = ? AND MaKhachHang != ?";
                        $checkStmt = $this->conn->prepare($checkQuery);
                        $checkStmt->execute([$data[$field], $id]);
                        
                        if ($checkStmt->fetch()) {
                            return ['success' => false, 'error' => 'Số điện thoại đã tồn tại'];
                        }
                    }
                    
                    $fields[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                return ['success' => false, 'error' => 'Không có dữ liệu cần cập nhật'];
            }
            
            $values[] = $id;
            $query = "UPDATE KhachHang SET " . implode(', ', $fields) . " WHERE MaKhachHang = ?";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($values);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Cập nhật khách hàng thành công'];
            } else if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'Khách hàng không tồn tại'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Xóa khách hàng
     */
    public function delete(int $id): array {
        try {
            // Check if exists
            $checkQuery = "SELECT MaKhachHang FROM KhachHang WHERE MaKhachHang = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            if (!$checkStmt->fetch()) {
                return ['success' => false, 'error' => 'Khách hàng không tồn tại'];
            }
            
            $query = "DELETE FROM KhachHang WHERE MaKhachHang = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Xóa khách hàng thành công'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Cộng điểm tích lũy
     */
    public function addDiem(int $id, int $diem): array {
        try {
            $query = "UPDATE KhachHang SET DiemTichLuy = DiemTichLuy + ? WHERE MaKhachHang = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$diem, $id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Cộng điểm thành công'];
            } else if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'Khách hàng không tồn tại'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Tìm khách hàng theo số điện thoại
     */
    public function searchByPhone(string $phone): array {
        try {
            $query = "SELECT * FROM KhachHang WHERE SDT LIKE ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute(['%' . $phone . '%']);
            
            return ['success' => true, 'data' => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Lấy khách hàng theo hạng
     */
    public function getByHang(string $hang): array {
        try {
            $query = "SELECT * FROM KhachHang WHERE Hang = ? ORDER BY MaKhachHang DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$hang]);
            
            return ['success' => true, 'data' => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
