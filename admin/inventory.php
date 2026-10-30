<?php
require "layout.php";

$categories = ['Polo', 'Shorts', 'Hoodies', 'Barong', 'Shirts'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	if (!admin_check_csrf()) {
		http_response_code(400);
		exit('Invalid request token. Refresh and try again.');
	}

	$action = post_text('action');

	if ($action == 'add_stock') {
		$category = post_text('category');
		$productId = post_int('product_id');
		$variantId = post_int('variant_id');
		$quantity = post_int('quantity');

		if (!in_array($category, $categories) || $productId < 1 || $variantId < 1 || $quantity < 1) {
			admin_flash('Choose a category, product, size, and quantity greater than zero.', 'error');
			admin_redirect('inventory.php');
		}

		$stmt = $conn->prepare('UPDATE product_variants v JOIN products p ON p.id = v.product_id SET v.stock = v.stock + ? WHERE v.id = ? AND p.id = ? AND p.category = ?');
		$stmt->bind_param('iiis', $quantity, $variantId, $productId, $category);
		$stmt->execute();

		if ($stmt->affected_rows > 0) {
			admin_flash('Stock quantity added.', 'success');
		} else {
			admin_flash('That product and size could not be found for the selected category.', 'error');
		}
		admin_redirect('inventory.php');
	}

	if ($action == 'update') {
		$productId = post_int('product_id');
		$name = post_text('name');
		$category = post_text('category');
		$price = post_float('price');
		$image = post_text('image');

		if ($productId < 1 || $name == '' || strlen($name) > 100 || !in_array($category, $categories) || $price <= 0 || $image == '' || strlen($image) > 255) {
			admin_flash('Enter valid product details before saving.', 'error');
			admin_redirect('inventory.php');
		}

		$stmt = $conn->prepare('UPDATE products SET name = ?, category = ?, price = ?, image = ? WHERE id = ?');
		$stmt->bind_param('ssdsi', $name, $category, $price, $image, $productId);
		$stmt->execute();

		admin_flash('Product details saved.');
		admin_redirect('inventory.php');
	}

	if ($action == 'toggle') {
		$productId = post_int('product_id');
		$active = post_int('is_active');

		if ($productId < 1 || ($active != 0 && $active != 1)) {
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

$productOptionsByCategory = [];
foreach ($categories as $cat) {
	$productOptionsByCategory[$cat] = [];
}

$inventoryProducts = $conn->query('SELECT id, name, category FROM products ORDER BY category, name');
while ($productOption = $inventoryProducts->fetch_assoc()) {
	$cat = $productOption['category'];
	if (isset($productOptionsByCategory[$cat])) {
		$productOptionsByCategory[$cat][] = $productOption;
	}
}

$variantsByProduct = [];
$inventoryVariants = $conn->query('SELECT v.id, v.product_id, v.size FROM product_variants v JOIN products p ON p.id = v.product_id ORDER BY p.category, p.name, v.id');
while ($variantOption = $inventoryVariants->fetch_assoc()) {
	$pid = $variantOption['product_id'];
	if (!isset($variantsByProduct[$pid])) {
		$variantsByProduct[$pid] = [];
	}
	$variantsByProduct[$pid][] = $variantOption;
}

$products = $conn->query('SELECT p.id, p.name, p.category, p.price, p.image, p.is_active, p.is_featured, v.id AS variant_id, v.size, v.color, v.stock FROM products p LEFT JOIN product_variants v ON v.product_id = p.id ORDER BY p.id DESC, v.id ASC');

$productRows = [];
while ($row = $products->fetch_assoc()) {
	$id = $row['id'];

	if (!isset($productRows[$id])) {
		$productRows[$id] = [
			'product'  => $row,
			'variants' => []
		];
	}

	if ($row['variant_id'] != null) {
		$productRows[$id]['variants'][] = $row;
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
				$categoryKey = strtolower($category);
			?>
				<form method="post" class="admin-category-stock-form">
					<?php admin_csrf_field(); ?>
					<input type="hidden" name="action" value="add_stock">
					<input type="hidden" name="category" value="<?php echo admin_escape($category); ?>">

					<strong class="admin-category-stock-label"><?php echo admin_escape($category); ?></strong>

					<div class="admin-field">
						<label for="stock-product-<?php echo $categoryKey; ?>">Exact product name</label>
						<select class="admin-select" id="stock-product-<?php echo $categoryKey; ?>" name="product_id" data-role="product" required>
							<option value="">Choose product</option>
							<?php foreach ($productOptionsByCategory[$category] as $productOption) { ?>
								<option value="<?php echo (int)$productOption['id']; ?>"><?php echo admin_escape($productOption['name']); ?></option>
							<?php } ?>
						</select>
					</div>

					<div class="admin-field">
						<label for="stock-size-<?php echo $categoryKey; ?>">Size</label>
						<select class="admin-select" id="stock-size-<?php echo $categoryKey; ?>" name="variant_id" data-role="size" required disabled>
							<option value="">Choose size</option>
							<?php foreach ($productOptionsByCategory[$category] as $productOption) {

								$sizes = [];
								if (isset($variantsByProduct[$productOption['id']])) {
									$sizes = $variantsByProduct[$productOption['id']];
								}

								foreach ($sizes as $variantOption) { ?>
									<option value="<?php echo (int)$variantOption['id']; ?>" data-product="<?php echo (int)$productOption['id']; ?>"><?php echo admin_escape($variantOption['size']); ?></option>
							<?php }
							} ?>
						</select>
					</div>

					<div class="admin-field">
						<label for="stock-quantity-<?php echo $categoryKey; ?>">Quantity to add</label>
						<input class="admin-input" id="stock-quantity-<?php echo $categoryKey; ?>" name="quantity" type="number" min="1" step="1" value="1" required>
					</div>

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
	<?php if (count($productRows) > 0) { ?>
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
				<tbody>
					<?php foreach ($productRows as $entry) {
						$product = $entry['product'];

						if ($product['is_active'] == 1) {
							$newActive = 0;
							$badgeClass = 'active';
							$badgeText = 'Active';
							$buttonText = 'Hide';
						} else {
							$newActive = 1;
							$badgeClass = 'disabled';
							$badgeText = 'Hidden';
							$buttonText = 'Show';
						}
					?>
						<tr>
							<td class="admin-product-cell">
								<form method="post" class="admin-grid" style="gap:7px">
									<?php admin_csrf_field(); ?>
									<input type="hidden" name="action" value="update">
									<input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">

									<input class="admin-input" name="name" value="<?php echo admin_escape($product['name']); ?>" maxlength="100" required>

									<select class="admin-select" name="category">
										<?php foreach ($categories as $cat) { ?>
											<option value="<?php echo admin_escape($cat); ?>" <?php if ($product['category'] == $cat) { echo 'selected'; } ?>><?php echo admin_escape($cat); ?></option>
										<?php } ?>
									</select>

									<input class="admin-input" name="price" type="number" min="0.01" step="0.01" value="<?php echo admin_escape($product['price']); ?>" required>
									<input class="admin-input" name="image" value="<?php echo admin_escape($product['image']); ?>" maxlength="255" required>
									<button class="admin-button" type="submit">Save details</button>
								</form>
							</td>

							<td><?php echo admin_money($product['price']); ?></td>

							<td class="admin-stock-cell">
								<?php if (count($entry['variants']) > 0) { ?>
									<?php foreach ($entry['variants'] as $variant) { ?>
										<div class="admin-stock-row">
											<span><?php echo admin_escape($variant['size']); ?></span>
											<strong><?php echo (int)$variant['stock']; ?></strong>
										</div>
									<?php } ?>
								<?php } else { ?>
									<span class="admin-muted">No variants</span>
								<?php } ?>
							</td>

							<td>
								<form method="post" class="admin-inline-form">
									<?php admin_csrf_field(); ?>
									<input type="hidden" name="action" value="toggle">
									<input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
									<input type="hidden" name="is_active" value="<?php echo $newActive; ?>">
									<span class="admin-badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
									<button class="admin-button" type="submit"><?php echo $buttonText; ?></button>
								</form>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	<?php } else { ?>
		<div class="admin-empty">No products found.</div>
	<?php } ?>
</section>

<script>
	var forms = document.querySelectorAll('.admin-category-stock-form');

	forms.forEach(function (form) {
		var productSelect = form.querySelector('[data-role="product"]');
		var sizeSelect = form.querySelector('[data-role="size"]');

		productSelect.addEventListener('change', function () {
			for (var i = 0; i < sizeSelect.options.length; i++) {
				var option = sizeSelect.options[i];
				if (option.value != '' && option.dataset.product != productSelect.value) {
					option.hidden = true;
				} else {
					option.hidden = false;
				}
			}
			sizeSelect.value = '';
			sizeSelect.disabled = (productSelect.value == '');
		});
	});
</script>
<?php admin_footer(); ?>