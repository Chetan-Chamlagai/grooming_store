<?php
// admin/views/products_add.php - Add / Auto-Update Product Module

$feedback = ['type' => '', 'text' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_product') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);

    if (empty($title) || empty($description) || $price <= 0 || empty($category) || $quantity < 0) {
        $feedback = ['type' => 'error', 'text' => 'Please fill in all mandatory fields with valid values.'];
    } else {
        // Handle File Upload
        $uploaded_photo_path = null;
        $upload_error = null;

        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            $file_type = mime_content_type($_FILES['product_image']['tmp_name']);
            $file_size = $_FILES['product_image']['size'];

            if (!in_array($file_type, $allowed_types)) {
                $upload_error = 'Only JPG, PNG, and WebP images are allowed.';
            } elseif ($file_size > (2 * 1024 * 1024)) { // 2MB limit
                $upload_error = 'The uploaded image exceeds the 2MB size limit.';
            } else {
                // Ensure upload folder exists
                $target_dir = __DIR__ . '/../../uploads/products/';
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }

                $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
                $unique_name = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $target_file = $target_dir . $unique_name;

                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
                    $uploaded_photo_path = 'uploads/products/' . $unique_name;
                } else {
                    $upload_error = 'Failed to move the uploaded file. Check directory permissions.';
                }
            }
        }

        if ($upload_error) {
            $feedback = ['type' => 'error', 'text' => $upload_error];
        } else {
            // Check if product with the exact same title already exists
            $check_stmt = $conn->prepare("SELECT id, photo, stock_quantity FROM products WHERE LOWER(TRIM(title)) = LOWER(?) LIMIT 1");
            $check_stmt->bind_param("s", $title);
            $check_stmt->execute();
            $existing_product = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();

            if ($existing_product) {
                // UPDATE branch: Product already exists
                $prod_id = $existing_product['id'];
                // Add new quantity to existing stock
                $new_stock = $existing_product['stock_quantity'] + $quantity;
                // Keep existing photo if no new image was uploaded
                $final_photo = $uploaded_photo_path !== null ? $uploaded_photo_path : $existing_product['photo'];

                $update_stmt = $conn->prepare("UPDATE products SET description = ?, price = ?, category = ?, photo = ?, stock_quantity = ? WHERE id = ?");
                $update_stmt->bind_param("sdssii", $description, $price, $category, $final_photo, $new_stock, $prod_id);

                if ($update_stmt->execute()) {
                    $feedback = [
                        'type' => 'success',
                        'text' => "Product '{$title}' already exists. Details were updated and {$quantity} units were added to stock (Total: {$new_stock})."
                    ];
                } else {
                    $feedback = ['type' => 'error', 'text' => 'Database update error: ' . $conn->error];
                }
                $update_stmt->close();
            } else {
                // INSERT branch: New product
                $final_photo = $uploaded_photo_path ?? 'images/default-product.jpg';

                $insert_stmt = $conn->prepare("INSERT INTO products (title, description, price, category, photo, stock_quantity) VALUES (?, ?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("ssdssi", $title, $description, $price, $category, $final_photo, $quantity);

                if ($insert_stmt->execute()) {
                    $feedback = [
                        'type' => 'success',
                        'text' => "New product '{$title}' created successfully with {$quantity} units in stock."
                    ];
                } else {
                    $feedback = ['type' => 'error', 'text' => 'Database insert error: ' . $conn->error];
                }
                $insert_stmt->close();
            }
        }
    }
}
?>

<div style="display: flex; flex-direction: column; gap: 25px;">
    <div>
        <h1 class="header-title">Add / Restock Product</h1>
        <p style="font-size: 10px; letter-spacing: 0.25em; text-transform: uppercase; color: var(--text-secondary); margin-top: 6px;">
            Catalog Management &bull; Duplicate titles automatically update price and append stock
        </p>
    </div>

    <!-- Alert Notifications -->
    <?php if (!empty($feedback['text'])): ?>
        <div style="padding: 14px 20px; font-size: 11px; border: 1px solid <?php echo $feedback['type'] === 'success' ? '#B2D8B2' : '#E0B4B4'; ?>; background: <?php echo $feedback['type'] === 'success' ? '#EAF4EA' : '#FDF2F2'; ?>; color: <?php echo $feedback['type'] === 'success' ? '#2C5E2C' : '#912D2D'; ?>;">
            <?php echo htmlspecialchars($feedback['text']); ?>
        </div>
    <?php endif; ?>

    <!-- Product Form -->
    <div class="section-box">
        <form method="POST" action="admin_dashboard.php?view=products_add" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_product">

            <div class="form-grid">
                <!-- Title -->
                <div class="field">
                    <label>Product Title *</label>
                    <input type="text" name="title" placeholder="e.g., Santal Royal Eau de Parfum" required>
                </div>

                <!-- Category -->
                <div class="field">
                    <label>Category *</label>
                    <select name="category" required>
                        <option value="">Select Category</option>
                        <option value="fragrances">Fragrances</option>
                        <option value="watches">Watches</option>
                        <option value="wallets">Wallets</option>
                    </select>
                </div>

                <!-- Price -->
                <div class="field">
                    <label>Price (Rs.) *</label>
                    <input type="number" step="0.01" min="0" name="price" placeholder="e.g., 4500.00" required>
                </div>

                <!-- Quantity -->
                <div class="field">
                    <label>Stock Units to Add *</label>
                    <input type="number" min="1" name="quantity" value="10" required>
                </div>

                <!-- Product Image Upload -->
                <div class="field" style="grid-column: span 2;">
                    <label>Product Image (JPG, PNG, WEBP &bull; Max 2MB)</label>
                    <input type="file" name="product_image" accept="image/jpeg,image/png,image/webp">
                </div>

                <!-- Description -->
                <div class="field" style="grid-column: span 2;">
                    <label>Description *</label>
                    <textarea name="description" rows="4" placeholder="Enter detailed sensory profile, specifications, or notes..." required style="background: var(--bg-main); border: 1px solid var(--borders); padding: 12px; font-size: 11px; outline: none; font-family: 'Montserrat', sans-serif; resize: vertical;"></textarea>
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 25px; border-top: 1px solid var(--borders); padding-top: 20px;">
                <span style="font-size: 9px; color: var(--text-secondary); letter-spacing: 0.1em;">
                    * Marked fields are mandatory
                </span>
                <button type="submit" class="btn">Save &amp; Update Inventory</button>
            </div>
        </form>
    </div>
</div>