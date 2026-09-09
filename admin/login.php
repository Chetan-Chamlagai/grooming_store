<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal — OGGENTLEME</title>
    <link rel="icon" type="image/png" href="images/favicon.png">
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
            --dark-section: #111111;
            --gold-on-dark: #B89455;
            --cards: #FFFFFF;
            --borders: #D8C39A;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .admin-login-wrapper {
            width: 100%;
            max-width: 440px;
            background: var(--cards);
            border: 1px solid var(--borders);
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            gap: 30px;
            box-shadow: 0 10px 30px rgba(17, 17, 17, 0.03);
        }

        .admin-login-header {
            text-align: center;
        }

        .admin-logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            font-weight: 400;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--text-primary);
            text-decoration: none;
            display: inline-block;
            margin-bottom: 8px;
        }

        .admin-logo span {
            color: var(--brand-gold);
        }

        .admin-subtitle {
            font-size: 8px;
            font-weight: 400;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            color: var(--text-secondary);
        }

        .admin-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
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
            padding: 14px 16px;
            font-family: 'Montserrat', sans-serif;
            font-size: 11px;
            color: var(--text-primary);
            outline: none;
            transition: border-color 0.3s;
            width: 100%;
        }

        .form-group input:focus {
            border-color: var(--brand-gold);
        }

        .btn-admin-submit {
            padding: 16px;
            background: var(--text-primary);
            color: var(--bg-main);
            font-family: 'Montserrat', sans-serif;
            font-size: 9px;
            font-weight: 400;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            border: 1px solid var(--text-primary);
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }

        .btn-admin-submit:hover {
            background: var(--brand-gold);
            border-color: var(--brand-gold);
            color: #FFFFFF;
        }

        .admin-footer-link {
            text-align: center;
            margin-top: 10px;
        }

        .admin-footer-link a {
            font-size: 9px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.3s;
        }

        .admin-footer-link a:hover {
            color: var(--brand-gold);
        }
    </style>
</head>
<body>

    <div class="admin-login-wrapper">
        <div class="admin-login-header">
            <a href="index.html" class="admin-logo">OGGENTLEME<span>.</span></a>
            <span class="admin-subtitle">Restricted Management Access</span>
        </div>

        <form class="admin-form" action="login-process.php" method="POST">
        <div class="form-group">
            <label for="email">Admin Email</label>
            <input type="email" id="email" name="email"
                placeholder="admin@gmail.com" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn-admin-submit">Login</button>
    </form>

    <?php
    $error = $_GET['error'] ?? '';

    $messages = [
        'empty_fields'        => 'Please enter your email and password.',
        'invalid_email'       => 'Please enter a valid email address.',
        'invalid_credentials' => 'Invalid email or password.',
        'invalid_request'     => 'Invalid request.',
        'server_error'        => 'Something went wrong. Please try again.'
    ];

    if (isset($messages[$error])) {
        echo '<div class="login-error">' .
            htmlspecialchars($messages[$error]) .
            '</div>';
    }
    ?>


        <div class="admin-footer-link">
            <a href="../index.html">&larr; Return to Store</a>
        </div>
    </div>

</body>
</html>