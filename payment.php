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

$exam_id = intval($_GET['exam_id']);
$user_id = $_SESSION['user']['id'];

// Sınav Bilgisi
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die("Sınav bulunamadı.");
}

// ÖDEME İŞLEMİ (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $card_name = $_POST['card_name'];
    $card_number = $_POST['card_number'];
    $card_expiry = $_POST['card_expiry'];
    $card_cvv = $_POST['card_cvv'];
    
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone_raw = $_POST['phone'];

    // Telefon numarasını temizle ve formatla (905xxxxxxxxx)
    $phone = preg_replace('/[^0-9]/', '', $phone_raw);
    if (strlen($phone) == 11 && substr($phone, 0, 1) == '0') {
        $phone = '90' . substr($phone, 1);
    } elseif (strlen($phone) == 10) {
        $phone = '90' . $phone;
    } elseif (strlen($phone) == 12 && substr($phone, 0, 2) == '90') {
        // Doğru format
    } else {
        $error = "Hatalı telefon numarası! Lütfen 05xx xxx xx xx formatında giriniz.";
    }

    if (!isset($error)) {
        // 1. JSON Fatura Oluştur (Simülasyon)
        $uploads_dir = 'uploads/invoices/';
        if (!is_dir($uploads_dir)) mkdir($uploads_dir, 0755, true);

        $fatura = [
            "fatura_no" => uniqid("INV-"),
            "ad_soyad" => $name,
            "email" => $email,
            "telefon" => $phone,
            "sinav" => $exam['title'],
            "ucret" => $exam['price'] . " TL",
            "tarih" => date('Y-m-d H:i:s'),
            "kart" => [
                "isim" => $card_name,
                "numara" => "**** **** **** " . substr($card_number, -4),
                "son_kullanma" => $card_expiry
            ]
        ];
        
        $json_file = "fatura_" . time() . ".json";
        file_put_contents($uploads_dir . $json_file, json_encode($fatura, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 2. Veritabanına Kayıt (Ödeme Başarılı, Onay Bekliyor statüsünde - Simülasyon)
        // Gerçekte payment_status 1 (ödendi) yapılır veya API cevabına göre işlem yapılır.
        // Burada senaryo gereği "Ödeme Yapıldı -> WhatsApp Bildirimi" akışı var.
        // payment_status = 0 (Onay Bekliyor) olarak kaydediyoruz.
        
        $sql = "INSERT INTO exam_participants (user_id, exam_id, payment_status, invoice_file, contact_email, contact_phone) 
                VALUES (?, ?, 0, ?, ?, ?)
                ON DUPLICATE KEY UPDATE contact_email=VALUES(contact_email), contact_phone=VALUES(contact_phone), invoice_file=VALUES(invoice_file)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $exam_id, $json_file, $email, $phone]);

        // 3. WhatsApp Yönlendirme
        $message = urlencode("Merhaba, {$exam['title']} sınavı için ödeme yaptım.\nAd Soyad: {$name}\nTutar: {$exam['price']} TL\nSipariş No: {$fatura['fatura_no']}");
        header("Location: https://wa.me/{$phone}?text={$message}");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Güvenli Ödeme | <?= htmlspecialchars($exam['title']) ?></title>
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

        /* Ödeme Kartı */
        .card-custom { border: none; border-radius: 12px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.03); padding: 30px; }
        
        /* Kredi Kartı Görünümü */
        .credit-card-preview {
            background: linear-gradient(135deg, #4361ee, #3f37c9);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
            position: relative;
            overflow: hidden;
        }
        .chip { width: 50px; height: 35px; background: linear-gradient(135deg, #fceabb, #f8b500); border-radius: 5px; margin-bottom: 20px; }
        .card-number { font-size: 1.4rem; letter-spacing: 2px; margin-bottom: 20px; font-family: 'Courier New', monospace; }
        .card-holder { font-size: 0.9rem; text-transform: uppercase; }
        .card-expiry { font-size: 0.9rem; }
        
        .form-label { font-weight: 500; color: #495057; font-size: 0.9rem; }
        .form-control { padding: 10px 15px; border-radius: 8px; border: 1px solid #dee2e6; }
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
        <a href="available_exams.php" class="active"><i class="fa-solid fa-pen-to-square"></i> Sınavlara Katıl</a>
        <a href="exam_history.php"><i class="fa-solid fa-clock-rotate-left"></i> Sonuçlarım</a>
        <a href="profile_manage.php"><i class="fa-solid fa-user-gear"></i> Profil Ayarları</a>
        <a href="logout.php" class="text-danger mt-3"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a>
    </div>

    <div class="main-content">
        <div class="container">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-dark m-0">Ödeme Yap</h2>
                <a href="available_exams.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Vazgeç</a>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger shadow-sm border-0"><i class="fa-solid fa-circle-exclamation me-2"></i> <?= $error ?></div>
            <?php endif; ?>

            <div class="row g-4">
                
                <div class="col-lg-4 order-lg-2">
                    <div class="card card-custom h-100 bg-primary bg-opacity-10 border border-primary border-opacity-10">
                        <h5 class="fw-bold text-primary mb-4">Sipariş Özeti</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Sınav Adı:</span>
                            <span class="fw-bold text-end"><?= htmlspecialchars($exam['title']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Süre:</span>
                            <span class="fw-bold"><?= $exam['duration_minutes'] ?> dk</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fs-5">Toplam Tutar:</span>
                            <span class="fs-3 fw-bold text-dark"><?= $exam['price'] ?> ₺</span>
                        </div>
                        <div class="mt-4 text-center text-muted small">
                            <i class="fa-solid fa-lock me-1"></i> Ödemeniz 256-bit SSL ile korunmaktadır.
                        </div>
                    </div>
                </div>

                <div class="col-lg-8 order-lg-1">
                    <div class="card card-custom">
                        <form method="POST">
                            <h5 class="fw-bold mb-4 border-bottom pb-3">Kart Bilgileri</h5>
                            
                            <div class="row g-3">
                                <div class="col-12 d-none d-md-block">
                                    <div class="credit-card-preview">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div class="chip"></div>
                                            <i class="fa-brands fa-cc-visa fa-2x"></i>
                                        </div>
                                        <div class="card-number" id="previewNumber">0000 0000 0000 0000</div>
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <small class="d-block opacity-75">KART SAHİBİ</small>
                                                <span class="card-holder" id="previewName">AD SOYAD</span>
                                            </div>
                                            <div>
                                                <small class="d-block opacity-75">SKT</small>
                                                <span class="card-expiry" id="previewExpiry">AA/YY</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Kart Üzerindeki İsim</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                                        <input type="text" name="card_name" class="form-control" placeholder="Ad Soyad" required oninput="document.getElementById('previewName').innerText = this.value.toUpperCase() || 'AD SOYAD'">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Kart Numarası</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa-regular fa-credit-card"></i></span>
                                        <input type="text" name="card_number" class="form-control" placeholder="0000 0000 0000 0000" maxlength="19" required oninput="document.getElementById('previewNumber').innerText = this.value || '0000 0000 0000 0000'">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Son Kullanma Tarihi (AA/YY)</label>
                                    <input type="text" name="card_expiry" class="form-control" placeholder="MM/YY" maxlength="5" required oninput="document.getElementById('previewExpiry').innerText = this.value || 'AA/YY'">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">CVV / CVC</label>
                                    <div class="input-group">
                                        <input type="text" name="card_cvv" class="form-control" placeholder="123" maxlength="3" required>
                                        <span class="input-group-text"><i class="fa-solid fa-question-circle" title="Kartın arkasındaki 3 haneli kod"></i></span>
                                    </div>
                                </div>
                            </div>

                            <h5 class="fw-bold mb-4 mt-5 border-bottom pb-3">İletişim Bilgileri</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Ad Soyad</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_SESSION['user']['name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">E-Posta</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_SESSION['user']['email']) ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Telefon (WhatsApp Bildirimi İçin)</label>
                                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_SESSION['user']['phone']) ?>" placeholder="05xx xxx xx xx" required>
                                    <div class="form-text">Ödeme onayı ve sınav bilgileri bu numaraya WhatsApp üzerinden iletilecektir.</div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success w-100 py-3 mt-4 fw-bold shadow">
                                <i class="fa-brands fa-whatsapp me-2"></i> Ödemeyi Tamamla ve Bildir
                            </button>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>