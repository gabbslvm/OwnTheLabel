<?php
require "layout.php";

$result = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
$row = $result->fetch_row();
$customers = (int)$row[0];

$result = $conn->query("SELECT COUNT(*) FROM products WHERE is_active = 1");
$row = $result->fetch_row();
$products = (int)$row[0];

$result = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'");
$row = $result->fetch_row();
$pending = (int)$row[0];

$result = $conn->query("SELECT SUM(total) FROM orders WHERE status = 'Completed'");
$row = $result->fetch_row();
$revenue = (float)$row[0];

$recentOrders = $conn->query("SELECT id, order_no, full_name, total, status, created_at FROM orders ORDER BY created_at DESC LIMIT 8");

admin_header('Dashboard', 'dashboard');
?>
<section class="admin-grid admin-stats" aria-label="Store summary">
    <article class="admin-stat"><span class="admin-stat-label">Customers</span><strong class="admin-stat-value"><?php echo number_format($customers); ?></strong></article>
    <article class="admin-stat"><span class="admin-stat-label">Active products</span><strong class="admin-stat-value"><?php echo number_format($products); ?></strong></article>
    <article class="admin-stat"><span class="admin-stat-label">Pending orders</span><strong class="admin-stat-value"><?php echo number_format($pending); ?></strong></article>
    <article class="admin-stat"><span class="admin-stat-label">Completed sales</span><strong class="admin-stat-value"><?php echo admin_money($revenue); ?></strong></article>
</section>

<section class="admin-panel">
    <div class="admin-panel-heading">
        <h2>Recent orders</h2>
    </div>
    <?php if ($recentOrders->num_rows > 0) { ?>
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
                <tbody>
                    <?php while ($order = $recentOrders->fetch_assoc()) {
                        $customerName = 'Customer';
                        if ($order['full_name'] != '') {
                            $customerName = $order['full_name'];
                        }
                    ?>
                        <tr>
                            <td>#<?php echo admin_escape($order['order_no']); ?></td>
                            <td><?php echo admin_escape($customerName); ?></td>
                            <td><?php echo admin_money($order['total']); ?></td>
                            <td><span class="admin-badge <?php echo admin_status_class($order['status']); ?>"><?php echo admin_escape($order['status']); ?></span></td>
                            <td><?php echo admin_escape(date('M j, Y', strtotime($order['created_at']))); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } else { ?>
        <div class="admin-empty">No orders have been placed yet.</div>
    <?php } ?>
</section>
<?php admin_footer(); ?>