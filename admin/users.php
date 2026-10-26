<?php
require "layout.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_check_csrf()) {
        http_response_code(400);
        exit('Invalid request token. Refresh and try again.');
    }

    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';
    if (!$userId || !in_array($status, ['active', 'disabled'], true)) {
        admin_flash('Choose a valid user and account status.', 'error');
        admin_redirect('users.php');
    }
    if ((int)$userId === (int)($_SESSION['user_id'] ?? 0) && $status === 'disabled') {
        admin_flash('You cannot disable the account you are currently using.', 'error');
        admin_redirect('users.php');
    }

    $stmt = $conn->prepare('UPDATE users SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $status, $userId);
    $stmt->execute();
    admin_flash($stmt->affected_rows ? 'User account status updated.' : 'No account changes were needed.');
    admin_redirect('users.php');
}

$users = $conn->query('SELECT id, name, email, role, status FROM users ORDER BY role DESC, name ASC');
admin_header('User Maintenance', 'users');
?>
<section class="admin-panel">
    <div class="admin-panel-heading">
        <h2>Registered accounts</h2>
    </div>
    <?php if ($users->num_rows): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Account access</th>
                    </tr>
                </thead>
                <tbody><?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= admin_escape($user['name']) ?><?= (int)$user['id'] === (int)$_SESSION['user_id'] ? ' <small>(you)</small>' : '' ?></td>
                            <td><?= admin_escape($user['email']) ?></td>
                            <td><span class="admin-badge"><?= admin_escape(ucfirst($user['role'])) ?></span></td>
                            <td><span class="admin-badge <?= admin_status_class($user['status']) ?>"><?= admin_escape(ucfirst($user['status'])) ?></span></td>
                            <td>
                                <form class="admin-inline-form" method="post">
                                    <?php admin_csrf_field(); ?>
                                    <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                    <select class="admin-select" name="status" aria-label="Status for <?= admin_escape($user['name']) ?>" <?= (int)$user['id'] === (int)$_SESSION['user_id'] ? 'disabled' : '' ?>>
                                        <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="disabled" <?= $user['status'] === 'disabled' ? 'selected' : '' ?>>Disabled</option>
                                    </select>
                                    <button class="admin-button" type="submit" <?= (int)$user['id'] === (int)$_SESSION['user_id'] ? 'disabled' : '' ?>>Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="admin-empty">No accounts found.</div>
    <?php endif; ?>
</section>
<?php admin_footer(); ?>