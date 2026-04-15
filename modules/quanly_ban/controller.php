<?php
declare(strict_types=1);

class BanController {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
    }

    private function readNonNegativeAmount($value): float {
        if (is_int($value) || is_float($value)) {
            return max(0.0, (float)$value);
        }

        if (is_string($value)) {
            $normalized = trim(str_replace(',', '.', $value));
            if ($normalized !== '' && is_numeric($normalized)) {
                return max(0.0, (float)$normalized);
            }
        }

        return 0.0;
    }
    
    /**
     * Lấy tất cả bàn
     */
    public function getAll(): array {
        try {
            $query = "SELECT b.*, lb.TenLoai, kv.TenKhuVuc 
                      FROM Ban b
                      LEFT JOIN LoaiBan lb ON b.MaLoaiBan = lb.MaLoaiBan
                      LEFT JOIN KhuVuc kv ON b.MaKhuVuc = kv.MaKhuVuc
                      ORDER BY b.MaBan ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Lấy danh sách loại bàn
     */
    public function getLoaiBan(): array {
        try {
            $query = "SELECT MaLoaiBan, TenLoai, PhuThu FROM LoaiBan ORDER BY MaLoaiBan ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return [
                'success' => true,
                'data' => $stmt->fetchAll()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Tạo loại bàn mới
     */
    public function createLoaiBan(array $data): array {
        try {
            $tenLoai = trim((string)($data['TenLoai'] ?? ''));
            if ($tenLoai === '') {
                return ['success' => false, 'error' => 'Tên loại bàn không được để trống'];
            }

            $checkStmt = $this->conn->prepare("SELECT MaLoaiBan FROM LoaiBan WHERE TenLoai = ? LIMIT 1");
            $checkStmt->execute([$tenLoai]);
            if ($checkStmt->fetch()) {
                return ['success' => false, 'error' => 'Loại bàn đã tồn tại'];
            }

            $phuThu = $this->readNonNegativeAmount($data['PhuThu'] ?? 0);

            $query = "INSERT INTO LoaiBan (TenLoai, PhuThu) VALUES (?, ?)";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$tenLoai, $phuThu]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Tạo loại bàn thành công',
                    'id' => $this->conn->lastInsertId()
                ];
            }

            return ['success' => false, 'error' => 'Không thể tạo loại bàn'];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Lấy danh sách khu vực
     */
    public function getKhuVuc(): array {
        try {
            $query = "SELECT MaKhuVuc, TenKhuVuc, PhuThu FROM KhuVuc ORDER BY MaKhuVuc ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return [
                'success' => true,
                'data' => $stmt->fetchAll()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Tạo khu vực mới
     */
    public function createKhuVuc(array $data): array {
        try {
            $tenKhuVuc = trim((string)($data['TenKhuVuc'] ?? ''));
            if ($tenKhuVuc === '') {
                return ['success' => false, 'error' => 'Tên khu vực không được để trống'];
            }

            $checkStmt = $this->conn->prepare("SELECT MaKhuVuc FROM KhuVuc WHERE TenKhuVuc = ? LIMIT 1");
            $checkStmt->execute([$tenKhuVuc]);
            if ($checkStmt->fetch()) {
                return ['success' => false, 'error' => 'Khu vực đã tồn tại'];
            }

            $phuThu = $this->readNonNegativeAmount($data['PhuThu'] ?? 0);

            $query = "INSERT INTO KhuVuc (TenKhuVuc, PhuThu) VALUES (?, ?)";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$tenKhuVuc, $phuThu]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Tạo khu vực thành công',
                    'id' => $this->conn->lastInsertId()
                ];
            }

            return ['success' => false, 'error' => 'Không thể tạo khu vực'];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Lấy bàn theo ID
     */
    public function getById(int $id): array {
        try {
            $query = "SELECT b.*, lb.TenLoai, kv.TenKhuVuc 
                      FROM Ban b
                      LEFT JOIN LoaiBan lb ON b.MaLoaiBan = lb.MaLoaiBan
                      LEFT JOIN KhuVuc kv ON b.MaKhuVuc = kv.MaKhuVuc
                      WHERE b.MaBan = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return ['success' => false, 'error' => 'Bàn không tồn tại'];
            }
            
            return ['success' => true, 'data' => $result];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Tạo bàn mới
     */
    public function create(array $data): array {
        try {
            // Validate
            if (empty($data['TenBan']) || empty($data['MaLoaiBan']) || !isset($data['GiaGio'])) {
                return ['success' => false, 'error' => 'Thiếu dữ liệu bắt buộc'];
            }
            
            $query = "INSERT INTO Ban (TenBan, TrangThai, MaLoaiBan, GiaGio, MaKhuVuc)
                      VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $data['TenBan'],
                $data['TrangThai'] ?? 'Trống',
                $data['MaLoaiBan'],
                $data['GiaGio'],
                $data['MaKhuVuc'] ?? null
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Tạo bàn thành công',
                    'id' => $this->conn->lastInsertId()
                ];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Cập nhật bàn
     */
    public function update(int $id, array $data): array {
        try {
            $fields = [];
            $values = [];
            
            foreach (['TenBan', 'TrangThai', 'MaLoaiBan', 'GiaGio', 'MaKhuVuc'] as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                return ['success' => false, 'error' => 'Không có dữ liệu cần cập nhật'];
            }
            
            $values[] = $id;
            $query = "UPDATE Ban SET " . implode(', ', $fields) . " WHERE MaBan = ?";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($values);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Cập nhật bàn thành công'];
            } else if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'Bàn không tồn tại'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Xóa bàn
     */
    public function delete(int $id): array {
        try {
            // Check if ban exists
            $checkQuery = "SELECT MaBan FROM Ban WHERE MaBan = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            if (!$checkStmt->fetch()) {
                return ['success' => false, 'error' => 'Bàn không tồn tại'];
            }
            
            $query = "DELETE FROM Ban WHERE MaBan = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Xóa bàn thành công'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Cập nhật trạng thái bàn
     */
    public function updateStatus(int $id, string $status): array {
        try {
            $query = "UPDATE Ban SET TrangThai = ? WHERE MaBan = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$status, $id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Cập nhật trạng thái thành công'];
            } else if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'Bàn không tồn tại'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Lấy bàn theo khu vực
     */
    public function getByKhuVuc(int $maKhuVuc): array {
        try {
            $query = "SELECT b.*, lb.TenLoai, kv.TenKhuVuc 
                      FROM Ban b
                      LEFT JOIN LoaiBan lb ON b.MaLoaiBan = lb.MaLoaiBan
                      LEFT JOIN KhuVuc kv ON b.MaKhuVuc = kv.MaKhuVuc
                      WHERE b.MaKhuVuc = ?
                      ORDER BY b.TenBan ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$maKhuVuc]);
            
            return ['success' => true, 'data' => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
