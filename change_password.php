<?php
session_start();
require 'db.php';

// Güvenlik: Oturum kontrolü
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$mesaj = "";
$hata = "";

// Mevcut şifre hash'ini çek
$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Şifre doğrulama
    if (!password_verify($current_password, $user['password'])) {
        $hata = "Mevcut şifreniz yanlış!";
    } elseif ($new_password !== $confirm_password) {
        $hata = "Yeni şifreler birbiriyle uyuşmuyor!";
    } elseif (strlen($new_password) < 6) {
        $hata = "Yeni şifre en az 6 karakter olmalı!";
    } else {
        // Yeni şifreyi hashleyip güncelle
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed_password, $user_id])) {
            $mesaj = "Şifreniz başarıyla güncellendi.";
        } else {
            $hata = "Veritabanı hatası oluştu.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Şifre Değiştir</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        
        /* Sidebar */
        .sidebar { width: 260px; height: 100vh; background: #212529; color: #fff; position: fixed; top: 0; left: 0; padding-top: 20px; z-index: 1000; }
        .sidebar-header { padding: 15px 25px; border-bottom: 1px solid #343a40; margin-bottom: 20px; }
        .sidebar a { padding: 12px 25px; text-decoration: none; font-size: 16px; color: #adb5bd; display: block; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { color: #fff; background-color: #0d6efd; border-radius: 0 25px 25px 0; }
        .sidebar i { width: 25px; }

        .main-content { margin-left: 260px; padding: 30px; }

        /* Kart */
        .card-custom { border: none; border-radius: 12px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.03); padding: 40px; max-width: 600px; margin: 0 auto; }
        
        /* Input Grubu (Şifre Göster/Gizle) */
        .input-group-text { background: white; border-left: none; cursor: pointer; color: #6c757d; }
        .form-control { border-right: none; }
        .form-control:focus { border-color: #dee2e6; box-shadow: none; }
        .form-control:focus + .input-group-text { border-color: #dee2e6; color: #0d6efd; }
        .input-group:focus-within { box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1); border-radius: 0.375rem; }
        .input-group:focus-within .form-control, .input-group:focus-within .input-group-text { border-color: #86b7fe; }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header d-flex align-items-center">
            <i class="fa-solid fa-graduation-cap fa-2x me-2 text-primary"></i>
            <h5 class="m-0 fw-bold">Sınav Sis.</h5>
        </div>
        <a href="dashboard.php"><i class="fa-solid fa-house"></i> Ana Sayfa</a>
        
        <?php if($_SESSION['user']['role'] === 'ogrenci'): ?>
            <a href="available_exams.php"><i class="fa-solid fa-pen-to-square"></i> Sınavlara Katıl</a>
            <a href="exam_history.php"><i class="fa-solid fa-clock-rotate-left"></i> Sonuçlarım</a>
        <?php else: ?>
            <a href="create_exam.php"><i class="fa-solid fa-plus-circle"></i> Sınav Oluştur</a>
            <a href="manage_exams.php"><i class="fa-solid fa-list-check"></i> Sınavları Yönet</a>
            <a href="student_results.php"><i class="fa-solid fa-chart-line"></i> Öğrenci Sonuçları</a>
        <?php endif; ?>
        
        <a href="profile_manage.php" class="active"><i class="fa-solid fa-user-gear"></i> Profil Ayarları</a>
        <a href="logout.php" class="text-danger mt-3"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a>
    </div>

    <div class="main-content">
        <div class="container">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-dark m-0">Şifre Değiştir</h2>
                <a href="profile_manage.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Geri Dön</a>
            </div>

            <?php if ($mesaj): ?>
                <div class="alert alert-success border-0 shadow-sm text-center"><i class="fa-solid fa-check-circle me-2"></i> <?= $mesaj ?></div>
            <?php endif; ?>
            
            <?php if ($hata): ?>
                <div class="alert alert-danger border-0 shadow-sm text-center"><i class="fa-solid fa-circle-exclamation me-2"></i> <?= $hata ?></div>
            <?php endif; ?>

            <div class="card card-custom">
                <div class="text-center mb-4">
                    <div class="bg-light d-inline-flex p-3 rounded-circle text-primary mb-3">
                        <i class="fa-solid fa-key fa-2x"></i>
                    </div>
                    <h5 class="fw-bold">Güvenliğinizi Güncelleyin</h5>
                    <p class="text-muted small">Güçlü bir şifre seçtiğinizden emin olun.</p>
                </div>

                <form method="POST">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Mevcut Şifre</label>
                        <div class="input-group">
                            <input type="password" name="current_password" class="form-control" id="currentPass" required>
                            <span class="input-group-text" onclick="togglePass('currentPass', this)"><i class="fa-solid fa-eye"></i></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Yeni Şifre</label>
                        <div class="input-group">
                            <input type="password" name="new_password" class="form-control" id="newPass" required minlength="6">
                            <span class="input-group-text" onclick="togglePass('newPass', this)"><i class="fa-solid fa-eye"></i></span>
                        </div>
                        <div class="form-text small">En az 6 karakter olmalı.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Yeni Şifre (Tekrar)</label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" class="form-control" id="confirmPass" required minlength="6">
                            <span class="input-group-text" onclick="togglePass('confirmPass', this)"><i class="fa-solid fa-eye"></i></span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                        <i class="fa-solid fa-save me-2"></i> Şifreyi Güncelle
                    </button>

                </form>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePass(inputId, iconSpan) {
            const input = document.getElementById(inputId);
            const icon = iconSpan.querySelector('i');
            
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
    </script>

</body>
</html>