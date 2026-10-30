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
    $row['avg_rating'] = 0;
    $row['rating_count'] = 0;
    $row['my_rating'] = 0;
    $products[$row['id']] = $row;
}

if ($products) {
    try {
        $r = $conn->query("SELECT product_id, ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS rating_count
                        FROM product_ratings GROUP BY product_id");
    } catch (Exception $e) {
        $r = false;
    }
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $pid = (int)$row['product_id'];
            if (isset($products[$pid])) {
                $products[$pid]['avg_rating'] = (float)$row['avg_rating'];
                $products[$pid]['rating_count'] = (int)$row['rating_count'];
            }
        }

        if (isset($_SESSION['user_id'])) {
            $uid = (int)$_SESSION['user_id'];
            $mine = $conn->prepare("SELECT product_id, rating FROM product_ratings WHERE user_id = ?");
            $mine->bind_param("i", $uid);
            $mine->execute();
            $mr = $mine->get_result();
            while ($row = $mr->fetch_assoc()) {
                $pid = (int)$row['product_id'];
                if (isset($products[$pid])) {
                    $products[$pid]['my_rating'] = (int)$row['rating'];
                }
            }
        }
    }
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