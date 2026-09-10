<?php
// admin/login.php - Self-Contained Admin Authentication & Form Module
session_start();
require_once '../db.php';

// If already logged in, redirect straight to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin_dashboard.php");
    exit();
}

$error = '';
$email_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_input = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email_input === '' || $password === '') {
        $error = 'Please fill in all required login fields.';
    } elseif (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address format.';
    } else {
        $sql = "SELECT id, full_name, email, password_hash, role FROM users WHERE email = ? AND role = 'admin' LIMIT 1";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $email_input);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $is_valid = password_verify($password, $user['password_hash']) || ($password === $user['password_hash']);

                if ($is_valid) {
                    session_regenerate_id(true);
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['logged_in']       = true;
                    $_SESSION['id']              = $user['id'];
                    $_SESSION['name']            = $user['full_name'];
                    $_SESSION['email']           = $user['email'];
                    $_SESSION['role']            = $user['role'];

                    $stmt->close();
                    $conn->close();

                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    $error = 'Incorrect password entered. Please verify your credentials.';
                }
            } else {
                $error = 'No administrator account found matching this email address.';
            }
            $stmt->close();
        } else {
            $error = 'Database server error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login &bull; Oggentleme</title>
    <style>
        :root {
            --bg-main: #FAF9F6;
            --bg-surface: #FFFFFF;
            --text-primary: #1A1A1A;
            --text-secondary: #767676;
            --borders: #E5E5E0;
            --brand-gold: #C5A059;
        }
        body {
            background-color: var(--bg-main);
            font-family: 'Montserrat', sans-serif;
            color: var(--text-primary);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-box {
            background: var(--bg-surface);
            border: 1px solid var(--borders);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }
        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            font-weight: 400;
            margin-bottom: 8px;
            letter-spacing: 0.05em;
        }
        .subtitle {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.25em;
            color: var(--text-secondary);
            margin-bottom: 30px;
        }
        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 20px;
        }
        label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--text-secondary);
        }
        input {
            background: var(--bg-main);
            border: 1px solid var(--borders);
            padding: 12px;
            font-size: 11px;
            outline: none;
            font-family: 'Montserrat', sans-serif;
            color: var(--text-primary);
        }
        input:focus {
            border-color: var(--brand-gold);
        }
        .btn {
            background: var(--text-primary);
            color: var(--bg-surface);
            border: none;
            padding: 12px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            transition: background 0.2s;
        }
        .btn:hover {
            background: var(--brand-gold);
        }
        .error-banner {
            background: #FDF2F2;
            border: 1px solid #E0B4B4;
            color: #912D2D;
            padding: 12px 15px;
            font-size: 10px;
            margin-bottom: 20px;
            line-height: 1.4;
            letter-spacing: 0.02em;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>Oggentleme</h1>
        <div class="subtitle">Admin Authentication Portal</div>

        <?php if (!empty($error)): ?>
            <div class="error-banner">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="field">
                <label>Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email_input); ?>" required autocomplete="email">
            </div>

            <div class="field">
                <label>Password</label>
                <input type="password" name="password" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn">Sign In to Dashboard</button>
        </form>
    </div>
</body>
</html>