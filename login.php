<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/db.php';

boot_session();

if (!empty($_SESSION["user_id"])) {
	header("Location: index.php");
	exit;
}

$error = "";
$enteredUsername = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	$username = trim((string) ($_POST["username"] ?? ""));
	$password = (string) ($_POST["password"] ?? "");
	$csrfToken = (string) ($_POST["csrf_token"] ?? "");
	$enteredUsername = $username;

	if (!csrf_validate($csrfToken)) {
		$error = "Phiên đăng nhập không hợp lệ. Vui lòng tải lại trang.";
	} elseif ($username === "" || $password === "") {
		$error = "Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.";
	} else {
		$sql = "
			SELECT
				u.MaUser AS user_id,
				u.Username AS username,
				u.PasswordHash AS password_hash,
				u.IsActive AS is_active,
				nv.MaNhanVien AS employee_id,
				nv.HoTen AS display_name,
				COALESCE(cv.TenChucVu, 'Manager') AS role_name
			FROM `User` u
			INNER JOIN `NhanVien` nv ON nv.MaNhanVien = u.MaNhanVien
			LEFT JOIN `ChucVu` cv ON cv.MaChucVu = nv.MaChucVu
			WHERE u.Username = :username
			LIMIT 1
		";

		$stmt = $conn->prepare($sql);
		$stmt->execute(["username" => $username]);
		$user = $stmt->fetch();

		if ($user && (int) $user["is_active"] === 1 && password_matches($password, (string) $user["password_hash"])) {
			login_user($user);
			header("Location: index.php");
			exit;
		}

		$error = "Sai tên đăng nhập hoặc mật khẩu.";
	}
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Login | DOAN_WEB</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css">
	<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
	<div class="bg-aura bg-aura-one"></div>
	<div class="bg-aura bg-aura-two"></div>

	<section class="auth-shell">
		<article class="auth-card">
			<div class="auth-brand">
				<div class="brand-mark">DW</div>
				<div>
					<h1>DOAN_WEB</h1>
					<p>Billiards Management Console</p>
				</div>
			</div>

			<form method="post" class="auth-form">
				<h2>Sign in</h2>
				<p>Access operations dashboard for tables, billing and staff.</p>

				<?php if ($error !== ""): ?>
					<div class="auth-error"><i class="ri-error-warning-line"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></div>
				<?php endif; ?>

				<?php echo csrf_field(); ?>

				<label class="auth-field">
					<span>Username</span>
					<input type="text" name="username" placeholder="Enter username" value="<?php echo htmlspecialchars($enteredUsername, ENT_QUOTES, 'UTF-8'); ?>" required>
				</label>

				<label class="auth-field">
					<span>Password</span>
					<input type="password" name="password" placeholder="Enter password" required>
				</label>

				<button type="submit" class="auth-btn">Sign in</button>
				<small class="auth-hint">Demo account: admin / 123456</small>
			</form>
		</article>
	</section>
</body>
</html>
