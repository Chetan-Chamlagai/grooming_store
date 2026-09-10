<?php
// admin/views/inventory.php - Stock Inventory Monitoring Module

// Fetch all products ordered by lowest stock first to prioritize replenishment alerts
$sql = "SELECT id, photo, title, price, stock_quantity, category FROM products ORDER BY stock_quantity ASC";
$result = $conn->query($sql);
?>

<div style="display: flex; flex-direction: column; gap: 25px;">
    <div>
        <h1 class="header-title">Stock Inventory &amp; Levels</h1>
        <p style="font-size: 10px; letter-spacing: 0.25em; text-transform: uppercase; color: var(--text-secondary); margin-top: 6px;">
            Real-time Warehouse Tracking &bull; 
        </p>
    </div>

    <div class="section-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Picture</th>
                    <th>Product Title</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($item = $result->fetch_assoc()): ?>
                    <?php 
                        $is_low_stock = intval($item['stock_quantity']) <= 5;
                    ?>
                    <tr style="<?php echo $is_low_stock ? 'background: rgba(155, 44, 44, 0.03);' : ''; ?>">
                        <td>#<?php echo $item['id']; ?></td>
                        <td>
                            <?php if (!empty($item['photo'])): ?>
                                <img src="../<?php echo htmlspecialchars($item['photo']); ?>" alt="" style="width: 38px; height: 38px; object-fit: cover; border: 1px solid var(--borders);">
                            <?php else: ?>
                                <span style="font-size: 9px; color: var(--text-secondary);">No Img</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($item['title']); ?></strong></td>
                        <td style="text-transform: uppercase; font-size: 9px; color: var(--text-secondary);"><?php echo htmlspecialchars($item['category']); ?></td>
                        <td>Rs. <?php echo number_format($item['price'], 2); ?></td>
                        <td>
                            <span style="font-weight: 500; <?php echo $is_low_stock ? 'color: #9B2C2C; font-weight: 600;' : ''; ?>">
                                <?php echo $item['stock_quantity']; ?> units
                                <?php if ($is_low_stock): ?>
                                    <span style="font-size: 7px; background: #9B2C2C; color: #fff; padding: 2px 6px; margin-left: 6px; text-transform: uppercase; letter-spacing: 0.1em;">Low Stock</span>
                                <?php endif; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 40px;">No inventory records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>