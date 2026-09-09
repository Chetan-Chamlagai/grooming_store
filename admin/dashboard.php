<?php
session_start();
require_once '../db.php';

// Security Guard
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.html");
    exit();
}

$message = "";
$view = $_GET['view'] ?? 'dashboard';

// Handle POST actions for Add / Update / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_product') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $category = $_POST['category'];
        $photo = trim($_POST['photo']);
        $stock = intval($_POST['stock_quantity']);

        $stmt = $conn->prepare("INSERT INTO products (title, description, price, category, photo, stock_quantity) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdssi", $title, $description, $price, $category, $photo, $stock);
        if ($stmt->execute()) {
            $message = "Product added successfully.";
        }
        $stmt->close();
    } elseif ($action === 'update_product') {
        $id = intval($_POST['product_id']);
        $title = trim($_POST['title']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock_quantity']);

        $stmt = $conn->prepare("UPDATE products SET title = ?, price = ?, stock_quantity = ? WHERE id = ?");
        $stmt->bind_param("sdii", $title, $price, $stock, $id);
        if ($stmt->execute()) {
            $message = "Product updated successfully.";
        }
        $stmt->close();
    } elseif ($action === 'delete_product') {
        $id = intval($_POST['product_id']);
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Product deleted successfully.";
        }
        $stmt->close();
    } elseif ($action === 'update_order_status') {
        $order_id = intval($_POST['order_id']);
        $status = $_POST['order_status'];
        $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $order_id);
        if ($stmt->execute()) {
            $message = "Order status updated.";
        }
        $stmt->close();
    }
}

