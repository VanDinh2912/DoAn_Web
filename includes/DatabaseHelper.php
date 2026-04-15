<?php
declare(strict_types=1);

/**
 * Database Helper Functions
 * Các hàm tiện lợi cho các module CRUD
 */

class DatabaseHelper {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    /**
     * Kiểm tra xem một record có tồn tại không
     */
    public function recordExists(string $table, string $idColumn, $idValue): bool {
        try {
            $query = "SELECT 1 FROM $table WHERE $idColumn = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$idValue]);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error checking record: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kiểm tra trùng lặp theo field
     */
    public function isDuplicate(string $table, string $field, $value, ?string $excludeIdColumn = null, $excludeIdValue = null): bool {
        try {
            $query = "SELECT 1 FROM $table WHERE $field = ?";
            $params = [$value];
            
            if ($excludeIdColumn && $excludeIdValue) {
                $query .= " AND $excludeIdColumn != ?";
                $params[] = $excludeIdValue;
            }
            
            $query .= " LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error checking duplicate: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Đếm số lượng records
     */
    public function countRecords(string $table, ?string $whereClause = null): int {
        try {
            $query = "SELECT COUNT(*) as total FROM $table";
            
            if ($whereClause) {
                $query .= " WHERE $whereClause";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch();
            
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Error counting records: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Lấy tất cả records với pagination
     */
    public function getPaginated(string $query, int $limit = 10, int $offset = 0): array {
        try {
            $pagQuery = $query . " LIMIT ? OFFSET ?";
            
            // Tính số lần cần bind param
            $params = [];
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->conn->prepare($pagQuery);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting paginated records: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Thống kê (dùng cho dashboard)
     */
    public function getStats(): array {
        try {
            $stats = [];
            
            // Số lượng bàn
            $banQuery = "SELECT COUNT(*) as total FROM Ban";
            $banStmt = $this->conn->prepare($banQuery);
            $banStmt->execute();
            $stats['totalBan'] = (int)$banStmt->fetch()['total'];
            
            // Số lượng bàn đang sử dụng
            $banUsingQuery = "SELECT COUNT(*) as total FROM Ban WHERE TrangThai != 'Trống'";
            $banUsingStmt = $this->conn->prepare($banUsingQuery);
            $banUsingStmt->execute();
            $stats['banDangSuDung'] = (int)$banUsingStmt->fetch()['total'];
            
            // Số lượng khách hàng
            $khQuery = "SELECT COUNT(*) as total FROM KhachHang";
            $khStmt = $this->conn->prepare($khQuery);
            $khStmt->execute();
            $stats['totalKhachHang'] = (int)$khStmt->fetch()['total'];
            
            // Số lượng dịch vụ
            $dvQuery = "SELECT COUNT(*) as total FROM Mon WHERE TrangThai = 1";
            $dvStmt = $this->conn->prepare($dvQuery);
            $dvStmt->execute();
            $stats['totalDichVu'] = (int)$dvStmt->fetch()['total'];
            
            // Tổng doanh thu ngày
            $doanhThuQuery = "SELECT IFNULL(SUM(TongTien), 0) as total FROM HoaDon WHERE DATE(NgayLap) = CURDATE() AND TrangThai = 1";
            $doanhThuStmt = $this->conn->prepare($doanhThuQuery);
            $doanhThuStmt->execute();
            $stats['doanhThuNgay'] = (float)$doanhThuStmt->fetch()['total'];
            
            // Số hóa đơn ngày
            $hdQuery = "SELECT COUNT(*) as total FROM HoaDon WHERE DATE(NgayLap) = CURDATE() AND TrangThai = 1";
            $hdStmt = $this->conn->prepare($hdQuery);
            $hdStmt->execute();
            $stats['hoaDonNgay'] = (int)$hdStmt->fetch()['total'];
            
            return ['success' => true, 'data' => $stats];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Lấy báo cáo doanh thu theo ngày
     */
    public function getReportByDate(string $startDate, string $endDate): array {
        try {
            $query = "SELECT 
                        DATE(NgayLap) as Ngay,
                        COUNT(*) as SoHoaDon,
                        SUM(TongTien) as DoanhThu,
                        AVG(TongTien) as DoanhThuTB
                      FROM HoaDon
                      WHERE DATE(NgayLap) BETWEEN ? AND ? AND TrangThai = 1
                      GROUP BY DATE(NgayLap)
                      ORDER BY DATE(NgayLap) DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            
            return ['success' => true, 'data' => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Lấy dịch vụ bán chạy nhất
     */
    public function getTopServices(int $limit = 10): array {
        try {
            $query = "SELECT 
                        m.MaMon,
                        m.TenMon,
                        COUNT(cthd.MaChiTietHD) as SoLanBan,
                        SUM(cthd.SoLuong) as TongSoLuong,
                        SUM(cthd.SoLuong * cthd.DonGia) as TongDoanhThu
                      FROM Mon m
                      LEFT JOIN ChiTietHoaDon cthd ON m.MaMon = cthd.MaMon
                      LEFT JOIN HoaDon hd ON cthd.MaHoaDon = hd.MaHoaDon
                      WHERE hd.TrangThai = 1
                      GROUP BY m.MaMon
                      ORDER BY SoLanBan DESC
                      LIMIT ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$limit]);
            
            return ['success' => true, 'data' => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Sanitize input string
     */
    public static function sanitize($input): string {
        return htmlspecialchars(stripslashes(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate phone number (Vietnam format)
     */
    public static function validatePhoneVN($phone): bool {
        $phone = str_replace(['-', ' ', '+84'], ['', '', '0'], $phone);
        return preg_match('/^0[0-9]{9}$/', $phone) === 1;
    }
    
    /**
     * Format currency
     */
    public static function formatCurrency($amount): string {
        return number_format($amount, 0, '.', ',');
    }
    
    /**
     * Format date
     */
    public static function formatDate($date, $format = 'Y-m-d H:i'): string {
        try {
            $dateObj = new DateTime($date);
            return $dateObj->format($format);
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * beginTransaction
     */
    public function beginTransaction(): void {
        $this->conn->beginTransaction();
    }
    
    /**
     * commit
     */
    public function commit(): void {
        $this->conn->commit();
    }
    
    /**
     * rollback
     */
    public function rollback(): void {
        $this->conn->rollBack();
    }
}

/**
 * API Response Helper
 */
class ApiResponse {
    public static function success($message = null, $data = null, $statusCode = 200): void {
        http_response_code($statusCode);
        $response = [
            'success' => true
        ];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if ($data) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
    }
    
    public static function error($error, $statusCode = 400): void {
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'error' => $error
        ]);
    }
    
    public static function created($id = null): void {
        http_response_code(201);
        $response = [
            'success' => true,
            'message' => 'Tạo mới thành công'
        ];
        
        if ($id) {
            $response['id'] = $id;
        }
        
        echo json_encode($response);
    }
}
