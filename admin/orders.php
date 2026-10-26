<?php
require "layout.php";
$statuses = ['Pending', 'Processing', 'Completed', 'Cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!admin_check_csrf()) {
		http_response_code(400);
		exit('Invalid request token. Refresh and try again.');
	}
	$orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
	$status = $_POST['status'] ?? '';
	if (!$orderId || !in_array($status, $statuses, true)) {
		admin_flash('Choose a valid order and status.', 'error');
		admin_redirect('orders.php');
	}
	$stmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
	$stmt->bind_param('si', $status, $orderId);
	$stmt->execute();
	admin_flash('Order status updated.');
	admin_redirect('orders.php');
}

$orderResult = $conn->query('SELECT o.*, u.email AS account_email FROM orders o LEFT JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC');
$orders = [];
while ($order = $orderResult->fetch_assoc()) {
	$orders[(int)$order['id']] = $order;
	$orders[(int)$order['id']]['items'] = [];
}
if ($orders) {
	$items = $conn->query('SELECT oi.order_id, oi.product_name, oi.size, oi.color, oi.price, oi.qty FROM order_items oi JOIN orders o ON o.id = oi.order_id ORDER BY oi.id');
	while ($item = $items->fetch_assoc()) {
		if (isset($orders[(int)$item['order_id']])) {
			$orders[(int)$item['order_id']]['items'][] = $item;
		}
	}
}
admin_header('Order Management', 'orders');
?>
<section class="admin-panel">
	<div class="admin-panel-heading">
		<h2>All orders</h2>
	</div>
	<?php if ($orders): ?>
		<div class="admin-table-wrap">
			<table class="admin-table">
				<thead>
					<tr>
						<th>Order</th>
						<th>Customer / delivery</th>
						<th>Items</th>
						<th>Total</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody><?php foreach ($orders as $order): ?>
						<tr>
							<td><strong>#<?= admin_escape($order['order_no']) ?></strong>
								<br><small><?= admin_escape(date('M j, Y g:i a', strtotime($order['created_at']))) ?></small>
							</td>
							<td><?= admin_escape($order['full_name'] ?: 'Customer') ?><br><small><?= admin_escape($order['email'] ?: $order['account_email']) ?></small>
								<br><small><?= admin_escape(trim(($order['address'] ?? '') . ', ' . ($order['city'] ?? '') . ' ' . ($order['postal'] ?? ''), ', ')) ?></small>
							</td>
							<td><?php if ($order['items']): ?><ul class="admin-order-items"><?php foreach ($order['items'] as $item): ?><li><?= admin_escape($item['product_name'] ?: 'Product') ?>, <?= admin_escape($item['size']) ?> / <?= admin_escape($item['color']) ?> × <?= (int)$item['qty'] ?></li><?php endforeach; ?></ul><?php else: ?><span class="admin-muted">No item details</span><?php endif; ?></td>
							<td><?= admin_money($order['total']) ?><br><small><?= admin_escape(strtoupper($order['payment_method'])) ?></small></td>
							<td><span class="admin-badge <?= admin_status_class($order['status']) ?>"><?= admin_escape($order['status']) ?></span>
								<form method="post" class="admin-inline-form" style="margin-top:8px">
									<?php admin_csrf_field(); ?><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
									<select class="admin-select" name="status" aria-label="Status for order <?= admin_escape($order['order_no']) ?>"><?php foreach ($statuses as $status): ?><option value="<?= admin_escape($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= admin_escape($status) ?></option><?php endforeach; ?></select>
									<button class="admin-button" type="submit">Update</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php else: ?><div class="admin-empty">No orders found.</div><?php endif; ?>
</section>
<?php admin_footer(); ?>