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

// Kullanıcı bilgilerini çek
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Profil Resmi Yolu
$profil_resmi = !empty($user['profile_picture']) ? $user['profile_picture'] : 'uploads/default_student.png'; // Varsayılan resim

// FORM GÖNDERİMİ (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $bio = trim($_POST['bio']); // Biyografi alanı da ekleyelim, veritabanında varsa

    if (empty($name) || empty($email)) {
        $hata = "Ad Soyad ve E-posta alanları zorunludur.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $hata = "Geçersiz e-posta formatı.";
    } else {
        
        // 1. Profil Resmi Yükleme
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $uploads_dir = 'uploads/profiles/';
                if (!is_dir($uploads_dir)) mkdir($uploads_dir, 0755, true);
                
                $new_name = "pp_" . uniqid() . "." . $ext;
                $target = $uploads_dir . $new_name;
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target)) {
                    // Eski resmi sil (default değilse)
                    if (!empty($user['profile_picture']) && file_exists($user['profile_picture']) && strpos($user['profile_picture'], 'default') === false) {
                        unlink($user['profile_picture']);
                    }
                    $profil_resmi = $target; // Yeni yolu ata
                } else {
                    $hata = "Resim yüklenirken hata oluştu.";
                }
            } else {
                $hata = "Sadece JPG ve PNG dosyaları yüklenebilir.";
            }
        }

        // 2. Veritabanı Güncelleme
        if (empty($hata)) {
            // bio sütunu veritabanında yoksa SQL'den çıkarabilirsiniz.
            $sql = "UPDATE users SET name = ?, email = ?, phone = ?, profile_picture = ? WHERE id = ?";
            $update_stmt = $pdo->prepare($sql);
            
            if ($update_stmt->execute([$name, $email, $phone, $profil_resmi, $user_id])) {
                $mesaj = "Profil başarıyla güncellendi.";
                // Session güncelle
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['profile_picture'] = $profil_resmi;
                // Bilgileri tazele
                $user['name'] = $name;
                $user['email'] = $email;
                $user['phone'] = $phone;
                $user['profile_picture'] = $profil_resmi;
            } else {
                $hata = "Veritabanı hatası.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Profil Ayarları</title>
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

        /* Profil Kartı */
        .card-custom { border: none; border-radius: 12px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.03); padding: 30px; }
        
        .profile-img-container {
            width: 150px; height: 150px; margin: 0 auto 20px; position: relative;
        }
        .profile-img {
            width: 100%; height: 100%; object-fit: cover; border-radius: 50%;
            border: 4px solid #f8f9fa; box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .upload-icon {
            position: absolute; bottom: 5px; right: 5px;
            background: #0d6efd; color: white; width: 35px; height: 35px;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: 0.2s;
        }
        .upload-icon:hover { transform: scale(1.1); }

        .form-control { padding: 10px 15px; border-radius: 8px; }
        .form-label { font-weight: 500; color: #495057; }

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
        
        <?php if($user['role'] === 'ogrenci'): ?>
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
                <h2 class="fw-bold text-dark m-0">Profilim</h2>
                <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Geri Dön</a>
            </div>

            <?php if ($mesaj): ?>
                <div class="alert alert-success border-0 shadow-sm"><i class="fa-solid fa-check-circle me-2"></i> <?= $mesaj ?></div>
            <?php endif; ?>
            <?php if ($hata): ?>
                <div class="alert alert-danger border-0 shadow-sm"><i class="fa-solid fa-circle-exclamation me-2"></i> <?= $hata ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card card-custom h-100">
                        <h5 class="fw-bold mb-4 border-bottom pb-3">Kişisel Bilgiler</h5>
                        <form method="POST" enctype="multipart/form-data">
                            
                            <div class="row g-3">
                                <input type="file" name="profile_picture" id="fileInput" class="d-none" onchange="previewImage(this)">

                                <div class="col-md-6">
                                    <label class="form-label">Ad Soyad</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">E-Posta</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Telefon</label>
                                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Kullanıcı Rolü</label>
                                    <input type="text" class="form-control bg-light" value="<?= ucfirst($user['role']) ?>" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Biyografi (Opsiyonel)</label>
                                    <textarea name="bio" class="form-control" rows="3"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-primary px-4 fw-bold">
                                    <i class="fa-solid fa-save me-2"></i> Değişiklikleri Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-custom text-center mb-4">
                        <div class="profile-img-container">
                            <img src="<?= $profil_resmi ?>" class="profile-img" id="profilePreview">
                            <label for="fileInput" class="upload-icon" title="Fotoğrafı Değiştir">
                                <i class="fa-solid fa-camera"></i>
                            </label>
                        </div>
                        <h5 class="fw-bold"><?= htmlspecialchars($user['name']) ?></h5>
                        <p class="text-muted small mb-2"><?= htmlspecialchars($user['email']) ?></p>
                        <span class="badge bg-primary rounded-pill px-3"><?= ucfirst($user['role']) ?></span>
                        <div class="mt-3 text-muted small">
                            <i class="fa-regular fa-calendar me-1"></i> Kayıt: <?= date('d.m.Y', strtotime($user['created_at'])) ?>
                        </div>
                    </div>

                    <div class="card card-custom text-center p-3">
                        <i class="fa-solid fa-lock fa-2x text-warning mb-2"></i>
                        <h6 class="fw-bold">Güvenlik</h6>
                        <p class="text-muted small mb-3">Şifrenizi düzenli olarak değiştirmeniz önerilir.</p>
                        <a href="change_password.php" class="btn btn-outline-warning w-100 fw-bold">Şifre Değiştir</a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Profil resmi önizleme
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

</body>
</html>