<?php
require "auth.php";

header("Content-Type: text/html; charset=utf-8");

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$res = $conn->query(
    "SELECT id, first_name, last_name, email, message, created_at
     FROM messages ORDER BY created_at DESC, id DESC"
);
$rows = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | OTL Admin</title>
    <style>
        body { font-family: Inter, Arial, sans-serif; background: #f7f7f5; color: #080808; margin: 0; padding: 32px; }
        .top { display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
        h1 { font-size: 1.4rem; letter-spacing: 2px; margin: 0; }
        a { color: #080808; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { text-align: left; padding: 12px 14px; border-bottom: 1px solid #ddd; vertical-align: top; font-size: .9rem; }
        th { background: #080808; color: #f7f7f5; letter-spacing: 1px; font-size: .75rem; }
        td.msg { white-space: pre-wrap; word-break: break-word; max-width: 480px; }
        td.date { white-space: nowrap; }
        .empty { background: #fff; padding: 40px; text-align: center; color: #777; }
    </style>
</head>
<body>
    <div class="top">
        <h1>CUSTOMER MESSAGES (<?= count($rows) ?>)</h1>
        <div><a href="dashboard.php">Dashboard</a> &middot; <a href="../index.html">Back to site</a></div>
    </div>

    <?php if (!$rows): ?>
        <div class="empty">No messages yet.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>DATE</th><th>NAME</th><th>EMAIL</th><th>MESSAGE</th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $m): ?>
                <tr>
                    <td class="date"><?= h(date("M j, Y g:i A", strtotime($m['created_at']))) ?></td>
                    <td><?= h($m['first_name'] . " " . $m['last_name']) ?></td>
                    <td><a href="mailto:<?= h($m['email']) ?>?subject=<?= h(rawurlencode("Re: your message to Own The Label")) ?>"><?= h($m['email']) ?></a></td>
                    <td class="msg"><?= h($m['message']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>