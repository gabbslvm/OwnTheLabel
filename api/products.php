<?php
require "connection.php";
header("Content-Type: application/json");

$where = "WHERE is_active = 1";
if (isset($_GET['featured'])) {
    $where .= " AND is_featured = 1";
}

$products = [];
$res = $conn->query("SELECT id, name, category, price, image, is_featured, created_at
                    FROM products $where ORDER BY id");
while ($row = $res->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['price'] = (float)$row['price'];
    $row['is_featured'] = (int)$row['is_featured'];
    $row['variants'] = [];
    $products[$row['id']] = $row;
}

if ($products) {
    $ids = implode(",", array_keys($products));
    $v = $conn->query("SELECT id, product_id, size, color, stock
                    FROM product_variants
                    WHERE product_id IN ($ids)
                    ORDER BY FIELD(size, 'Small', 'Medium', 'Large'), color");
    while ($r = $v->fetch_assoc()) {
        $products[(int)$r['product_id']]['variants'][] = [
            "id" => (int)$r['id'],
            "size" => $r['size'],
            "color" => $r['color'],
            "stock" => (int)$r['stock']
        ];
    }
}

echo json_encode(array_values($products));