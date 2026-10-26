<?php
require_once "auth.php";

function admin_escape($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function admin_csrf_token()
{
	if (empty($_SESSION['admin_csrf'])) {
		$_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
	}
	return $_SESSION['admin_csrf'];
}

function admin_csrf_field()
{
	echo '<input type="hidden" name="csrf_token" value="' . admin_escape(admin_csrf_token()) . '">';
}

function admin_check_csrf()
{
	return isset($_POST['csrf_token'], $_SESSION['admin_csrf'])
		&& hash_equals($_SESSION['admin_csrf'], (string)$_POST['csrf_token']);
}

function admin_flash($message, $type = 'success')
{
	$_SESSION['admin_flash'] = ['message' => $message, 'type' => $type];
}

function admin_redirect($location)
{
	header('Location: ' . $location);
	exit;
}

function admin_header($title, $active)
{
	header('Content-Type: text/html; charset=UTF-8');
	$links = [
		'dashboard' => ['Dashboard', 'dashboard.php'],
		'users' => ['Users', 'users.php'],
		'inventory' => ['Inventory', 'inventory.php'],
		'orders' => ['Orders', 'orders.php'],
		'reports' => ['Reports', 'reports.php'],
	];
	$flash = $_SESSION['admin_flash'] ?? null;
	unset($_SESSION['admin_flash']);
	?>
	<!DOCTYPE html>
	<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?= admin_escape($title) ?> | OTL Admin</title>
		<link rel="stylesheet" href="admin.css">
	</head>
	<body>
	<div class="admin-shell">
		<header class="admin-topbar">
			<a class="admin-brand" href="dashboard.php">OTL / ADMIN</a>
			<div class="admin-topbar-meta">
				<span><?= admin_escape($_SESSION['name'] ?? 'Admin') ?></span>
				<a href="../index.html">Store</a>
				<a href="../api/logout.php">Log out</a>
			</div>
		</header>
		<nav class="admin-nav" aria-label="Admin navigation">
			<?php foreach ($links as $key => [$label, $href]): ?>
				<a href="<?= admin_escape($href) ?>" <?= $active === $key ? 'aria-current="page"' : '' ?>><?= admin_escape($label) ?></a>
			<?php endforeach; ?>
		</nav>
		<main class="admin-main">
			<?php if ($flash): ?>
				<div class="admin-flash <?= admin_escape($flash['type']) ?>" role="status"><?= admin_escape($flash['message']) ?></div>
			<?php endif; ?>
			<div class="admin-page-heading"><div><h1><?= admin_escape($title) ?></h1></div></div>
	<?php
}

function admin_footer()
{
	?>
			<footer class="admin-footer">OTL Store Administration</footer>
		</main>
	</div>
	</body>
	</html>
	<?php
}

function admin_money($amount)
{
	return 'PHP ' . number_format((float)$amount, 2);
}

function admin_status_class($status)
{
	return strtolower((string)$status);
}
?>
