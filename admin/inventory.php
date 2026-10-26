<?php
require "layout.php";

$categories = ['Polo', 'Shorts', 'Hoodies', 'Barong', 'Shirts'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!admin_check_csrf()) {
		http_response_code(400);
		exit('Invalid request token. Refresh and try again.');
	}

	$action = $_POST['action'] ?? '';
	if ($action === 'add_stock') {
		$category = $_POST['category'] ?? '';
		$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
		$variantId = filter_input(INPUT_POST, 'variant_id', FILTER_VALIDATE_INT);
		$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
		if (!in_array($category, $categories, true) || !$productId || !$variantId || $quantity === false || $quantity < 1) {
			admin_flash('Choose a category, product, size, and quantity greater than zero.', 'error');
			admin_redirect('inventory.php');
		}
		$stmt = $conn->prepare('UPDATE product_variants v JOIN products p ON p.id = v.product_id SET v.stock = v.stock + ? WHERE v.id = ? AND p.id = ? AND p.category = ?');
		$stmt->bind_param('iiis', $quantity, $variantId, $productId, $category);
		$stmt->execute();
		admin_flash($stmt->affected_rows ? 'Stock quantity added.' : 'That product and size could not be found for the selected category.', $stmt->affected_rows ? 'success' : 'error');
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

	admin_flash('Unknown inventory action.', 'error');
	admin_redirect('inventory.php');
}

$productOptionsByCategory = array_fill_keys($categories, []);
$inventoryProducts = $conn->query('SELECT id, name, category FROM products ORDER BY category, name');
while ($productOption = $inventoryProducts->fetch_assoc()) {
	if (isset($productOptionsByCategory[$productOption['category']])) {
		$productOptionsByCategory[$productOption['category']][] = $productOption;
	}
}
$variantsByProduct = [];
$inventoryVariants = $conn->query('SELECT v.id, v.product_id, v.size FROM product_variants v JOIN products p ON p.id = v.product_id ORDER BY p.category, p.name, v.id');
while ($variantOption = $inventoryVariants->fetch_assoc()) {
	$variantsByProduct[$variantOption['product_id']][] = $variantOption;
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
	<div class="admin-panel-heading">
		<h2>Add stock</h2>
	</div>
	<div class="admin-panel-body">
		<div class="admin-category-stock-list">
			<?php foreach ($categories as $category) {
				$categoryKey = strtolower($category); ?>
				<form method="post" class="admin-category-stock-form">
					<?php admin_csrf_field(); ?><input type="hidden" name="action" value="add_stock"><input type="hidden" name="category" value="<?= admin_escape($category) ?>">
					<strong class="admin-category-stock-label"><?= admin_escape($category) ?></strong>
					<div class="admin-field"><label for="stock-product-<?= admin_escape($categoryKey) ?>">Exact product name</label><select class="admin-select" id="stock-product-<?= admin_escape($categoryKey) ?>" name="product_id" data-role="product" required>
							<option value="">Choose product</option><?php foreach ($productOptionsByCategory[$category] as $productOption) { ?><option value="<?= (int)$productOption['id'] ?>"><?= admin_escape($productOption['name']) ?></option><?php } ?>
						</select></div>
					<div class="admin-field"><label for="stock-size-<?= admin_escape($categoryKey) ?>">Size</label><select class="admin-select" id="stock-size-<?= admin_escape($categoryKey) ?>" name="variant_id" data-role="size" required disabled>
							<option value="">Choose size</option>
							<?php foreach ($productOptionsByCategory[$category] as $productOption) {
									foreach ($variantsByProduct[$productOption['id']] ?? [] as $variantOption) { ?><option value="<?= (int)$variantOption['id'] ?>" data-product="<?= (int)$productOption['id'] ?>"><?= admin_escape($variantOption['size']) ?></option><?php }
							} 
							?>
						</select></div>
					<div class="admin-field"><label for="stock-quantity-<?= admin_escape($categoryKey) ?>">Quantity to add</label><input class="admin-input" id="stock-quantity-<?= admin_escape($categoryKey) ?>" name="quantity" type="number" min="1" step="1" value="1" required></div>
					<button class="admin-button primary" type="submit">Add stock</button>
				</form>
			<?php } ?>
		</div>
	</div>
</section>
<section class="admin-panel">
	<div class="admin-panel-heading">
		<h2>Products and variant stock</h2>
	</div>
	<?php if ($productRows) { ?>
		<div class="admin-table-wrap">
			<table class="admin-table">
				<thead>
					<tr>
						<th>Product details</th>
						<th>Price</th>
						<th>Variants / stock</th>
						<th>Visibility</th>
					</tr>
				</thead>
				<tbody><?php foreach ($productRows as $entry) {
							$product = $entry['product']; ?>
						<tr>
							<td class="admin-product-cell">
								<form method="post" class="admin-grid" style="gap:7px">
									<?php admin_csrf_field(); ?><input type="hidden" name="action" value="update"><input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
									<input class="admin-input" name="name" aria-label="Product name" value="<?= admin_escape($product['name']) ?>" maxlength="100" required>
									<select class="admin-select" name="category" aria-label="Category"><?php foreach ($categories as $category) { ?><option value="<?= admin_escape($category) ?>" <?= $product['category'] === $category ? 'selected' : '' ?>><?= admin_escape($category) ?></option><?php } ?></select>
									<input class="admin-input" name="price" type="number" min="0.01" step="0.01" aria-label="Price in PHP" value="<?= admin_escape($product['price']) ?>" required>
									<input class="admin-input" name="image" aria-label="Image path" value="<?= admin_escape($product['image']) ?>" maxlength="255" required>
									<button class="admin-button" type="submit">Save details</button>
								</form>
							</td>
							<td><?= admin_money($product['price']) ?></td>
							<td class="admin-stock-cell">
								<?php if (!empty($entry['variants'])) {
									foreach ($entry['variants'] as $variant) { ?>
										<div class="admin-stock-row"><span><?= admin_escape($variant['size']) ?></span><strong><?= (int)$variant['stock'] ?></strong></div>
									<?php }
								} else { ?><span class="admin-muted">No variants</span><?php } ?>
							</td>
							<td>
								<form method="post" class="admin-inline-form">
									<?php admin_csrf_field(); ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>"><input type="hidden" name="is_active" value="<?= $product['is_active'] ? 0 : 1 ?>">
									<span class="admin-badge <?= $product['is_active'] ? 'active' : 'disabled' ?>"><?= $product['is_active'] ? 'Active' : 'Hidden' ?></span><button class="admin-button" type="submit"><?= $product['is_active'] ? 'Hide' : 'Show' ?></button>
								</form>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	<?php } else { ?><div class="admin-empty">No products found.</div><?php } ?>
</section>
<script>
	document.querySelectorAll('.admin-category-stock-form').forEach(function(form) {
		const productSelect = form.querySelector('[data-role="product"]');
		const sizeSelect = form.querySelector('[data-role="size"]');
		productSelect.addEventListener('change', function() {
			for (const option of sizeSelect.options) {
				option.hidden = option.value !== '' && option.dataset.product !== productSelect.value;
			}
			sizeSelect.value = '';
			sizeSelect.disabled = !productSelect.value;
		});
	});
</script>
<?php admin_footer(); ?>