<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/DatabaseHelper.php';
require_once __DIR__ . '/modules/quanly_ban/controller.php';
require_once __DIR__ . '/modules/quanly_hoadon/controller.php';
require_once __DIR__ . '/modules/quanly_dichvu/controller.php';
require_once __DIR__ . '/modules/quanly_khachhang/controller.php';

boot_session();
require_login();

date_default_timezone_set("Asia/Ho_Chi_Minh");

function h(string $value): string
{
	return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

$pages = [
	"dashboard" => ["title" => "Tổng quan", "icon" => "ri-dashboard-3-line"],
	"tables" => ["title" => "Quản lý bàn", "icon" => "ri-layout-grid-line"],
	"orders" => ["title" => "Gọi món và hóa đơn", "icon" => "ri-receipt-line"],
	"customers" => ["title" => "Khách hàng", "icon" => "ri-group-line"],
	"inventory" => ["title" => "Kho", "icon" => "ri-archive-line"],
	"staff" => ["title" => "Nhân sự", "icon" => "ri-team-line"],
	"reports" => ["title" => "Báo cáo", "icon" => "ri-line-chart-line"],
	"settings" => ["title" => "Cài đặt", "icon" => "ri-settings-3-line"],
];

$currentPage = $_GET["page"] ?? "dashboard";
if (!array_key_exists($currentPage, $pages)) {
	$currentPage = "dashboard";
}

$pageTitle = $pages[$currentPage]["title"];
$activeUser = current_user_name();
$activeRole = current_user_role();

function fetchAllSafe(PDO $conn, string $query, array $params = []): array
{
	try {
		$stmt = $conn->prepare($query);
		$stmt->execute($params);
		return $stmt->fetchAll();
	} catch (PDOException $e) {
		error_log("Query failed: " . $e->getMessage());
		return [];
	}
}

function fetchOneSafe(PDO $conn, string $query, array $params = []): ?array
{
	$rows = fetchAllSafe($conn, $query, $params);
	return $rows[0] ?? null;
}

function formatMoney(float $amount): string
{
	return number_format($amount, 0, ".", ",");
}

function lowerText(string $value): string
{
	$value = trim($value);
	if (function_exists("mb_strtolower")) {
		return mb_strtolower($value, "UTF-8");
	}
	return strtolower($value);
}

function textContains(string $haystack, string $needle): bool
{
	return strpos($haystack, $needle) !== false;
}

function tableUiStatus(string $dbStatus): string
{
	$status = lowerText($dbStatus);
	if ($status === "trống" || $status === "trong" || $status === "available") {
		return "available";
	}
	if ($status === "đặt trước" || $status === "dat truoc" || $status === "reserved") {
		return "reserved";
	}
	return "playing";
}

function menuIconByCategory(string $category): string
{
	$label = lowerText($category);
	if (textContains($label, "uống") || textContains($label, "drink") || textContains($label, "nước")) {
		return "ri-cup-line";
	}
	if (textContains($label, "snack") || textContains($label, "ăn vặt")) {
		return "ri-cake-2-line";
	}
	return "ri-restaurant-line";
}

function tableStatusLabel(string $status): string
{
	if ($status === "available") {
		return "TRỐNG";
	}
	if ($status === "reserved") {
		return "ĐẶT TRƯỚC";
	}
	return "ĐANG CHƠI";
}

function activityStateLabel(string $state): string
{
	if ($state === "paid") {
		return "ĐÃ THANH TOÁN";
	}
	if ($state === "warn") {
		return "CẢNH BÁO";
	}
	if ($state === "active") {
		return "ĐANG PHỤC VỤ";
	}
	return strtoupper($state);
}

function readPositiveInt($value): int
{
	// Chỉ chấp nhận ID số nguyên dương từ GET/POST.
	if (is_int($value)) {
		return $value > 0 ? $value : 0;
	}

	if (is_string($value) && ctype_digit($value)) {
		$parsed = (int)$value;
		return $parsed > 0 ? $parsed : 0;
	}

	return 0;
}

function readNullablePositiveInt($value): ?int
{
	$parsed = readPositiveInt($value);
	return $parsed > 0 ? $parsed : null;
}

function readTextValue($value, int $maxLength = 255): string
{
	if (!is_string($value) && !is_numeric($value)) {
		return "";
	}

	$text = trim((string)$value);
	if ($text === "") {
		return "";
	}

	if (function_exists("mb_substr")) {
		return mb_substr($text, 0, $maxLength, "UTF-8");
	}

	return substr($text, 0, $maxLength);
}

function readNonNegativeFloat($value): float
{
	if (is_int($value) || is_float($value)) {
		return max(0.0, (float)$value);
	}

	if (is_string($value)) {
		$normalized = trim(str_replace(",", ".", $value));
		if ($normalized !== "" && is_numeric($normalized)) {
			return max(0.0, (float)$normalized);
		}
	}

	return 0.0;
}

function normalizeTableStatusInput(string $status): string
{
	$label = lowerText($status);
	if ($label === "đặt trước" || $label === "dat truoc" || $label === "reserved") {
		return "Đặt trước";
	}
	if ($label === "đang sử dụng" || $label === "dang su dung" || $label === "playing" || $label === "active") {
		return "Đang sử dụng";
	}

	return "Trống";
}

function pushFlashMessage(string $tone, string $text): void
{
	boot_session();

	if (empty($_SESSION["flash_messages"]) || !is_array($_SESSION["flash_messages"])) {
		$_SESSION["flash_messages"] = [];
	}

	$_SESSION["flash_messages"][] = [
		"tone" => $tone,
		"text" => $text,
	];
}

function pullFlashMessages(): array
{
	boot_session();

	$messages = [];
	if (!empty($_SESSION["flash_messages"]) && is_array($_SESSION["flash_messages"])) {
		$messages = $_SESSION["flash_messages"];
	}

	unset($_SESSION["flash_messages"]);
	return $messages;
}

function redirectToPage(string $page, int $tableId = 0): void
{
	$url = "index.php?page=" . rawurlencode($page);
	if ($tableId > 0) {
		$url .= "&table_id=" . $tableId;
	}

	header("Location: " . $url);
	exit;
}

function getOpenInvoiceByTable(PDO $conn, int $tableId): ?array
{
	$hoaDonController = new HoaDonController($conn);
	$response = $hoaDonController->getHoaDonByBan($tableId);

	if (!($response["success"] ?? false) || empty($response["data"]) || !is_array($response["data"])) {
		return null;
	}

	return $response["data"];
}

function startTable(PDO $conn, int $tableId): array
{
	$banController = new BanController($conn);
	$tableResponse = $banController->getById($tableId);

	if (!($tableResponse["success"] ?? false) || empty($tableResponse["data"])) {
		return ["success" => false, "error" => "Bàn không tồn tại."];
	}

	$table = $tableResponse["data"];
	// Bàn đã có người chơi/đặt trước thì không cho mở thêm phiên mới.
	if (tableUiStatus((string)($table["TrangThai"] ?? "")) !== "available") {
		return ["success" => false, "error" => "Không thể mở bàn vì bàn đang được sử dụng hoặc đã đặt trước."];
	}

	return $banController->updateStatus($tableId, "Đang sử dụng");
}

function createDefaultTable(PDO $conn): array
{
	$banController = new BanController($conn);

	$typeRow = fetchOneSafe(
		$conn,
		"SELECT MaLoaiBan FROM LoaiBan ORDER BY MaLoaiBan ASC LIMIT 1"
	);

	if ($typeRow === null) {
		return ["success" => false, "error" => "Chưa có loại bàn để thêm bàn mới."];
	}

	$defaultTypeId = readPositiveInt($typeRow["MaLoaiBan"] ?? 0);
	if ($defaultTypeId <= 0) {
		return ["success" => false, "error" => "Không xác định được loại bàn mặc định."];
	}

	$areaRow = fetchOneSafe($conn, "SELECT MaKhuVuc FROM KhuVuc ORDER BY MaKhuVuc ASC LIMIT 1");
	if ($areaRow === null) {
		return ["success" => false, "error" => "Chưa có khu vực để thêm bàn mới."];
	}

	$defaultAreaId = readPositiveInt($areaRow["MaKhuVuc"] ?? 0);
	if ($defaultAreaId <= 0) {
		return ["success" => false, "error" => "Không xác định được khu vực mặc định."];
	}

	$nextIndexRow = fetchOneSafe($conn, "SELECT IFNULL(MAX(MaBan), 0) + 1 AS NextIndex FROM Ban");
	$nextIndex = readPositiveInt($nextIndexRow["NextIndex"] ?? 0);
	if ($nextIndex <= 0) {
		$nextIndex = 1;
	}
	$defaultName = "Bàn " . $nextIndex;

	return $banController->create([
		"TenBan" => $defaultName,
		"TrangThai" => "Trống",
		"MaLoaiBan" => $defaultTypeId,
		"MaKhuVuc" => $defaultAreaId,
	]);
}

function createInvoice(PDO $conn, int $tableId, int $employeeId, ?int $customerId = null): array
{
	$hoaDonController = new HoaDonController($conn);
	$openInvoice = getOpenInvoiceByTable($conn, $tableId);

	if ($openInvoice !== null) {
		return ["success" => false, "error" => "Bàn này đã có hóa đơn đang phục vụ."];
	}

	return $hoaDonController->create([
		"MaBan" => $tableId,
		"MaNhanVien" => $employeeId,
		"MaKhachHang" => $customerId,
		"TongTien" => 0,
		"TrangThai" => 0,
		"TienGioLuyKe" => 0,
	]);
}

function addProductToInvoice(PDO $conn, int $invoiceId, int $productId, int $quantity): array
{
	if ($quantity <= 0) {
		return ["success" => false, "error" => "Số lượng phải lớn hơn 0."];
	}

	$hoaDonController = new HoaDonController($conn);
	$dichVuController = new DichVuController($conn);

	$productResponse = $dichVuController->getById($productId);
	if (!($productResponse["success"] ?? false) || empty($productResponse["data"])) {
		return ["success" => false, "error" => "Món không tồn tại hoặc đã ngừng phục vụ."];
	}

	$invoiceResponse = $hoaDonController->getById($invoiceId);
	if (!($invoiceResponse["success"] ?? false) || empty($invoiceResponse["data"])) {
		return ["success" => false, "error" => "Hóa đơn không tồn tại."];
	}

	$invoice = $invoiceResponse["data"];
	if ((int)($invoice["TrangThai"] ?? 1) !== 0) {
		return ["success" => false, "error" => "Hóa đơn đã thanh toán, không thể thêm món."];
	}

	$product = $productResponse["data"];
	$unitPrice = (float)($product["GiaBan"] ?? 0);
	$details = $invoice["ChiTiet"] ?? [];

	foreach ($details as $detail) {
		if ((int)($detail["MaMon"] ?? 0) === $productId) {
			$currentQty = (int)($detail["SoLuong"] ?? 0);
			return $hoaDonController->updateChiTiet((int)$detail["MaChiTietHD"], [
				"SoLuong" => $currentQty + $quantity,
				"DonGia" => $unitPrice,
			]);
		}
	}

	return $hoaDonController->addChiTiet($invoiceId, [
		"MaMon" => $productId,
		"SoLuong" => $quantity,
		"DonGia" => $unitPrice,
	]);
}

function calculateTotal(PDO $conn, int $invoiceId): array
{
	$hoaDonController = new HoaDonController($conn);
	$banController = new BanController($conn);

	$invoiceResponse = $hoaDonController->getById($invoiceId);
	if (!($invoiceResponse["success"] ?? false) || empty($invoiceResponse["data"])) {
		return ["success" => false, "error" => "Không tìm thấy hóa đơn để tính tiền."];
	}

	$invoice = $invoiceResponse["data"];
	$tableId = (int)($invoice["MaBan"] ?? 0);

	$tableResponse = $banController->getById($tableId);
	if (!($tableResponse["success"] ?? false) || empty($tableResponse["data"])) {
		return ["success" => false, "error" => "Không tìm thấy thông tin bàn để tính tiền giờ."];
	}

	$serviceTotal = 0.0;
	$details = $invoice["ChiTiet"] ?? [];
	foreach ($details as $detail) {
		$serviceTotal += ((float)($detail["SoLuong"] ?? 0)) * ((float)($detail["DonGia"] ?? 0));
	}

	$startTimestamp = strtotime((string)($invoice["NgayLap"] ?? ""));
	if ($startTimestamp === false) {
		$startTimestamp = time();
	}

	$endTimestamp = time();
	if (!empty($invoice["NgayKetThuc"])) {
		$parsedEnd = strtotime((string)$invoice["NgayKetThuc"]);
		if ($parsedEnd !== false) {
			$endTimestamp = $parsedEnd;
		}
	}

	$elapsedSeconds = max(0, $endTimestamp - $startTimestamp);
	$elapsedHours = $elapsedSeconds / 3600;
	$hourRate = (float)($tableResponse["data"]["GiaGio"] ?? 0);
	$timeCharge = round($hourRate * $elapsedHours, 2);
	$total = round($serviceTotal + $timeCharge, 2);

	return [
		"success" => true,
		"invoice" => $invoice,
		"serviceTotal" => $serviceTotal,
		"timeCharge" => $timeCharge,
		"hours" => $elapsedHours,
		"elapsedSeconds" => $elapsedSeconds,
		"total" => $total,
	];
}

function checkoutInvoice(PDO $conn, int $invoiceId, ?array $calculated = null): array
{
	$hoaDonController = new HoaDonController($conn);
	$banController = new BanController($conn);

	if ($calculated === null) {
		$calculated = calculateTotal($conn, $invoiceId);
	}

	if (!($calculated["success"] ?? false)) {
		return $calculated;
	}

	$invoice = $calculated["invoice"];
	if ((int)($invoice["TrangThai"] ?? 1) !== 0) {
		return ["success" => false, "error" => "Hóa đơn đã được thanh toán trước đó."];
	}

	$tableId = (int)($invoice["MaBan"] ?? 0);

	try {
		// Transaction giúp cập nhật hóa đơn và trạng thái bàn đồng bộ.
		$conn->beginTransaction();

		$updateStmt = $conn->prepare("UPDATE HoaDon SET TongTien = ?, TienGioLuyKe = ? WHERE MaHoaDon = ?");
		$updateStmt->execute([
			$calculated["total"],
			$calculated["timeCharge"],
			$invoiceId,
		]);

		if ($updateStmt->rowCount() === 0) {
			$existsStmt = $conn->prepare("SELECT MaHoaDon FROM HoaDon WHERE MaHoaDon = ? LIMIT 1");
			$existsStmt->execute([$invoiceId]);
			if (!$existsStmt->fetch()) {
				throw new RuntimeException("Không thể cập nhật hóa đơn.");
			}
		}

		$completeResponse = $hoaDonController->completeHoaDon($invoiceId);
		if (!($completeResponse["success"] ?? false)) {
			throw new RuntimeException((string)($completeResponse["error"] ?? "Không thể hoàn tất hóa đơn."));
		}

		if ($tableId > 0) {
			$updateStatusResponse = $banController->updateStatus($tableId, "Trống");
			if (!($updateStatusResponse["success"] ?? false)) {
				throw new RuntimeException((string)($updateStatusResponse["error"] ?? "Không thể cập nhật trạng thái bàn."));
			}
		}

		$conn->commit();

		return [
			"success" => true,
			"message" => "Thanh toán thành công",
			"data" => $calculated,
		];
	} catch (Throwable $e) {
		if ($conn->inTransaction()) {
			$conn->rollBack();
		}

		return [
			"success" => false,
			"error" => $e->getMessage(),
		];
	}
}

$selectedTableId = readPositiveInt($_GET["table_id"] ?? 0);
$customerEditId = readPositiveInt($_GET["customer_edit_id"] ?? 0);
$inventoryEditId = readPositiveInt($_GET["inventory_edit_id"] ?? 0);
$staffEditId = readPositiveInt($_GET["staff_edit_id"] ?? 0);

if (isset($_GET["action"])) {
	switch ((string)$_GET["action"]) {
		case "select_table":
			if ($selectedTableId > 0) {
				$currentPage = "orders";
				$pageTitle = $pages[$currentPage]["title"];
			}
			break;
	}
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
	// Khối xử lý thao tác từ UI theo action POST.
	$action = (string)$_POST["action"];
	$redirectPage = (string)($_POST["redirect_page"] ?? "tables");
	if (!array_key_exists($redirectPage, $pages)) {
		$redirectPage = "tables";
	}

	$tableId = readPositiveInt($_POST["table_id"] ?? 0);
	$invoiceId = readPositiveInt($_POST["invoice_id"] ?? 0);

	if (!csrf_validate($_POST["csrf_token"] ?? null)) {
		pushFlashMessage("warn", "Phiên thao tác không hợp lệ, vui lòng thử lại.");
		redirectToPage($redirectPage, $tableId);
	}

	switch ($action) {
		case "table_create":
			$tableName = readTextValue($_POST["table_name"] ?? "", 100);
			$tableTypeId = readPositiveInt($_POST["table_type_id"] ?? 0);
			$tableAreaId = readPositiveInt($_POST["table_area_id"] ?? 0);
			$tableStatus = normalizeTableStatusInput(readTextValue($_POST["table_status"] ?? "Trống", 30));

			if ($tableName === "" || $tableTypeId <= 0 || $tableAreaId <= 0) {
				pushFlashMessage("warn", "Vui lòng nhập đầy đủ thông tin bàn (tên, loại bàn, khu vực).");
				redirectToPage("tables");
			}

			$banController = new BanController($conn);
			$createResponse = $banController->create([
				"TenBan" => $tableName,
				"TrangThai" => $tableStatus,
				"MaLoaiBan" => $tableTypeId,
				"MaKhuVuc" => $tableAreaId,
			]);

			if (!($createResponse["success"] ?? false)) {
				pushFlashMessage("warn", (string)($createResponse["error"] ?? "Không thể thêm bàn."));
				redirectToPage("tables");
			}

			pushFlashMessage("good", "Đã thêm bàn mới thành công.");
			redirectToPage("tables", readPositiveInt($createResponse["id"] ?? 0));
			break;

		case "table_update":
			if ($tableId <= 0) {
				pushFlashMessage("warn", "Mã bàn không hợp lệ.");
				redirectToPage("tables");
			}

			$tableName = readTextValue($_POST["table_name"] ?? "", 100);
			$tableTypeId = readPositiveInt($_POST["table_type_id"] ?? 0);
			$tableAreaId = readPositiveInt($_POST["table_area_id"] ?? 0);
			$tableStatus = normalizeTableStatusInput(readTextValue($_POST["table_status"] ?? "Trống", 30));

			if ($tableName === "" || $tableTypeId <= 0 || $tableAreaId <= 0) {
				pushFlashMessage("warn", "Vui lòng nhập đầy đủ thông tin bàn để cập nhật.");
				redirectToPage("tables", $tableId);
			}

			$banController = new BanController($conn);
			$updateResponse = $banController->update($tableId, [
				"TenBan" => $tableName,
				"TrangThai" => $tableStatus,
				"MaLoaiBan" => $tableTypeId,
				"MaKhuVuc" => $tableAreaId,
			]);

			if (!($updateResponse["success"] ?? false)) {
				pushFlashMessage("warn", (string)($updateResponse["error"] ?? "Không thể cập nhật bàn."));
				redirectToPage("tables", $tableId);
			}

			pushFlashMessage("good", "Cập nhật bàn thành công.");
			redirectToPage("tables", $tableId);
			break;

		case "table_delete":
			if ($tableId <= 0) {
				pushFlashMessage("warn", "Mã bàn không hợp lệ.");
				redirectToPage("tables");
			}

			$banController = new BanController($conn);
			$deleteResponse = $banController->delete($tableId);
			if (!($deleteResponse["success"] ?? false)) {
				pushFlashMessage("warn", (string)($deleteResponse["error"] ?? "Không thể xóa bàn."));
				redirectToPage("tables", $tableId);
			}

			pushFlashMessage("good", "Đã xóa bàn thành công.");
			redirectToPage("tables");
			break;

		case "table_type_create":
			$tableTypeName = readTextValue($_POST["table_type_name"] ?? "", 100);
			$tableTypeBasePrice = readNonNegativeFloat($_POST["table_type_base_price"] ?? 0);

			if ($tableTypeName === "") {
				pushFlashMessage("warn", "Tên loại bàn không được để trống.");
				redirectToPage("tables");
			}

			$banController = new BanController($conn);
			$createTypeResponse = $banController->createLoaiBan([
				"TenLoai" => $tableTypeName,
				"GiaCoBan" => $tableTypeBasePrice,
			]);

			if (!($createTypeResponse["success"] ?? false)) {
				pushFlashMessage("warn", (string)($createTypeResponse["error"] ?? "Không thể thêm loại bàn."));
				redirectToPage("tables");
			}

			pushFlashMessage("good", "Đã thêm loại bàn mới thành công.");
			redirectToPage("tables");
			break;

		case "table_area_create":
			$tableAreaName = readTextValue($_POST["table_area_name"] ?? "", 100);
			$tableAreaExtraPrice = readNonNegativeFloat($_POST["table_area_extra_price"] ?? 0);

			if ($tableAreaName === "") {
				pushFlashMessage("warn", "Tên khu vực không được để trống.");
				redirectToPage("tables");
			}

			$banController = new BanController($conn);
			$createAreaResponse = $banController->createKhuVuc([
				"TenKhuVuc" => $tableAreaName,
				"ExtraPrice" => $tableAreaExtraPrice,
			]);

			if (!($createAreaResponse["success"] ?? false)) {
				pushFlashMessage("warn", (string)($createAreaResponse["error"] ?? "Không thể thêm khu vực."));
				redirectToPage("tables");
			}

			pushFlashMessage("good", "Đã thêm khu vực mới thành công.");
			redirectToPage("tables");
			break;

		case "customer_create":
			$customerName = readTextValue($_POST["customer_name"] ?? "", 120);
			$customerPhone = readTextValue($_POST["customer_phone"] ?? "", 30);
			$customerTier = readTextValue($_POST["customer_tier"] ?? "Mới", 30);
			$customerPoints = readPositiveInt($_POST["customer_points"] ?? 0);

			if ($customerName === "") {
				pushFlashMessage("warn", "Tên khách hàng không được để trống.");
				redirectToPage("customers");
			}

			$khachHangController = new KhachHangController($conn);
			$createResponse = $khachHangController->create([
				"HoTen" => $customerName,
				"SDT" => $customerPhone === "" ? null : $customerPhone,
				"Hang" => $customerTier === "" ? "Mới" : $customerTier,
				"DiemTichLuy" => $customerPoints,
			]);

			if (!($createResponse["success"] ?? false)) {
				pushFlashMessage("warn", (string)($createResponse["error"] ?? "Không thể thêm khách hàng."));
				redirectToPage("customers");
			}

			pushFlashMessage("good", "Đã thêm khách hàng thành công.");
			redirectToPage("customers");
			break;

		case "customer_update":
			$customerId = readPositiveInt($_POST["customer_id"] ?? 0);
			$customerName = readTextValue($_POST["customer_name"] ?? "", 120);
			$customerPhone = readTextValue($_POST["customer_phone"] ?? "", 30);
			$customerTier = readTextValue($_POST["customer_tier"] ?? "Mới", 30);
			$customerPoints = readPositiveInt($_POST["customer_points"] ?? 0);

			if ($customerId <= 0 || $customerName === "") {
				pushFlashMessage("warn", "Dữ liệu cập nhật khách hàng không hợp lệ.");
				redirectToPage("customers");
			}

			$khachHangController = new KhachHangController($conn);
			$updateResponse = $khachHangController->update($customerId, [
				"HoTen" => $customerName,
				"SDT" => $customerPhone === "" ? null : $customerPhone,
				"Hang" => $customerTier === "" ? "Mới" : $customerTier,
				"DiemTichLuy" => $customerPoints,
			]);

			if (!($updateResponse["success"] ?? false)) {
				pushFlashMessage("warn", (string)($updateResponse["error"] ?? "Không thể cập nhật khách hàng."));
				redirectToPage("customers");
			}

			pushFlashMessage("good", "Cập nhật khách hàng thành công.");
			redirectToPage("customers");
			break;

		case "customer_delete":
			$customerId = readPositiveInt($_POST["customer_id"] ?? 0);
			if ($customerId <= 0) {
				pushFlashMessage("warn", "Mã khách hàng không hợp lệ.");
				redirectToPage("customers");
			}

			$khachHangController = new KhachHangController($conn);
			$deleteResponse = $khachHangController->delete($customerId);
			if (!($deleteResponse["success"] ?? false)) {
				pushFlashMessage("warn", (string)($deleteResponse["error"] ?? "Không thể xóa khách hàng."));
				redirectToPage("customers");
			}

			pushFlashMessage("good", "Đã xóa khách hàng thành công.");
			redirectToPage("customers");
			break;

		case "inventory_create":
			$ingredientName = readTextValue($_POST["ingredient_name"] ?? "", 120);
			$ingredientDvtId = readPositiveInt($_POST["ingredient_dvt_id"] ?? 0);
			$ingredientQty = readNonNegativeFloat($_POST["ingredient_qty"] ?? 0);
			$ingredientCost = readNonNegativeFloat($_POST["ingredient_cost"] ?? 0);
			$ingredientSupplierId = readTextValue($_POST["ingredient_supplier_id"] ?? "", 20);

			if ($ingredientName === "" || $ingredientDvtId <= 0 || $ingredientSupplierId === "") {
				pushFlashMessage("warn", "Vui lòng nhập đủ thông tin nguyên liệu.");
				redirectToPage("inventory");
			}

			try {
				$stmt = $conn->prepare("INSERT INTO NguyenLieu (TenNguyenLieu, SoLuongTon, MaDVT, MaNhaCungCap, GiaVon) VALUES (?, ?, ?, ?, ?)");
				$stmt->execute([$ingredientName, $ingredientQty, $ingredientDvtId, $ingredientSupplierId, $ingredientCost]);
				pushFlashMessage("good", "Đã thêm nguyên liệu thành công.");
			} catch (Throwable $e) {
				pushFlashMessage("warn", "Không thể thêm nguyên liệu: " . $e->getMessage());
			}
			redirectToPage("inventory");
			break;

		case "inventory_update":
			$ingredientId = readPositiveInt($_POST["ingredient_id"] ?? 0);
			$ingredientName = readTextValue($_POST["ingredient_name"] ?? "", 120);
			$ingredientDvtId = readPositiveInt($_POST["ingredient_dvt_id"] ?? 0);
			$ingredientQty = readNonNegativeFloat($_POST["ingredient_qty"] ?? 0);
			$ingredientCost = readNonNegativeFloat($_POST["ingredient_cost"] ?? 0);
			$ingredientSupplierId = readTextValue($_POST["ingredient_supplier_id"] ?? "", 20);

			if ($ingredientId <= 0 || $ingredientName === "" || $ingredientDvtId <= 0 || $ingredientSupplierId === "") {
				pushFlashMessage("warn", "Dữ liệu cập nhật nguyên liệu không hợp lệ.");
				redirectToPage("inventory");
			}

			try {
				$stmt = $conn->prepare("UPDATE NguyenLieu SET TenNguyenLieu = ?, SoLuongTon = ?, MaDVT = ?, MaNhaCungCap = ?, GiaVon = ? WHERE MaNguyenLieu = ?");
				$stmt->execute([$ingredientName, $ingredientQty, $ingredientDvtId, $ingredientSupplierId, $ingredientCost, $ingredientId]);
				pushFlashMessage("good", "Cập nhật nguyên liệu thành công.");
			} catch (Throwable $e) {
				pushFlashMessage("warn", "Không thể cập nhật nguyên liệu: " . $e->getMessage());
			}
			redirectToPage("inventory");
			break;

		case "inventory_delete":
			$ingredientId = readPositiveInt($_POST["ingredient_id"] ?? 0);
			if ($ingredientId <= 0) {
				pushFlashMessage("warn", "Mã nguyên liệu không hợp lệ.");
				redirectToPage("inventory");
			}

			try {
				$stmt = $conn->prepare("DELETE FROM NguyenLieu WHERE MaNguyenLieu = ?");
				$stmt->execute([$ingredientId]);
				pushFlashMessage("good", "Đã xóa nguyên liệu thành công.");
			} catch (Throwable $e) {
				pushFlashMessage("warn", "Không thể xóa nguyên liệu: " . $e->getMessage());
			}
			redirectToPage("inventory");
			break;

		case "staff_create":
			$staffName = readTextValue($_POST["staff_name"] ?? "", 120);
			$staffPhone = readTextValue($_POST["staff_phone"] ?? "", 30);
			$staffRoleId = readPositiveInt($_POST["staff_role_id"] ?? 0);
			$staffAddress = readTextValue($_POST["staff_address"] ?? "", 255);

			if ($staffName === "" || $staffPhone === "" || $staffRoleId <= 0 || $staffAddress === "") {
				pushFlashMessage("warn", "Vui lòng nhập đầy đủ thông tin nhân sự.");
				redirectToPage("staff");
			}

			try {
				$stmt = $conn->prepare("INSERT INTO NhanVien (HoTen, SDT, MaChucVu, DiaChi) VALUES (?, ?, ?, ?)");
				$stmt->execute([$staffName, $staffPhone, $staffRoleId, $staffAddress]);
				pushFlashMessage("good", "Đã thêm nhân viên mới.");
			} catch (Throwable $e) {
				pushFlashMessage("warn", "Không thể thêm nhân viên: " . $e->getMessage());
			}
			redirectToPage("staff");
			break;

		case "staff_update":
			$staffId = readPositiveInt($_POST["staff_id"] ?? 0);
			$staffName = readTextValue($_POST["staff_name"] ?? "", 120);
			$staffPhone = readTextValue($_POST["staff_phone"] ?? "", 30);
			$staffRoleId = readPositiveInt($_POST["staff_role_id"] ?? 0);
			$staffAddress = readTextValue($_POST["staff_address"] ?? "", 255);

			if ($staffId <= 0 || $staffName === "" || $staffPhone === "" || $staffRoleId <= 0 || $staffAddress === "") {
				pushFlashMessage("warn", "Dữ liệu cập nhật nhân sự không hợp lệ.");
				redirectToPage("staff");
			}

			try {
				$stmt = $conn->prepare("UPDATE NhanVien SET HoTen = ?, SDT = ?, MaChucVu = ?, DiaChi = ? WHERE MaNhanVien = ?");
				$stmt->execute([$staffName, $staffPhone, $staffRoleId, $staffAddress, $staffId]);
				pushFlashMessage("good", "Đã cập nhật thông tin nhân viên.");
			} catch (Throwable $e) {
				pushFlashMessage("warn", "Không thể cập nhật nhân viên: " . $e->getMessage());
			}
			redirectToPage("staff");
			break;

		case "staff_delete":
			$staffId = readPositiveInt($_POST["staff_id"] ?? 0);
			if ($staffId <= 0) {
				pushFlashMessage("warn", "Mã nhân viên không hợp lệ.");
				redirectToPage("staff");
			}

			$linkedUser = fetchOneSafe($conn, "SELECT MaUser FROM User WHERE MaNhanVien = ? LIMIT 1", [$staffId]);
			if ($linkedUser !== null) {
				pushFlashMessage("warn", "Nhân viên đã gắn tài khoản đăng nhập, không thể xóa.");
				redirectToPage("staff");
			}

			$linkedInvoice = fetchOneSafe($conn, "SELECT MaHoaDon FROM HoaDon WHERE MaNhanVien = ? LIMIT 1", [$staffId]);
			if ($linkedInvoice !== null) {
				pushFlashMessage("warn", "Nhân viên đã có lịch sử hóa đơn, không thể xóa.");
				redirectToPage("staff");
			}

			try {
				$stmt = $conn->prepare("DELETE FROM NhanVien WHERE MaNhanVien = ?");
				$stmt->execute([$staffId]);
				pushFlashMessage("good", "Đã xóa nhân viên thành công.");
			} catch (Throwable $e) {
				pushFlashMessage("warn", "Không thể xóa nhân viên: " . $e->getMessage());
			}
			redirectToPage("staff");
			break;

		case "settings_save":
			$newClubName = readTextValue($_POST["club_name"] ?? "", 120);
			$newClubAddress = readTextValue($_POST["club_address"] ?? "", 255);
			$newClubPhone = readTextValue($_POST["club_phone"] ?? "", 30);
			$newClubEmail = readTextValue($_POST["club_email"] ?? "", 100);

			if ($newClubName === "") {
				pushFlashMessage("warn", "Tên cơ sở không được để trống.");
				redirectToPage("settings");
			}

			try {
				$conn->beginTransaction();

				$settingPairs = [
					"club_name" => $newClubName,
					"club_address" => $newClubAddress,
					"club_phone" => $newClubPhone,
					"club_email" => $newClubEmail,
				];

				$upsertStmt = $conn->prepare("INSERT INTO SystemSetting (`Key`, `Value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `Value` = VALUES(`Value`)");
				foreach ($settingPairs as $key => $value) {
					$upsertStmt->execute([$key, $value]);
				}

				$conn->commit();
				pushFlashMessage("good", "Đã lưu cài đặt cơ sở.");
			} catch (Throwable $e) {
				if ($conn->inTransaction()) {
					$conn->rollBack();
				}
				pushFlashMessage("warn", "Không thể lưu cài đặt: " . $e->getMessage());
			}
			redirectToPage("settings");
			break;

		case "add_table":
			$createTableResponse = createDefaultTable($conn);
			if (!($createTableResponse["success"] ?? false)) {
				pushFlashMessage("warn", (string)($createTableResponse["error"] ?? "Không thể thêm bàn mới."));
				redirectToPage("tables");
			}

			$newTableId = readPositiveInt($createTableResponse["id"] ?? 0);
			$newTableLabel = $newTableId > 0 ? " #" . $newTableId : "";
			pushFlashMessage("good", "Đã thêm bàn mới" . $newTableLabel . " thành công.");
			redirectToPage("tables", $newTableId);
			break;

		case "start_table":
			$employeeId = readPositiveInt($_SESSION["employee_id"] ?? 0);
			if ($employeeId <= 0) {
				pushFlashMessage("warn", "Không tìm thấy nhân viên đăng nhập. Vui lòng đăng nhập lại.");
				redirectToPage("tables", $tableId);
			}

			if ($tableId <= 0) {
				pushFlashMessage("warn", "Mã bàn không hợp lệ.");
				redirectToPage("tables");
			}

			$customerId = readNullablePositiveInt($_POST["customer_id"] ?? null);

			try {
				$conn->beginTransaction();

				$startResponse = startTable($conn, $tableId);
				if (!($startResponse["success"] ?? false)) {
					throw new RuntimeException((string)($startResponse["error"] ?? "Không thể mở bàn."));
				}

				$createResponse = createInvoice($conn, $tableId, $employeeId, $customerId);
				if (!($createResponse["success"] ?? false)) {
					throw new RuntimeException((string)($createResponse["error"] ?? "Không thể tạo hóa đơn."));
				}

				$conn->commit();
				pushFlashMessage("good", "Mở bàn thành công. Đã tạo hóa đơn #" . (string)$createResponse["id"] . ".");
				redirectToPage("orders", $tableId);
			} catch (Throwable $e) {
				if ($conn->inTransaction()) {
					$conn->rollBack();
				}

				pushFlashMessage("warn", $e->getMessage());
				redirectToPage("tables", $tableId);
			}
			break;

		case "end_table":
			if ($tableId <= 0) {
				pushFlashMessage("warn", "Mã bàn không hợp lệ.");
				redirectToPage("tables");
			}

			$openInvoice = getOpenInvoiceByTable($conn, $tableId);
			if ($openInvoice === null) {
				pushFlashMessage("warn", "Bàn này chưa có hóa đơn mở để kết thúc.");
				redirectToPage("tables", $tableId);
			}

			$invoiceId = (int)($openInvoice["MaHoaDon"] ?? 0);
			$calculated = calculateTotal($conn, $invoiceId);
			$checkoutResponse = checkoutInvoice($conn, $invoiceId, $calculated);

			if (!($checkoutResponse["success"] ?? false)) {
				pushFlashMessage("warn", (string)($checkoutResponse["error"] ?? "Không thể kết thúc bàn."));
				redirectToPage("tables", $tableId);
			}

			$elapsedLabel = gmdate("H:i:s", (int)($calculated["elapsedSeconds"] ?? 0));
			pushFlashMessage("good", "Kết thúc bàn thành công. Thời gian chơi: " . $elapsedLabel . ".");
			redirectToPage("tables");
			break;

		case "add_product":
			$productId = readPositiveInt($_POST["product_id"] ?? 0);
			$quantity = readPositiveInt($_POST["quantity"] ?? 0);

			if ($tableId > 0 && $invoiceId <= 0) {
				$openInvoice = getOpenInvoiceByTable($conn, $tableId);
				if ($openInvoice !== null) {
					$invoiceId = (int)($openInvoice["MaHoaDon"] ?? 0);
				}
			}

			if ($invoiceId <= 0) {
				pushFlashMessage("warn", "Không có hóa đơn mở. Hãy mở bàn trước khi thêm món.");
				redirectToPage("orders", $tableId);
			}

			if ($productId <= 0 || $quantity <= 0) {
				pushFlashMessage("warn", "Món hoặc số lượng không hợp lệ.");
				redirectToPage("orders", $tableId);
			}

			$addResponse = addProductToInvoice($conn, $invoiceId, $productId, $quantity);
			if (!($addResponse["success"] ?? false)) {
				pushFlashMessage("warn", (string)($addResponse["error"] ?? "Không thể thêm món."));
				redirectToPage("orders", $tableId);
			}

			pushFlashMessage("good", "Thêm món vào hóa đơn thành công.");
			redirectToPage("orders", $tableId);
			break;

		case "checkout":
			if ($tableId > 0 && $invoiceId <= 0) {
				$openInvoice = getOpenInvoiceByTable($conn, $tableId);
				if ($openInvoice !== null) {
					$invoiceId = (int)($openInvoice["MaHoaDon"] ?? 0);
				}
			}

			if ($invoiceId <= 0) {
				pushFlashMessage("warn", "Không thể thanh toán vì chưa có hóa đơn mở.");
				redirectToPage("orders", $tableId);
			}

			$calculated = calculateTotal($conn, $invoiceId);
			if (!($calculated["success"] ?? false)) {
				pushFlashMessage("warn", (string)($calculated["error"] ?? "Không thể tính tổng tiền."));
				redirectToPage("orders", $tableId);
			}

			$checkoutResponse = checkoutInvoice($conn, $invoiceId, $calculated);
			if (!($checkoutResponse["success"] ?? false)) {
				pushFlashMessage("warn", (string)($checkoutResponse["error"] ?? "Thanh toán thất bại."));
				redirectToPage("orders", $tableId);
			}

			$paidTotal = formatMoney((float)($calculated["total"] ?? 0));
			pushFlashMessage("good", "Thanh toán thành công: " . $paidTotal . " VND.");
			redirectToPage("orders", readPositiveInt($calculated["invoice"]["MaBan"] ?? 0));
			break;

		default:
			pushFlashMessage("warn", "Hành động không hợp lệ.");
			redirectToPage($redirectPage, $tableId);
	}
}

$dbHelper = new DatabaseHelper($conn);
$statsResponse = $dbHelper->getStats();
$stats = ($statsResponse["success"] ?? false) ? ($statsResponse["data"] ?? []) : [];

$totalBan = (int)($stats["totalBan"] ?? 0);
$banDangSuDung = (int)($stats["banDangSuDung"] ?? 0);
$doanhThuNgay = (float)($stats["doanhThuNgay"] ?? 0);
$hoaDonNgay = (int)($stats["hoaDonNgay"] ?? 0);
$totalKhachHang = (int)($stats["totalKhachHang"] ?? 0);

$openOrdersRow = fetchOneSafe($conn, "SELECT COUNT(*) AS total FROM HoaDon WHERE TrangThai = 0");
$openOrders = (int)($openOrdersRow["total"] ?? 0);

$kpiCards = [
	["label" => "Bàn đang sử dụng", "value" => $banDangSuDung . "/" . $totalBan, "change" => "Số bàn hoạt động", "tone" => $banDangSuDung > 0 ? "good" : "mute", "icon" => "ri-billiards-line"],
	["label" => "Doanh thu hôm nay", "value" => formatMoney($doanhThuNgay) . " VND", "change" => $hoaDonNgay . " hóa đơn hoàn tất", "tone" => $doanhThuNgay > 0 ? "good" : "mute", "icon" => "ri-money-dollar-circle-line"],
	["label" => "Hóa đơn mở", "value" => (string)$openOrders, "change" => "Hóa đơn đang phục vụ", "tone" => $openOrders > 0 ? "warn" : "good", "icon" => "ri-shopping-bag-3-line"],
	["label" => "Khách hàng", "value" => (string)$totalKhachHang, "change" => "Tổng khách hàng", "tone" => "good", "icon" => "ri-user-add-line"],
];

$revenueRows = fetchAllSafe(
	$conn,
	"SELECT DATE(NgayLap) AS date_key, SUM(TongTien) AS revenue
	 FROM HoaDon
	 WHERE TrangThai = 1 AND DATE(NgayLap) BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
	 GROUP BY DATE(NgayLap)"
);

$revenueMap = [];
foreach ($revenueRows as $row) {
	$revenueMap[$row["date_key"]] = (float)$row["revenue"];
}

$revenueSeries = [];
for ($i = 6; $i >= 0; $i--) {
	$date = date("Y-m-d", strtotime("-{$i} days"));
	$revenueSeries[] = [
		"day" => date("D", strtotime($date)),
		"value" => round(($revenueMap[$date] ?? 0) / 1000000, 2),
	];
}

$tableUsageRows = fetchAllSafe(
	$conn,
	"SELECT TenBan, TrangThai
	 FROM Ban
	 ORDER BY MaBan ASC
	 LIMIT 5"
);

$tableUsage = [];
foreach ($tableUsageRows as $row) {
	$status = tableUiStatus((string)$row["TrangThai"]);
	$tableUsage[] = [
		"name" => (string)$row["TenBan"],
		"percent" => $status === "available" ? 0 : 100,
	];
}

$recentActivities = [];
$activityRows = fetchAllSafe(
	$conn,
	"SELECT hd.MaHoaDon, hd.NgayLap, hd.TrangThai, b.TenBan, nv.HoTen AS TenNhanVien
	 FROM HoaDon hd
	 LEFT JOIN Ban b ON hd.MaBan = b.MaBan
	 LEFT JOIN NhanVien nv ON hd.MaNhanVien = nv.MaNhanVien
	 ORDER BY hd.MaHoaDon DESC
	 LIMIT 4"
);

foreach ($activityRows as $row) {
	$isPaid = (int)$row["TrangThai"] === 1;
	$recentActivities[] = [
		"icon" => $isPaid ? "ri-shopping-cart-2-line" : "ri-billiards-line",
		"title" => $isPaid
			? "Hóa đơn #" . $row["MaHoaDon"] . " đã thanh toán"
			: "Bàn " . ($row["TenBan"] ?? "Không rõ") . " đang phục vụ",
		"meta" => "Nhân viên: " . ($row["TenNhanVien"] ?? "Không rõ") . " • " . date("d/m H:i", strtotime((string)$row["NgayLap"])),
		"state" => $isPaid ? "paid" : "active",
	];
}

$lowStockRows = fetchAllSafe(
	$conn,
	"SELECT TenNguyenLieu, SoLuongTon
	 FROM NguyenLieu
	 WHERE SoLuongTon < 10
	 ORDER BY SoLuongTon ASC
	 LIMIT 1"
);

if (!empty($lowStockRows)) {
	$recentActivities[] = [
		"icon" => "ri-alert-line",
		"title" => "Tồn kho thấp: " . $lowStockRows[0]["TenNguyenLieu"],
		"meta" => "Số lượng hiện tại: " . $lowStockRows[0]["SoLuongTon"],
		"state" => "warn",
	];
}

$tableOptionController = new BanController($conn);
$tableTypeResponse = $tableOptionController->getLoaiBan();
$tableAreaResponse = $tableOptionController->getKhuVuc();

$tableTypeOptions = ($tableTypeResponse["success"] ?? false) && !empty($tableTypeResponse["data"])
	? $tableTypeResponse["data"]
	: [];

$tableAreaOptions = ($tableAreaResponse["success"] ?? false) && !empty($tableAreaResponse["data"])
	? $tableAreaResponse["data"]
	: [];

$dvtOptions = fetchAllSafe(
	$conn,
	"SELECT MaDVT, TenDVT FROM DonViTinhs ORDER BY MaDVT ASC"
);

$supplierOptions = fetchAllSafe(
	$conn,
	"SELECT MaNhaCungCap, TenNhaCungCap FROM NhaCungCap WHERE TrangThai = 1 ORDER BY TenNhaCungCap ASC"
);

$roleOptions = fetchAllSafe(
	$conn,
	"SELECT MaChucVu, TenChucVu FROM ChucVu ORDER BY MaChucVu ASC"
);

$tablesRows = fetchAllSafe(
	$conn,
	"SELECT b.MaBan, b.TenBan, b.TrangThai, b.GiaGio, b.MaLoaiBan, b.MaKhuVuc, lb.TenLoai,
	        hd.MaHoaDon AS OpenInvoiceId,
	        hd.NgayLap AS OpenedAt,
	        kh.HoTen AS CustomerName
	 FROM Ban b
	 LEFT JOIN LoaiBan lb ON b.MaLoaiBan = lb.MaLoaiBan
	 LEFT JOIN HoaDon hd ON hd.MaHoaDon = (
			SELECT h2.MaHoaDon
			FROM HoaDon h2
			WHERE h2.MaBan = b.MaBan AND h2.TrangThai = 0
			ORDER BY h2.MaHoaDon DESC
			LIMIT 1
	 )
	 LEFT JOIN KhachHang kh ON kh.MaKhachHang = hd.MaKhachHang
	 ORDER BY b.MaBan ASC"
);

$tables = [];
$tablesById = [];
foreach ($tablesRows as $row) {
	$status = tableUiStatus((string)$row["TrangThai"]);
	$timeLabel = "Chưa mở phiên";
	if (!empty($row["OpenedAt"])) {
		$seconds = max(0, time() - strtotime((string)$row["OpenedAt"]));
		$timeLabel = gmdate("H:i:s", $seconds);
	}

	$tableItem = [
		"id" => (int)$row["MaBan"],
		"name" => (string)$row["TenBan"],
		"type" => (string)($row["TenLoai"] ?? "Không rõ"),
		"typeId" => (int)$row["MaLoaiBan"],
		"areaId" => readNullablePositiveInt($row["MaKhuVuc"] ?? null),
		"rawStatus" => (string)$row["TrangThai"],
		"status" => $status,
		"currentBillId" => readPositiveInt($row["OpenInvoiceId"] ?? 0),
		"openInvoiceId" => readPositiveInt($row["OpenInvoiceId"] ?? 0),
		"openedAt" => !empty($row["OpenedAt"]) ? (string)$row["OpenedAt"] : null,
		"player" => !empty($row["CustomerName"]) ? (string)$row["CustomerName"] : ($status === "available" ? "Sẵn sàng" : "Đang phục vụ"),
		"time" => $timeLabel,
		"rateValue" => (float)$row["GiaGio"],
		"rate" => formatMoney((float)$row["GiaGio"]) . " VND/h",
	];

	$tables[] = $tableItem;
	$tablesById[$tableItem["id"]] = $tableItem;
}

if ($selectedTableId > 0 && !isset($tablesById[$selectedTableId])) {
	$selectedTableId = 0;
}

if ($selectedTableId === 0 && !empty($tables)) {
	$selectedTableId = (int)$tables[0]["id"];
}

$selectedTable = $tablesById[$selectedTableId] ?? null;

$defaultTableTypeId = readPositiveInt($tableTypeOptions[0]["MaLoaiBan"] ?? 0);
$defaultTableAreaId = readNullablePositiveInt($tableAreaOptions[0]["MaKhuVuc"] ?? null);

$openBill = null;
if ($selectedTableId > 0) {
	$openBill = fetchOneSafe(
		$conn,
		"SELECT hd.MaHoaDon, hd.NgayLap, hd.MaBan, hd.TongTien, b.TenBan, b.GiaGio, kh.HoTen AS TenKhachHang
		 FROM HoaDon hd
		 LEFT JOIN Ban b ON hd.MaBan = b.MaBan
		 LEFT JOIN KhachHang kh ON hd.MaKhachHang = kh.MaKhachHang
		 WHERE hd.TrangThai = 0 AND hd.MaBan = ?
		 ORDER BY hd.MaHoaDon DESC
		 LIMIT 1",
		[$selectedTableId]
	);
}

$openOrder = [];
$orderServiceTotal = 0.0;
$orderTimeCharge = 0.0;
$orderTotal = 0.0;
$openOrderTitle = $selectedTable !== null
	? "Hóa đơn hiện tại - " . $selectedTable["name"]
	: "Hóa đơn hiện tại";
$openOrderSubtitle = $selectedTable !== null
	? "Chưa có hóa đơn đang phục vụ cho bàn này"
	: "Không có hóa đơn đang phục vụ";

if ($openBill !== null) {
	$openOrderTitle = "Hóa đơn hiện tại - " . ($openBill["TenBan"] ?? "Không rõ");
	$elapsedSeconds = max(0, time() - strtotime((string)$openBill["NgayLap"]));
	$openOrderSubtitle = "Khách: " . ($openBill["TenKhachHang"] ?? "Khách lẻ") . " • Phiên " . gmdate("H:i:s", $elapsedSeconds);
	$orderTimeCharge = round(((float)$openBill["GiaGio"]) * ($elapsedSeconds / 3600), 2);

	$orderRows = fetchAllSafe(
		$conn,
		"SELECT m.TenMon, cthd.SoLuong, cthd.DonGia
		 FROM ChiTietHoaDon cthd
		 LEFT JOIN Mon m ON cthd.MaMon = m.MaMon
		 WHERE cthd.MaHoaDon = ?",
		[(int)$openBill["MaHoaDon"]]
	);

	foreach ($orderRows as $row) {
		$lineTotal = ((float)$row["SoLuong"]) * ((float)$row["DonGia"]);
		$orderServiceTotal += $lineTotal;
		$openOrder[] = [
			"item" => (string)($row["TenMon"] ?? "Không rõ"),
			"qty" => (string)$row["SoLuong"],
			"price" => formatMoney($lineTotal),
		];
	}
}

$orderTotal = $orderServiceTotal + $orderTimeCharge;

$menuRows = fetchAllSafe(
	$conn,
	"SELECT m.MaMon, m.TenMon, m.GiaBan, dm.TenDanhMuc
	 FROM Mon m
	 LEFT JOIN DanhMucMon dm ON m.MaDanhMuc = dm.MaDanhMuc
	 WHERE m.TrangThai = 1
	 ORDER BY m.MaMon DESC
	 LIMIT 12"
);

$menuItems = [];
foreach ($menuRows as $row) {
	$category = (string)($row["TenDanhMuc"] ?? "Khác");
	$menuItems[] = [
		"id" => (int)$row["MaMon"],
		"name" => (string)$row["TenMon"],
		"category" => $category,
		"priceValue" => (float)$row["GiaBan"],
		"price" => formatMoney((float)$row["GiaBan"]),
		"icon" => menuIconByCategory($category),
	];
}

$customersRows = fetchAllSafe(
	$conn,
	"SELECT kh.MaKhachHang, kh.HoTen, kh.SDT, kh.Hang, kh.DiemTichLuy, kh.IsStaff,
	        COUNT(hd.MaHoaDon) AS visits,
	        IFNULL(SUM(CASE WHEN hd.TrangThai = 1 THEN hd.TongTien ELSE 0 END), 0) AS spent
	 FROM KhachHang kh
	 LEFT JOIN HoaDon hd ON hd.MaKhachHang = kh.MaKhachHang
	 GROUP BY kh.MaKhachHang, kh.HoTen, kh.SDT, kh.Hang, kh.DiemTichLuy, kh.IsStaff
	 ORDER BY spent DESC
	 LIMIT 20"
);

$customers = [];
$customersById = [];
foreach ($customersRows as $row) {
	$customerItem = [
		"id" => (int)$row["MaKhachHang"],
		"name" => (string)$row["HoTen"],
		"phone" => (string)($row["SDT"] ?? ""),
		"points" => (int)$row["DiemTichLuy"],
		"isStaff" => (int)($row["IsStaff"] ?? 0) === 1,
		"visits" => (int)$row["visits"],
		"spent" => formatMoney((float)$row["spent"]),
		"tier" => (string)($row["Hang"] ?? "Mới"),
	];

	$customers[] = $customerItem;
	$customersById[$customerItem["id"]] = $customerItem;
}

if ($customerEditId > 0 && !isset($customersById[$customerEditId])) {
	$customerEditId = 0;
}
$customerEditItem = $customerEditId > 0 ? $customersById[$customerEditId] : null;

$inventoryRows = fetchAllSafe(
	$conn,
	"SELECT nl.MaNguyenLieu, nl.TenNguyenLieu, nl.MaDVT, dvt.TenDVT, nl.SoLuongTon, nl.MaNhaCungCap, nl.GiaVon, ncc.TenNhaCungCap
	 FROM NguyenLieu nl
	 LEFT JOIN DonViTinhs dvt ON nl.MaDVT = dvt.MaDVT
	 LEFT JOIN NhaCungCap ncc ON nl.MaNhaCungCap = ncc.MaNhaCungCap
	 ORDER BY nl.TenNguyenLieu ASC"
);

$inventory = [];
$inventoryById = [];
foreach ($inventoryRows as $row) {
	$inventoryItem = [
		"id" => (int)$row["MaNguyenLieu"],
		"name" => (string)$row["TenNguyenLieu"],
		"category" => (string)($row["TenDVT"] ?? "Không rõ"),
		"dvtId" => (int)$row["MaDVT"],
		"qty" => (float)$row["SoLuongTon"],
		"min" => 10,
		"supplierId" => (string)$row["MaNhaCungCap"],
		"supplier" => (string)($row["TenNhaCungCap"] ?? "Không rõ"),
		"cost" => (float)($row["GiaVon"] ?? 0),
	];

	$inventory[] = $inventoryItem;
	$inventoryById[$inventoryItem["id"]] = $inventoryItem;
}

if ($inventoryEditId > 0 && !isset($inventoryById[$inventoryEditId])) {
	$inventoryEditId = 0;
}
$inventoryEditItem = $inventoryEditId > 0 ? $inventoryById[$inventoryEditId] : null;

$defaultDvtId = readPositiveInt($dvtOptions[0]["MaDVT"] ?? 0);
$defaultSupplierId = readTextValue($supplierOptions[0]["MaNhaCungCap"] ?? "", 20);

$staffRows = fetchAllSafe(
	$conn,
	"SELECT nv.MaNhanVien, nv.HoTen, nv.SDT, nv.DiaChi, nv.MaChucVu, cv.TenChucVu,
	        COUNT(hd.MaHoaDon) AS billsToday
	 FROM NhanVien nv
	 LEFT JOIN ChucVu cv ON nv.MaChucVu = cv.MaChucVu
	 LEFT JOIN HoaDon hd ON hd.MaNhanVien = nv.MaNhanVien AND DATE(hd.NgayLap) = CURDATE()
	 GROUP BY nv.MaNhanVien, nv.HoTen, nv.SDT, nv.DiaChi, nv.MaChucVu, cv.TenChucVu
	 ORDER BY nv.MaNhanVien ASC"
);

$staff = [];
$staffById = [];
foreach ($staffRows as $row) {
	$status = (int)$row["billsToday"] > 0 ? "Đang làm" : "Nghỉ";
	$staffItem = [
		"id" => (int)$row["MaNhanVien"],
		"name" => (string)$row["HoTen"],
		"role" => (string)($row["TenChucVu"] ?? "Nhân viên"),
		"roleId" => (int)$row["MaChucVu"],
		"phone" => (string)($row["SDT"] ?? "-"),
		"address" => (string)($row["DiaChi"] ?? "-"),
		"status" => $status,
		"billsToday" => (int)$row["billsToday"],
	];

	$staff[] = $staffItem;
	$staffById[$staffItem["id"]] = $staffItem;
}

if ($staffEditId > 0 && !isset($staffById[$staffEditId])) {
	$staffEditId = 0;
}
$staffEditItem = $staffEditId > 0 ? $staffById[$staffEditId] : null;

$defaultRoleId = readPositiveInt($roleOptions[0]["MaChucVu"] ?? 0);

$reportRows = fetchAllSafe(
	$conn,
	"SELECT DATE_FORMAT(NgayLap, '%Y-%m') AS month_key,
	        SUM(TongTien) AS revenue
	 FROM HoaDon
	 WHERE TrangThai = 1
	   AND DATE(NgayLap) >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
	 GROUP BY DATE_FORMAT(NgayLap, '%Y-%m')"
);

$reportMap = [];
foreach ($reportRows as $row) {
	$reportMap[$row["month_key"]] = (float)$row["revenue"];
}

$reportMonths = [];
$reportRevenue = [];
for ($i = 5; $i >= 0; $i--) {
	$key = date("Y-m", strtotime("first day of -{$i} month"));
	$reportMonths[] = "T" . date("n", strtotime($key . "-01"));
	$reportRevenue[] = round(($reportMap[$key] ?? 0) / 1000000, 2);
}

$topServices = [];
$topServicesData = $dbHelper->getTopServices(4);
if (($topServicesData["success"] ?? false) && !empty($topServicesData["data"])) {
	$totalQty = 0.0;
	foreach ($topServicesData["data"] as $service) {
		$totalQty += (float)$service["TongSoLuong"];
	}
	$totalQty = max(1, $totalQty);

	foreach ($topServicesData["data"] as $service) {
		$percent = (int)round(((float)$service["TongSoLuong"] / $totalQty) * 100);
		$topServices[] = [
			"name" => (string)$service["TenMon"],
			"value" => $percent,
		];
	}
}

$settingsRows = fetchAllSafe($conn, "SELECT `Key`, `Value` FROM SystemSetting");
$settingsMap = [];
foreach ($settingsRows as $row) {
	$settingsMap[(string)$row["Key"]] = (string)$row["Value"];
}

$clubName = $settingsMap["club_name"] ?? "DOAN_WEB Billiards";
$clubAddress = $settingsMap["club_address"] ?? "";
$clubPhone = $settingsMap["club_phone"] ?? "";
$clubEmail = $settingsMap["club_email"] ?? "";
$flashMessages = pullFlashMessages();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo h($pageTitle); ?> | DOAN_WEB</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css">
	<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
	<div class="bg-aura bg-aura-one"></div>
	<div class="bg-aura bg-aura-two"></div>

	<div class="app-shell" id="appShell">
		<?php include __DIR__ . "/includes/sidebar.php"; ?>

		<div class="app-main">
			<?php include __DIR__ . "/includes/header.php"; ?>

			<main class="page-content">
				<?php if (!empty($flashMessages)): ?>
					<section class="notice-stack stagger-fade">
						<?php foreach ($flashMessages as $message): ?>
							<?php
							$tone = (string)($message["tone"] ?? "mute");
							if ($tone !== "good" && $tone !== "warn") {
								$tone = "mute";
							}
							?>
							<div class="notice-banner notice-<?php echo h($tone); ?>">
								<?php echo h((string)($message["text"] ?? "")); ?>
							</div>
						<?php endforeach; ?>
					</section>
				<?php endif; ?>

				<?php if ($currentPage === "dashboard"): ?>
					<section class="metric-grid stagger-fade">
						<?php foreach ($kpiCards as $card): ?>
							<article class="metric-card">
								<div class="metric-head">
									<p><?php echo h($card["label"]); ?></p>
									<span class="metric-icon"><i class="<?php echo h($card["icon"]); ?>"></i></span>
								</div>
								<h3><?php echo h($card["value"]); ?></h3>
								<div class="chip chip-<?php echo h($card["tone"]); ?>"><?php echo h($card["change"]); ?></div>
							</article>
						<?php endforeach; ?>
					</section>

					<section class="dashboard-grid stagger-fade">
						<article class="panel panel-large">
							<div class="panel-header">
								<div>
									<h3>Xu hướng doanh thu (Triệu VND)</h3>
									<p>Hiệu suất doanh thu trong tuần hiện tại</p>
								</div>
								<button class="ghost-btn">Xuất báo cáo</button>
							</div>
							<div class="bar-chart">
								<?php
								$maxRevenue = max(1.0, (float)max(array_column($revenueSeries, "value")));
								foreach ($revenueSeries as $point):
									$height = (int) round(($point["value"] / $maxRevenue) * 100);
								?>
									<div class="bar-item">
										<div class="bar-fill" style="height: <?php echo $height; ?>%;"></div>
										<span><?php echo h($point["day"]); ?></span>
									</div>
								<?php endforeach; ?>
							</div>
						</article>

						<article class="panel">
							<div class="panel-header">
								<div>
									<h3>Tỷ lệ sử dụng bàn</h3>
									<p>Tỷ lệ hoạt động theo thời gian thực</p>
								</div>
							</div>
							<div class="usage-list">
								<?php foreach ($tableUsage as $usage): ?>
									<div class="usage-item">
										<div class="usage-meta">
											<span><?php echo h($usage["name"]); ?></span>
											<strong><?php echo h((string) $usage["percent"]); ?>%</strong>
										</div>
										<div class="usage-track">
											<div class="usage-progress" style="width: <?php echo (int) $usage["percent"]; ?>%;"></div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</article>

						<article class="panel panel-wide">
							<div class="panel-header">
								<div>
									<h3>Hoạt động gần đây</h3>
									<p>Cập nhật mới nhất từ quầy và khu vực bàn</p>
								</div>
							</div>
							<div class="activity-list">
								<?php foreach ($recentActivities as $activity): ?>
									<div class="activity-item">
										<span class="activity-icon"><i class="<?php echo h($activity["icon"]); ?>"></i></span>
										<div class="activity-body">
											<p><?php echo h($activity["title"]); ?></p>
											<small><?php echo h($activity["meta"]); ?></small>
										</div>
										<span class="chip chip-<?php echo h($activity["state"]); ?>"><?php echo h(activityStateLabel($activity["state"])); ?></span>
									</div>
								<?php endforeach; ?>
							</div>
						</article>
					</section>
				<?php elseif ($currentPage === "tables"): ?>
					<section class="panel stagger-fade">
						<?php
						$tableCreateTypeId = $defaultTableTypeId;
						$tableCreateAreaId = $defaultTableAreaId;
						?>
						<div class="panel-header table-panel-header">
							<div>
								<h3>Sơ đồ bàn thời gian thực</h3>
								<p>Tập trung quản lý trạng thái bàn theo thời gian thực</p>
							</div>
							<div class="table-toolbar-actions">
								<button type="button" class="ghost-btn thin" onclick="openPopup('table')"><i class="ri-add-line"></i>Thêm bàn</button>
								<button type="button" class="ghost-btn thin" onclick="openPopup('type')"><i class="ri-price-tag-3-line"></i>Thêm loại bàn</button>
								<button type="button" class="ghost-btn thin" onclick="openPopup('area')"><i class="ri-map-pin-line"></i>Thêm khu vực</button>
							</div>
						</div>

						<div class="table-filter-row">
							<div class="filter-pills" id="tableFilters">
								<button class="pill is-active" data-filter="all">Tất cả</button>
								<button class="pill" data-filter="playing">Đang chơi</button>
								<button class="pill" data-filter="reserved">Đặt trước</button>
								<button class="pill" data-filter="available">Trống</button>
							</div>
						</div>

						<div class="table-card-grid" id="tableCardGrid">
							<?php foreach ($tables as $table): ?>
								<?php $isSelectedTable = (int)$table["id"] === $selectedTableId; ?>
								<article
									class="table-card <?php echo $isSelectedTable ? "is-selected" : ""; ?>"
									data-status="<?php echo h($table["status"]); ?>"
									data-table-id="<?php echo (int)$table["id"]; ?>"
									data-table-name="<?php echo h($table["name"]); ?>"
									data-table-rate="<?php echo h((string)$table["rateValue"]); ?>"
									data-current-bill-id="<?php echo (int)$table["currentBillId"]; ?>"
									data-opened-at="<?php echo h((string)($table["openedAt"] ?? "")); ?>"
								>
									<div class="table-top">
										<h4><?php echo h($table["name"]); ?></h4>
											<span class="chip chip-<?php echo h($table["status"]); ?>" data-role="table-status-label"><?php echo h(tableStatusLabel($table["status"])); ?></span>
									</div>
									<p class="table-type"><?php echo h($table["type"]); ?> • <?php echo h($table["rate"]); ?></p>
									<div class="table-center"><i class="ri-layout-grid-line"></i></div>
									<div class="table-bottom">
											<strong data-role="table-player"><?php echo h($table["player"]); ?></strong>
											<small data-role="table-elapsed"><?php echo h($table["time"]); ?></small>
									</div>
									<div class="table-actions">
										<a class="ghost-btn thin full" href="index.php?page=orders&amp;action=select_table&amp;table_id=<?php echo (int)$table["id"]; ?>">Chọn bàn</a>

										<?php if ($table["status"] === "available"): ?>
											<form method="post" class="inline-form">
												<?php echo csrf_field(); ?>
												<input type="hidden" name="action" value="start_table">
												<input type="hidden" name="table_id" value="<?php echo (int)$table["id"]; ?>">
												<input type="hidden" name="redirect_page" value="tables">
												<button type="submit" class="solid-btn thin full">Mở bàn</button>
											</form>
										<?php else: ?>
											<form method="post" class="inline-form">
												<?php echo csrf_field(); ?>
												<input type="hidden" name="action" value="end_table">
												<input type="hidden" name="table_id" value="<?php echo (int)$table["id"]; ?>">
												<input type="hidden" name="redirect_page" value="tables">
												<button type="submit" class="ghost-btn thin full" <?php echo (int)$table["openInvoiceId"] > 0 ? "" : "disabled"; ?>>Kết thúc bàn</button>
											</form>
										<?php endif; ?>

										<div class="table-inline-actions">
											<button
												type="button"
												class="ghost-btn thin full"
												onclick="openEditTablePopup(this)"
												data-table-id="<?php echo (int)$table["id"]; ?>"
												data-table-name="<?php echo h($table["name"]); ?>"
												data-table-type-id="<?php echo (int)$table["typeId"]; ?>"
												data-table-area-id="<?php echo $table["areaId"] !== null ? (int)$table["areaId"] : ""; ?>"
												data-table-status="<?php echo h(normalizeTableStatusInput((string)$table["rawStatus"])); ?>"
											>
												Sửa
											</button>
										</div>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					</section>

					<div id="overlay" class="overlay hidden" aria-hidden="true">
						<div id="popupTable" class="popup hidden" role="dialog" aria-modal="true" aria-labelledby="popupTableTitle">
							<div class="popup-titlebar">
								<h4 id="popupTableTitle">Thêm bàn</h4>
								<button type="button" class="popup-close" onclick="closePopup()" aria-label="Đóng popup">&times;</button>
							</div>
							<p class="popup-subtitle" id="popupTableSubtitle">Nhập thông tin để tạo bàn mới.</p>
							<form method="post" class="crud-form popup-body" data-popup-form="table" id="popupTableForm">
								<?php echo csrf_field(); ?>
								<input type="hidden" name="action" value="table_create" id="popupTableAction">
								<input type="hidden" name="redirect_page" value="tables">
								<input type="hidden" name="table_id" value="" id="popupTableId">

								<label class="crud-field">Tên bàn
									<input type="text" name="table_name" id="popupTableName" required>
								</label>

								<label class="crud-field">Loại bàn
									<select name="table_type_id" id="popupTableTypeId" required>
										<option value="">Chọn loại bàn</option>
										<?php foreach ($tableTypeOptions as $typeOption): ?>
											<?php $typeOptionId = (int)($typeOption["MaLoaiBan"] ?? 0); ?>
											<?php $typeBasePrice = (float)($typeOption["GiaCoBan"] ?? $typeOption["PhuThu"] ?? 0); ?>
											<option value="<?php echo $typeOptionId; ?>" data-base-price="<?php echo h((string)$typeBasePrice); ?>" <?php echo $typeOptionId === $tableCreateTypeId ? "selected" : ""; ?>>
												<?php echo h((string)($typeOption["TenLoai"] ?? "")); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</label>

								<label class="crud-field">Khu vực
									<select name="table_area_id" id="popupTableAreaId" required>
										<option value="">Chọn khu vực</option>
										<?php foreach ($tableAreaOptions as $areaOption): ?>
											<?php $areaOptionId = (int)($areaOption["MaKhuVuc"] ?? 0); ?>
											<?php $areaExtraPrice = (float)($areaOption["ExtraPrice"] ?? $areaOption["PhuThu"] ?? 0); ?>
											<option value="<?php echo $areaOptionId; ?>" data-extra-price="<?php echo h((string)$areaExtraPrice); ?>" <?php echo $areaOptionId === (int)$tableCreateAreaId ? "selected" : ""; ?>>
												<?php echo h((string)($areaOption["TenKhuVuc"] ?? "")); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</label>

								<label class="crud-field">Giá bàn tự động (VND/giờ)
									<input type="text" id="popupTableCalculatedPrice" value="" readonly>
									<small>Giá = Giá loại bàn + Phụ thu khu vực</small>
								</label>

								<label class="crud-field">Trạng thái
									<select name="table_status" id="popupTableStatus" required>
										<option value="Trống" selected>Trống</option>
										<option value="Đang sử dụng">Đang sử dụng</option>
										<option value="Đặt trước">Đặt trước</option>
									</select>
								</label>

								<div class="crud-actions">
									<button type="submit" class="solid-btn thin" id="popupTableSubmit">Lưu bàn</button>
									<button type="button" class="danger-btn thin hidden" id="popupTableDeleteBtn" onclick="confirmDeleteTableFromPopup()">Xóa bàn</button>
									<button type="button" class="ghost-btn thin" onclick="closePopup()">Đóng</button>
								</div>
							</form>
							<form method="post" class="hidden" id="popupTableDeleteForm">
								<?php echo csrf_field(); ?>
								<input type="hidden" name="action" value="table_delete">
								<input type="hidden" name="redirect_page" value="tables">
								<input type="hidden" name="table_id" value="" id="popupTableDeleteId">
							</form>
						</div>

						<div id="popupTableType" class="popup hidden" role="dialog" aria-modal="true" aria-labelledby="popupTableTypeTitle">
							<div class="popup-titlebar">
								<h4 id="popupTableTypeTitle">Thêm loại bàn</h4>
								<button type="button" class="popup-close" onclick="closePopup()" aria-label="Đóng popup">&times;</button>
							</div>
							<form method="post" class="crud-form popup-body" data-popup-form="type" id="popupTableTypeForm">
								<?php echo csrf_field(); ?>
								<input type="hidden" name="action" value="table_type_create">
								<input type="hidden" name="redirect_page" value="tables">

								<label class="crud-field">Tên loại bàn
									<input type="text" name="table_type_name" id="popupTableTypeName" maxlength="100" required>
								</label>

								<label class="crud-field">Giá cơ bản / giờ (VND)
									<input type="number" name="table_type_base_price" id="popupTableTypeBasePrice" min="0" step="1000" value="0" required>
								</label>

								<div class="crud-actions">
									<button type="submit" class="solid-btn thin" id="popupTableTypeSubmit">Lưu loại bàn</button>
									<button type="button" class="ghost-btn thin hidden" id="popupTableTypeCancelEdit">Hủy sửa</button>
									<button type="button" class="ghost-btn thin" onclick="closePopup()">Đóng</button>
								</div>
							</form>
							<div class="popup-entity-panel">
								<div class="popup-entity-header">
									<h5>Danh sách loại bàn</h5>
									<small>Quản lý nhanh ngay trong popup</small>
								</div>
								<div id="popupTableTypeList" class="popup-entity-list" aria-live="polite"></div>
							</div>
						</div>

						<div id="popupArea" class="popup hidden" role="dialog" aria-modal="true" aria-labelledby="popupAreaTitle">
							<div class="popup-titlebar">
								<h4 id="popupAreaTitle">Thêm khu vực</h4>
								<button type="button" class="popup-close" onclick="closePopup()" aria-label="Đóng popup">&times;</button>
							</div>
							<form method="post" class="crud-form popup-body" data-popup-form="area" id="popupAreaForm">
								<?php echo csrf_field(); ?>
								<input type="hidden" name="action" value="table_area_create">
								<input type="hidden" name="redirect_page" value="tables">

								<label class="crud-field">Tên khu vực
									<input type="text" name="table_area_name" id="popupAreaName" maxlength="100" required>
								</label>

								<label class="crud-field">Phụ thu khu vực (VND)
									<input type="number" name="table_area_extra_price" id="popupAreaExtraPrice" min="0" step="1000" value="0" required>
								</label>

								<div class="crud-actions">
									<button type="submit" class="solid-btn thin" id="popupAreaSubmit">Lưu khu vực</button>
									<button type="button" class="ghost-btn thin hidden" id="popupAreaCancelEdit">Hủy sửa</button>
									<button type="button" class="ghost-btn thin" onclick="closePopup()">Đóng</button>
								</div>
							</form>
							<div class="popup-entity-panel">
								<div class="popup-entity-header">
									<h5>Danh sách khu vực</h5>
									<small>Có thể sửa hoặc xóa trực tiếp</small>
								</div>
								<div id="popupAreaList" class="popup-entity-list" aria-live="polite"></div>
							</div>
						</div>
					</div>
				<?php elseif ($currentPage === "orders"): ?>
					<section class="split-grid stagger-fade">
						<article class="panel">
							<div class="panel-header">
								<div>
									<h3><?php echo h($openOrderTitle); ?></h3>
									<p><?php echo h($openOrderSubtitle); ?></p>
								</div>
								<form method="get" class="inline-form order-picker-form">
									<input type="hidden" name="page" value="orders">
									<input type="hidden" name="action" value="select_table">
									<select name="table_id" class="order-picker" required>
										<?php foreach ($tables as $tableOption): ?>
											<option value="<?php echo (int)$tableOption["id"]; ?>" <?php echo (int)$tableOption["id"] === $selectedTableId ? "selected" : ""; ?>>
												<?php echo h($tableOption["name"]); ?> (<?php echo h(tableStatusLabel($tableOption["status"])); ?>)
											</option>
										<?php endforeach; ?>
									</select>
									<button type="submit" class="ghost-btn thin">Xem bàn</button>
								</form>
							</div>

							<?php if ($openBill === null && $selectedTable !== null && $selectedTable["status"] === "available"): ?>
								<form method="post" class="inline-form inline-note-form">
									<?php echo csrf_field(); ?>
									<input type="hidden" name="action" value="start_table">
									<input type="hidden" name="table_id" value="<?php echo (int)$selectedTable["id"]; ?>">
									<input type="hidden" name="redirect_page" value="orders">
									<button type="submit" class="solid-btn thin">Mở bàn ngay để bắt đầu gọi món</button>
								</form>
							<?php endif; ?>

							<table class="data-table">
								<thead>
									<tr>
										<th>Món</th>
										<th>SL</th>
										<th>Thành tiền (VND)</th>
									</tr>
								</thead>
								<tbody>
									<?php if (empty($openOrder)): ?>
										<tr>
											<td colspan="3">Chưa có chi tiết hóa đơn.</td>
										</tr>
									<?php else: ?>
										<?php foreach ($openOrder as $line): ?>
											<tr>
												<td><?php echo h($line["item"]); ?></td>
												<td><?php echo h($line["qty"]); ?></td>
												<td><?php echo h($line["price"]); ?></td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
							<div class="bill-total">
								<div><span>Tiền dịch vụ</span><strong><?php echo h(formatMoney($orderServiceTotal)); ?></strong></div>
								<div><span>Tiền giờ chơi</span><strong><?php echo h(formatMoney($orderTimeCharge)); ?></strong></div>
								<div class="bill-grand"><span>Tổng cộng</span><strong><?php echo h(formatMoney($orderTotal)); ?></strong></div>
							</div>

							<?php if ($openBill !== null): ?>
								<div class="action-row">
									<form method="post" class="inline-form">
										<?php echo csrf_field(); ?>
										<input type="hidden" name="action" value="checkout">
										<input type="hidden" name="table_id" value="<?php echo (int)$selectedTableId; ?>">
										<input type="hidden" name="invoice_id" value="<?php echo (int)$openBill["MaHoaDon"]; ?>">
										<input type="hidden" name="redirect_page" value="orders">
										<button type="submit" class="solid-btn">Thanh toán</button>
									</form>

									<form method="post" class="inline-form">
										<?php echo csrf_field(); ?>
										<input type="hidden" name="action" value="end_table">
										<input type="hidden" name="table_id" value="<?php echo (int)$selectedTableId; ?>">
										<input type="hidden" name="redirect_page" value="orders">
										<button type="submit" class="ghost-btn">Kết thúc bàn</button>
									</form>
								</div>
							<?php else: ?>
								<p class="inline-note">Bàn chưa có hóa đơn mở. Hãy mở bàn trước khi thanh toán.</p>
							<?php endif; ?>
						</article>

						<article class="panel">
							<div class="panel-header">
								<div>
									<h3>Thực đơn nhanh</h3>
									<p>Thêm món và đồ uống vào hóa đơn đang phục vụ</p>
								</div>
							</div>
							<div class="menu-grid">
								<?php $canAddProduct = $openBill !== null; ?>
								<?php foreach ($menuItems as $menu): ?>
									<form method="post" class="menu-card menu-form">
										<?php echo csrf_field(); ?>
										<input type="hidden" name="action" value="add_product">
										<input type="hidden" name="table_id" value="<?php echo (int)$selectedTableId; ?>">
										<input type="hidden" name="product_id" value="<?php echo (int)$menu["id"]; ?>">
										<input type="hidden" name="redirect_page" value="orders">
										<?php if ($openBill !== null): ?>
											<input type="hidden" name="invoice_id" value="<?php echo (int)$openBill["MaHoaDon"]; ?>">
										<?php endif; ?>

										<span class="menu-icon"><i class="<?php echo h($menu["icon"]); ?>"></i></span>
										<strong><?php echo h($menu["name"]); ?></strong>
										<small><?php echo h($menu["category"]); ?> • <?php echo h($menu["price"]); ?> VND</small>

										<label class="qty-field">
											<span>SL</span>
											<input class="qty-input" type="number" name="quantity" min="1" step="1" value="1" <?php echo $canAddProduct ? "" : "disabled"; ?>>
										</label>

										<button type="submit" class="ghost-btn thin full" <?php echo $canAddProduct ? "" : "disabled"; ?>>Thêm món</button>
									</form>
								<?php endforeach; ?>
							</div>
						</article>
					</section>
				<?php elseif ($currentPage === "customers"): ?>
					<section class="panel stagger-fade">
						<?php
						$isEditingCustomer = $customerEditItem !== null;
						$customerFormName = $isEditingCustomer ? (string)$customerEditItem["name"] : "";
						$customerFormPhone = $isEditingCustomer ? (string)$customerEditItem["phone"] : "";
						$customerFormTier = $isEditingCustomer ? (string)$customerEditItem["tier"] : "Mới";
						$customerFormPoints = $isEditingCustomer ? (string)((int)$customerEditItem["points"]) : "0";
						?>
						<div class="panel-header">
							<div>
								<h3>Danh sách khách hàng</h3>
								<p>Theo dõi hạng thành viên và chi tiêu</p>
							</div>
							<a class="ghost-btn thin" href="index.php?page=customers"><i class="ri-user-add-line"></i> Biểu mẫu khách hàng</a>
						</div>

						<div class="crud-form-wrap">
							<div class="crud-form-head">
								<h4><?php echo $isEditingCustomer ? "Cập nhật khách hàng #" . (int)$customerEditItem["id"] : "Thêm khách hàng"; ?></h4>
								<p><?php echo $isEditingCustomer ? "Chỉnh sửa thông tin khách hàng." : "Tạo mới hồ sơ khách hàng để tích điểm và theo dõi chi tiêu."; ?></p>
							</div>
							<form method="post" class="crud-form">
								<?php echo csrf_field(); ?>
								<input type="hidden" name="action" value="<?php echo $isEditingCustomer ? "customer_update" : "customer_create"; ?>">
								<input type="hidden" name="redirect_page" value="customers">
								<?php if ($isEditingCustomer): ?>
									<input type="hidden" name="customer_id" value="<?php echo (int)$customerEditItem["id"]; ?>">
								<?php endif; ?>

								<div class="crud-grid crud-grid-4">
									<label class="crud-field">Họ tên
										<input type="text" name="customer_name" value="<?php echo h($customerFormName); ?>" required>
									</label>

									<label class="crud-field">Số điện thoại
										<input type="text" name="customer_phone" value="<?php echo h($customerFormPhone); ?>" placeholder="VD: 0901234567">
									</label>

									<label class="crud-field">Hạng
										<select name="customer_tier" required>
											<option value="Mới" <?php echo lowerText($customerFormTier) === "mới" || lowerText($customerFormTier) === "moi" ? "selected" : ""; ?>>Mới</option>
											<option value="Thường" <?php echo lowerText($customerFormTier) === "thường" || lowerText($customerFormTier) === "thuong" || lowerText($customerFormTier) === "regular" ? "selected" : ""; ?>>Thường</option>
											<option value="VIP" <?php echo lowerText($customerFormTier) === "vip" ? "selected" : ""; ?>>VIP</option>
										</select>
									</label>

									<label class="crud-field">Điểm tích lũy
										<input type="number" name="customer_points" min="0" step="1" value="<?php echo h($customerFormPoints); ?>" required>
									</label>
								</div>

								<div class="crud-actions">
									<button type="submit" class="solid-btn thin"><?php echo $isEditingCustomer ? "Lưu thay đổi" : "Thêm khách hàng"; ?></button>
									<?php if ($isEditingCustomer): ?>
										<a class="ghost-btn thin" href="index.php?page=customers">Hủy sửa</a>
									<?php endif; ?>
								</div>
							</form>
						</div>

						<table class="data-table">
							<thead>
								<tr>
									<th>Khách hàng</th>
									<th>Số điện thoại</th>
									<th>Điểm</th>
									<th>Lượt ghé</th>
									<th>Tổng chi (VND)</th>
									<th>Hạng</th>
									<th>Thao tác</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($customers as $customer): ?>
									<?php
									$tierValue = lowerText((string)$customer["tier"]);
									$tierClass = "new";
									if ($tierValue === "vip") {
										$tierClass = "warn";
									} elseif ($tierValue === "regular" || $tierValue === "thường" || $tierValue === "thuong") {
										$tierClass = "paid";
									}
									?>
									<tr>
										<td><?php echo h($customer["name"]); ?></td>
										<td><?php echo h($customer["phone"] !== "" ? $customer["phone"] : "-"); ?></td>
										<td><?php echo h((string)$customer["points"]); ?></td>
										<td><?php echo h((string) $customer["visits"]); ?></td>
										<td><?php echo h($customer["spent"]); ?></td>
										<td><span class="chip chip-<?php echo h($tierClass); ?>"><?php echo h($customer["tier"]); ?></span></td>
										<td>
											<div class="table-inline-actions">
												<a class="ghost-btn thin full" href="index.php?page=customers&amp;customer_edit_id=<?php echo (int)$customer["id"]; ?>">Sửa</a>
												<form method="post" class="inline-form js-confirm-delete-form" data-confirm-title="Xóa khách hàng" data-confirm-message="Bạn có chắc muốn xóa khách hàng này?">
													<?php echo csrf_field(); ?>
													<input type="hidden" name="action" value="customer_delete">
													<input type="hidden" name="customer_id" value="<?php echo (int)$customer["id"]; ?>">
													<input type="hidden" name="redirect_page" value="customers">
													<button type="submit" class="danger-btn thin full">Xóa</button>
												</form>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</section>
				<?php elseif ($currentPage === "inventory"): ?>
					<section class="panel stagger-fade">
						<?php
						$isEditingInventory = $inventoryEditItem !== null;
						$inventoryFormName = $isEditingInventory ? (string)$inventoryEditItem["name"] : "";
						$inventoryFormDvtId = $isEditingInventory ? (int)$inventoryEditItem["dvtId"] : $defaultDvtId;
						$inventoryFormQty = $isEditingInventory ? (string)((float)$inventoryEditItem["qty"]) : "0";
						$inventoryFormSupplierId = $isEditingInventory ? (string)$inventoryEditItem["supplierId"] : $defaultSupplierId;
						$inventoryFormCost = $isEditingInventory ? (string)((float)$inventoryEditItem["cost"]) : "0";
						?>
						<div class="panel-header">
							<div>
								<h3>Tổng quan tồn kho</h3>
								<p>Cảnh báo số lượng thấp và theo dõi nhà cung cấp</p>
							</div>
							<a class="ghost-btn thin" href="index.php?page=inventory"><i class="ri-add-line"></i> Biểu mẫu kho</a>
						</div>

						<div class="crud-form-wrap">
							<div class="crud-form-head">
								<h4><?php echo $isEditingInventory ? "Cập nhật nguyên liệu #" . (int)$inventoryEditItem["id"] : "Thêm nguyên liệu"; ?></h4>
								<p><?php echo $isEditingInventory ? "Chỉnh sửa nguyên liệu trong kho." : "Tạo mới nguyên liệu để theo dõi tồn kho và giá vốn."; ?></p>
							</div>
							<form method="post" class="crud-form">
								<?php echo csrf_field(); ?>
								<input type="hidden" name="action" value="<?php echo $isEditingInventory ? "inventory_update" : "inventory_create"; ?>">
								<input type="hidden" name="redirect_page" value="inventory">
								<?php if ($isEditingInventory): ?>
									<input type="hidden" name="ingredient_id" value="<?php echo (int)$inventoryEditItem["id"]; ?>">
								<?php endif; ?>

								<div class="crud-grid crud-grid-4">
									<label class="crud-field">Tên nguyên liệu
										<input type="text" name="ingredient_name" value="<?php echo h($inventoryFormName); ?>" required>
									</label>

									<label class="crud-field">Đơn vị tính
										<select name="ingredient_dvt_id" required>
											<option value="">Chọn đơn vị</option>
											<?php foreach ($dvtOptions as $dvtOption): ?>
												<?php $dvtOptionId = (int)($dvtOption["MaDVT"] ?? 0); ?>
												<option value="<?php echo $dvtOptionId; ?>" <?php echo $dvtOptionId === $inventoryFormDvtId ? "selected" : ""; ?>>
													<?php echo h((string)($dvtOption["TenDVT"] ?? "")); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</label>

									<label class="crud-field">Số lượng tồn
										<input type="number" name="ingredient_qty" min="0" step="0.01" value="<?php echo h($inventoryFormQty); ?>" required>
									</label>

									<label class="crud-field">Nhà cung cấp
										<select name="ingredient_supplier_id" required>
											<option value="">Chọn nhà cung cấp</option>
											<?php foreach ($supplierOptions as $supplierOption): ?>
												<?php $supplierOptionId = (string)($supplierOption["MaNhaCungCap"] ?? ""); ?>
												<option value="<?php echo h($supplierOptionId); ?>" <?php echo $supplierOptionId === $inventoryFormSupplierId ? "selected" : ""; ?>>
													<?php echo h((string)($supplierOption["TenNhaCungCap"] ?? "")); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</label>

									<label class="crud-field">Giá vốn (VND)
										<input type="number" name="ingredient_cost" min="0" step="100" value="<?php echo h($inventoryFormCost); ?>" required>
									</label>
								</div>

								<div class="crud-actions">
									<button type="submit" class="solid-btn thin"><?php echo $isEditingInventory ? "Lưu thay đổi" : "Thêm nguyên liệu"; ?></button>
									<?php if ($isEditingInventory): ?>
										<a class="ghost-btn thin" href="index.php?page=inventory">Hủy sửa</a>
									<?php endif; ?>
								</div>
							</form>
						</div>

						<table class="data-table">
							<thead>
								<tr>
									<th>Nguyên liệu</th>
									<th>Đơn vị tính</th>
									<th>Số lượng</th>
									<th>Mức tối thiểu</th>
									<th>Nhà cung cấp</th>
									<th>Giá vốn</th>
									<th>Trạng thái</th>
									<th>Thao tác</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($inventory as $item): ?>
									<?php $stockState = $item["qty"] < $item["min"] ? "warn" : "good"; ?>
									<tr>
										<td><?php echo h($item["name"]); ?></td>
										<td><?php echo h($item["category"]); ?></td>
										<td><?php echo h((string) $item["qty"]); ?></td>
										<td><?php echo h((string) $item["min"]); ?></td>
										<td><?php echo h($item["supplier"]); ?></td>
										<td><?php echo h(formatMoney((float)$item["cost"])); ?></td>
										<td><span class="chip chip-<?php echo h($stockState); ?>"><?php echo $stockState === "warn" ? "THẤP" : "ỔN"; ?></span></td>
										<td>
											<div class="table-inline-actions">
												<a class="ghost-btn thin full" href="index.php?page=inventory&amp;inventory_edit_id=<?php echo (int)$item["id"]; ?>">Sửa</a>
												<form method="post" class="inline-form js-confirm-delete-form" data-confirm-title="Xóa nguyên liệu" data-confirm-message="Bạn có chắc muốn xóa nguyên liệu này?">
													<?php echo csrf_field(); ?>
													<input type="hidden" name="action" value="inventory_delete">
													<input type="hidden" name="ingredient_id" value="<?php echo (int)$item["id"]; ?>">
													<input type="hidden" name="redirect_page" value="inventory">
													<button type="submit" class="danger-btn thin full">Xóa</button>
												</form>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</section>
				<?php elseif ($currentPage === "staff"): ?>
					<section class="panel stagger-fade">
						<?php
						$isEditingStaff = $staffEditItem !== null;
						$staffFormName = $isEditingStaff ? (string)$staffEditItem["name"] : "";
						$staffFormPhone = $isEditingStaff ? (string)$staffEditItem["phone"] : "";
						$staffFormRoleId = $isEditingStaff ? (int)$staffEditItem["roleId"] : $defaultRoleId;
						$staffFormAddress = $isEditingStaff ? (string)$staffEditItem["address"] : "";
						?>
						<div class="panel-header">
							<div>
								<h3>Quản lý ca làm</h3>
								<p>Danh sách nhân viên và trạng thái theo dữ liệu thực tế</p>
							</div>
							<a class="ghost-btn thin" href="index.php?page=staff"><i class="ri-team-line"></i> Biểu mẫu nhân sự</a>
						</div>

						<div class="crud-form-wrap">
							<div class="crud-form-head">
								<h4><?php echo $isEditingStaff ? "Cập nhật nhân viên #" . (int)$staffEditItem["id"] : "Thêm nhân viên"; ?></h4>
								<p><?php echo $isEditingStaff ? "Chỉnh sửa thông tin nhân sự hiện tại." : "Tạo nhân sự mới và gán chức vụ làm việc."; ?></p>
							</div>
							<form method="post" class="crud-form">
								<?php echo csrf_field(); ?>
								<input type="hidden" name="action" value="<?php echo $isEditingStaff ? "staff_update" : "staff_create"; ?>">
								<input type="hidden" name="redirect_page" value="staff">
								<?php if ($isEditingStaff): ?>
									<input type="hidden" name="staff_id" value="<?php echo (int)$staffEditItem["id"]; ?>">
								<?php endif; ?>

								<div class="crud-grid crud-grid-4">
									<label class="crud-field">Họ tên
										<input type="text" name="staff_name" value="<?php echo h($staffFormName); ?>" required>
									</label>

									<label class="crud-field">Số điện thoại
										<input type="text" name="staff_phone" value="<?php echo h($staffFormPhone); ?>" required>
									</label>

									<label class="crud-field">Chức vụ
										<select name="staff_role_id" required>
											<option value="">Chọn chức vụ</option>
											<?php foreach ($roleOptions as $roleOption): ?>
												<?php $roleOptionId = (int)($roleOption["MaChucVu"] ?? 0); ?>
												<option value="<?php echo $roleOptionId; ?>" <?php echo $roleOptionId === $staffFormRoleId ? "selected" : ""; ?>>
													<?php echo h((string)($roleOption["TenChucVu"] ?? "")); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</label>

									<label class="crud-field">Địa chỉ
										<input type="text" name="staff_address" value="<?php echo h($staffFormAddress); ?>" required>
									</label>
								</div>

								<div class="crud-actions">
									<button type="submit" class="solid-btn thin"><?php echo $isEditingStaff ? "Lưu thay đổi" : "Thêm nhân viên"; ?></button>
									<?php if ($isEditingStaff): ?>
										<a class="ghost-btn thin" href="index.php?page=staff">Hủy sửa</a>
									<?php endif; ?>
								</div>
							</form>
						</div>
						<div class="staff-grid">
							<?php foreach ($staff as $member): ?>
								<?php
								$statusClass = "paid";
								if ($member["status"] === "Nghỉ phép") {
									$statusClass = "warn";
								} elseif ($member["status"] === "Nghỉ") {
									$statusClass = "mute";
								}
								?>
								<article class="staff-card">
									<div class="avatar-circle"><?php echo h(strtoupper(substr($member["name"], 0, 1))); ?></div>
									<div>
										<h4><?php echo h($member["name"]); ?></h4>
										<p><?php echo h($member["role"]); ?> • <?php echo h($member["phone"]); ?></p>
									</div>
									<div class="staff-foot">
										<span class="chip chip-<?php echo h($statusClass); ?>"><?php echo h($member["status"]); ?></span>
										<small><?php echo h((string) $member["billsToday"]); ?> hóa đơn hôm nay</small>
									</div>
									<div class="staff-actions">
										<a class="ghost-btn thin full" href="index.php?page=staff&amp;staff_edit_id=<?php echo (int)$member["id"]; ?>">Sửa</a>
										<form method="post" class="inline-form full js-confirm-delete-form" data-confirm-title="Xóa nhân viên" data-confirm-message="Bạn có chắc muốn xóa nhân viên này?">
											<?php echo csrf_field(); ?>
											<input type="hidden" name="action" value="staff_delete">
											<input type="hidden" name="staff_id" value="<?php echo (int)$member["id"]; ?>">
											<input type="hidden" name="redirect_page" value="staff">
											<button type="submit" class="danger-btn thin full">Xóa</button>
										</form>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					</section>
				<?php elseif ($currentPage === "reports"): ?>
					<section class="split-grid stagger-fade">
						<article class="panel">
							<div class="panel-header">
								<div>
									<h3>Doanh thu theo tháng (Triệu VND)</h3>
									<p>Xu hướng 6 tháng gần nhất</p>
								</div>
								<button class="ghost-btn thin">6 tháng gần nhất</button>
							</div>
							<div class="line-bars">
								<?php
								$maxReportRevenue = max(1.0, (float)max($reportRevenue));
								foreach ($reportRevenue as $index => $value):
									$height = (int) round(($value / $maxReportRevenue) * 100);
								?>
									<div class="line-bar-item">
										<div class="line-bar" style="height: <?php echo $height; ?>%;"></div>
										<span><?php echo h($reportMonths[$index]); ?></span>
									</div>
								<?php endforeach; ?>
							</div>
						</article>
						<article class="panel">
							<div class="panel-header">
								<div>
									<h3>Dịch vụ bán chạy</h3>
									<p>Tỷ trọng theo số lượng gọi món</p>
								</div>
							</div>
							<div class="usage-list">
								<?php
								$maxService = !empty($topServices) ? max(1, (int)max(array_column($topServices, "value"))) : 1;
								foreach ($topServices as $service):
									$width = (int) round(($service["value"] / $maxService) * 100);
								?>
									<div class="usage-item">
										<div class="usage-meta">
											<span><?php echo h($service["name"]); ?></span>
											<strong><?php echo h((string) $service["value"]); ?>%</strong>
										</div>
										<div class="usage-track">
											<div class="usage-progress amber" style="width: <?php echo $width; ?>%;"></div>
										</div>
									</div>
								<?php endforeach; ?>
								<?php if (empty($topServices)): ?>
									<p>Chưa có dữ liệu dịch vụ bán chạy.</p>
								<?php endif; ?>
							</div>
						</article>
					</section>
				<?php elseif ($currentPage === "settings"): ?>
					<section class="settings-grid stagger-fade">
						<article class="panel">
							<div class="panel-header">
								<div>
									<h3>Thông tin cơ sở</h3>
									<p>Thông tin vận hành chính</p>
								</div>
							</div>
							<form method="post" class="settings-form">
								<?php echo csrf_field(); ?>
								<input type="hidden" name="action" value="settings_save">
								<input type="hidden" name="redirect_page" value="settings">
								<label>Tên cơ sở<input type="text" name="club_name" value="<?php echo h($clubName); ?>" required></label>
								<label>Địa chỉ<input type="text" name="club_address" value="<?php echo h($clubAddress); ?>"></label>
								<label>Số điện thoại<input type="text" name="club_phone" value="<?php echo h($clubPhone); ?>"></label>
								<label>Email<input type="email" name="club_email" value="<?php echo h($clubEmail); ?>"></label>
								<button type="submit" class="solid-btn">Lưu thông tin cơ sở</button>
							</form>
						</article>
						<article class="panel">
							<div class="panel-header">
								<div>
									<h3>Thiết lập hệ thống</h3>
									<p>Các tùy chọn vận hành nhanh</p>
								</div>
							</div>
							<div class="toggle-list">
								<div class="toggle-row"><span>Tự động kết thúc phiên sau 3 giờ</span><button class="switch is-on"></button></div>
								<div class="toggle-row"><span>Thông báo tồn kho thấp</span><button class="switch is-on"></button></div>
								<div class="toggle-row"><span>Bật âm thanh cảnh báo</span><button class="switch"></button></div>
								<div class="toggle-row"><span>Hiển thị thẻ dạng gọn</span><button class="switch"></button></div>
							</div>
							<p class="inline-note">Các công tắc hệ thống hiện là giao diện minh họa và chưa ghi xuống CSDL.</p>
						</article>
					</section>
				<?php endif; ?>
			</main>

			<?php include __DIR__ . "/includes/footer.php"; ?>
		</div>
	</div>

	<div id="confirmOverlay" class="overlay hidden" aria-hidden="true">
		<div id="popupConfirmDelete" class="popup popup-confirm hidden" role="dialog" aria-modal="true" aria-labelledby="popupConfirmDeleteTitle">
			<div class="popup-titlebar">
				<h4 id="popupConfirmDeleteTitle">Xác nhận xóa</h4>
				<button type="button" class="popup-close" onclick="closeConfirmPopup()" aria-label="Đóng popup xác nhận">&times;</button>
			</div>
			<p class="popup-subtitle" id="popupConfirmDeleteMessage">Bạn có chắc muốn xóa dữ liệu này?</p>
			<div class="crud-actions popup-confirm-actions">
				<button type="button" class="danger-btn thin" id="popupConfirmDeleteAccept">Xóa</button>
				<button type="button" class="ghost-btn thin" onclick="closeConfirmPopup()">Hủy</button>
			</div>
		</div>
	</div>

	<div class="mobile-overlay" id="mobileOverlay"></div>

	<script src="assets/js/main.js"></script>
</body>
</html>
