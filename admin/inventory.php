<?php
require "layout.php";

$categories = ['Hoodies', 'Shirts', 'Shorts', 'Barong', 'Polo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!admin_check_csrf()) {
		http_response_code(400);
		exit('Invalid request token. Refresh and try again.');
	}

	$action = $_POST['action'] ?? '';
	if ($action === 'create') {
		$name = trim($_POST['name'] ?? '');
		$category = $_POST['category'] ?? '';
		$price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
		$image = trim($_POST['image'] ?? '');
		if ($name === '' || strlen($name) > 100 || !in_array($category, $categories, true) || $price === false || $price <= 0 || $image === '' || strlen($image) > 255) {
			admin_flash('Enter a valid name, category, price, and image path.', 'error');
			admin_redirect('inventory.php');
		}
		$conn->begin_transaction();
		try {
			$stmt = $conn->prepare('INSERT INTO products (name, category, price, image, is_active) VALUES (?, ?, ?, ?, 1)');
			$stmt->bind_param('ssds', $name, $category, $price, $image);
			$stmt->execute();
			$productId = $conn->insert_id;
			$variant = $conn->prepare('INSERT INTO product_variants (product_id, size, color, stock) VALUES (?, ?, ?, 0)');
			$color = trim($_POST['color'] ?? 'Black');
			if ($color === '' || strlen($color) > 30) {
				throw new RuntimeException('Enter a valid color.');
			}
			foreach (['Small', 'Medium', 'Large'] as $size) {
				$variant->bind_param('iss', $productId, $size, $color);
				$variant->execute();
			}
			$conn->commit();
			admin_flash('Product added with Small, Medium, and Large variants. Set stock below.');
		} catch (Throwable $error) {
			$conn->rollback();
			admin_flash('Product could not be added. Check the details and try again.', 'error');
		}
		admin_redirect('inventory.php');
	}

	if ($action === 'update') {
		$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
		$name = trim($_POST['name'] ?? '');
		$category = $_POST['category'] ?? '';
		$price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
		$image = trim($_POST['image'] ?? '');
		if (!$productId || $name === '' || strlen($name) > 100 || !in_array($category, $categories, true) || $price === false || $price <= 0 || $image === '' || strlen($image) > 255) {
			admin_flash('Enter valid product details before saving.', 'error');
			admin_redirect('inventory.php');
		}
		$stmt = $conn->prepare('UPDATE products SET name = ?, category = ?, price = ?, image = ? WHERE id = ?');
		$stmt->bind_param('ssdsi', $name, $category, $price, $image, $productId);
		$stmt->execute();
		admin_flash('Product details saved.');
		admin_redirect('inventory.php');
	}

	if ($action === 'toggle') {
		$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
		$active = filter_input(INPUT_POST, 'is_active', FILTER_VALIDATE_INT);
		if (!$productId || !in_array($active, [0, 1], true)) {
			admin_flash('Choose a valid product status.', 'error');
			admin_redirect('inventory.php');
		}
		$stmt = $conn->prepare('UPDATE products SET is_active = ? WHERE id = ?');
		$stmt->bind_param('ii', $active, $productId);
		$stmt->execute();
		admin_flash('Product visibility updated.');
		admin_redirect('inventory.php');
	}

	if ($action === 'stock') {
		$variantId = filter_input(INPUT_POST, 'variant_id', FILTER_VALIDATE_INT);
		$stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
		if (!$variantId || $stock === false || $stock < 0) {
			admin_flash('Stock must be a whole number of zero or more.', 'error');
			admin_redirect('inventory.php');
		}
		$stmt = $conn->prepare('UPDATE product_variants SET stock = ? WHERE id = ?');
		$stmt->bind_param('ii', $stock, $variantId);
		$stmt->execute();
		admin_flash('Variant stock updated.');
		admin_redirect('inventory.php');
	}

	admin_flash('Unknown inventory action.', 'error');
	admin_redirect('inventory.php');
}

