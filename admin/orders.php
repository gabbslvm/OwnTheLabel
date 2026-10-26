<?php
require "layout.php";

$statuses = ['Pending', 'Processing', 'Completed', 'Cancelled'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	if (!admin_check_csrf()) {
		http_response_code(400);
		exit('Invalid request token. Refresh and try again.');
	}

	$orderId = post_int('order_id');
	$status = post_text('status');

	if ($orderId < 1 || !in_array($status, $statuses)) {
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
	$id = (int)$order['id'];
	$order['items'] = [];
	$orders[$id] = $order;
}

if (count($orders) > 0) {
	$items = $conn->query('SELECT order_id, product_name, size, color, price, qty FROM order_items ORDER BY id');
	while ($item = $items->fetch_assoc()) {
		$orderId = (int)$item['order_id'];
		if (isset($orders[$orderId])) {
			$orders[$orderId]['items'][] = $item;
		}
	}
}

admin_header('Order Management', 'orders');
?>
<section class="admin-panel">
	<div class="admin-panel-heading">
		<h2>All orders</h2>
	</div>
	<?php if (count($orders) > 0) { ?>
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
				<tbody>
					<?php foreach ($orders as $order) {

						$customerName = 'Customer';
						if ($order['full_name'] != '') {
							$customerName = $order['full_name'];
						}

						$customerEmail = $order['account_email'];
						if ($order['email'] != '') {
							$customerEmail = $order['email'];
						}

						$fullAddress = $order['address'] . ', ' . $order['city'] . ' ' . $order['postal'];

						$placed = date('M j, Y g:i a', strtotime($order['created_at']));
					?>
						<tr>
							<td>
								<strong>#<?php echo admin_escape($order['order_no']); ?></strong><br>
								<small><?php echo admin_escape($placed); ?></small>
							</td>
							<td>
								<?php echo admin_escape($customerName); ?><br>
								<small><?php echo admin_escape($customerEmail); ?></small><br>
								<small><?php echo admin_escape($fullAddress); ?></small>
							</td>
							<td>
								<?php if (count($order['items']) > 0) { ?>
									<ul class="admin-order-items">
										<?php foreach ($order['items'] as $item) { ?>
											<li><?php echo admin_escape($item['product_name']); ?>, <?php echo admin_escape($item['size']); ?> / <?php echo admin_escape($item['color']); ?> × <?php echo (int)$item['qty']; ?></li>
										<?php } ?>
									</ul>
								<?php } else { ?>
									<span class="admin-muted">No item details</span>
								<?php } ?>
							</td>
							<td>
								<?php echo admin_money($order['total']); ?><br>
								<small><?php echo admin_escape(strtoupper($order['payment_method'])); ?></small>
							</td>
							<td>
								<span class="admin-badge <?php echo admin_status_class($order['status']); ?>"><?php echo admin_escape($order['status']); ?></span>
								<form method="post" class="admin-inline-form" style="margin-top:8px">
									<?php admin_csrf_field(); ?>
									<input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
									<select class="admin-select" name="status">
										<?php foreach ($statuses as $s) { ?>
											<option value="<?php echo admin_escape($s); ?>" <?php if ($order['status'] == $s) { echo 'selected'; } ?>><?php echo admin_escape($s); ?></option>
										<?php } ?>
									</select>
									<button class="admin-button" type="submit">Update</button>
								</form>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	<?php } else { ?>
		<div class="admin-empty">No orders found.</div>
	<?php } ?>
</section>
<?php admin_footer(); ?>