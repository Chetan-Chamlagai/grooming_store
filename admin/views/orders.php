<?php
// admin/views/orders.php - Order Fulfillment & Details Module

$feedback = ['type' => '', 'text' => ''];
$order_status_filter = $_GET['status'] ?? 'all';
$selected_order_id = intval($_GET['order_id'] ?? 0);

// Handle Order Status Update via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order_status') {
    $target_order_id = intval($_POST['order_id'] ?? 0);
    $new_order_status = trim($_POST['order_status'] ?? '');
    $new_payment_status = trim($_POST['payment_status'] ?? '');

    if ($target_order_id > 0 && !empty($new_order_status) && !empty($new_payment_status)) {
        $update_stmt = $conn->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?");
        $update_stmt->bind_param("ssi", $new_order_status, $new_payment_status, $target_order_id);

        if ($update_stmt->execute()) {
            $feedback = ['type' => 'success', 'text' => "Order #{$target_order_id} fulfillment statuses updated successfully."];
        } else {
            $feedback = ['type' => 'error', 'text' => 'Database error during status update: ' . $conn->error];
        }
        $update_stmt->close();
    }
}

// Fetch single order details & associated items if an order is clicked
$selected_order = null;
$order_items = null;
if ($selected_order_id > 0) {
    // Fetch order header info along with customer data
    $ord_stmt = $conn->prepare("
        SELECT o.*, u.full_name, u.email, u.phone_number 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ? LIMIT 1
    ");
    $ord_stmt->bind_param("i", $selected_order_id);
    $ord_stmt->execute();
    $selected_order = $ord_stmt->get_result()->fetch_assoc();
    $ord_stmt->close();

    // Fetch items belonging to this order
    $item_stmt = $conn->prepare("
        SELECT oi.*, p.title, p.photo 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $item_stmt->bind_param("i", $selected_order_id);
    $item_stmt->execute();
    $order_items = $item_stmt->get_result();
    $item_stmt->close();
}

// Fetch all orders for the main directory table
$sql = "SELECT o.id, o.total_amount, o.order_status, o.payment_status, o.created_at, u.full_name 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE 1=1";
$params = [];
$types = "";

if ($order_status_filter !== 'all' && !empty($order_status_filter)) {
    $sql .= " AND o.order_status = ?";
    $params[] = $order_status_filter;
    $types .= "s";
}

$sql .= " ORDER BY o.created_at DESC";
$list_stmt = $conn->prepare($sql);
if (!empty($params)) {
    $list_stmt->bind_param($types, ...$params);
}
$list_stmt->execute();
$orders_result = $list_stmt->get_result();
$list_stmt->close();
?>

<div style="display: flex; flex-direction: column; gap: 25px;">
    <div>
        <h1 class="header-title">Fulfillment &amp; Orders Management</h1>
        <p style="font-size: 10px; letter-spacing: 0.25em; text-transform: uppercase; color: var(--text-secondary); margin-top: 6px;">
            Transactions &bull; Review customer orders, line items, prices, and status updates
        </p>
    </div>

    <!-- Alert Notifications -->
    <?php if (!empty($feedback['text'])): ?>
        <div style="padding: 14px 20px; font-size: 11px; border: 1px solid <?php echo $feedback['type'] === 'success' ? '#B2D8B2' : '#E0B4B4'; ?>; background: <?php echo $feedback['type'] === 'success' ? '#EAF4EA' : '#FDF2F2'; ?>; color: <?php echo $feedback['type'] === 'success' ? '#2C5E2C' : '#912D2D'; ?>;">
            <?php echo htmlspecialchars($feedback['text']); ?>
        </div>
    <?php endif; ?>

    <?php if ($selected_order): ?>
        <!-- DETAILED ORDER VIEW & LINE ITEMS -->
        <div class="section-box" style="display: flex; flex-direction: column; gap: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--borders); padding-bottom: 15px;">
                <div>
                    <h3 style="border:none; padding:0; font-size: 20px;">Order #<?php echo $selected_order['id']; ?> Details</h3>
                    <p style="font-size: 9px; color: var(--text-secondary); margin-top: 4px;">Placed on <?php echo date('Y/m/d H:i:s', strtotime($selected_order['created_at'])); ?></p>
                </div>
                <a href="admin_dashboard.php?view=orders" class="btn" style="text-decoration: none; background: transparent; color: var(--text-primary); border: 1px solid var(--borders); padding: 8px 14px; font-size: 8px;">&larr; Back to Orders List</a>
            </div>

            <!-- Customer & Status Summary Grid -->
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; background: var(--bg-main); padding: 20px; border: 1px solid var(--borders);">
                <div>
                    <h4 style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: var(--text-secondary); margin-bottom: 8px;">Customer Information</h4>
                    <p style="font-size: 12px; font-weight: 600; margin-bottom: 4px;"><?php echo htmlspecialchars($selected_order['full_name'] ?? 'Guest Customer'); ?></p>
                    <p style="font-size: 11px; color: var(--text-secondary); margin-bottom: 4px;">Email: <?php echo htmlspecialchars($selected_order['email'] ?? 'N/A'); ?></p>
                    <p style="font-size: 11px; color: var(--text-secondary);">Phone: <?php echo htmlspecialchars($selected_order['phone_number'] ?? 'N/A'); ?></p>
                </div>

                <div>
                    <h4 style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: var(--text-secondary); margin-bottom: 8px;">Update Fulfillment Status</h4>
                    <form method="POST" action="admin_dashboard.php?view=orders&order_id=<?php echo $selected_order['id']; ?>" style="display: flex; flex-direction: column; gap: 10px; margin: 0;">
                        <input type="hidden" name="action" value="update_order_status">
                        <input type="hidden" name="order_id" value="<?php echo $selected_order['id']; ?>">

                        <div style="display: flex; gap: 10px;">
                            <select name="order_status" style="flex: 1; padding: 8px; font-size: 10px; background: var(--bg-surface); border: 1px solid var(--borders);">
                                <option value="Pending" <?php if($selected_order['order_status']=='Pending') echo 'selected'; ?>>Order: Pending</option>
                                <option value="Processing" <?php if($selected_order['order_status']=='Processing') echo 'selected'; ?>>Order: Processing</option>
                                <option value="Shipped" <?php if($selected_order['order_status']=='Shipped') echo 'selected'; ?>>Order: Shipped</option>
                                <option value="Delivered" <?php if($selected_order['order_status']=='Delivered') echo 'selected'; ?>>Order: Delivered</option>
                                <option value="Cancelled" <?php if($selected_order['order_status']=='Cancelled') echo 'selected'; ?>>Order: Cancelled</option>
                            </select>

                            <select name="payment_status" style="flex: 1; padding: 8px; font-size: 10px; background: var(--bg-surface); border: 1px solid var(--borders);">
                                <option value="Pending" <?php if($selected_order['payment_status']=='Pending') echo 'selected'; ?>>Payment: Pending</option>
                                <option value="Completed" <?php if($selected_order['payment_status']=='Completed') echo 'selected'; ?>>Payment: Completed</option>
                                <option value="Failed" <?php if($selected_order['payment_status']=='Failed') echo 'selected'; ?>>Payment: Failed</option>
                            </select>

                            <button type="submit" class="btn" style="padding: 8px 14px; font-size: 8px;">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Ordered Product Line Items Table -->
            <div>
                <h4 style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: var(--text-secondary); margin-bottom: 12px;">Ordered Line Items &amp; Pricing</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Picture</th>
                            <th>Product Title</th>
                            <th>Unit Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($order_items && $order_items->num_rows > 0): ?>
                            <?php while($item = $order_items->fetch_assoc()): ?>
                            <?php 
                                $unit_price = floatval($item['price']);
                                $qty = intval($item['quantity']);
                                $line_total = $unit_price * $qty;
                            ?>
                            <tr>
                                <td>
                                    <?php if (!empty($item['photo'])): ?>
                                        <img src="../<?php echo htmlspecialchars($item['photo']); ?>" alt="" style="width: 38px; height: 38px; object-fit: cover; border: 1px solid var(--borders);">
                                    <?php else: ?>
                                        <span style="font-size: 9px; color: var(--text-secondary);">No Img</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($item['title'] ?? 'Custom/Deleted Item'); ?></strong></td>
                                <td>Rs. <?php echo number_format($unit_price, 2); ?></td>
                                <td><?php echo $qty; ?> units</td>
                                <td><strong>Rs. <?php echo number_format($line_total, 2); ?></strong></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 30px;">No items found associated with this order.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Total Order Amount Footer -->
            <div style="display: flex; justify-content: flex-end; align-items: center; border-top: 1px solid var(--borders); padding-top: 15px;">
                <div style="text-align: right;">
                    <span style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.15em; color: var(--text-secondary);">Grand Total: </span>
                    <span style="font-size: 18px; font-weight: 600; color: var(--brand-gold); margin-left: 10px;">Rs. <?php echo number_format($selected_order['total_amount'], 2); ?></span>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- MAIN ORDERS DIRECTORY TABLE -->
        <div class="section-box">
            <div class="controls-toolbar">
                <div class="search-wrap">
                    <input type="text" id="liveOrderSearch" placeholder="Type to instantly search customer name or order ID..." autocomplete="off">
                </div>

                <div class="sort-wrap">
                    <form method="GET" action="admin_dashboard.php" id="orderFilterForm" style="margin: 0;">
                        <input type="hidden" name="view" value="orders">
                        <select name="status" onchange="document.getElementById('orderFilterForm').submit()">
                            <option value="all" <?php if($order_status_filter=='all') echo 'selected'; ?>>Status: All Orders</option>
                            <option value="Pending" <?php if($order_status_filter=='Pending') echo 'selected'; ?>>Status: Pending</option>
                            <option value="Processing" <?php if($order_status_filter=='Processing') echo 'selected'; ?>>Status: Processing</option>
                            <option value="Shipped" <?php if($order_status_filter=='Shipped') echo 'selected'; ?>>Status: Shipped</option>
                            <option value="Delivered" <?php if($order_status_filter=='Delivered') echo 'selected'; ?>>Status: Delivered</option>
                            <option value="Cancelled" <?php if($order_status_filter=='Cancelled') echo 'selected'; ?>>Status: Cancelled</option>
                        </select>
                    </form>
                </div>
            </div>

            <table id="orderTable">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th>Total Amount</th>
                        <th>Payment Status</th>
                        <th>Order Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                        <?php while($ord = $orders_result->fetch_assoc()): ?>
                        <tr class="order-row">
                            <td>#<?php echo $ord['id']; ?></td>
                            <td><strong class="order-customer"><?php echo htmlspecialchars($ord['full_name'] ?? 'Guest Customer'); ?></strong></td>
                            <td>Rs. <?php echo number_format($ord['total_amount'], 2); ?></td>
                            <td>
                                <span style="font-size: 8px; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 600; color: <?php echo $ord['payment_status'] === 'Completed' ? '#2C5E2C' : '#9B2C2C'; ?>;">
                                    <?php echo htmlspecialchars($ord['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <span style="display: inline-block; padding: 3px 8px; font-size: 8px; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 600; background: <?php echo $ord['order_status'] === 'Delivered' ? '#EAF4EA' : '#FAF9F6'; ?>; border: 1px solid var(--borders); color: var(--text-primary);">
                                    <?php echo htmlspecialchars($ord['order_status']); ?>
                                </span>
                            </td>
                            <td style="font-size: 10px; color: var(--text-secondary); white-space: nowrap;">
                                <?php echo date('Y/m/d', strtotime($ord['created_at'])); ?>
                            </td>
                            <td>
                                <a href="admin_dashboard.php?view=orders&order_id=<?php echo $ord['id']; ?>" class="btn" style="text-decoration: none; padding: 6px 12px; font-size: 7px;">View Details</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 40px;">No orders found matching this status filter.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Instant Live Search Script -->
<script>
document.getElementById('liveOrderSearch')?.addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#orderTable tbody tr.order-row');

    rows.forEach(row => {
        let content = row.textContent.toLowerCase();
        if (content.includes(filter)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
});
</script>