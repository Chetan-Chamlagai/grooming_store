<?php
// signup.php - Customer Registration Module matching exact database schema
session_start();
require_once 'db.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name    = trim($_POST['full_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $password     = $_POST['password'] ?? ''; // Saved as plaintext per current preference

    if (!empty($full_name) && !empty($email) && !empty($phone_number) && !empty($password)) {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error_message = "An account with this email address already exists.";
        } else {
            $check_stmt->close();

            // Insert into users matching columns: full_name, email, phone_number, password_hash
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone_number, password_hash, role) VALUES (?, ?, ?, ?, 'customer')");
            $stmt->bind_param("ssss", $full_name, $email, $phone_number, $password);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: login.php?registered=success");
                exit();
            } else {
                $error_message = "Registration failed: " . $conn->error;
            }
        }
        $check_stmt->close();
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
    <title>Register &mdash; Oggentleme</title>
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
        
        <h1 class="auth-title">Create Account</h1>
        <span class="auth-subtitle">Join the gentleman's standard</span>

        <?php if (!empty($error_message)): ?>
            <div class="error-banner">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="signup.php">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" placeholder="Rohan Sharma" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone_number" placeholder="98XXXXXXXX" required value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required>
            </div>

            <button type="submit" class="btn-submit">Register Account</button>
        </form>

        <div class="auth-footer-text">
            Already have an account?<a href="login.php">Sign In</a>
        </div>
    </div>

</body>
</html>