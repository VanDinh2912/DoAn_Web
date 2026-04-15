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

    private function readPositiveId($value): int {
        if (is_int($value)) {
            return $value > 0 ? $value : 0;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed !== '' && ctype_digit($trimmed)) {
                $parsed = (int)$trimmed;
                return $parsed > 0 ? $parsed : 0;
            }
        }

        return 0;
    }

    private function columnExists(string $table, string $column): bool {
        try {
            $query = "SELECT 1
                      FROM INFORMATION_SCHEMA.COLUMNS
                      WHERE TABLE_SCHEMA = DATABASE()
                        AND TABLE_NAME = ?
                        AND COLUMN_NAME = ?
                      LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$table, $column]);
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }

    private function getLoaiBanBasePriceColumn(): string {
        static $column = null;
        if ($column !== null) {
            return $column;
        }

        $column = $this->columnExists('LoaiBan', 'GiaCoBan') ? 'GiaCoBan' : 'PhuThu';
        return $column;
    }

    private function getKhuVucExtraPriceColumn(): string {
        static $column = null;
        if ($column !== null) {
            return $column;
        }

        $column = $this->columnExists('KhuVuc', 'ExtraPrice') ? 'ExtraPrice' : 'PhuThu';
        return $column;
    }

    private function calculateTablePrice(int $maLoaiBan, int $maKhuVuc): array {
        try {
            $basePriceColumn = $this->getLoaiBanBasePriceColumn();
            $extraPriceColumn = $this->getKhuVucExtraPriceColumn();

            $typeStmt = $this->conn->prepare("SELECT {$basePriceColumn} AS BasePrice FROM LoaiBan WHERE MaLoaiBan = ? LIMIT 1");
            $typeStmt->execute([$maLoaiBan]);
            $typeRow = $typeStmt->fetch();

            if (!$typeRow) {
                return ['success' => false, 'error' => 'Loại bàn không tồn tại'];
            }

            $areaStmt = $this->conn->prepare("SELECT {$extraPriceColumn} AS ExtraPrice FROM KhuVuc WHERE MaKhuVuc = ? LIMIT 1");
            $areaStmt->execute([$maKhuVuc]);
            $areaRow = $areaStmt->fetch();

            if (!$areaRow) {
                return ['success' => false, 'error' => 'Khu vực không tồn tại'];
            }

            $basePrice = $this->readNonNegativeAmount($typeRow['BasePrice'] ?? 0);
            $extraPrice = $this->readNonNegativeAmount($areaRow['ExtraPrice'] ?? 0);
            $finalPrice = round($basePrice + $extraPrice, 2);

            return [
                'success' => true,
                'basePrice' => $basePrice,
                'extraPrice' => $extraPrice,
                'price' => $finalPrice,
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Lấy tất cả bàn
     */
    public function getAll(): array {
        try {
            $basePriceColumn = $this->getLoaiBanBasePriceColumn();
            $extraPriceColumn = $this->getKhuVucExtraPriceColumn();
            $query = "SELECT b.*, lb.TenLoai, kv.TenKhuVuc 
                      , lb.{$basePriceColumn} AS GiaCoBan
                      , kv.{$extraPriceColumn} AS PhuThuKhuVuc
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
            $basePriceColumn = $this->getLoaiBanBasePriceColumn();
            $query = "SELECT MaLoaiBan, TenLoai,
                             {$basePriceColumn} AS GiaCoBan,
                             {$basePriceColumn} AS PhuThu
                      FROM LoaiBan
                      ORDER BY MaLoaiBan ASC";
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

            $basePrice = $this->readNonNegativeAmount(
                $data['GiaCoBan']
                ?? $data['basePrice']
                ?? $data['BasePrice']
                ?? $data['PhuThu']
                ?? 0
            );
            $basePriceColumn = $this->getLoaiBanBasePriceColumn();

            $query = "INSERT INTO LoaiBan (TenLoai, {$basePriceColumn}) VALUES (?, ?)";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$tenLoai, $basePrice]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Tạo loại bàn thành công',
                    'id' => $this->conn->lastInsertId(),
                    'basePrice' => $basePrice,
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
            $extraPriceColumn = $this->getKhuVucExtraPriceColumn();
            $query = "SELECT MaKhuVuc, TenKhuVuc,
                             {$extraPriceColumn} AS PhuThu,
                             {$extraPriceColumn} AS ExtraPrice
                      FROM KhuVuc
                      ORDER BY MaKhuVuc ASC";
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

            $extraPrice = $this->readNonNegativeAmount(
                $data['ExtraPrice']
                ?? $data['extraPrice']
                ?? $data['PhuThu']
                ?? 0
            );
            $extraPriceColumn = $this->getKhuVucExtraPriceColumn();

            $query = "INSERT INTO KhuVuc (TenKhuVuc, {$extraPriceColumn}) VALUES (?, ?)";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$tenKhuVuc, $extraPrice]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Tạo khu vực thành công',
                    'id' => $this->conn->lastInsertId(),
                    'extraPrice' => $extraPrice,
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
            $basePriceColumn = $this->getLoaiBanBasePriceColumn();
            $extraPriceColumn = $this->getKhuVucExtraPriceColumn();
            $query = "SELECT b.*, lb.TenLoai, kv.TenKhuVuc 
                      , lb.{$basePriceColumn} AS GiaCoBan
                      , kv.{$extraPriceColumn} AS PhuThuKhuVuc
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
            $tenBan = trim((string)($data['TenBan'] ?? ''));
            $maLoaiBan = $this->readPositiveId($data['MaLoaiBan'] ?? 0);
            $maKhuVuc = $this->readPositiveId($data['MaKhuVuc'] ?? 0);

            if ($tenBan === '' || $maLoaiBan <= 0 || $maKhuVuc <= 0) {
                return ['success' => false, 'error' => 'Thiếu dữ liệu bắt buộc (Tên bàn, Loại bàn, Khu vực)'];
            }

            $priceResult = $this->calculateTablePrice($maLoaiBan, $maKhuVuc);
            if (!($priceResult['success'] ?? false)) {
                return ['success' => false, 'error' => $priceResult['error'] ?? 'Không thể tính giá bàn'];
            }

            $giaGio = (float)$priceResult['price'];
            
            $query = "INSERT INTO Ban (TenBan, TrangThai, MaLoaiBan, GiaGio, MaKhuVuc)
                      VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $tenBan,
                $data['TrangThai'] ?? 'Trống',
                $maLoaiBan,
                $giaGio,
                $maKhuVuc
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Tạo bàn thành công',
                    'id' => $this->conn->lastInsertId(),
                    'price' => $giaGio,
                    'basePrice' => $priceResult['basePrice'] ?? 0,
                    'extraPrice' => $priceResult['extraPrice'] ?? 0,
                ];
            }

            return ['success' => false, 'error' => 'Không thể tạo bàn'];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Cập nhật bàn
     */
    public function update(int $id, array $data): array {
        try {
            $currentStmt = $this->conn->prepare("SELECT MaBan, MaLoaiBan, MaKhuVuc FROM Ban WHERE MaBan = ? LIMIT 1");
            $currentStmt->execute([$id]);
            $currentRow = $currentStmt->fetch();

            if (!$currentRow) {
                return ['success' => false, 'error' => 'Bàn không tồn tại'];
            }

            $hasName = array_key_exists('TenBan', $data);
            $hasStatus = array_key_exists('TrangThai', $data);
            $hasLoaiBan = array_key_exists('MaLoaiBan', $data);
            $hasKhuVuc = array_key_exists('MaKhuVuc', $data);

            if (!$hasName && !$hasStatus && !$hasLoaiBan && !$hasKhuVuc) {
                return ['success' => false, 'error' => 'Không có dữ liệu cần cập nhật'];
            }

            $tenBan = trim((string)($data['TenBan'] ?? ''));
            if ($hasName && $tenBan === '') {
                return ['success' => false, 'error' => 'Tên bàn không được để trống'];
            }

            $maLoaiBan = $hasLoaiBan
                ? $this->readPositiveId($data['MaLoaiBan'])
                : $this->readPositiveId($currentRow['MaLoaiBan']);

            $maKhuVuc = $hasKhuVuc
                ? $this->readPositiveId($data['MaKhuVuc'])
                : $this->readPositiveId($currentRow['MaKhuVuc']);

            if ($maLoaiBan <= 0 || $maKhuVuc <= 0) {
                return ['success' => false, 'error' => 'Loại bàn hoặc khu vực không hợp lệ'];
            }

            $priceResult = $this->calculateTablePrice($maLoaiBan, $maKhuVuc);
            if (!($priceResult['success'] ?? false)) {
                return ['success' => false, 'error' => $priceResult['error'] ?? 'Không thể tính giá bàn'];
            }

            $giaGio = (float)$priceResult['price'];
            $fields = [];
            $values = [];
            
            if ($hasName) {
                $fields[] = "TenBan = ?";
                $values[] = $tenBan;
            }

            if ($hasStatus) {
                $fields[] = "TrangThai = ?";
                $values[] = $data['TrangThai'];
            }

            if ($hasLoaiBan) {
                $fields[] = "MaLoaiBan = ?";
                $values[] = $maLoaiBan;
            }

            if ($hasKhuVuc) {
                $fields[] = "MaKhuVuc = ?";
                $values[] = $maKhuVuc;
            }

            $fields[] = "GiaGio = ?";
            $values[] = $giaGio;
            
            if (empty($fields)) {
                return ['success' => false, 'error' => 'Không có dữ liệu cần cập nhật'];
            }
            
            $values[] = $id;
            $query = "UPDATE Ban SET " . implode(', ', $fields) . " WHERE MaBan = ?";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($values);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Cập nhật bàn thành công',
                    'price' => $giaGio,
                    'basePrice' => $priceResult['basePrice'] ?? 0,
                    'extraPrice' => $priceResult['extraPrice'] ?? 0,
                ];
            } else if ($result && $stmt->rowCount() === 0) {
                return [
                    'success' => true,
                    'message' => 'Không có thay đổi dữ liệu',
                    'price' => $giaGio,
                    'basePrice' => $priceResult['basePrice'] ?? 0,
                    'extraPrice' => $priceResult['extraPrice'] ?? 0,
                ];
            }

            return ['success' => false, 'error' => 'Không thể cập nhật bàn'];
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
            $basePriceColumn = $this->getLoaiBanBasePriceColumn();
            $extraPriceColumn = $this->getKhuVucExtraPriceColumn();
            $query = "SELECT b.*, lb.TenLoai, kv.TenKhuVuc 
                      , lb.{$basePriceColumn} AS GiaCoBan
                      , kv.{$extraPriceColumn} AS PhuThuKhuVuc
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
