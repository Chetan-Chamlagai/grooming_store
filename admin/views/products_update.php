<?php
// admin/views/products_update.php - Modular Product Update with Live Search

$feedback = ['type' => '', 'text' => ''];
$category_filter = $_GET['category'] ?? 'all';
$selected_product_id = intval($_GET['edit_id'] ?? 0);

// Handle POST request for updating the product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_product') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $new_title = trim($_POST['title'] ?? '');
    $new_desc = trim($_POST['description'] ?? '');
    $new_price = trim($_POST['price'] ?? '');
    $add_quantity = trim($_POST['quantity_to_add'] ?? '');
    $new_category = trim($_POST['category'] ?? '');

    // Fetch existing product data to compare and apply partial updates
    $fetch_existing = $conn->prepare("SELECT title, description, price, stock_quantity, category, photo FROM products WHERE id = ?");
    $fetch_existing->bind_param("i", $product_id);
    $fetch_existing->execute();
    $current = $fetch_existing->get_result()->fetch_assoc();
    $fetch_existing->close();

    if (!$current) {
        $feedback = ['type' => 'error', 'text' => 'Target product record could not be found.'];
    } else {
        $title_to_save = ($new_title !== '' && $new_title !== $current['title']) ? $new_title : $current['title'];
        $desc_to_save = ($new_desc !== '' && $new_desc !== $current['description']) ? $new_desc : $current['description'];
        $price_to_save = ($new_price !== '' && floatval($new_price) != floatval($current['price'])) ? floatval($new_price) : $current['price'];
        $category_to_save = ($new_category !== '' && $new_category !== $current['category']) ? $new_category : $current['category'];
        
        $stock_to_save = $current['stock_quantity'];
        if ($add_quantity !== '' && intval($add_quantity) > 0) {
            $stock_to_save += intval($add_quantity);
        }

        $has_changed = (
            $title_to_save !== $current['title'] ||
            $desc_to_save !== $current['description'] ||
            floatval($price_to_save) !== floatval($current['price']) ||
            $category_to_save !== $current['category'] ||
            intval($stock_to_save) !== intval($current['stock_quantity'])
        );

        $photo_to_save = $current['photo'];
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            $file_type = mime_content_type($_FILES['product_image']['tmp_name']);
            
            if (in_array($file_type, $allowed_types) && $_FILES['product_image']['size'] <= (5 * 1024 * 1024)) {
                $target_dir = __DIR__ . '/../../uploads/products/';
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }
                $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
                $unique_name = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_dir . $unique_name)) {
                    $photo_to_save = 'uploads/products/' . $unique_name;
                    $has_changed = true;
                }
            }
        }

        if (!$has_changed) {
            $feedback = ['type' => 'error', 'text' => 'No modifications were detected. Please change at least one detail or add quantity.'];
        } else {
            $update_stmt = $conn->prepare("UPDATE products SET title = ?, description = ?, price = ?, category = ?, stock_quantity = ?, photo = ? WHERE id = ?");
            $update_stmt->bind_param("ssdsisi", $title_to_save, $desc_to_save, $price_to_save, $category_to_save, $stock_to_save, $photo_to_save, $product_id);

            if ($update_stmt->execute()) {
                $feedback = ['type' => 'success', 'text' => "Product '{$title_to_save}' updated successfully."];
                $selected_product_id = 0;
            } else {
                $feedback = ['type' => 'error', 'text' => 'Database update error: ' . $conn->error];
            }
            $update_stmt->close();
        }
    }
}

// Fetch single product details if an edit target is selected
$edit_product = null;
if ($selected_product_id > 0) {
    $edit_stmt = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $edit_stmt->bind_param("i", $selected_product_id);
    $edit_stmt->execute();
    $edit_product = $edit_stmt->get_result()->fetch_assoc();
    $edit_stmt->close();
}

// Fetch full catalog list (filtered by category dropdown) for instant client-side searching
$sql = "SELECT id, title, category, price, stock_quantity, photo FROM products WHERE 1=1";
$params = [];
$types = "";

