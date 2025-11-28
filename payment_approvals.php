<?php
session_start();
require 'db.php';

// Güvenlik: Öğretmen mi?
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogretmen') {
    header("Location: login.php");
    exit;
}

$ogretmen_id = $_SESSION['user']['id'];
$mesaj = "";
$hata = "";

// İşlem (Onayla / Reddet)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $participant_id = $_POST['participant_id'];
    $action = $_POST['action']; // 'approve' veya 'reject'

    if ($action === 'approve') {
        // Ödemeyi onayla (payment_status = 1)
        $stmt = $pdo->prepare("UPDATE exam_participants SET payment_status = 1 WHERE id = ?");
        if ($stmt->execute([$participant_id])) {
            $mesaj = "Öğrenci sınava onaylandı.";
        } else {
            $hata = "Onaylama işlemi başarısız.";
        }
    } elseif ($action === 'reject') {
        // Reddet (Kaydı sil veya durumunu değiştir - burada siliyoruz ki tekrar başvurabilsin)
        $stmt = $pdo->prepare("DELETE FROM exam_participants WHERE id = ?");
        if ($stmt->execute([$participant_id])) {
            $mesaj = "Başvuru reddedildi ve silindi.";
        } else {
            $hata = "Reddetme işlemi başarısız.";
        }
    }
}

// Onay Bekleyenleri Listele
// Sadece bu öğretmenin oluşturduğu sınavlara ait başvurular
$sql = "SELECT ep.id, ep.invoice_file, ep.contact_email, ep.contact_phone, u.name as ogrenci_adi, e.title as sinav_adi, e.price
        FROM exam_participants ep
        JOIN exams e ON ep.exam_id = e.id
        JOIN users u ON ep.user_id = u.id
        WHERE e.creator_id = ? AND ep.payment_status = 0";

$stmt = $pdo->prepare($sql);
$stmt->execute([$ogretmen_id]);
$bekleyenler = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Ödeme Onayları</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        
        /* Sidebar (Aynı Kalıp) */
        .sidebar { width: 260px; height: 100vh; background: #212529; color: #fff; position: fixed; top: 0; left: 0; padding-top: 20px; z-index: 1000; }
        .sidebar-header { padding: 15px 25px; border-bottom: 1px solid #343a40; margin-bottom: 20px; }
        .sidebar a { padding: 12px 25px; text-decoration: none; font-size: 16px; color: #adb5bd; display: block; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { color: #fff; background-color: #0d6efd; border-radius: 0 25px 25px 0; }
        .sidebar i { width: 25px; }

        .main-content { margin-left: 260px; padding: 30px; }
        .card-custom { border: none; border-radius: 12px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.03); padding: 25px; }

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
        <a href="manage_exams.php"><i class="fa-solid fa-list-check"></i> Sınavları Yönet</a>
        <a href="student_results.php"><i class="fa-solid fa-chart-line"></i> Öğrenci Sonuçları</a>
        <a href="payment_approvals.php" class="active"><i class="fa-solid fa-file-invoice-dollar"></i> Ödeme Onayları</a>
        <a href="logout.php" class="text-danger mt-3"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a>
    </div>

    <div class="main-content">
        <div class="container">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-dark m-0">Ödeme ve Katılım Onayları</h2>
                <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Geri Dön</a>
            </div>

            <?php if ($mesaj): ?>
                <div class="alert alert-success border-0 shadow-sm"><i class="fa-solid fa-check-circle me-2"></i> <?= $mesaj ?></div>
            <?php endif; ?>
            <?php if ($hata): ?>
                <div class="alert alert-danger border-0 shadow-sm"><i class="fa-solid fa-circle-exclamation me-2"></i> <?= $hata ?></div>
            <?php endif; ?>

            <div class="card card-custom">
                <?php if (empty($bekleyenler)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa-regular fa-folder-open fa-3x mb-3"></i>
                        <h5>Onay bekleyen başvuru yok.</h5>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Öğrenci</th>
                                    <th>Sınav</th>
                                    <th>Ücret</th>
                                    <th>Dekont/Belge</th>
                                    <th>İletişim</th>
                                    <th class="text-end">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bekleyenler as $b): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($b['ogrenci_adi']) ?></td>
                                        <td><?= htmlspecialchars($b['sinav_adi']) ?></td>
                                        <td><span class="badge bg-warning text-dark"><?= $b['price'] ?> TL</span></td>
                                        <td>
                                            <?php if (!empty($b['invoice_file'])): ?>
                                                <a href="uploads/invoices/<?= htmlspecialchars($b['invoice_file']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fa-solid fa-file-invoice"></i> Görüntüle
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">Dosya Yok</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small">
                                            <div><i class="fa-solid fa-envelope me-1"></i> <?= htmlspecialchars($b['contact_email']) ?></div>
                                            <div><i class="fa-solid fa-phone me-1"></i> <?= htmlspecialchars($b['contact_phone']) ?></div>
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="participant_id" value="<?= $b['id'] ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-success" title="Onayla">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger" title="Reddet" onclick="return confirm('Bu başvuruyu reddetmek istediğinize emin misiniz?')">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>