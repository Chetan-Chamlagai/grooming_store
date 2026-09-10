<?php
// admin/views/dashboard.php - Executive Overview Metrics & Quick Actions Module

// 1. Registered Customers Count
$users_query = $conn->query("SELECT COUNT(*) AS total_users FROM users WHERE role = 'customer'");
$total_users = $users_query->fetch_assoc()['total_users'] ?? 0;

// 2. Total Orders Count
$orders_query = $conn->query("SELECT COUNT(*) AS total_orders FROM orders");
$total_orders = $orders_query->fetch_assoc()['total_orders'] ?? 0;

// 3. Pending Orders Count (Order lifecycle not yet Delivered, or Payment Pending)
$pending_query = $conn->query("SELECT COUNT(*) AS pending_orders FROM orders WHERE order_status != 'Delivered' OR payment_status = 'Pending'");
$pending_orders = $pending_query->fetch_assoc()['pending_orders'] ?? 0;

// 4. Completed Lifetime Revenue
$revenue_query = $conn->query("SELECT SUM(total_amount) AS total_revenue FROM orders WHERE payment_status = 'Completed'");
$total_revenue = $revenue_query->fetch_assoc()['total_revenue'] ?? 0.00;

// 5. Total Categories Count
$cat_query = $conn->query("SELECT COUNT(DISTINCT category) AS total_categories FROM products WHERE (status IS NULL OR status != 'hidden')");
$total_categories = $cat_query->fetch_assoc()['total_categories'] ?? 0;

// 6. Total Active Products Count
$prod_query = $conn->query("SELECT COUNT(*) AS total_products FROM products WHERE (status IS NULL OR status != 'hidden')");
$total_products = $prod_query->fetch_assoc()['total_products'] ?? 0;

// 7. Low Stock Products Count (<= 5 units remaining)
$low_query = $conn->query("SELECT COUNT(*) AS low_stock FROM products WHERE stock_quantity <= 10 AND (status IS NULL OR status != 'hidden')");
$low_stock_count = $low_query->fetch_assoc()['low_stock'] ?? 0;
?>

<div style="display: flex; flex-direction: column; gap: 30px;">
    <div>
        <h1 class="header-title">Executive Overview</h1>
        <p style="font-size: 10px; letter-spacing: 0.25em; text-transform: uppercase; color: var(--text-secondary); margin-top: 6px;">
            Real-time Commercial &amp; Inventory Performance &bull; OGGENTLEME
        </p>
    </div>

    <!-- Metrics Grid (7 Cards in 4-Column Layout) -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
        
        <!-- Metric 1: Registered Users -->
        <div class="card">
            <span class="card-title">Registered Customers</span>
            <span class="card-value"><?php echo number_format($total_users); ?></span>
            <span style="font-size: 8px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--text-secondary); margin-top: 4px;">Verified Accounts</span>
        </div>

        <!-- Metric 2: Total Orders -->
        <div class="card">
            <span class="card-title">Total Orders</span>
            <span class="card-value"><?php echo number_format($total_orders); ?></span>
            <span style="font-size: 8px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--text-secondary); margin-top: 4px;">All Transactions</span>
        </div>

        <!-- Metric 3: Pending Orders -->
        <div class="card" style="<?php echo $pending_orders > 0 ? 'border-color: var(--brand-gold);' : ''; ?>">
            <span class="card-title">Pending Orders</span>
            <span class="card-value" style="<?php echo $pending_orders > 0 ? 'color: var(--brand-gold);' : ''; ?>">
                <?php echo number_format($pending_orders); ?>
            </span>
            <span style="font-size: 8px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--text-secondary); margin-top: 4px;">Awaiting Delivery</span>
        </div>

        <!-- Metric 4: Total Revenue -->
        <div class="card">
            <span class="card-title">Total Revenue</span>
            <span class="card-value" style="color: var(--brand-gold);">Rs. <?php echo number_format($total_revenue, 2); ?></span>
            <span style="font-size: 8px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--text-secondary); margin-top: 4px;">Completed Payments</span>
        </div>

        <!-- Metric 5: Total Categories -->
        <div class="card">
            <span class="card-title">Product Categories</span>
            <span class="card-value"><?php echo number_format($total_categories); ?></span>
            <span style="font-size: 8px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--text-secondary); margin-top: 4px;">Active Segments</span>
        </div>

        <!-- Metric 6: Total Active Products -->
        <div class="card">
            <span class="card-title">Total Catalog Items</span>
            <span class="card-value"><?php echo number_format($total_products); ?></span>
            <span style="font-size: 8px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--text-secondary); margin-top: 4px;">Active Products</span>
        </div>

        <!-- Metric 7: Low Stock Products -->
        <div class="card" style="<?php echo $low_stock_count > 0 ? 'border-color: #9B2C2C;' : ''; ?>">
            <span class="card-title">Low Stock Alerts</span>
            <span class="card-value" style="<?php echo $low_stock_count > 0 ? 'color: #9B2C2C;' : ''; ?>">
                <?php echo number_format($low_stock_count); ?>
            </span>
            <span style="font-size: 8px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--text-secondary); margin-top: 4px;">&le; 10 Units Remaining</span>
        </div>

    </div>

    <!-- Quick Overview Summary Bar -->
    <div class="section-box" style="padding: 25px 35px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <h3 style="border: none; padding: 0; font-size: 18px;">Catalog &amp; Fulfillment Quick Actions</h3>
                <p style="font-size: 10px; color: var(--text-secondary); font-weight: 300;">Manage master product listings and review fulfillment statuses.</p>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="?view=products_add" class="btn" style="text-decoration: none; padding: 10px 18px;">+ Add Product</a>
                <a href="?view=inventory" class="btn" style="text-decoration: none; padding: 10px 18px; background: transparent; color: var(--text-primary); border: 1px solid var(--borders);">Check Inventory</a>
            </div>
        </div>
    </div>
</div>