if ($category_filter !== 'all' && !empty($category_filter)) {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

$sql .= " ORDER BY id DESC";
$list_stmt = $conn->prepare($sql);
if (!empty($params)) {
    $list_stmt->bind_param($types, ...$params);
}
$list_stmt->execute();
$products_catalog = $list_stmt->get_result();
$list_stmt->close();
?>

<div style="display: flex; flex-direction: column; gap: 25px;">
    <div>
        <h1 class="header-title">Update Product Catalog</h1>
        <p style="font-size: 10px; letter-spacing: 0.25em; text-transform: uppercase; color: var(--text-secondary); margin-top: 6px;">
            Select a product below to update &bull;
        </p>
    </div>

    <!-- Feedback Notification -->
    <?php if (!empty($feedback['text'])): ?>
        <div style="padding: 14px 20px; font-size: 11px; border: 1px solid <?php echo $feedback['type'] === 'success' ? '#B2D8B2' : '#E0B4B4'; ?>; background: <?php echo $feedback['type'] === 'success' ? '#EAF4EA' : '#FDF2F2'; ?>; color: <?php echo $feedback['type'] === 'success' ? '#2C5E2C' : '#912D2D'; ?>;">
            <?php echo htmlspecialchars($feedback['text']); ?>
        </div>
    <?php endif; ?>

    <?php if ($edit_product): ?>
        <!-- STEP 2: EDIT FORM INTERFACE -->
        <div class="section-box">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--borders); padding-bottom: 15px;">
                <h3 style="border:none; padding:0; font-size: 20px;">Editing: <?php echo htmlspecialchars($edit_product['title']); ?></h3>
                <a href="admin_dashboard.php?view=products_update" class="btn" style="text-decoration: none; background: transparent; color: var(--text-primary); border: 1px solid var(--borders); padding: 8px 14px; font-size: 8px;">&larr; Back to Catalog List</a>
            </div>

            <form method="POST" action="admin_dashboard.php?view=products_update&edit_id=<?php echo $edit_product['id']; ?>" enctype="multipart/form-data" style="margin-top: 15px;">
                <input type="hidden" name="action" value="update_product">
                <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">

                <p style="font-size: 10px; color: var(--text-secondary); margin-bottom: 15px;">Leave fields empty to keep current values. Fill only what you want to modify.</p>

                <div class="form-grid">
                    <div class="field">
                        <label>New Title (Optional)</label>
                        <input type="text" name="title" placeholder="<?php echo htmlspecialchars($edit_product['title']); ?>">
                    </div>

                    <div class="field">
                        <label>Category (Optional)</label>
                        <select name="category">
                            <option value="">-- Keep Current (<?php echo strtoupper($edit_product['category']); ?>) --</option>
                            <option value="fragrances">Fragrances</option>
                            <option value="watches">Watches</option>
                            <option value="wallets">Wallets</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>New Price in Rs. (Optional)</label>
                        <input type="number" step="0.01" min="0" name="price" placeholder="<?php echo $edit_product['price']; ?>">
                    </div>

                    <div class="field">
                        <label>Add Stock Quantity (Optional)</label>
                        <input type="number" min="0" name="quantity_to_add" placeholder="Current Stock: <?php echo $edit_product['stock_quantity']; ?> units">
                    </div>

                    <div class="field" style="grid-column: span 2;">
                        <label>New Product Image (Optional)</label>
                        <input type="file" name="product_image" accept="image/jpeg,image/png,image/webp">
                    </div>

                    <div class="field" style="grid-column: span 2;">
                        <label>New Description (Optional)</label>
                        <textarea name="description" rows="3" placeholder="Enter updated description..." style="background: var(--bg-main); border: 1px solid var(--borders); padding: 12px; font-size: 11px; outline: none; font-family: 'Montserrat', sans-serif; resize: vertical;"></textarea>
                    </div>
                </div>

                <div style="margin-top: 25px; display: flex; justify-content: flex-end; gap: 10px;">
                    <a href="admin_dashboard.php?view=products_update" class="btn" style="text-decoration: none; background: #ccc; color: #111;">Cancel</a>
                    <button type="submit" class="btn">Save Modifications</button>
                </div>
            </form>
        </div>

    <?php else: ?>
        <!-- STEP 1: CATALOG LIVE SEARCH, CATEGORY FILTER & SELECTION TABLE -->
        <div class="section-box">
            <div class="controls-toolbar">
                <div class="search-wrap">
                    <input type="text" id="liveProductSearch" placeholder="Type to instantly search product title..." autocomplete="off">
                </div>

                <div class="sort-wrap">
                    <form method="GET" action="admin_dashboard.php" id="catFilterForm" style="margin: 0;">
                        <input type="hidden" name="view" value="products_update">
                        <select name="category" onchange="document.getElementById('catFilterForm').submit()">
                            <option value="all" <?php if($category_filter=='all') echo 'selected'; ?>>Category: All Products</option>
                            <option value="fragrances" <?php if($category_filter=='fragrances') echo 'selected'; ?>>Category: Fragrances</option>
                            <option value="wallets" <?php if($category_filter=='wallets') echo 'selected'; ?>>Category: Wallets</option>
                            <option value="watches" <?php if($category_filter=='watches') echo 'selected'; ?>>Category: Watches</option>
                        </select>
                    </form>
                </div>
            </div>

            <table id="productTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products_catalog->num_rows > 0): ?>
                        <?php while($p = $products_catalog->fetch_assoc()): ?>
                        <tr class="product-row">
                            <td>#<?php echo $p['id']; ?></td>
                            <td><strong class="product-title-text"><?php echo htmlspecialchars($p['title']); ?></strong></td>
                            <td style="text-transform: uppercase;"><?php echo htmlspecialchars($p['category']); ?></td>
                            <td>Rs. <?php echo number_format($p['price'], 2); ?></td>
                            <td><?php echo $p['stock_quantity']; ?> units</td>
                            <td>
                                <a href="admin_dashboard.php?view=products_update&edit_id=<?php echo $p['id']; ?>" class="btn" style="text-decoration: none; padding: 6px 12px; font-size: 7px;">Select to Edit</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 40px;">No products found in this category.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Instant Live Search Script -->
<script>
document.getElementById('liveProductSearch')?.addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#productTable tbody tr.product-row');

    rows.forEach(row => {
        let titleText = row.querySelector('.product-title-text').textContent.toLowerCase();
        if (titleText.includes(filter)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
});
</script>