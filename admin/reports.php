<?php
require "layout.php";

$result = $conn->query("SELECT COUNT(*) AS total FROM orders");
$row = $result->fetch_assoc();
$orderCount = $row['total'];

$result = $conn->query("SELECT SUM(total) AS total FROM orders WHERE status = 'Completed'");
$row = $result->fetch_assoc();
$completedSales = $row['total'];
if ($completedSales == null) {
	$completedSales = 0;
}

$result = $conn->query("SELECT AVG(total) AS total FROM orders WHERE status = 'Completed'");
$row = $result->fetch_assoc();
$averageOrder = $row['total'];
if ($averageOrder == null) {
	$averageOrder = 0;
}

$result = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE status = 'Pending'");
$row = $result->fetch_assoc();
$pendingCount = $row['total'];

$topProducts = $conn->query("SELECT oi.product_name, SUM(oi.qty) AS units_sold, SUM(oi.price * oi.qty) AS gross_sales
	FROM order_items oi
	JOIN orders o ON o.id = oi.order_id
	WHERE o.status = 'Completed'
	GROUP BY oi.product_name
	ORDER BY units_sold DESC
	LIMIT 10");

$monthly = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key, SUM(total) AS revenue, COUNT(*) AS order_count
	FROM orders
	WHERE status = 'Completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
	GROUP BY month_key
	ORDER BY month_key DESC");

admin_header('Sales Reports', 'reports');
?>
<section class="admin-grid admin-stats" aria-label="Sales summary">
	<article class="admin-stat"><span class="admin-stat-label">All orders</span>
		<strong class="admin-stat-value"><?php echo number_format($orderCount); ?></strong>
	</article>
	<article class="admin-stat"><span class="admin-stat-label">Completed sales</span>
		<strong class="admin-stat-value"><?php echo admin_money($completedSales); ?></strong>
	</article>
	<article class="admin-stat"><span class="admin-stat-label">Average completed order</span>
		<strong class="admin-stat-value"><?php echo admin_money($averageOrder); ?></strong>
	</article>
	<article class="admin-stat"><span class="admin-stat-label">Pending orders</span>
		<strong class="admin-stat-value"><?php echo number_format($pendingCount); ?></strong>
	</article>
</section>

<section class="admin-panel">
	<div class="admin-panel-heading">
		<h2>Top products by completed units</h2>
	</div>
	<?php if ($topProducts->num_rows > 0) { ?>
		<div class="admin-table-wrap">
			<table class="admin-table">
				<thead>
					<tr>
						<th>Product</th>
						<th>Units sold</th>
						<th>Gross sales</th>
					</tr>
				</thead>
				<tbody>
					<?php while ($product = $topProducts->fetch_assoc()) { ?>
						<tr>
							<td><?php echo admin_escape($product['product_name']); ?></td>
							<td><?php echo number_format((int)$product['units_sold']); ?></td>
							<td><?php echo admin_money($product['gross_sales']); ?></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	<?php } else { ?>
		<div class="admin-empty">Completed product sales will appear here.</div>
	<?php } ?>
</section>

<section class="admin-panel">
	<div class="admin-panel-heading">
		<h2>Monthly completed sales</h2>
	</div>
	<?php if ($monthly->num_rows > 0) { ?>
		<div class="admin-table-wrap">
			<table class="admin-table">
				<thead>
					<tr>
						<th>Month</th>
						<th>Orders</th>
						<th>Revenue</th>
					</tr>
				</thead>
				<tbody>
					<?php while ($month = $monthly->fetch_assoc()) { ?>
						<tr>
							<td><?php echo admin_escape(date('F Y', strtotime($month['month_key'] . '-01'))); ?></td>
							<td><?php echo number_format((int)$month['order_count']); ?></td>
							<td><?php echo admin_money($month['revenue']); ?></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	<?php } else { ?>
		<div class="admin-empty">No completed orders in the last 12 months.</div>
	<?php } ?>
</section>
<?php admin_footer(); ?>