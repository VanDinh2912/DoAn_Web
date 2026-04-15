<?php
declare(strict_types=1);

class DichVuController {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    /**
     * Lấy tất cả dịch vụ (Món)
     */
    public function getAll(): array {
        try {
            $query = "SELECT m.*, dm.TenDanhMuc, dvt.TenDVT
                      FROM Mon m
                      LEFT JOIN DanhMucMon dm ON m.MaDanhMuc = dm.MaDanhMuc
                      LEFT JOIN DonViTinhs dvt ON m.DonViTinhMaDVT = dvt.MaDVT
                      WHERE m.TrangThai = 1
                      ORDER BY m.MaMon DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return ['success' => true, 'data' => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Lấy dịch vụ theo ID
     */
    public function getById(int $id): array {
        try {
            $query = "SELECT m.*, dm.TenDanhMuc, dvt.TenDVT
                      FROM Mon m
                      LEFT JOIN DanhMucMon dm ON m.MaDanhMuc = dm.MaDanhMuc
                      LEFT JOIN DonViTinhs dvt ON m.DonViTinhMaDVT = dvt.MaDVT
                      WHERE m.MaMon = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return ['success' => false, 'error' => 'Dịch vụ không tồn tại'];
            }
            
            return ['success' => true, 'data' => $result];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Tạo dịch vụ mới
     */
    public function create(array $data): array {
        try {
            // Validate
            if (empty($data['TenMon']) || empty($data['MaDanhMuc']) || !isset($data['GiaBan'])) {
                return ['success' => false, 'error' => 'Thiếu dữ liệu bắt buộc'];
            }
            
            if ($data['GiaBan'] < 0) {
                return ['success' => false, 'error' => 'Giá bán phải lớn hơn 0'];
            }
            
            $query = "INSERT INTO Mon (TenMon, GiaBan, MaDanhMuc, DonViTinhMaDVT, SoLuongTon, TrangThai)
                      VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $data['TenMon'],
                $data['GiaBan'],
                $data['MaDanhMuc'],
                $data['DonViTinhMaDVT'] ?? null,
                $data['SoLuongTon'] ?? 0,
                1 // TrangThai = 1 (Hoạt động)
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Tạo dịch vụ thành công',
                    'id' => $this->conn->lastInsertId()
                ];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Cập nhật dịch vụ
     */
    public function update(int $id, array $data): array {
        try {
            $fields = [];
            $values = [];
            
            foreach (['TenMon', 'GiaBan', 'MaDanhMuc', 'DonViTinhMaDVT', 'SoLuongTon', 'TrangThai'] as $field) {
                if (isset($data[$field])) {
                    // Validate giá bán
                    if ($field === 'GiaBan' && $data[$field] < 0) {
                        return ['success' => false, 'error' => 'Giá bán phải lớn hơn 0'];
                    }
                    
                    $fields[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                return ['success' => false, 'error' => 'Không có dữ liệu cần cập nhật'];
            }
            
            $values[] = $id;
            $query = "UPDATE Mon SET " . implode(', ', $fields) . " WHERE MaMon = ?";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($values);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Cập nhật dịch vụ thành công'];
            } else if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'Dịch vụ không tồn tại'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Xóa dịch vụ (soft delete - cập nhật TrangThai)
     */
    public function delete(int $id): array {
        try {
            // Check if exists
            $checkQuery = "SELECT MaMon FROM Mon WHERE MaMon = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            if (!$checkStmt->fetch()) {
                return ['success' => false, 'error' => 'Dịch vụ không tồn tại'];
            }
            
            $query = "UPDATE Mon SET TrangThai = 0 WHERE MaMon = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Xóa dịch vụ thành công'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Lấy dịch vụ theo danh mục
     */
    public function getByDanhMuc(int $maDanhMuc): array {
        try {
            $query = "SELECT m.*, dm.TenDanhMuc, dvt.TenDVT
                      FROM Mon m
                      LEFT JOIN DanhMucMon dm ON m.MaDanhMuc = dm.MaDanhMuc
                      LEFT JOIN DonViTinhs dvt ON m.DonViTinhMaDVT = dvt.MaDVT
                      WHERE m.MaDanhMuc = ? AND m.TrangThai = 1
                      ORDER BY m.TenMon ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$maDanhMuc]);
            
            return ['success' => true, 'data' => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Cập nhật số lượng tồn
     */
    public function updateSoLuong(int $id, float $soLuong): array {
        try {
            $query = "UPDATE Mon SET SoLuongTon = ? WHERE MaMon = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$soLuong, $id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Cập nhật số lượng thành công'];
            } else if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'Dịch vụ không tồn tại'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Tìm kiếm dịch vụ theo tên
     */
    public function search(string $keyword): array {
        try {
            $query = "SELECT m.*, dm.TenDanhMuc, dvt.TenDVT
                      FROM Mon m
                      LEFT JOIN DanhMucMon dm ON m.MaDanhMuc = dm.MaDanhMuc
                      LEFT JOIN DonViTinhs dvt ON m.DonViTinhMaDVT = dvt.MaDVT
                      WHERE (m.TenMon LIKE ? OR dm.TenDanhMuc LIKE ?) AND m.TrangThai = 1
                      ORDER BY m.TenMon ASC";
            
            $stmt = $this->conn->prepare($query);
            $keyword = '%' . $keyword . '%';
            $stmt->execute([$keyword, $keyword]);
            
            return ['success' => true, 'data' => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
