<?php
require "layout.php";

$filters = ['all', 'pending', 'replied', 'cancel'];
$filter = 'all';
$requested = '';
if (isset($_POST['filter'])) {
    $requested = $_POST['filter'];
} elseif (isset($_GET['filter'])) {
    $requested = $_GET['filter'];
}
if (in_array($requested, $filters)) {
    $filter = $requested;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (!admin_check_csrf()) {
        http_response_code(400);
        exit('Invalid request token. Refresh and try again.');
    }

    $messageId = post_int('message_id');
    $reply = post_text('reply');

    if ($messageId < 1 || $reply == '' || strlen($reply) > 2000) {
        admin_flash('Write a reply (up to 2000 characters).', 'error');
        admin_redirect('messages.php?filter=' . $filter);
    }

    $stmt = $conn->prepare("UPDATE messages SET reply = ?, status = 'Replied', replied_at = NOW() WHERE id = ?");
    $stmt->bind_param('si', $reply, $messageId);
    $stmt->execute();

    admin_flash('Reply saved. The customer can now see it on the Contact page.');
    admin_redirect('messages.php?filter=' . $filter);
}

$where = '';
if ($filter == 'pending') {
    $where = "WHERE status = 'Pending'";
} elseif ($filter == 'replied') {
    $where = "WHERE status = 'Replied'";
} elseif ($filter == 'cancel') {
    $where = "WHERE topic = 'Order Cancellation'";
}

$counts = $conn->query("SELECT COUNT(*) AS total,
    SUM(status = 'Pending') AS pending,
    SUM(status = 'Replied') AS replied,
    SUM(topic = 'Order Cancellation') AS cancel
    FROM messages")->fetch_assoc();

$res = $conn->query("SELECT m.id, m.first_name, m.last_name, m.email, m.topic, m.order_no, m.message, m.status, m.reply, m.replied_at, m.created_at,
        o.status AS order_status
    FROM (SELECT * FROM messages $where) m
    LEFT JOIN orders o ON o.order_no = m.order_no AND m.order_no <> ''
    ORDER BY m.created_at DESC, m.id DESC");

$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}

$tabs = [
    'all'     => ['All', (int)$counts['total']],
    'pending' => ['Pending', (int)$counts['pending']],
    'replied' => ['Replied', (int)$counts['replied']],
    'cancel'  => ['Cancellation', (int)$counts['cancel']],
];

admin_header('Customer Messages', 'messages');
?>
<div class="admin-actions" style="margin-bottom:18px">
    <?php foreach ($tabs as $key => $tab) { ?>
        <a class="admin-button <?php if ($filter == $key) { echo 'primary'; } ?>" href="messages.php?filter=<?php echo $key; ?>"><?php echo admin_escape($tab[0]); ?> (<?php echo $tab[1]; ?>)</a>
    <?php } ?>
</div>

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
                        <th>From</th>
                        <th>Message</th>
                        <th>Reply</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $m) {
                        $fullName = $m['first_name'] . " " . $m['last_name'];
                        $date = date("M j, Y g:i A", strtotime($m['created_at']));
                        $mailLink = "mailto:" . $m['email'] . "?subject=" . rawurlencode("Re: your message to Own The Label");
                        $replied = ($m['status'] == 'Replied');
                        $isCancel = ($m['topic'] == 'Order Cancellation');
                    ?>
                        <tr>
                            <td class="admin-nowrap"><?php echo admin_escape($date); ?></td>
                            <td>
                                <?php echo admin_escape($fullName); ?><br>
                                <a href="<?php echo admin_escape($mailLink); ?>"><small><?php echo admin_escape($m['email']); ?></small></a><br>
                                <span class="admin-badge <?php if ($isCancel) { echo 'disabled'; } ?>"><?php echo admin_escape($m['topic']); ?></span>
                                <?php if ($m['order_no'] != '') { ?>
                                    <br><small>Order: <?php echo admin_escape($m['order_no']); ?></small>
                                    <?php if ($isCancel && $m['order_status'] != '') { ?>
                                        <br><span class="admin-badge <?php echo admin_status_class($m['order_status']); ?>">Order is <?php echo admin_escape($m['order_status']); ?></span>
                                    <?php } ?>
                                <?php } ?>
                            </td>
                            <td class="admin-msg"><?php echo admin_escape($m['message']); ?></td>
                            <td class="admin-reply-cell">
                                <span class="admin-badge <?php echo $replied ? 'active' : 'pending'; ?>"><?php echo admin_escape($m['status']); ?></span>
                                <?php if ($replied) { ?>
                                    <small class="admin-muted"> · <?php echo admin_escape(date("M j, Y g:i A", strtotime($m['replied_at']))); ?></small>
                                <?php } ?>
                                <form method="post" style="margin-top:8px">
                                    <?php admin_csrf_field(); ?>
                                    <input type="hidden" name="message_id" value="<?php echo (int)$m['id']; ?>">
                                    <input type="hidden" name="filter" value="<?php echo admin_escape($filter); ?>">
                                    <?php if ($isCancel) { ?>
                                        <div class="admin-actions" style="margin-bottom:8px">
                                            <?php if ($m['order_status'] == 'Pending') { ?>
                                                <button type="button" class="admin-button" data-tpl="approve">Approve</button>
                                            <?php } elseif ($m['order_status'] == 'Processing') { ?>
                                                <button type="button" class="admin-button" data-tpl="processing">Already processing</button>
                                            <?php } ?>
                                            <button type="button" class="admin-button" data-tpl="denied">Already shipped</button>
                                            <button type="button" class="admin-button" data-tpl="details">Ask details</button>
                                        </div>
                                    <?php } ?>
                                    <textarea class="admin-input admin-textarea" name="reply" rows="3" maxlength="2000" placeholder="Write your reply..." required><?php echo admin_escape($m['reply']); ?></textarea>
                                    <button class="admin-button primary" type="submit" style="margin-top:8px"><?php echo $replied ? 'Update reply' : 'Send reply'; ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } else { ?>
        <div class="admin-empty">No messages to show.</div>
    <?php } ?>
</section>

<p class="admin-muted" style="margin-top:12px"><small>To approve a cancellation, set the order to <strong>Cancelled</strong> on the Orders page (only possible while it is Pending). Its items go back to stock automatically.</small></p>

<script>
    var TEMPLATES = {
        approve: "Hi! Your cancellation request has been approved. Your order is now cancelled and any payment made will be refunded within 3-5 business days. Thank you for shopping with Own The Label.",
        processing: "Hi! Unfortunately we can't cancel this order anymore because it is already being processed. Thank you for understanding.",
        denied: "Hi! Unfortunately we can't cancel this order anymore because it has already been shipped. You may refuse the parcel on delivery or contact us again for a return. Thank you for understanding.",
        details: "Hi! Could you please send us your order number and the email you used at checkout so we can look into your request? Thank you."
    };

    document.querySelectorAll('[data-tpl]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.closest('form').querySelector('textarea').value = TEMPLATES[btn.dataset.tpl];
        });
    });
</script>
<?php admin_footer(); ?>