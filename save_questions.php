<?php
session_start();
require_once "db.php"; // PDO bağlantısı

if (!isset($_POST['exam_id'])) {
    die("Geçersiz erişim.");
}

$exam_id = (int) $_POST['exam_id'];
$mc_count = (int) $_POST['mc_count'];
$classic_count = (int) $_POST['classic_count'];

try {
    $pdo->beginTransaction();

    // Çoktan seçmeli sorular (coktan_secimli)
    for ($i = 1; $i <= $mc_count; $i++) {
        $question_text = $_POST["mc_question_$i"];
        $optionA = $_POST["mc_{$i}_A"];
        $optionB = $_POST["mc_{$i}_B"];
        $optionC = $_POST["mc_{$i}_C"];
        $optionD = $_POST["mc_{$i}_D"];
        $optionE = $_POST["mc_{$i}_E"];
        $correct = $_POST["mc_correct_$i"]; // 'A', 'B', 'C', 'D', 'E'

        // JSON formatında şıklar
        $options = json_encode([
            'A' => $optionA,
            'B' => $optionB,
            'C' => $optionC,
            'D' => $optionD,
            'E' => $optionE
        ], JSON_UNESCAPED_UNICODE);

        $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, question_type, correct_answer, options) VALUES (?, ?, 'coktan_secimli', ?, ?)");
        $stmt->execute([$exam_id, $question_text, $correct, $options]);
    }

    // Klasik sorular (klasik)
    for ($j = 1; $j <= $classic_count; $j++) {
        $question_text = $_POST["classic_question_$j"];

        $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, question_type) VALUES (?, ?, 'klasik')");
        $stmt->execute([$exam_id, $question_text]);
    }

    $pdo->commit();
    echo "<h3 style='color:green;'>✅ Sorular başarıyla eklendi.</h3>";
    echo "<a href='manage_exams.php'>Sınavlara Dön</a>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h3 style='color:red;'>❌ Hata: " . htmlspecialchars($e->getMessage()) . "</h3>";
    echo "<a href='add_question_step1.php'>Yeniden dene</a>";
}
?>
