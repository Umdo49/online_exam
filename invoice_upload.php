<?php
session_start();
require 'db.php';

// Güvenlik: Öğrenci mi?
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogrenci') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['exam_id'])) {
    header("Location: available_exams.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$exam_id = intval($_GET['exam_id']);
$mesaj = "";
$hata = "";

// 1. Sınav Bilgisini Al (Başlık vs. için)
$stmt = $pdo->prepare("SELECT title FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die("Sınav bulunamadı.");
}

// 2. Katılım Kontrolü
$stmt = $pdo->prepare("SELECT * FROM exam_participants WHERE user_id = ? AND exam_id = ?");
$stmt->execute([$user_id, $exam_id]);
$participant = $stmt->fetch();

if (!$participant) {
    $hata = "Bu sınav için henüz bir ödeme kaydı başlatmadınız.";
}

// 3. Dosya Yükleme İşlemi (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['invoice'])) {
    $file = $_FILES['invoice'];
    
    if ($file['error'] === 0) {
        $allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        
        if (in_array($file['type'], $allowed)) {
            $uploads_dir = 'uploads/invoices/';
            if (!is_dir($uploads_dir)) mkdir($uploads_dir, 0755, true);
            
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_name = "dekont_" . $user_id . "_" . $exam_id . "_" . time() . "." . $ext;
            $target = $uploads_dir . $new_name;
            
            if (move_uploaded_file($file['tmp_name'], $target)) {
                // DB Güncelle (Dosya yüklendi, durum tekrar onaya döndü)
                $stmt = $pdo->prepare("UPDATE exam_participants SET invoice_file = ?, payment_status = 0 WHERE user_id = ? AND exam_id = ?");
                
                if ($stmt->execute([$new_name, $user_id, $exam_id])) {
                    $mesaj = "Dekont başarıyla yüklendi. Onay bekleniyor.";
                } else {
                    $hata = "Veritabanı güncellenemedi.";
                }
            } else {
                $hata = "Dosya sunucuya taşınamadı.";
            }
        } else {
            $hata = "Sadece PDF, JPG ve PNG formatları kabul edilir.";
        }
    } else {
        $hata = "Dosya yükleme hatası oluştu.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Dekont Yükle | <?= htmlspecialchars($exam['title']) ?></title>
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
        .card-custom { border: none; border-radius: 12px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.03); padding: 30px; }
        
        /* Upload Alanı */
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s;
            position: relative;
            cursor: pointer;
        }
        .upload-area:hover { border-color: #0d6efd; background: #e9ecef; }
        .upload-area input[type="file"] {
            position: absolute;
            width: 100%; height: 100%; top: 0; left: 0; opacity: 0; cursor: pointer;
        }
        
        .upload-icon { font-size: 3rem; color: #6c757d; margin-bottom: 15px; transition: 0.3s; }
        .upload-area:hover .upload-icon { color: #0d6efd; transform: scale(1.1); }

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
        <a href="available_exams.php" class="active"><i class="fa-solid fa-pen-to-square"></i> Sınavlara Katıl</a>
        <a href="exam_history.php"><i class="fa-solid fa-clock-rotate-left"></i> Sonuçlarım</a>
        <a href="profile_manage.php"><i class="fa-solid fa-user-gear"></i> Profil Ayarları</a>
        <a href="logout.php" class="text-danger mt-3"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a>
    </div>

    <div class="main-content">
        <div class="container">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-dark m-0">Ödeme Bildirimi</h2>
                <a href="available_exams.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Geri Dön</a>
            </div>

            <?php if ($mesaj): ?>
                <div class="alert alert-success border-0 shadow-sm"><i class="fa-solid fa-check-circle me-2"></i> <?= $mesaj ?></div>
            <?php endif; ?>
            
            <?php if ($hata): ?>
                <div class="alert alert-danger border-0 shadow-sm"><i class="fa-solid fa-circle-exclamation me-2"></i> <?= $hata ?></div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card card-custom">
                        <h5 class="fw-bold text-primary mb-4 text-center">
                            <?= htmlspecialchars($exam['title']) ?>
                        </h5>
                        <p class="text-center text-muted mb-4">
                            Lütfen ödeme dekontunuzu veya faturanızı aşağıya yükleyin.<br>
                            <small>(Kabul edilen formatlar: PDF, JPG, PNG - Max: 5MB)</small>
                        </p>

                        <form method="POST" enctype="multipart/form-data">
                            
                            <div class="upload-area" id="drop-area">
                                <input type="file" name="invoice" id="fileInput" required onchange="showFile(this)">
                                <i class="fa-solid fa-cloud-arrow-up upload-icon"></i>
                                <h5 class="fw-bold text-dark">Dosyayı buraya sürükleyin</h5>
                                <p class="text-muted mb-0">veya dosya seçmek için tıklayın</p>
                            </div>
                            
                            <div id="file-info" class="mt-3 text-center text-success fw-bold"></div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-success py-3 fw-bold shadow-sm">
                                    <i class="fa-solid fa-upload me-2"></i> Dekontu Gönder
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showFile(input) {
            const fileInfo = document.getElementById('file-info');
            if (input.files && input.files[0]) {
                fileInfo.innerHTML = '<i class="fa-solid fa-file-invoice me-2"></i> Seçilen Dosya: ' + input.files[0].name;
            } else {
                fileInfo.innerHTML = '';
            }
        }
    </script>

</body>
</html>