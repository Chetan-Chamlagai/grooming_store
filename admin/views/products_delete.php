<?php
// admin/views/products_delete.php - Safe Dependency-Aware Deletion Module

$feedback = ['type' => '', 'text' => ''];
$category_filter = $_GET['category'] ?? 'all';

// Handle Deletion Action via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_product') {
    $product_id = intval($_POST['product_id'] ?? 0);

    if ($product_id > 0) {
        // 1. Fetch product title first for feedback message
        $title_stmt = $conn->prepare("SELECT title FROM products WHERE id = ? LIMIT 1");
        $title_stmt->bind_param("i", $product_id);
        $title_stmt->execute();
        $prod_res = $title_stmt->get_result()->fetch_assoc();
        $title_stmt->close();

        $product_title = $prod_res['title'] ?? 'Product';

        // 2. Check if the product is linked to any existing order items first
        $check_stmt = $conn->prepare("SELECT COUNT(*) as total FROM order_items WHERE product_id = ?");
        $check_stmt->bind_param("i", $product_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();

        if ($check_result['total'] > 0) {
            // Product is tied to past orders: Apply soft delete (hide) to protect historical records
            $hide_stmt = $conn->prepare("UPDATE products SET status = 'hidden' WHERE id = ?");
            $hide_stmt->bind_param("i", $product_id);
            
            if ($hide_stmt->execute()) {
                $feedback = [
                    'type' => 'success', 
                    'text' => "Product '{$product_title}' is linked to previous customer orders. It has been hidden from new purchases, but past order records remain fully intact."
                ];
            } else {
                $feedback = ['type' => 'error', 'text' => 'Failed to update product status: ' . $conn->error];
            }
            $hide_stmt->close();
        } else {
            // No past orders use this product: Safe to permanently delete
            $delete_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $delete_stmt->bind_param("i", $product_id);
            
            if ($delete_stmt->execute()) {
                $feedback = [
                    'type' => 'success', 
                    'text' => "Product '{$product_title}' has been permanently deleted from the system."
                ];
            } else {
                $feedback = ['type' => 'error', 'text' => 'Database error during deletion: ' . $conn->error];
            }
            $delete_stmt->close();
        }
    }
}

// Fetch products filtered by category (exclude hidden products)
$sql = "SELECT id, title, category, price, stock_quantity, photo FROM products WHERE (status IS NULL OR status != 'hidden')";
$params = [];
$types = "";

if ($category_filter !== 'all' && !empty($category_filter)) {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

$sql .= " ORDER BY id DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products_result = $stmt->get_result();
$stmt->close();
?>

<div style="display: flex; flex-direction: column; gap: 25px;">
    <div>
        <h1 class="header-title">Product Deletion Catalog</h1>
        <p style="font-size: 10px; letter-spacing: 0.25em; text-transform: uppercase; color: var(--text-secondary); margin-top: 6px;">
            Catalog Management &bull; Safely handles historical order dependencies
        </p>
    </div>

    <!-- Feedback Notification -->
    <?php if (!empty($feedback['text'])): ?>
        <div style="padding: 14px 20px; font-size: 11px; border: 1px solid <?php echo $feedback['type'] === 'success' ? '#B2D8B2' : '#E0B4B4'; ?>; background: <?php echo $feedback['type'] === 'success' ? '#EAF4EA' : '#FDF2F2'; ?>; color: <?php echo $feedback['type'] === 'success' ? '#2C5E2C' : '#912D2D'; ?>;">
            <?php echo htmlspecialchars($feedback['text']); ?>
        </div>
    <?php endif; ?>

    <div class="section-box">
        <!-- Controls Toolbar: Live Typing Search & Category Filter -->
        <div class="controls-toolbar">
            <div class="search-wrap">
                <input type="text" id="liveDeleteSearch" placeholder="Type to instantly filter products by title..." autocomplete="off">
            </div>

            <div class="sort-wrap">
                <form method="GET" action="admin_dashboard.php" id="deleteCategoryForm" style="margin: 0;">
                    <input type="hidden" name="view" value="products_delete">
                    <select name="category" onchange="document.getElementById('deleteCategoryForm').submit()">
                        <option value="all" <?php if($category_filter=='all') echo 'selected'; ?>>Category: All Products</option>
                        <option value="fragrances" <?php if($category_filter=='fragrances') echo 'selected'; ?>>Category: Fragrances</option>
                        <option value="wallets" <?php if($category_filter=='wallets') echo 'selected'; ?>>Category: Wallets</option>
                        <option value="watches" <?php if($category_filter=='watches') echo 'selected'; ?>>Category: Watches</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- Products Table -->
        <table id="deleteProductTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Title</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock Units</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($products_result->num_rows > 0): ?>
                    <?php while($p = $products_result->fetch_assoc()): ?>
                    <tr class="product-delete-row">
                        <td>#<?php echo $p['id']; ?></td>
                        <td><strong class="delete-title-text"><?php echo htmlspecialchars($p['title']); ?></strong></td>
                        <td style="text-transform: uppercase;"><?php echo htmlspecialchars($p['category']); ?></td>
                        <td>Rs. <?php echo number_format($p['price'], 2); ?></td>
                        <td><?php echo $p['stock_quantity']; ?> units</td>
                        <td>
                            <form method="POST" action="admin_dashboard.php?view=products_delete" onsubmit="return confirm('Are you sure you want to delete &quot;<?php echo htmlspecialchars($p['title'], ENT_QUOTES); ?>&quot;?');">
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                <button type="submit" class="btn btn-del" style="background: #9B2C2C; color: #fff; border: none; padding: 4px 8px; font-size: 7px; cursor: pointer;">Delete Product</button>
                            </form>
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
</div>

<!-- Instant Live Search & Typo Filter Script -->
<script>
document.getElementById('liveDeleteSearch')?.addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#deleteProductTable tbody tr.product-delete-row');

    rows.forEach(row => {
        let titleText = row.querySelector('.delete-title-text').textContent.toLowerCase();
        if (titleText.includes(filter)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
});
</script>