<?php
session_start();
require 'db.php';

// Güvenlik: Öğretmen mi?
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogretmen') {
    header("Location: login.php");
    exit;
}

$ogretmen_id = $_SESSION['user']['id'];
$exam_id = $_GET['exam_id'] ?? null;

$mesaj = "";
$durum = "danger"; // varsayılan hata

if (!$exam_id) {
    $mesaj = "Geçersiz işlem: Sınav ID eksik.";
} else {
    // 1. Sınavın bu öğretmene ait olup olmadığını kontrol et
    $stmt = $pdo->prepare("SELECT id FROM exams WHERE id = ? AND creator_id = ?");
    $stmt->execute([$exam_id, $ogretmen_id]);
    $exam = $stmt->fetch();

    if (!$exam) {
        $mesaj = "Bu sınav size ait değil veya bulunamadı.";
    } else {
        try {
            // İşlemleri bir bütün olarak yap (Transaction başlat)
            $pdo->beginTransaction();

            // --- BAĞLI TABLOLARIN TEMİZLENMESİ ---
            
            // 1. Dosyaları Sil (Hata veren kısım burasıydı)
            $pdo->prepare("DELETE FROM exam_files WHERE exam_id = ?")->execute([$exam_id]);

            // 2. Materyalleri Sil
            $pdo->prepare("DELETE FROM exam_materials WHERE exam_id = ?")->execute([$exam_id]);

            // 3. Cevapları Sil
            $pdo->prepare("DELETE FROM answers WHERE exam_id = ?")->execute([$exam_id]);

            // 4. Manuel Puanları Sil
            $pdo->prepare("DELETE FROM manual_scores WHERE exam_id = ?")->execute([$exam_id]);

            // 5. Katılımcıları Sil
            $pdo->prepare("DELETE FROM exam_participants WHERE exam_id = ?")->execute([$exam_id]);
            
            // 6. Sonuçları Sil (Eğer exam_id ile bağlıysa)
            $pdo->prepare("DELETE FROM results WHERE exam_id = ?")->execute([$exam_id]);
            
            // 7. Kullanıcı Sınav Kayıtlarını Sil
            $pdo->prepare("DELETE FROM user_exam WHERE exam_id = ?")->execute([$exam_id]);

            // 8. Soruları Sil
            $pdo->prepare("DELETE FROM questions WHERE exam_id = ?")->execute([$exam_id]);

            // --- EN SON: SINAVI SİL ---
            $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
            $stmt->execute([$exam_id]);

            // İşlemi onayla
            $pdo->commit();
            
            $mesaj = "Sınav ve bağlı tüm veriler başarıyla silindi.";
            $durum = "success";

        } catch (PDOException $e) {
            // Hata olursa işlemleri geri al
            $pdo->rollBack();
            $mesaj = "Veritabanı hatası: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sınav Siliniyor...</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card-custom { border: none; border-radius: 15px; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.08); padding: 40px; text-align: center; max-width: 400px; width: 100%; }
        .icon-box { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; font-size: 40px; }
        .success-icon { background-color: #d1fae5; color: #059669; }
        .error-icon { background-color: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>

    <div class="card card-custom">
        <?php if ($durum == 'success'): ?>
            <div class="icon-box success-icon">
                <i class="fa-solid fa-check"></i>
            </div>
            <h3 class="fw-bold text-success">Başarılı!</h3>
            <p class="text-muted"><?= $mesaj ?></p>
            <p class="small text-secondary">Yönlendiriliyorsunuz...</p>
            <meta http-equiv="refresh" content="2;url=manage_exams.php">
        <?php else: ?>
            <div class="icon-box error-icon">
                <i class="fa-solid fa-xmark"></i>
            </div>
            <h3 class="fw-bold text-danger">Hata!</h3>
            <p class="text-muted"><?= $mesaj ?></p>
            <a href="manage_exams.php" class="btn btn-outline-secondary mt-3">Geri Dön</a>
        <?php endif; ?>
    </div>

</body>
</html>