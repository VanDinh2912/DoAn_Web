<?php
declare(strict_types=1);

session_start();

if (isset($_SESSION["user_name"])) {
	header("Location: index.php");
	exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	$username = trim((string) ($_POST["username"] ?? ""));
	$password = trim((string) ($_POST["password"] ?? ""));

	if ($username === "admin" && $password === "123456") {
		$_SESSION["user_name"] = "DOAN Admin";
		$_SESSION["user_role"] = "Manager";
		header("Location: index.php");
		exit;
	}

	$error = "Invalid credentials. Use demo account below.";
}
?>
<!DOCTYPE html>
<html lang="en">
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

				<label class="auth-field">
					<span>Username</span>
					<input type="text" name="username" placeholder="Enter username" required>
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
