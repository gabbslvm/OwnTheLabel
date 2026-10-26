<?php
require_once "auth.php";

function admin_escape($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function post_int($name)
{
	if (isset($_POST[$name])) {
		return intval($_POST[$name]);
	}
	return 0;
}

function post_float($name)
{
	if (isset($_POST[$name])) {
		return floatval($_POST[$name]);
	}
	return 0;
}

function post_text($name)
{
	if (isset($_POST[$name])) {
		return trim($_POST[$name]);
	}
	return '';
}

function admin_csrf_token()
{
	if (empty($_SESSION['admin_csrf'])) {
		$_SESSION['admin_csrf'] = md5(uniqid(rand(), true));
	}
	return $_SESSION['admin_csrf'];
}

function admin_csrf_field()
{
	echo '<input type="hidden" name="csrf_token" value="' . admin_escape(admin_csrf_token()) . '">';
}

function admin_check_csrf()
{
	if (isset($_POST['csrf_token']) && isset($_SESSION['admin_csrf'])) {
		if ($_POST['csrf_token'] == $_SESSION['admin_csrf']) {
			return true;
		}
	}
	return false;
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
		'users'     => ['Users', 'users.php'],
		'inventory' => ['Inventory', 'inventory.php'],
		'orders'    => ['Orders', 'orders.php'],
		'reports'   => ['Reports', 'reports.php'],
		'messages'  => ['Messages', 'messages.php'],
	];

	$flash = null;
	if (isset($_SESSION['admin_flash'])) {
		$flash = $_SESSION['admin_flash'];
		unset($_SESSION['admin_flash']);
	}

	$adminName = 'Admin';
	if (isset($_SESSION['name'])) {
		$adminName = $_SESSION['name'];
	}
?>
	<!DOCTYPE html>
	<html lang="en">

	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php echo admin_escape($title); ?> | OTL Admin</title>
		<link rel="stylesheet" href="admin.css">
	</head>

	<body>
		<div class="admin-shell">
			<header class="admin-topbar">
				<a class="admin-brand" href="dashboard.php"><img src="../img/logo2.png" alt="Own The Label"><span>ADMIN</span></a>
				<div class="admin-topbar-meta">
					<span><?php echo admin_escape($adminName); ?></span>
					<a href="../index.html">Store</a>
					<a href="../api/logout.php">Log out</a>
				</div>
			</header>
			<nav class="admin-nav" aria-label="Admin navigation">
				<?php foreach ($links as $key => $link) {
					$label = $link[0];
					$href = $link[1];
				?>
					<a href="<?php echo admin_escape($href); ?>" <?php if ($active == $key) { echo 'aria-current="page"'; } ?>><?php echo admin_escape($label); ?></a>
				<?php } ?>
			</nav>
			<main class="admin-main">
				<?php if ($flash) { ?>
					<div class="admin-flash <?php echo admin_escape($flash['type']); ?>" role="status"><?php echo admin_escape($flash['message']); ?></div>
				<?php } ?>
				<div class="admin-page-heading">
					<div>
						<h1><?php echo admin_escape($title); ?></h1>
					</div>
				</div>
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