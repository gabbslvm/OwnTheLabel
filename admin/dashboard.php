<?php
require "layout.php";

$stats = [
    'customers' => (int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetch_row()[0],
    'products' => (int)$conn->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetch_row()[0],
    'pending' => (int)$conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetch_row()[0],
    'revenue' => (float)$conn->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status = 'Completed'")->fetch_row()[0],
];
$recentOrders = $conn->query("SELECT id, order_no, full_name, total, status, created_at FROM orders ORDER BY created_at DESC LIMIT 8");
admin_header('Dashboard', 'dashboard');
?>
<section class="admin-grid admin-stats" aria-label="Store summary">
    <article class="admin-stat"><span class="admin-stat-label">Customers</span><strong class="admin-stat-value"><?= number_format($stats['customers']) ?></strong></article>
    <article class="admin-stat"><span class="admin-stat-label">Active products</span><strong class="admin-stat-value"><?= number_format($stats['products']) ?></strong></article>
    <article class="admin-stat"><span class="admin-stat-label">Pending orders</span><strong class="admin-stat-value"><?= number_format($stats['pending']) ?></strong></article>
    <article class="admin-stat"><span class="admin-stat-label">Completed sales</span><strong class="admin-stat-value"><?= admin_money($stats['revenue']) ?></strong></article>
</section>
<section class="admin-panel">
    <div class="admin-panel-heading">
        <h2>Recent orders</h2>
    </div>
    <?php if ($recentOrders->num_rows): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Placed</th>
                    </tr>
                </thead>
                <tbody><?php while ($order = $recentOrders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= admin_escape($order['order_no']) ?></td>
                            <td><?= admin_escape($order['full_name'] ?: 'Customer') ?></td>
                            <td><?= admin_money($order['total']) ?></td>
                            <td><span class="admin-badge <?= admin_status_class($order['status']) ?>"><?= admin_escape($order['status']) ?></span></td>
                            <td><?= admin_escape(date('M j, Y', strtotime($order['created_at']))) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="admin-empty">No orders have been placed yet.</div>
    <?php endif; ?>
</section>
<?php admin_footer(); ?>