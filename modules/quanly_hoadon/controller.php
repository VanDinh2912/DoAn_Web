<?php
declare(strict_types=1);

class HoaDonController {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    /**
     * Lấy tất cả hóa đơn
     */
    public function getAll(): array {
        try {
            $query = "SELECT hd.*, b.TenBan, nv.HoTen as TenNhanVien, kh.HoTen as TenKhachHang
                      FROM HoaDon hd
                      LEFT JOIN Ban b ON hd.MaBan = b.MaBan
                      LEFT JOIN NhanVien nv ON hd.MaNhanVien = nv.MaNhanVien
                      LEFT JOIN KhachHang kh ON hd.MaKhachHang = kh.MaKhachHang
                      ORDER BY hd.MaHoaDon DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return ['success' => true, 'data' => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Lấy hóa đơn theo ID (với chi tiết)
     */
    public function getById(int $id): array {
        try {
            $query = "SELECT hd.*, b.TenBan, nv.HoTen as TenNhanVien, kh.HoTen as TenKhachHang
                      FROM HoaDon hd
                      LEFT JOIN Ban b ON hd.MaBan = b.MaBan
                      LEFT JOIN NhanVien nv ON hd.MaNhanVien = nv.MaNhanVien
                      LEFT JOIN KhachHang kh ON hd.MaKhachHang = kh.MaKhachHang
                      WHERE hd.MaHoaDon = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return ['success' => false, 'error' => 'Hóa đơn không tồn tại'];
            }
            
            // Get chi tiết hóa đơn
            $detailQuery = "SELECT cthd.*, m.TenMon, dvt.TenDVT
                           FROM ChiTietHoaDon cthd
                           LEFT JOIN Mon m ON cthd.MaMon = m.MaMon
                           LEFT JOIN DonViTinhs dvt ON m.DonViTinhMaDVT = dvt.MaDVT
                           WHERE cthd.MaHoaDon = ?";
            
            $detailStmt = $this->conn->prepare($detailQuery);
            $detailStmt->execute([$id]);
            $result['ChiTiet'] = $detailStmt->fetchAll();
            
            return ['success' => true, 'data' => $result];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Tạo hóa đơn mới
     */
    public function create(array $data): array {
        try {
            // Validate
            if (empty($data['MaBan']) || empty($data['MaNhanVien'])) {
                return ['success' => false, 'error' => 'Thiếu dữ liệu bắt buộc (MaBan, MaNhanVien)'];
            }
            
            // Check Ban exists
            $checkBanQuery = "SELECT MaBan FROM Ban WHERE MaBan = ?";
            $checkBanStmt = $this->conn->prepare($checkBanQuery);
            $checkBanStmt->execute([$data['MaBan']]);
            
            if (!$checkBanStmt->fetch()) {
                return ['success' => false, 'error' => 'Bàn không tồn tại'];
            }
            
            $query = "INSERT INTO HoaDon (NgayLap, MaBan, MaNhanVien, MaKhachHang, MaKhuyenMai, TongTien, TrangThai, TienGioLuyKe)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                date('Y-m-d H:i:s'),
                $data['MaBan'],
                $data['MaNhanVien'],
                $data['MaKhachHang'] ?? null,
                $data['MaKhuyenMai'] ?? null,
                $data['TongTien'] ?? 0,
                $data['TrangThai'] ?? 0, // 0 = Đang phục vụ
                $data['TienGioLuyKe'] ?? 0
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Tạo hóa đơn thành công',
                    'id' => $this->conn->lastInsertId()
                ];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Cập nhật hóa đơn
     */
    public function update(int $id, array $data): array {
        try {
            $fields = [];
            $values = [];
            
            foreach (['TongTien', 'TrangThai', 'MaKhachHang', 'MaKhuyenMai', 'TienGioLuyKe'] as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                return ['success' => false, 'error' => 'Không có dữ liệu cần cập nhật'];
            }
            
            $values[] = $id;
            $query = "UPDATE HoaDon SET " . implode(', ', $fields) . " WHERE MaHoaDon = ?";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($values);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Cập nhật hóa đơn thành công'];
            } else if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'Hóa đơn không tồn tại'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Xóa hóa đơn
     */
    public function delete(int $id): array {
        try {
            // Check if exists
            $checkQuery = "SELECT MaHoaDon FROM HoaDon WHERE MaHoaDon = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            if (!$checkStmt->fetch()) {
                return ['success' => false, 'error' => 'Hóa đơn không tồn tại'];
            }
            
            // Delete chi tiết first
            $deleteDetailQuery = "DELETE FROM ChiTietHoaDon WHERE MaHoaDon = ?";
            $deleteDetailStmt = $this->conn->prepare($deleteDetailQuery);
            $deleteDetailStmt->execute([$id]);
            
            // Delete hóa đơn
            $query = "DELETE FROM HoaDon WHERE MaHoaDon = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Xóa hóa đơn thành công'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Thêm món vào hóa đơn
     */
    public function addChiTiet(int $maHoaDon, array $data): array {
        try {
            // Validate
            if (empty($data['MaMon']) || empty($data['SoLuong']) || !isset($data['DonGia'])) {
                return ['success' => false, 'error' => 'Thiếu dữ liệu bắt buộc'];
            }
            
            if ($data['SoLuong'] <= 0 || $data['DonGia'] < 0) {
                return ['success' => false, 'error' => 'Số lượng và giá phải lớn hơn 0'];
            }
            
            // Check mon exists
            $checkMonQuery = "SELECT MaMon FROM Mon WHERE MaMon = ?";
            $checkMonStmt = $this->conn->prepare($checkMonQuery);
            $checkMonStmt->execute([$data['MaMon']]);
            
            if (!$checkMonStmt->fetch()) {
                return ['success' => false, 'error' => 'Dịch vụ không tồn tại'];
            }
            
            $query = "INSERT INTO ChiTietHoaDon (MaHoaDon, MaMon, SoLuong, DonGia, GhiChu)
                      VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $maHoaDon,
                $data['MaMon'],
                $data['SoLuong'],
                $data['DonGia'],
                $data['GhiChu'] ?? null
            ]);
            
            if ($result) {
                // Update tổng tiền hóa đơn
                $this->updateTongTien($maHoaDon);
                
                return [
                    'success' => true,
                    'message' => 'Thêm dịch vụ thành công',
                    'id' => $this->conn->lastInsertId()
                ];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Cập nhật chi tiết hóa đơn
     */
    public function updateChiTiet(int $maChiTietHD, array $data): array {
        try {
            $fields = [];
            $values = [];
            
            foreach (['SoLuong', 'DonGia', 'GhiChu'] as $field) {
                if (isset($data[$field])) {
                    if ($field === 'SoLuong' && $data[$field] <= 0) {
                        return ['success' => false, 'error' => 'Số lượng phải lớn hơn 0'];
                    }
                    if ($field === 'DonGia' && $data[$field] < 0) {
                        return ['success' => false, 'error' => 'Giá phải lớn hơn hoặc bằng 0'];
                    }
                    
                    $fields[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                return ['success' => false, 'error' => 'Không có dữ liệu cần cập nhật'];
            }
            
            $values[] = $maChiTietHD;
            $query = "UPDATE ChiTietHoaDon SET " . implode(', ', $fields) . " WHERE MaChiTietHD = ?";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($values);
            
            if ($result && $stmt->rowCount() > 0) {
                // Get MaHoaDon để update tổng tiền
                $getHDQuery = "SELECT MaHoaDon FROM ChiTietHoaDon WHERE MaChiTietHD = ?";
                $getHDStmt = $this->conn->prepare($getHDQuery);
                $getHDStmt->execute([$maChiTietHD]);
                $row = $getHDStmt->fetch();
                
                if ($row) {
                    $this->updateTongTien($row['MaHoaDon']);
                }
                
                return ['success' => true, 'message' => 'Cập nhật chi tiết thành công'];
            } else if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'Chi tiết không tồn tại'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Xóa chi tiết hóa đơn
     */
    public function deleteChiTiet(int $maChiTietHD): array {
        try {
            // Get MaHoaDon
            $getHDQuery = "SELECT MaHoaDon FROM ChiTietHoaDon WHERE MaChiTietHD = ?";
            $getHDStmt = $this->conn->prepare($getHDQuery);
            $getHDStmt->execute([$maChiTietHD]);
            $row = $getHDStmt->fetch();
            
            if (!$row) {
                return ['success' => false, 'error' => 'Chi tiết không tồn tại'];
            }
            
            $maHoaDon = $row['MaHoaDon'];
            
            // Delete
            $query = "DELETE FROM ChiTietHoaDon WHERE MaChiTietHD = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$maChiTietHD]);
            
            if ($result) {
                // Update tổng tiền hóa đơn
                $this->updateTongTien($maHoaDon);
                
                return ['success' => true, 'message' => 'Xóa chi tiết thành công'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Cập nhật tổng tiền hóa đơn
     */
    private function updateTongTien(int $maHoaDon): void {
        try {
            $query = "UPDATE HoaDon 
                      SET TongTien = (SELECT IFNULL(SUM(SoLuong * DonGia), 0) FROM ChiTietHoaDon WHERE MaHoaDon = ?)
                      WHERE MaHoaDon = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$maHoaDon, $maHoaDon]);
        } catch (PDOException $e) {
            // Log error but don't throw
            error_log("Error updating tong tien: " . $e->getMessage());
        }
    }
    
    /**
     * Hoàn thành hóa đơn
     */
    public function completeHoaDon(int $id): array {
        try {
            $query = "UPDATE HoaDon SET TrangThai = 1, NgayKetThuc = ? WHERE MaHoaDon = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([date('Y-m-d H:i:s'), $id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Hoàn thành hóa đơn thành công'];
            } else if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'Hóa đơn không tồn tại'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Lấy hóa đơn đang phục vụ của bàn
     */
    public function getHoaDonByBan(int $maBan): array {
        try {
            $query = "SELECT hd.*, b.TenBan, nv.HoTen as TenNhanVien, kh.HoTen as TenKhachHang
                      FROM HoaDon hd
                      LEFT JOIN Ban b ON hd.MaBan = b.MaBan
                      LEFT JOIN NhanVien nv ON hd.MaNhanVien = nv.MaNhanVien
                      LEFT JOIN KhachHang kh ON hd.MaKhachHang = kh.MaKhachHang
                      WHERE hd.MaBan = ? AND hd.TrangThai = 0
                      ORDER BY hd.MaHoaDon DESC
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$maBan]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return ['success' => true, 'data' => null];
            }
            
            // Get chi tiết
            $detailQuery = "SELECT cthd.*, m.TenMon, dvt.TenDVT
                           FROM ChiTietHoaDon cthd
                           LEFT JOIN Mon m ON cthd.MaMon = m.MaMon
                           LEFT JOIN DonViTinhs dvt ON m.DonViTinhMaDVT = dvt.MaDVT
                           WHERE cthd.MaHoaDon = ?";
            
            $detailStmt = $this->conn->prepare($detailQuery);
            $detailStmt->execute([$result['MaHoaDon']]);
            $result['ChiTiet'] = $detailStmt->fetchAll();
            
            return ['success' => true, 'data' => $result];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
