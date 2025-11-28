<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogretmen') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback']) && is_array($_POST['feedback'])) {
    $evaluator_id = $_SESSION['user']['id'];
    $success = true;

    foreach ($_POST['feedback'] as $answer_id => $evaluation) {
        if (!in_array($evaluation, ['dogru', 'kismen_dogru', 'yanlis'])) continue;

        // Puan hesapla
        $score = match ($evaluation) {
            'dogru' => 2,
            'kismen_dogru' => 1,
            'yanlis' => 0,
            default => null
        };

        // Cevap detaylarını al
        $stmt = $pdo->prepare("SELECT exam_id, question_id, user_id FROM answers WHERE id = ?");
        $stmt->execute([$answer_id]);
        $answer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($answer) {
            try {
                // Zaten değerlendirilmişse tekrar işleme
                $check = $pdo->prepare("SELECT COUNT(*) FROM manual_scores WHERE exam_id = ? AND user_id = ? AND question_id = ?");
                $check->execute([$answer['exam_id'], $answer['user_id'], $answer['question_id']]);
                if ($check->fetchColumn() > 0) {
                    continue; // değerlendirilmişse atla
                }

                // Feedback güncelle
                $updateFeedback = $pdo->prepare("UPDATE answers SET classic_feedback = ? WHERE id = ?");
                $updateFeedback->execute([$evaluation, $answer_id]);

                // Skor kaydet
                $insertScore = $pdo->prepare("
                    INSERT INTO manual_scores (exam_id, user_id, question_id, score, evaluator_id)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $insertScore->execute([
                    $answer['exam_id'],
                    $answer['user_id'],
                    $answer['question_id'],
                    $score,
                    $evaluator_id
                ]);

            } catch (Exception $e) {
                $success = false;
                error_log("Hata: " . $e->getMessage());
            }
        }
    }

    // Sonuç mesajı ile yönlendir
    $redirect = "evaluate_classics.php";
    header("Location: {$redirect}?" . ($success ? "success=1" : "error=1"));
    exit;

} else {
    header("Location: evaluate_classics.php?error=1");
    exit;
}
