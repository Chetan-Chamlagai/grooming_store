<?php
session_start();
require_once '../db.php';

// Security Guard
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Determine which module to load from query param
$view = $_GET['view'] ?? 'dashboard';

// Whitelist allowed view modules to prevent directory traversal
$allowed_views = [
    'dashboard',
    'users',
    'user_address',
    'orders',
    'products_add',
    'products_update',
    'products_delete',
    'inquiries',
    'inventory'
];

if (!in_array($view, $allowed_views)) {
    $view = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal — OGGENTLEME</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300&family=Montserrat:wght@200;300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bg-main: #F8F6F1;
            --text-primary: #111111;
            --text-secondary: #555555;
            --brand-gold: #B89455;
            --dark-section: #111111;
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

        /* Fixed Sidebar */
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

        /* Main Dynamic Area */
        main {
            margin-left: 260px;
            flex-grow: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        /* Shared UI Blueprint Elements for Child Views */
        .header-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            font-weight: 300;
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
        .controls-toolbar {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
        }
        .search-wrap {
            display: flex;
            gap: 8px;
            flex-grow: 1;
            max-width: 450px;
        }
        .search-wrap input, .sort-wrap select, .field input, .field select {
            background: var(--bg-main);
            border: 1px solid var(--borders);
            padding: 10px 14px;
            font-family: 'Montserrat', sans-serif;
            font-size: 11px;
            color: var(--text-primary);
            outline: none;
        }
        .search-wrap input { flex-grow: 1; }
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
        .btn {
            padding: 11px 22px;
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
            <li><a href="?view=dashboard" class="<?php if($view=='dashboard') echo 'active'; ?>">Dashboard</a></li>
            <li><a href="?view=users" class="<?php if($view=='users') echo 'active'; ?>">Users</a></li>
            <li><a href="?view=user_address" class="<?php if($view=='user_address') echo 'active'; ?>">User Address</a></li>
            <li><a href="?view=orders" class="<?php if($view=='orders') echo 'active'; ?>">Orders</a></li>
            <li><a href="?view=products_add" class="<?php if(strpos($view, 'products') === 0) echo 'active'; ?>">Products</a></li>
            <li><a href="?view=products_add" class="sidebar-sub <?php if($view=='products_add') echo 'active'; ?>">- Add</a></li>
            <li><a href="?view=products_update" class="sidebar-sub <?php if($view=='products_update') echo 'active'; ?>">- Update</a></li>
            <li><a href="?view=products_delete" class="sidebar-sub <?php if($view=='products_delete') echo 'active'; ?>">- Delete</a></li>
            <li><a href="?view=inquiries" class="<?php if($view=='inquiries') echo 'active'; ?>">Inquiries</a></li>
            <li><a href="?view=inventory" class="<?php if($view=='inventory') echo 'active'; ?>">Inventory</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-link" onclick="return confirm('Are you sure you want to sign out of the admin panel?');">Sign Out</a>
        </div>
    </aside>

    <!-- DYNAMIC MAIN CONTENT AREA -->
    <main>
        <?php
        // Inject selected module view
        $view_file = __DIR__ . '/views/' . $view . '.php';
        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo "<p>Module view not found.</p>";
        }
        ?>
    </main>

</body>
</html>