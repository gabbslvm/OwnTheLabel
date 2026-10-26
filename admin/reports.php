<?php
require "layout.php";

$summary = $conn->query("SELECT COUNT(*) AS order_count, COALESCE(SUM(CASE WHEN status = 'Completed' THEN total ELSE 0 END), 0) 
	AS completed_sales, COALESCE(SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END), 0) AS pending_count, 
	COALESCE(AVG(CASE WHEN status = 'Completed' THEN total END), 0) AS average_order FROM orders")->fetch_assoc();

$topProducts = $conn->query("SELECT COALESCE(NULLIF(oi.product_name, ''), p.name, 'Product') AS product_name, SUM(oi.qty) 
	AS units_sold, SUM(oi.price * oi.qty) AS gross_sales FROM order_items oi JOIN orders o ON o.id = oi.order_id LEFT JOIN product_variants v 
	ON v.id = oi.variant_id LEFT JOIN products p ON p.id = v.product_id WHERE o.status = 'Completed' GROUP BY product_name ORDER BY units_sold DESC LIMIT 10");

$monthly = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key, SUM(total) AS revenue, COUNT(*) 
	AS order_count FROM orders WHERE status = 'Completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY month_key ORDER BY month_key DESC");
	admin_header('Sales Reports', 'reports');
?>
<section class="admin-grid admin-stats" aria-label="Sales summary">
	<article class="admin-stat"><span class="admin-stat-label">All orders</span>
		<strong class="admin-stat-value"><?= number_format((int)$summary['order_count']) ?></strong>
	</article>

	<article class="admin-stat"><span class="admin-stat-label">Completed sales</span>
		<strong class="admin-stat-value"><?= admin_money($summary['completed_sales']) ?></strong>
	</article>

	<article class="admin-stat"><span class="admin-stat-label">Average completed order</span>
		<strong class="admin-stat-value"><?= admin_money($summary['average_order']) ?></strong>
	</article>

	<article class="admin-stat"><span class="admin-stat-label">Pending orders</span>
		<strong class="admin-stat-value"><?= number_format((int)$summary['pending_count']) ?></strong>
	</article>

</section>
<section class="admin-panel">
	<div class="admin-panel-heading">
		<h2>Top products by completed units</h2>
	</div>
	<?php if ($topProducts->num_rows): ?>
		<div class="admin-table-wrap">
			<table class="admin-table">
				<thead>
					<tr>
						<th>Product</th>
						<th>Units sold</th>
						<th>Gross sales</th>
					</tr>
				</thead>
				<tbody><?php while ($product = $topProducts->fetch_assoc()): ?><tr>
							<td><?= admin_escape($product['product_name']) ?></td>
							<td><?= number_format((int)$product['units_sold']) ?></td>
							<td><?= admin_money($product['gross_sales']) ?></td>
						</tr><?php endwhile; ?></tbody>
			</table>
		</div>
	<?php else: ?><div class="admin-empty">Completed product sales will appear here.</div><?php endif; ?>
</section>
<section class="admin-panel">
	<div class="admin-panel-heading">
		<h2>Monthly completed sales</h2>
	</div>
	<?php if ($monthly->num_rows): ?>
		<div class="admin-table-wrap">
			<table class="admin-table">
				<thead>
					<tr>
						<th>Month</th>
						<th>Orders</th>
						<th>Revenue</th>
					</tr>
				</thead>
				<tbody><?php while ($month = $monthly->fetch_assoc()): ?><tr>
							<td><?= admin_escape(date('F Y', strtotime($month['month_key'] . '-01'))) ?></td>
							<td><?= number_format((int)$month['order_count']) ?></td>
							<td><?= admin_money($month['revenue']) ?></td>
						</tr><?php endwhile; ?></tbody>
			</table>
		</div>
	<?php else: ?><div class="admin-empty">No completed orders in the last 12 months.</div><?php endif; ?>
</section>
<?php admin_footer(); ?>