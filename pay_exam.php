<?php
session_start();
require 'db.php';
require 'fpdf/fpdf.php'; // Kütüphane yüklü olmalı

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogrenci') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$exam_id = $_GET['exam_id'] ?? null;

if (!$exam_id) {
    echo "Sınav ID eksik.";
    exit;
}

// Sınav ve kullanıcı bilgisi
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$now = date('Y-m-d H:i:s');

// Zaten ödeme yapılmış mı?
$stmt = $pdo->prepare("SELECT * FROM exam_participants WHERE user_id = ? AND exam_id = ?");
$stmt->execute([$user_id, $exam_id]);
$existing = $stmt->fetch();

// Ödeme sonrası
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing) {
    // 1. veritabanına kaydet
    $stmt = $pdo->prepare("INSERT INTO exam_participants (user_id, exam_id, payment_status) VALUES (?, ?, 1)");
    $stmt->execute([$user_id, $exam_id]);

    // 2. PDF fatura oluştur
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,10,'Sınav Faturası',0,1,'C');
    $pdf->Ln(10);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,10,'Fatura Sahibi: ' . $user['name'],0,1);
    $pdf->Cell(0,10,'E-posta: ' . $user['email'],0,1);
    $pdf->Cell(0,10,'Sınav: ' . $exam['title'],0,1);
    $pdf->Cell(0,10,'Açıklama: ' . $exam['description'],0,1);
    $pdf->Cell(0,10,'Tarih: ' . date('d.m.Y H:i', strtotime($exam['start_time'])),0,1);
    $pdf->Cell(0,10,'Ödenen Tutar: ' . $exam['price'] . ' TL',0,1);
    $pdf->Cell(0,10,'Fatura Tarihi: ' . date('d.m.Y H:i'),0,1);

    $fileName = "fatura_" . uniqid() . ".pdf";
    $filePath = __DIR__ . '/' . $fileName;
    $pdf->Output('F', $filePath); // Kaydet

    // 3. mail gönder
    $to = $user['email'];
    $subject = "Faturanız - " . $exam['title'] . " Sınavı";
    $message = "Merhaba " . $user['name'] . ",\n\n" .
               $exam['title'] . " sınavı için ödemeniz alınmıştır. Faturanız ekte gönderilmiştir.\n\n" .
               "Başarılar dileriz!\n";

    $separator = md5(time());
    $eol = PHP_EOL;
    $filename = basename($filePath);
    $attachment = chunk_split(base64_encode(file_get_contents($filePath)));

    $headers = "From: sinav@siteadi.com" . $eol;
    $headers .= "MIME-Version: 1.0" . $eol;
    $headers .= "Content-Type: multipart/mixed; boundary=\"" . $separator . "\"" . $eol . $eol;
    $body = "--" . $separator . $eol;
    $body .= "Content-Type: text/plain; charset=\"utf-8\"" . $eol;
    $body .= "Content-Transfer-Encoding: 7bit" . $eol . $eol;
    $body .= $message . $eol;
    $body .= "--" . $separator . $eol;
    $body .= "Content-Type: application/pdf; name=\"" . $filename . "\"" . $eol;
    $body .= "Content-Transfer-Encoding: base64" . $eol;
    $body .= "Content-Disposition: attachment" . $eol . $eol;
    $body .= $attachment . $eol;
    $body .= "--" . $separator . "--";

    mail($to, $subject, $body, $headers);
    unlink($filePath); // Faturayı sil

    $existing = true;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sınav Ödeme</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 20px; }
        .box { max-width: 500px; margin: auto; background: white; padding: 25px; border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #007bff; }
        .btn { display: block; width: 100%; padding: 12px; background: #28a745; color: white; font-size: 16px;
            border: none; border-radius: 5px; cursor: pointer; text-align: center; text-decoration: none; }
        .btn:hover { background: #218838; }
        .info, .success { margin-top: 20px; padding: 15px; border-radius: 6px; }
        .info { background: #e9ecef; }
        .success { background: #d4edda; }
    </style>
</head>
<body>
<div class="box">
    <h2><?= htmlspecialchars($exam['title']) ?> Ödemesi</h2>
    <p><strong>Açıklama:</strong> <?= htmlspecialchars($exam['description']) ?></p>
    <p><strong>Başlangıç:</strong> <?= date('d.m.Y H:i', strtotime($exam['start_time'])) ?></p>
    <p><strong>Ücret:</strong> <?= $exam['price'] ?> TL</p>

    <?php if ($existing && $existing['payment_status']): ?>
        <div class="success">✅ Ödeme alındı ve fatura e-posta ile gönderildi.</div>
    <?php else: ?>
        <form method="POST">
            <button type="submit" class="btn">Ödemeyi Onayla ve Faturayı Mail Gönder</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
