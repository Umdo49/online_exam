<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogretmen') {
    header("Location: login.php");
    exit;
}

$exam_id = $_POST['exam_id'];
$scores = $_POST['score'] ?? [];

foreach ($scores as $user_id => $user_scores) {
    $total = 0;
    foreach ($user_scores as $question_id => $score) {
        $score = intval($score);
        $total += $score;

        // İleride analiz yaparsak işimize yarar diye her puanı ayrı saklıyoruz
        $stmt = $pdo->prepare("INSERT INTO manual_scores (exam_id, user_id, question_id, score)
                               VALUES (?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE score = VALUES(score)");
        $stmt->execute([$exam_id, $user_id, $question_id, $score]);
    }

    // Toplam skoru exam_results tablosuna da yazalım (ekle veya güncelle)
    $stmt = $pdo->prepare("SELECT score FROM exam_results WHERE exam_id = ? AND user_id = ?");
    $stmt->execute([$exam_id, $user_id]);
    $existing = $stmt->fetchColumn();

    $final_score = ($existing ?? 0) + $total;

    $stmt = $pdo->prepare("INSERT INTO exam_results (exam_id, user_id, score, evaluated_at)
                           VALUES (?, ?, ?, NOW())
                           ON DUPLICATE KEY UPDATE score = VALUES(score), evaluated_at = NOW()");
    $stmt->execute([$exam_id, $user_id, $final_score]);
}

echo "✅ Manuel puanlar başarıyla kaydedildi.";
