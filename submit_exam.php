<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogrenci') {
    header("Location: login.php");
    exit;
}

if (!isset($_POST['exam_id']) || !isset($_POST['answers']) || !is_array($_POST['answers'])) {
    echo "Geçersiz istek.";
    exit;
}

$exam_id = $_POST['exam_id'];
$user_id = $_SESSION['user']['id'];

// Oturumda sınav bilgisi ve süresi kontrolü
if (!isset($_SESSION['exam'][$exam_id])) {
    echo "Sınav oturumu bulunamadı veya geçersiz.";
    exit;
}

$exam_session = $_SESSION['exam'][$exam_id];
$now = time();
if ($now > $exam_session['end_time']) {
    echo "Sınav süresi doldu, cevaplarınız kaydedilemedi.";
    exit;
}

$answers = $_POST['answers'];

// PDO için transaction başlat (tutarlılık için)
$pdo->beginTransaction();

try {
    foreach ($answers as $question_id => $answer) {
        // Temizleme (trim, gerekirse daha detaylı yapılabilir)
        $clean_answer = trim($answer);

        // Eğer cevap boşsa kayıt yapma
        if ($clean_answer === '') continue;

        // Var mı kontrol et, varsa güncelle yoksa ekle
        $stmtCheck = $pdo->prepare("SELECT id FROM answers WHERE exam_id = ? AND user_id = ? AND question_id = ?");
        $stmtCheck->execute([$exam_id, $user_id, $question_id]);
        $existing = $stmtCheck->fetch();

        if ($existing) {
            $stmtUpdate = $pdo->prepare("UPDATE answers SET answer = ? WHERE id = ?");
            $stmtUpdate->execute([$clean_answer, $existing['id']]);
        } else {
            $stmtInsert = $pdo->prepare("INSERT INTO answers (exam_id, user_id, question_id, answer) VALUES (?, ?, ?, ?)");
            $stmtInsert->execute([$exam_id, $user_id, $question_id, $clean_answer]);
        }
    }

    $pdo->commit();

    // İstersen burada oturumdaki sınav cevaplarını temizleyebilirsin
    unset($_SESSION['exam'][$exam_id]);

    // Yönlendir
    header("Location: exam_results.php?exam_id=" . urlencode($exam_id));
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Cevaplar kaydedilirken hata oluştu: " . htmlspecialchars($e->getMessage());
    exit;
}
