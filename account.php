<?php
session_start();
require_once 'db.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


$stmt = $conn->prepare("SELECT full_name, email, phone_number, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// २. Address Details तान्ने
$addr_stmt = $conn->prepare("SELECT address_line, city, province, postal_code FROM user_addresses WHERE user_id = ? LIMIT 1");
$addr_stmt->bind_param("i", $user_id);
$addr_stmt->execute();
$address = $addr_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Oggentleme</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background-color: #f7f5f0; color: #1a1a1a; padding: 40px 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #ffffff; border: 1px solid #e0dace; padding: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1 { font-family: 'Cormorant Garamond', serif; font-size: 32px; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 20px; text-align: center; border-bottom: 1px solid #e0dace; padding-bottom: 15px; }
        .nav-back { display: inline-block; margin-bottom: 25px; color: #1a1a1a; text-decoration: none; font-size: 13px; letter-spacing: 1px; text-transform: uppercase; border-bottom: 1px solid transparent; transition: 0.3s; }
        .nav-back:hover { border-bottom-color: #1a1a1a; }
        .profile-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-top: 20px; }
        .card { background: #faf8f5; border: 1px solid #eee8df; padding: 20px; }
        .card h2 { font-family: 'Cormorant Garamond', serif; font-size: 20px; margin-bottom: 15px; border-bottom: 1px dashed #ccc; padding-bottom: 5px; }
        .info-group { margin-bottom: 12px; }
        .info-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #777; margin-bottom: 3px; }
        .info-value { font-size: 15px; font-weight: 500; color: #222; }
        .badge { display: inline-block; padding: 3px 8px; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; background: #1a1a1a; color: #fff; margin-top: 4px; }
        .actions { margin-top: 30px; text-align: center; }
        .btn-logout { display: inline-block; padding: 12px 30px; background: #8b0000; color: #fff; text-decoration: none; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; transition: 0.3s; }
        .btn-logout:hover { background: #a50000; }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" class="nav-back">&larr; Back to Store</a>
    <h1>My Account Profile</h1>

    <div class="profile-grid">
        <!-- Personal Details -->
        <div class="card">
            <h2>Personal Information</h2>
            <div class="info-group">
                <div class="info-label">Full Name</div>
                <div class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Email Address</div>
                <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Phone Number</div>
                <div class="info-value"><?php echo htmlspecialchars($user['phone_number'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Account Role</div>
                <div class="badge"><?php echo htmlspecialchars($user['role']); ?></div>
            </div>
        </div>

        <!-- Address Details -->
        <div class="card">
            <h2>Primary Address</h2>
            <?php if ($address): ?>
                <div class="info-group">
                    <div class="info-label">Street Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($address['address_line']); ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">City</div>
                    <div class="info-value"><?php echo htmlspecialchars($address['city']); ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">Province / Postal Code</div>
                    <div class="info-value"><?php echo htmlspecialchars($address['province']) . ' - ' . htmlspecialchars($address['postal_code']); ?></div>
                </div>
            <?php else: ?>
                <p style="font-size: 13px; color: #777;">No default address added yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="actions">
        <a href="logout.php" class="btn-logout">Sign Out</a>
    </div>
</div>

</body>
</html>