// Fetch metrics for Dashboard view
$users_count = $conn->query("SELECT COUNT(*) AS cnt FROM users")->fetch_assoc()['cnt'] ?? 0;
$orders_count = $conn->query("SELECT COUNT(*) AS cnt FROM orders")->fetch_assoc()['cnt'] ?? 0;
$revenue = $conn->query("SELECT SUM(total_amount) AS rev FROM orders WHERE payment_status = 'Completed'")->fetch_assoc()['rev'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — OGGENTLEME</title>
    <link rel="shortcut icon" href="../images/favicon.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300&family=Montserrat:wght@200;300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bg-main: #F8F6F1;
            --text-primary: #111111;
            --text-secondary: #555555;
            --brand-gold: #B89455;
            --dark-section: #111111;
            --gold-on-dark: #B89455;
            --cards: #FFFFFF;
            --borders: #D8C39A;
        }
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-primary);
            display: flex;
            min-height: 100vh;
        }
        /* Sidebar Layout */
        aside {
            width: 260px;
            background: var(--cards);
            border-right: 1px solid var(--borders);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; bottom: 0; left: 0;
            z-index: 100;
        }
        .sidebar-brand {
            padding: 30px;
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            border-bottom: 1px solid var(--borders);
        }
        .sidebar-brand span { color: var(--brand-gold); }
        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex-grow: 1;
        }
        .sidebar-menu a {
            display: block;
            padding: 12px 30px;
            font-size: 9px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            color: var(--text-primary);
            background: rgba(184, 148, 85, 0.08);
            border-left: 3px solid var(--brand-gold);
        }
        .sidebar-sub {
            padding-left: 45px !important;
            font-size: 8px !important;
            letter-spacing: 0.25em !important;
        }
        .sidebar-footer {
            padding: 20px 30px;
            border-top: 1px solid var(--borders);
        }
        .logout-link {
            font-size: 9px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: #9B2C2C;
            text-decoration: none;
        }

        /* Main Content Area */
        main {
            margin-left: 260px;
            flex-grow: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        .header-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            font-weight: 300;
            color: var(--text-primary);
        }
        .alert {
            background: #EAF4EA;
            border: 1px solid #B2D8B2;
            color: #2C5E2C;
            padding: 12px 18px;
            font-size: 11px;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .card {
            background: var(--cards);
            border: 1px solid var(--borders);
            padding: 30px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .card-title {
            font-size: 8px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--text-secondary);
        }
        .card-value {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            font-weight: 400;
            color: var(--text-primary);
        }
        .section-box {
            background: var(--cards);
            border: 1px solid var(--borders);
            padding: 35px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .section-box h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 400;
            border-bottom: 1px solid var(--borders);
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        th, td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--borders);
            text-align: left;
        }
        th {
            font-size: 8px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--text-secondary);
            background: #FAF8F5;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .field label {
            font-size: 8px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--text-secondary);
        }
        .field input, .field select {
            background: var(--bg-main);
            border: 1px solid var(--borders);
            padding: 12px;
            font-size: 11px;
            outline: none;
            font-family: 'Montserrat', sans-serif;
        }
        .btn {
            padding: 12px 24px;
            background: var(--text-primary);
            color: #fff;
            font-size: 9px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover { background: var(--brand-gold); }
        .btn-del { background: #9B2C2C; padding: 6px 12px; font-size: 7px; }
        .btn-del:hover { background: #7A1F1F; }

        @media (max-width: 1024px) {
            aside { width: 200px; }
            main { margin-left: 200px; padding: 30px; }
            .metrics-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <aside>
        <div class="sidebar-brand">OGGENTLEME<span>.</span></div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php?view=dashboard" class="<?php if($view=='dashboard') echo 'active'; ?>">Dashboard</a></li>
            <li><a href="admin_dashboard.php?view=users" class="<?php if($view=='users') echo 'active'; ?>">Users</a></li>
            <li><a href="admin_dashboard.php?view=orders" class="<?php if($view=='orders') echo 'active'; ?>">Orders</a></li>
            <li><a href="admin_dashboard.php?view=products_add" class="<?php if($view=='products_add') echo 'active'; ?>">Products</a></li>
            <li><a href="admin_dashboard.php?view=products_add" class="sidebar-sub <?php if($view=='products_add') echo 'active'; ?>">- Add</a></li>
            <li><a href="admin_dashboard.php?view=products_update" class="sidebar-sub <?php if($view=='products_update') echo 'active'; ?>">- Update</a></li>
            <li><a href="admin_dashboard.php?view=products_delete" class="sidebar-sub <?php if($view=='products_delete') echo 'active'; ?>">- Delete</a></li>
            <li><a href="admin_dashboard.php?view=inquires" class="<?php if($view=='inquires') echo 'active'; ?>">Inquires</a></li>
            <li><a href="admin_dashboard.php?view=inventory" class="<?php if($view=='inventory') echo 'active'; ?>">Inventory</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-link">Sign Out</a>
        </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <main>
        <?php if (!empty($message)): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($view === 'dashboard'): ?>
            <h1 class="header-title">Dashboard Overview</h1>
            <div class="metrics-grid">
                <div class="card">
                    <span class="card-title">Registered Users</span>
                    <span class="card-value"><?php echo intval($users_count); ?></span>
                </div>
                <div class="card">
                    <span class="card-title">Total Orders</span>
                    <span class="card-value"><?php echo intval($orders_count); ?></span>
                </div>
                <div class="card">
                    <span class="card-title">Total Revenue</span>
                    <span class="card-value">Rs. <?php echo number_format($revenue, 2); ?></span>
                </div>
            </div>

        <?php elseif ($view === 'users'): ?>
            <h1 class="header-title">Customer Users</h1>
            <div class="section-box">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $res = $conn->query("SELECT * FROM users ORDER BY id DESC");
                        while($u = $res->fetch_assoc()): 
                        ?>
                        <tr>
                            <td>#<?php echo $u['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo htmlspecialchars($u['phone_number']); ?></td>
                            <td style="text-transform:uppercase;"><?php echo $u['role']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($view === 'orders'): ?>
            <h1 class="header-title">Customer Orders</h1>
            <div class="section-box">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total Amount</th>
                            <th>Payment Status</th>
                            <th>Order Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $res = $conn->query("SELECT o.*, u.full_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.id DESC");
                        while($o = $res->fetch_assoc()): 
                        ?>
                        <tr>
                            <td>#<?php echo $o['id']; ?></td>
                            <td><?php echo htmlspecialchars($o['full_name']); ?></td>
                            <td>Rs. <?php echo number_format($o['total_amount'], 2); ?></td>
                            <td><?php echo $o['payment_status']; ?></td>
                            <td><strong><?php echo $o['order_status']; ?></strong></td>
                            <td>
                                <form method="POST" style="display:flex; gap:6px;">
                                    <input type="hidden" name="action" value="update_order_status">
                                    <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                    <select name="order_status" style="font-size:8px; padding:4px;">
                                        <option value="Created">Created</option>
                                        <option value="Picked Up">Picked Up</option>
                                        <option value="On the Way">On the Way</option>
                                        <option value="Delivered">Delivered</option>
                                    </select>
                                    <button type="submit" class="btn" style="padding:4px 8px; font-size:7px;">Set</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($view === 'products_add'): ?>
            <h1 class="header-title">Add New Product</h1>
            <div class="section-box">
                <form method="POST">
                    <input type="hidden" name="action" value="add_product">
                    <div class="form-grid">
                        <div class="field">
                            <label>Title</label>
                            <input type="text" name="title" required>
                        </div>
                        <div class="field">
                            <label>Category</label>
                            <select name="category">
                                <option value="fragrances">Fragrances</option>
                                <option value="watches">Watches</option>
                                <option value="wallets">Wallets</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Price (Rs.)</label>
                            <input type="number" step="0.01" name="price" required>
                        </div>
                        <div class="field">
                            <label>Stock Quantity</label>
                            <input type="number" name="stock_quantity" value="10" required>
                        </div>
                        <div class="field" style="grid-column: span 2;">
                            <label>Description</label>
                            <input type="text" name="description" required>
                        </div>
                        <div class="field" style="grid-column: span 2;">
                            <label>Photo Path</label>
                            <input type="text" name="photo" value="images/item.jpg" required>
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn">Save Product</button>
                    </div>
                </form>
            </div>

        <?php elseif ($view === 'products_update'): ?>
            <h1 class="header-title">Update Products</h1>
            <div class="section-box">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $res = $conn->query("SELECT * FROM products ORDER BY id DESC");
                        while($p = $res->fetch_assoc()): 
                        ?>
                        <tr>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_product">
                                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                <td>#<?php echo $p['id']; ?></td>
                                <td><input type="text" name="title" value="<?php echo htmlspecialchars($p['title']); ?>" style="padding:6px; font-size:10px;"></td>
                                <td><input type="number" step="0.01" name="price" value="<?php echo $p['price']; ?>" style="padding:6px; font-size:10px; width:90px;"></td>
                                <td><input type="number" name="stock_quantity" value="<?php echo $p['stock_quantity']; ?>" style="padding:6px; font-size:10px; width:70px;"></td>
                                <td><button type="submit" class="btn" style="padding:6px 12px; font-size:7px;">Update</button></td>
                            </form>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($view === 'products_delete'): ?>
            <h1 class="header-title">Delete Products</h1>
            <div class="section-box">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $res = $conn->query("SELECT * FROM products ORDER BY id DESC");
                        while($p = $res->fetch_assoc()): 
                        ?>
                        <tr>
                            <td>#<?php echo $p['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($p['title']); ?></strong></td>
                            <td style="text-transform:uppercase;"><?php echo $p['category']; ?></td>
                            <td>Rs. <?php echo number_format($p['price'], 2); ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Delete this product?');">
                                    <input type="hidden" name="action" value="delete_product">
                                    <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" class="btn btn-del">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($view === 'inquires'): ?>
            <h1 class="header-title">Customer Inquiries</h1>
            <div class="section-box">
                <p style="font-size: 11px; color: var(--text-secondary);">Customer inquiries and contact messages submitted from the storefront will appear here.</p>
                <!-- Placeholder table if inquiry table is used later -->
                <table>
                    <thead>
                        <tr>
                            <th>Sender</th>
                            <th>Email</th>
                            <th>Message</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4" style="text-align:center; color: var(--text-secondary); padding: 30px;">No new inquiries recorded.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        <?php elseif ($view === 'inventory'): ?>
            <h1 class="header-title">Stock Inventory</h1>
            <div class="section-box">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Title</th>
                            <th>Category</th>
                            <th>Remaining Stock Units</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $res = $conn->query("SELECT * FROM products ORDER BY stock_quantity ASC");
                        while($p = $res->fetch_assoc()): 
                        ?>
                        <tr>
                            <td>#<?php echo $p['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($p['title']); ?></strong></td>
                            <td style="text-transform:uppercase;"><?php echo $p['category']; ?></td>
                            <td>
                                <span style="font-weight: 500; color: <?php echo $p['stock_quantity'] < 5 ? '#9B2C2C' : 'inherit'; ?>;">
                                    <?php echo $p['stock_quantity']; ?> units
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>