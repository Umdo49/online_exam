<?php
session_start();
require 'db.php';

// Güvenlik: Öğretmen mi?
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogretmen') {
    header("Location: login.php");
    exit;
}

$exam_id = $_GET['exam_id'] ?? null;
if (!$exam_id) {
    header("Location: manage_exams.php");
    exit;
}

$ogretmen_id = $_SESSION['user']['id'];
$mesaj = "";
$hata = "";

// Sınav Bilgilerini Çek
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ? AND creator_id = ?");
$stmt->execute([$exam_id, $ogretmen_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die("Bu sınav size ait değil veya bulunamadı.");
}

// GÜNCELLEME İŞLEMİ (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $duration = $_POST['duration'];
    $format = $_POST['format'];
    $is_paid = isset($_POST['is_paid']) ? 1 : 0;
    $price = ($is_paid && !empty($_POST['price'])) ? $_POST['price'] : 0;

    // Tarih kontrolü
    if (strtotime($start) >= strtotime($end)) {
        $hata = "Bitiş tarihi, başlangıç tarihinden sonra olmalıdır!";
    } else {
        $sql = "UPDATE exams SET title=?, description=?, start_time=?, end_time=?, duration_minutes=?, format=?, is_paid=?, price=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$title, $desc, $start, $end, $duration, $format, $is_paid, $price, $exam_id])) {
            $mesaj = "Sınav başarıyla güncellendi.";
            // Güncel veriyi tekrar çekelim ki formda görünsün
            $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
            $stmt->execute([$exam_id]);
            $exam = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $hata = "Güncelleme sırasında hata oluştu.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sınav Düzenle | <?= htmlspecialchars($exam['title']) ?></title>
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
        
        .form-label { font-weight: 500; color: #495057; }
        .form-control, .form-select { padding: 10px 15px; border-radius: 8px; border: 1px solid #dee2e6; }
        .form-control:focus { box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1); border-color: #0d6efd; }

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
        <a href="create_exam.php"><i class="fa-solid fa-plus-circle"></i> Sınav Oluştur</a>
        <a href="manage_exams.php" class="active"><i class="fa-solid fa-list-check"></i> Sınavları Yönet</a>
        <a href="student_results.php"><i class="fa-solid fa-chart-line"></i> Öğrenci Sonuçları</a>
        <a href="logout.php" class="text-danger mt-3"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a>
    </div>

    <div class="main-content">
        <div class="container">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-dark m-0">Sınavı Düzenle</h2>
                <a href="manage_exams.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Geri Dön</a>
            </div>

            <?php if ($mesaj): ?>
                <div class="alert alert-success border-0 shadow-sm"><i class="fa-solid fa-check-circle me-2"></i> <?= $mesaj ?></div>
            <?php endif; ?>
            <?php if ($hata): ?>
                <div class="alert alert-danger border-0 shadow-sm"><i class="fa-solid fa-circle-exclamation me-2"></i> <?= $hata ?></div>
            <?php endif; ?>

            <div class="card card-custom">
                <form method="POST">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Sınav Başlığı</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($exam['title']) ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Açıklama</label>
                            <textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($exam['description']) ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Başlangıç Zamanı</label>
                            <input type="datetime-local" name="start_time" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($exam['start_time'])) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Bitiş Zamanı</label>
                            <input type="datetime-local" name="end_time" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($exam['end_time'])) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Süre (Dakika)</label>
                            <input type="number" name="duration" class="form-control" value="<?= $exam['duration_minutes'] ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Format</label>
                            <select name="format" class="form-select" required>
                                <option value="coktan_secimli" <?= $exam['format'] == 'coktan_secimli' ? 'selected' : '' ?>>Çoktan Seçmeli</option>
                                <option value="klasik" <?= $exam['format'] == 'klasik' ? 'selected' : '' ?>>Klasik</option>
                                <option value="karisik" <?= $exam['format'] == 'karisik' ? 'selected' : '' ?>>Karışık</option>
                            </select>
                        </div>

                        <div class="col-12 mt-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_paid" name="is_paid" <?= $exam['is_paid'] ? 'checked' : '' ?> onchange="togglePrice()">
                                <label class="form-check-label fw-bold" for="is_paid">Bu sınav ücretli mi?</label>
                            </div>
                        </div>

                        <div class="col-md-4 mt-2" id="priceDiv" style="display: <?= $exam['is_paid'] ? 'block' : 'none' ?>;">
                            <label class="form-label">Ücret (TL)</label>
                            <div class="input-group">
                                <span class="input-group-text">₺</span>
                                <input type="number" name="price" class="form-control" value="<?= $exam['price'] ?>">
                            </div>
                        </div>

                        <div class="col-12 mt-4 text-end">
                            <button type="submit" class="btn btn-primary px-4 fw-bold">
                                <i class="fa-solid fa-save me-2"></i> Değişiklikleri Kaydet
                            </button>
                        </div>
                    </div>

                </form>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePrice() {
            const isChecked = document.getElementById('is_paid').checked;
            document.getElementById('priceDiv').style.display = isChecked ? 'block' : 'none';
        }
    </script>

</body>
</html>