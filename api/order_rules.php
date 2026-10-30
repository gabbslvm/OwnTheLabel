<?php
// Order status rules, shared by the customer API and the admin pages.
//
//   Pending     -> Processing or Cancelled   (the only time an order can be cancelled)
//   Processing  -> Completed                  (already being handled: no cancelling)
//   Completed   -> (locked)                   (transaction done)
//   Cancelled   -> (locked)                   (stock already returned)

$ORDER_NEXT_STATUS = [
    'Pending'    => ['Processing', 'Cancelled'],
    'Processing' => ['Completed'],
    'Completed'  => [],
    'Cancelled'  => [],
];

function order_can_cancel($status)
{
    return $status == 'Pending';
}

function order_next_statuses($status)
{
    global $ORDER_NEXT_STATUS;
    return isset($ORDER_NEXT_STATUS[$status]) ? $ORDER_NEXT_STATUS[$status] : [];
}

function order_cancel_block_reason($status)
{
    if ($status == 'Processing') {
        return "This order is already being processed and can no longer be cancelled.";
    }
    if ($status == 'Completed') {
        return "This order is already completed and can no longer be cancelled.";
    }
    if ($status == 'Cancelled') {
        return "This order is already cancelled.";
    }
    return "This order can no longer be cancelled.";
}

// Changes an order's status safely inside one transaction.
// Locks the order row so two admins (or a double-click) can't change it at the same time,
// and returns the stock to product_variants when an order is cancelled.
// Returns '' on success, or an error message.
function order_change_status($conn, $orderId, $newStatus)
{
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            $conn->rollback();
            return "Order not found.";
        }

        $current = $row['status'];
        if ($current == $newStatus) {
            $conn->rollback();
            return "This order is already " . $current . ".";
        }
        if (!in_array($newStatus, order_next_statuses($current))) {
            $conn->rollback();
            if ($newStatus == 'Cancelled') {
                return order_cancel_block_reason($current);
            }
            return "A " . $current . " order cannot be changed to " . $newStatus . ".";
        }

        if ($newStatus == 'Cancelled') {
            $items = $conn->prepare("SELECT variant_id, qty FROM order_items WHERE order_id = ?");
            $items->bind_param("i", $orderId);
            $items->execute();
            $res = $items->get_result();
            $restock = $conn->prepare("UPDATE product_variants SET stock = stock + ? WHERE id = ?");
            while ($it = $res->fetch_assoc()) {
                $qty = (int)$it['qty'];
                $vid = (int)$it['variant_id'];
                $restock->bind_param("ii", $qty, $vid);
                $restock->execute();
            }
        }

        $upd = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $upd->bind_param("si", $newStatus, $orderId);
        $upd->execute();

        $conn->commit();
        return "";
    } catch (Exception $e) {
        $conn->rollback();
        return "Could not update the order. Please try again.";
    }
}