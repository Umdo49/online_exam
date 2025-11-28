<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_or_phone = $_POST['email_or_phone'];
    $password       = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
    $stmt->execute([$email_or_phone, $email_or_phone]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Hatalı giriş bilgileri!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap - Uzaktan Sınav Sistemi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #004e92, #000428);
            background-size: cover;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 100%;
        }
        .login-card h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #004e92;
        }
        .form-label {
            font-weight: 500;
        }
        .form-control {
            border-radius: 8px;
        }
        .btn-primary {
            width: 100%;
            border-radius: 8px;
            font-weight: bold;
        }
        .show-password {
            font-size: 13px;
            color: #004e92;
            text-align: right;
            cursor: pointer;
        }
        .bottom-link {
            text-align: center;
            margin-top: 20px;
        }
        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }
        @media (max-width: 768px) {
            body {
                padding: 20px;
                background: linear-gradient(to bottom, #004e92, #000428);
            }
        }
    </style>
</head>
<body>

<div class="login-card">
    <h2>Giriş Yap</h2>

    <?php if (!empty($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <div class="mb-3">
            <label for="email_or_phone" class="form-label">E-posta veya Telefon</label>
            <input type="text" class="form-control" id="email_or_phone" name="email_or_phone" required>
        </div>

        <div class="mb-2">
            <label for="password" class="form-label">Şifre</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="show-password" onclick="togglePassword()">👁 Şifreyi Göster</div>

        <div class="d-grid mt-3">
            <button type="submit" class="btn btn-primary">Giriş Yap</button>
        </div>
    </form>

    <div class="bottom-link">
        Hesabınız yok mu? <a href="register.php">Kayıt Ol</a>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById("password");
    input.type = input.type === "password" ? "text" : "password";
}
</script>

</body>
</html>