$products = $conn->query('SELECT p.id, p.name, p.category, p.price, p.image, p.is_active, p.is_featured, v.id AS variant_id, v.size, v.color, v.stock FROM products p LEFT JOIN product_variants v ON v.product_id = p.id ORDER BY p.id DESC, v.id ASC');
$productRows = [];
while ($row = $products->fetch_assoc()) {
	$productRows[$row['id']]['product'] = $row;
	if ($row['variant_id'] !== null) {
		$productRows[$row['id']]['variants'][] = $row;
	}
}
admin_header('Inventory Maintenance', 'inventory');
?>
<section class="admin-panel">
	<div class="admin-panel-heading"><h2>Add product</h2></div>
	<div class="admin-panel-body">
		<form method="post" class="admin-form-grid">
			<?php admin_csrf_field(); ?><input type="hidden" name="action" value="create">
			<div class="admin-field"><label for="new-name">Product name</label><input class="admin-input" id="new-name" name="name" maxlength="100" required></div>
			<div class="admin-field"><label for="new-category">Category</label><select class="admin-select" id="new-category" name="category" required><?php foreach ($categories as $category): ?><option><?= admin_escape($category) ?></option><?php endforeach; ?></select></div>
			<div class="admin-field"><label for="new-price">Price (PHP)</label><input class="admin-input" id="new-price" name="price" type="number" min="0.01" step="0.01" required></div>
			<div class="admin-field"><label for="new-color">Variant color</label><input class="admin-input" id="new-color" name="color" maxlength="30" value="Black" required></div>
			<div class="admin-field admin-field-wide"><label for="new-image">Image path</label><input class="admin-input" id="new-image" name="image" maxlength="255" placeholder="img/products/shirts/item.jpg" required></div>
			<div><button class="admin-button primary" type="submit">Add product</button></div>
		</form>
	</div>
</section>
<section class="admin-panel">
	<div class="admin-panel-heading"><h2>Products and variant stock</h2></div>
	<?php if ($productRows): ?>
		<div class="admin-table-wrap"><table class="admin-table">
			<thead><tr><th>Product details</th><th>Price</th><th>Variants / stock</th><th>Visibility</th></tr></thead>
			<tbody><?php foreach ($productRows as $entry): $product = $entry['product']; ?>
				<tr>
					<td class="admin-product-cell">
						<form method="post" class="admin-grid" style="gap:7px">
							<?php admin_csrf_field(); ?><input type="hidden" name="action" value="update"><input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
							<input class="admin-input" name="name" aria-label="Product name" value="<?= admin_escape($product['name']) ?>" maxlength="100" required>
							<select class="admin-select" name="category" aria-label="Category"><?php foreach ($categories as $category): ?><option value="<?= admin_escape($category) ?>" <?= $product['category'] === $category ? 'selected' : '' ?>><?= admin_escape($category) ?></option><?php endforeach; ?></select>
							<input class="admin-input" name="price" type="number" min="0.01" step="0.01" aria-label="Price in PHP" value="<?= admin_escape($product['price']) ?>" required>
							<input class="admin-input" name="image" aria-label="Image path" value="<?= admin_escape($product['image']) ?>" maxlength="255" required>
							<button class="admin-button" type="submit">Save details</button>
						</form>
					</td>
					<td><?= admin_money($product['price']) ?></td>
					<td class="admin-stock-cell">
						<?php if (!empty($entry['variants'])): foreach ($entry['variants'] as $variant): ?>
							<form method="post" class="admin-inline-form" style="margin:0 0 7px">
								<?php admin_csrf_field(); ?><input type="hidden" name="action" value="stock"><input type="hidden" name="variant_id" value="<?= (int)$variant['variant_id'] ?>">
								<span class="admin-muted"><?= admin_escape($variant['size']) ?> / <?= admin_escape($variant['color']) ?></span>
								<input class="admin-input" name="stock" type="number" min="0" step="1" value="<?= (int)$variant['stock'] ?>" aria-label="Stock for <?= admin_escape($product['name'] . ' ' . $variant['size']) ?>">
								<button class="admin-button" type="submit">Set</button>
							</form>
						<?php endforeach; else: ?><span class="admin-muted">No variants</span><?php endif; ?>
					</td>
					<td><form method="post" class="admin-inline-form">
						<?php admin_csrf_field(); ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>"><input type="hidden" name="is_active" value="<?= $product['is_active'] ? 0 : 1 ?>">
						<span class="admin-badge <?= $product['is_active'] ? 'active' : 'disabled' ?>"><?= $product['is_active'] ? 'Active' : 'Hidden' ?></span><button class="admin-button" type="submit"><?= $product['is_active'] ? 'Hide' : 'Show' ?></button>
					</form></td>
				</tr>
			<?php endforeach; ?></tbody>
		</table></div>
	<?php else: ?><div class="admin-empty">No products found.</div><?php endif; ?>
</section>
<?php admin_footer(); ?>
