<?php
declare(strict_types=1);

function boot_session(): void
{
	if (session_status() === PHP_SESSION_ACTIVE) {
		return;
	}

	$isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
	session_set_cookie_params(0, '/', '', $isSecure, true);
	session_start();
}

function require_login(): void
{
	boot_session();

	if (empty($_SESSION['user_id'])) {
		header('Location: login.php');
		exit;
	}
}

function login_user(array $user): void
{
	boot_session();
	session_regenerate_id(true);

	$_SESSION['user_id'] = (int) $user['user_id'];
	$_SESSION['username'] = (string) $user['username'];
	$_SESSION['user_name'] = (string) $user['display_name'];
	$_SESSION['user_role'] = (string) $user['role_name'];
	$_SESSION['employee_id'] = (int) $user['employee_id'];
}

function logout_user(): void
{
	boot_session();

	$_SESSION = [];

	if (ini_get('session.use_cookies')) {
		$params = session_get_cookie_params();
		setcookie(
			session_name(),
			'',
			time() - 42000,
			$params['path'],
			$params['domain'],
			$params['secure'],
			$params['httponly']
		);
	}

	session_destroy();
}

function current_user_name(): string
{
	boot_session();

	if (!empty($_SESSION['user_name'])) {
		return (string) $_SESSION['user_name'];
	}

	return 'Club Admin';
}

function current_user_role(): string
{
	boot_session();

	if (!empty($_SESSION['user_role'])) {
		return (string) $_SESSION['user_role'];
	}

	return 'Manager';
}

function password_matches(string $plainPassword, string $storedHash): bool
{
	$info = password_get_info($storedHash);
	if ($info['algo'] !== 0) {
		return password_verify($plainPassword, $storedHash);
	}

	$normalizedHash = strtolower(trim($storedHash));
	if (preg_match('/^[a-f0-9]{64}$/', $normalizedHash) === 1) {
		return hash_equals($normalizedHash, hash('sha256', $plainPassword));
	}

	return hash_equals($storedHash, $plainPassword);
}
