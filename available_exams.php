<?php
session_start();
require 'db.php';

// Güvenlik: Öğrenci mi?
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogrenci') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
date_default_timezone_set('Europe/Istanbul');
$now = date('Y-m-d H:i:s');

// --- VERİ ÇEKME ---

// 1. Aktif ve Gelecek Sınavlar (Bitiş tarihi geçmemiş olanlar)
$stmt = $pdo->prepare("SELECT * FROM exams WHERE end_time >= ? ORDER BY start_time ASC");
$stmt->execute([$now]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Katılım ve Ödeme Durumu
$stmt = $pdo->prepare("SELECT exam_id, payment_status, invoice_file FROM exam_participants WHERE user_id = ?");
$stmt->execute([$user_id]);
$participant_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$paid_exam_ids = [];      // Ödemesi onaylanmış
$invoice_uploaded = [];   // Dekont yüklenmiş ama onay bekleyen
$waiting_exam_ids = [];   // Diğer durumlar

foreach ($participant_data as $row) {
    if ($row['payment_status'] == 1) {
        $paid_exam_ids[] = $row['exam_id'];
    } elseif (!empty($row['invoice_file'])) {
        $invoice_uploaded[] = $row['exam_id'];
    } else {
        $waiting_exam_ids[] = $row['exam_id'];
    }
}

// 3. Daha Önce Katılınmış (Cevaplanmış) Sınavlar
$answered_exam_ids = [];
$check_stmt = $pdo->prepare("SELECT DISTINCT exam_id FROM answers WHERE user_id = ?");
$check_stmt->execute([$user_id]);
foreach ($check_stmt->fetchAll(PDO::FETCH_ASSOC) as $a) {
    $answered_exam_ids[] = $a['exam_id'];
}

// 4. Materyaller
$materials_stmt = $pdo->query("SELECT * FROM exam_materials");
$all_materials = $materials_stmt->fetchAll(PDO::FETCH_ASSOC);
$materials_by_exam = [];
foreach ($all_materials as $mat) {
    if (!empty($mat['exam_id'])) {
        $materials_by_exam[$mat['exam_id']][] = $mat;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sınavlara Katıl</title>
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

        /* Sınav Kartları */
        .exam-card { 
            border: none; 
            border-radius: 12px; 
            background: #fff; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.03); 
            transition: transform 0.2s; 
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .exam-card:hover { transform: translateY(-5px); box-shadow: 0 8px 16px rgba(0,0,0,0.06); }
        
        .card-header-custom { 
            background: #f8f9fa; 
            border-bottom: 1px solid #eee; 
            padding: 15px; 
            font-weight: 600;
            color: #495057;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-body { padding: 20px; flex: 1; }
        .card-footer-custom { padding: 15px; background: white; border-top: 1px solid #eee; }

        .material-list { margin-top: 10px; font-size: 0.85rem; }
        .material-list a { text-decoration: none; display: block; margin-bottom: 3px; color: #0d6efd; }
        .material-list a:hover { text-decoration: underline; }

        /* Rozetler */
        .badge-price { background-color: #ffc107; color: #000; }
        .badge-free { background-color: #198754; color: #fff; }

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
                <h2 class="fw-bold text-dark m-0">Aktif Sınavlar</h2>
                <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Geri Dön</a>
            </div>

            <?php if (empty($exams)): ?>
                <div class="alert alert-info border-0 shadow-sm text-center py-5">
                    <i class="fa-regular fa-calendar-xmark fa-3x mb-3 text-info"></i>
                    <h5>Şu anda aktif bir sınav bulunmamaktadır.</h5>
                    <p class="text-muted">Lütfen daha sonra tekrar kontrol ediniz.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($exams as $exam): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="exam-card">
                                <div class="card-header-custom">
                                    <span class="text-truncate" title="<?= htmlspecialchars($exam['title']) ?>"><?= htmlspecialchars($exam['title']) ?></span>
                                    <?php if ($exam['is_paid']): ?>
                                        <span class="badge badge-price"><?= $exam['price'] ?> TL</span>
                                    <?php else: ?>
                                        <span class="badge badge-free">Ücretsiz</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-body">
                                    <p class="text-muted small mb-3"><?= htmlspecialchars($exam['description']) ?></p>
                                    
                                    <ul class="list-unstyled small text-secondary mb-0">
                                        <li class="mb-2"><i class="fa-regular fa-calendar me-2"></i> Başlangıç: <?= date('d.m.Y H:i', strtotime($exam['start_time'])) ?></li>
                                        <li class="mb-2"><i class="fa-solid fa-hourglass-end me-2"></i> Bitiş: <?= date('d.m.Y H:i', strtotime($exam['end_time'])) ?></li>
                                        <li><i class="fa-solid fa-stopwatch me-2"></i> Süre: <?= $exam['duration_minutes'] ?> dk</li>
                                    </ul>

                                    <?php if (!empty($materials_by_exam[$exam['id']])): ?>
                                        <div class="mt-3 pt-3 border-top">
                                            <h6 class="small fw-bold text-dark mb-2"><i class="fa-solid fa-paperclip me-1"></i> Materyaller</h6>
                                            <div class="material-list">
                                                <?php foreach ($materials_by_exam[$exam['id']] as $mat): ?>
                                                    <a href="<?= htmlspecialchars($mat['filepath']) ?>" target="_blank">
                                                        <i class="fa-regular fa-file-pdf me-1"></i> <?= htmlspecialchars($mat['title'] ?: $mat['filename']) ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="card-footer-custom">
                                    <div class="d-grid gap-2">
                                        <?php 
                                            // --- DURUM KONTROLÜ VE BUTONLAR ---
                                            
                                            // 1. Zaten katıldı mı?
                                            if (in_array($exam['id'], $answered_exam_ids)) {
                                                echo '<button class="btn btn-secondary disabled"><i class="fa-solid fa-check-double"></i> Sınava Katıldınız</button>';
                                            }
                                            // 2. Ücretli sınav mı?
                                            elseif ($exam['is_paid']) {
                                                if (in_array($exam['id'], $paid_exam_ids)) {
                                                    // Ödeme yapılmış
                                                    if (strtotime($exam['start_time']) <= strtotime($now)) {
                                                        echo '<a href="start_exam.php?exam_id='.$exam['id'].'" class="btn btn-success"><i class="fa-solid fa-play"></i> Sınava Başla</a>';
                                                    } else {
                                                        echo '<button class="btn btn-warning disabled"><i class="fa-solid fa-clock"></i> Başlangıç Bekleniyor</button>';
                                                    }
                                                } elseif (in_array($exam['id'], $invoice_uploaded)) {
                                                    // Dekont yüklenmiş, onay bekliyor
                                                    echo '<button class="btn btn-info text-white disabled"><i class="fa-solid fa-spinner fa-spin"></i> Onay Bekleniyor</button>';
                                                    echo '<a href="invoice_upload.php?exam_id='.$exam['id'].'" class="btn btn-outline-primary btn-sm">Dekontu Güncelle</a>';
                                                } else {
                                                    // Ödeme yapılmamış
                                                    echo '<a href="payment.php?exam_id='.$exam['id'].'" class="btn btn-primary"><i class="fa-solid fa-cart-shopping"></i> Satın Al</a>';
                                                    echo '<a href="payment_confirm.php?exam_id='.$exam['id'].'" class="btn btn-outline-secondary btn-sm">Ödeme Bildir</a>';
                                                }
                                            } 
                                            // 3. Ücretsiz sınav
                                            else {
                                                if (strtotime($exam['start_time']) <= strtotime($now)) {
                                                    echo '<a href="start_exam.php?exam_id='.$exam['id'].'" class="btn btn-success"><i class="fa-solid fa-play"></i> Sınava Başla</a>';
                                                } else {
                                                    echo '<button class="btn btn-secondary disabled"><i class="fa-solid fa-clock"></i> Henüz Başlamadı</button>';
                                                }
                                            }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>