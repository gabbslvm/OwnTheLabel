<?php
require "layout.php";

$res = $conn->query("SELECT id, first_name, last_name, email, message, created_at
    FROM messages ORDER BY created_at DESC, id DESC");

$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}

admin_header('Customer Messages', 'messages');
?>
<section class="admin-panel">
    <div class="admin-panel-heading">
        <h2>Inbox (<?php echo count($rows); ?>)</h2>
    </div>
    <?php if (count($rows) > 0) { ?>
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
                <tbody>
                    <?php foreach ($rows as $m) {
                        $fullName = $m['first_name'] . " " . $m['last_name'];
                        $date = date("M j, Y g:i A", strtotime($m['created_at']));
                        $mailLink = "mailto:" . $m['email'] . "?subject=" . rawurlencode("Re: your message to Own The Label");
                    ?>
                        <tr>
                            <td class="admin-nowrap"><?php echo admin_escape($date); ?></td>
                            <td><?php echo admin_escape($fullName); ?></td>
                            <td><a href="<?php echo admin_escape($mailLink); ?>"><?php echo admin_escape($m['email']); ?></a></td>
                            <td class="admin-msg"><?php echo admin_escape($m['message']); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } else { ?>
        <div class="admin-empty">No messages yet.</div>
    <?php } ?>
</section>
<?php admin_footer(); ?>