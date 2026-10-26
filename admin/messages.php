<?php
require "layout.php";

$res = $conn->query(
    "SELECT id, first_name, last_name, email, message, created_at
    FROM messages ORDER BY created_at DESC, id DESC"
);
$rows = $res->fetch_all(MYSQLI_ASSOC);

admin_header('Customer Messages', 'messages');
?>
<section class="admin-panel">
    <div class="admin-panel-heading">
        <h2>Inbox (<?= count($rows) ?>)</h2>
    </div>
    <?php if ($rows): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody><?php foreach ($rows as $m): ?>
                        <tr>
                            <td class="admin-nowrap"><?= admin_escape(date("M j, Y g:i A", strtotime($m['created_at']))) ?></td>
                            <td><?= admin_escape($m['first_name'] . " " . $m['last_name']) ?></td>
                            <td><a href="mailto:<?= admin_escape($m['email']) ?>?subject=<?= admin_escape(rawurlencode("Re: your message to Own The Label")) ?>"><?= admin_escape($m['email']) ?></a></td>
                            <td class="admin-msg"><?= admin_escape($m['message']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="admin-empty">No messages yet.</div>
    <?php endif; ?>
</section>
<?php admin_footer(); ?>