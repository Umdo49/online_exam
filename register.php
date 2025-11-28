<?php
session_start();
require 'db.php';

// Zaten giriş yapıldıysa panele yönlendir
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}

$mesaj = "";
$durum = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    // Temel Doğrulamalar
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($role)) {
        $mesaj = "Lütfen tüm alanları doldurunuz.";
        $durum = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mesaj = "Geçersiz e-posta formatı.";
        $durum = "danger";
    } elseif (strlen($password) < 6) {
        $mesaj = "Şifre en az 6 karakter olmalıdır.";
        $durum = "danger";
    } else {
        // E-posta tekrar kontrolü
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->rowCount() > 0) {
            $mesaj = "Bu e-posta adresi zaten kayıtlı.";
            $durum = "danger";
        } else {
            // Kayıt İşlemi
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$name, $email, $phone, $hashed_password, $role])) {
                $mesaj = "Kayıt başarılı! Giriş sayfasına yönlendiriliyorsunuz...";
                $durum = "success";
                header("refresh:2;url=login.php");
            } else {
                $mesaj = "Kayıt sırasında bir hata oluştu.";
                $durum = "danger";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kayıt Ol - Sınav Portalı</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a, #334155); /* Koyu modern tema */
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .register-card {
            background: rgba(255, 255, 255, 1);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        .register-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 5px;
            background: linear-gradient(90deg, #0d6efd, #0dcaf0);
        }
        .register-header { text-align: center; margin-bottom: 30px; }
        .register-header h2 { font-weight: 700; color: #1e293b; margin: 0; }
        .register-header p { color: #64748b; margin-top: 5px; font-size: 0.95rem; }
        
        .form-label { font-weight: 600; color: #334155; font-size: 0.9rem; }
        .form-control, .form-select {
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            transition: 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
        }

        .input-group-text {
            background-color: #f8fafc; border: 1px solid #e2e8f0; cursor: pointer;
        }

        /* Rol Bilgilendirme Kutusu */
        .role-info {
            display: none; /* Başlangıçta gizli */
            background-color: #eff6ff;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 0.85rem;
            color: #1e40af;
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

        .btn-primary {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            background-color: #0d6efd;
            border: none;
            margin-top: 10px;
            transition: 0.3s;
        }
        .btn-primary:hover { background-color: #0b5ed7; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2); }

        .bottom-link { text-align: center; margin-top: 25px; font-size: 0.9rem; color: #64748b; }
        .bottom-link a { color: #0d6efd; text-decoration: none; font-weight: 600; }
        .bottom-link a:hover { text-decoration: underline; }

        @media (max-width: 576px) {
            body { padding: 20px; align-items: flex-start; overflow-y: auto; }
            .register-card { margin-top: 20px; padding: 25px; }
        }
    </style>
</head>
<body>

<div class="register-card">
    <div class="register-header">
        <h2>Aramıza Katıl</h2>
        <p>Sınav dünyasına adım atmak için kaydolun.</p>
    </div>

    <?php if ($mesaj): ?>
        <div class="alert alert-<?= $durum ?> text-center py-2 border-0 shadow-sm rounded-3 mb-4">
            <?= $mesaj ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Ad Soyad</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-regular fa-user"></i></span>
                <input type="text" class="form-control" id="name" name="name" placeholder="Tam adınız" required>
            </div>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">E-posta Adresi</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                <input type="email" class="form-control" id="email" name="email" placeholder="ornek@mail.com" required>
            </div>
        </div>

        <div class="mb-3">
            <label for="phone" class="form-label">Telefon Numarası</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                <input type="text" class="form-control" id="phone" name="phone" placeholder="05xxxxxxxxx" required>
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Şifre</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" placeholder="Güçlü bir şifre belirleyin" required>
                <span class="input-group-text" onclick="togglePassword()" style="cursor: pointer;"><i class="fa-regular fa-eye" id="eyeIcon"></i></span>
            </div>
        </div>

        <div class="mb-4">
            <label for="role" class="form-label">Hesap Türü</label>
            <select class="form-select" id="role" name="role" required onchange="showRoleInfo()">
                <option value="" selected disabled>Seçiniz...</option>
                <option value="ogrenci">👨‍🎓 Öğrenci</option>
                <option value="ogretmen">👨‍🏫 Öğretmen / Eğitmen</option>
            </select>

            <div id="roleInfoOgrenci" class="role-info">
                <i class="fa-solid fa-circle-info me-2"></i> <strong>Öğrenci Hesabı:</strong> Sınavlara katılabilir, sonuçlarınızı görebilir ve geçmişinizi takip edebilirsiniz. Sınav oluşturamazsınız.
            </div>
            <div id="roleInfoOgretmen" class="role-info">
                <i class="fa-solid fa-circle-info me-2"></i> <strong>Öğretmen Hesabı:</strong> Sınavlar oluşturabilir, soru ekleyebilir, öğrencilerin sonuçlarını değerlendirebilir ve materyal paylaşabilirsiniz.
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Hesap Oluştur</button>
    </form>

    <div class="bottom-link">
        Zaten bir hesabınız var mı? <a href="login.php">Giriş Yap</a>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById("password");
    const icon = document.getElementById("eyeIcon");
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

function showRoleInfo() {
    const role = document.getElementById("role").value;
    const infoOgrenci = document.getElementById("roleInfoOgrenci");
    const infoOgretmen = document.getElementById("roleInfoOgretmen");

    // Hepsini gizle
    infoOgrenci.style.display = "none";
    infoOgretmen.style.display = "none";

    // Seçilene göre göster
    if (role === "ogrenci") {
        infoOgrenci.style.display = "block";
    } else if (role === "ogretmen") {
        infoOgretmen.style.display = "block";
    }
}
</script>

</body>
</html>