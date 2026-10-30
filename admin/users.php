<?php
require "layout.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (!admin_check_csrf()) {
        http_response_code(400);
        exit('Invalid request token. Refresh and try again.');
    }

    $userId = post_int('user_id');
    $status = post_text('status');

    if ($userId < 1 || ($status != 'active' && $status != 'disabled')) {
        admin_flash('Choose a valid user and account status.', 'error');
        admin_redirect('users.php');
    }

    if ($userId == $_SESSION['user_id'] && $status == 'disabled') {
        admin_flash('You cannot disable the account you are currently using.', 'error');
        admin_redirect('users.php');
    }

    $stmt = $conn->prepare('UPDATE users SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $status, $userId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        admin_flash('User account status updated.');
    } else {
        admin_flash('No account changes were needed.');
    }
    admin_redirect('users.php');
}

$users = $conn->query('SELECT id, name, email, role, status FROM users ORDER BY role DESC, name ASC');
admin_header('User Maintenance', 'users');
?>
<section class="admin-panel">
    <div class="admin-panel-heading">
        <h2>Registered accounts</h2>
    </div>
    <?php if ($users->num_rows > 0) { ?>
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
                <tbody>
                    <?php while ($user = $users->fetch_assoc()) {
                        $isMe = false;
                        if ($user['id'] == $_SESSION['user_id']) {
                            $isMe = true;
                        }
                    ?>
                        <tr>
                            <td>
                                <?php echo admin_escape($user['name']); ?>
                                <?php if ($isMe) { echo ' <small>(you)</small>'; } ?>
                            </td>
                            <td><?php echo admin_escape($user['email']); ?></td>
                            <td><span class="admin-badge"><?php echo admin_escape(ucfirst($user['role'])); ?></span></td>
                            <td><span class="admin-badge <?php echo admin_status_class($user['status']); ?>"><?php echo admin_escape(ucfirst($user['status'])); ?></span></td>
                            <td>
                                <form class="admin-inline-form" method="post">
                                    <?php admin_csrf_field(); ?>
                                    <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                    <select class="admin-select" name="status" <?php if ($isMe) { echo 'disabled'; } ?>>
                                        <option value="active" <?php if ($user['status'] == 'active') { echo 'selected'; } ?>>Active</option>
                                        <option value="disabled" <?php if ($user['status'] == 'disabled') { echo 'selected'; } ?>>Disabled</option>
                                    </select>
                                    <button class="admin-button" type="submit" <?php if ($isMe) { echo 'disabled'; } ?>>Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } else { ?>
        <div class="admin-empty">No accounts found.</div>
    <?php } ?>
</section>
<?php admin_footer(); ?>