<?php
// login.php - Customer & Admin Detection Routing Module
session_start();
require_once 'db.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, full_name, email, password_hash, role FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($password === $user['password_hash']) {
                // Check if user is an admin
                if ($user['role'] === 'admin') {
                    // Store credentials temporarily in session to prefill the admin login portal
                    $_SESSION['admin_prefill_email'] = $email;
                    $_SESSION['admin_prefill_password'] = $password;
                    
                    header("Location: admin/login.php");
                    exit();
                } else {
                    // Standard customer login flow
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];

                    header("Location: index.php");
                    exit();
                }
            } else {
                $error_message = "Invalid email or password. Please try again.";
            }
        } else {
            $error_message = "No account found associated with this email address.";
        }
        $stmt->close();
    } else {
        $error_message = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In &mdash; Oggentleme</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300&family=Montserrat:wght@200;300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-main: #F8F6F1;
            --text-primary: #111111;
            --text-secondary: #555555;
            --brand-gold: #B89455;
            --cards: #FFFFFF;
            --borders: #D8C39A;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .auth-container {
            background: var(--cards);
            border: 1px solid var(--borders);
            width: 100%;
            max-width: 440px;
            padding: 50px 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        }

        .auth-logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            text-align: center;
            display: block;
            margin-bottom: 30px;
            color: var(--text-primary);
            text-decoration: none;
        }

        .auth-logo span { color: var(--brand-gold); }

        .auth-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 32px;
            font-weight: 300;
            text-align: center;
            margin-bottom: 8px;
        }

        .auth-subtitle {
            font-size: 9px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--text-secondary);
            text-align: center;
            margin-bottom: 35px;
            display: block;
        }

        .error-banner {
            background: #FDF2F2;
            border: 1px solid #E0B4B4;
            color: #912D2D;
            padding: 12px 16px;
            font-size: 10px;
            margin-bottom: 25px;
            letter-spacing: 0.05em;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 20px;
        }

        .form-group label {
            font-size: 8px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--text-secondary);
        }

        .form-group input {
            background: var(--bg-main);
            border: 1px solid var(--borders);
            padding: 12px 16px;
            font-family: 'Montserrat', sans-serif;
            font-size: 11px;
            color: var(--text-primary);
            outline: none;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            border-color: var(--brand-gold);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: var(--text-primary);
            color: #FFFFFF;
            border: 1px solid var(--text-primary);
            font-size: 9px;
            letter-spacing: 0.35em;
            text-transform: uppercase;
            font-family: 'Montserrat', sans-serif;
            cursor: pointer;
            transition: background 0.3s, border-color 0.3s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: var(--brand-gold);
            border-color: var(--brand-gold);
        }

        .auth-footer-text {
            text-align: center;
            margin-top: 30px;
            font-size: 10px;
            color: var(--text-secondary);
            letter-spacing: 0.05em;
        }

        .auth-footer-text a {
            color: var(--brand-gold);
            text-decoration: none;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-size: 9px;
            font-weight: 500;
            margin-left: 5px;
        }

        .auth-footer-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <a href="index.php" class="auth-logo">Oggentleme<span>.</span></a>
        
        <h1 class="auth-title">Welcome Back</h1>
        <span class="auth-subtitle">Sign in to your account</span>

        <?php if (!empty($error_message)): ?>
            <div class="error-banner">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required>
            </div>

            <button type="submit" class="btn-submit">Sign In</button>
        </form>

        <div class="auth-footer-text">
            New to Oggentleme?<a href="signup.php">Register a user</a>
        </div>
    </div>

</body>
</html>