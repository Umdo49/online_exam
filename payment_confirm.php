<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogrenci') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

if (!isset($_GET['exam_id'])) {
    die("Sınav ID yok.");
}

$exam_id = intval($_GET['exam_id']);
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

if (!$exam) {
    die("Sınav bulunamadı.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_title = trim($_POST['title']);
    $input_price = trim($_POST['price']);

    if (
        mb_strtolower($input_title) === mb_strtolower($exam['title']) &&
        intval($input_price) === intval($exam['price'])
    ) {
        // Daha önce kayıt varsa güncelle, yoksa ekle
        $check = $pdo->prepare("SELECT * FROM exam_participants WHERE user_id = ? AND exam_id = ?");
        $check->execute([$user_id, $exam_id]);
        if ($check->fetch()) {
            $pdo->prepare("UPDATE exam_participants SET payment_status = 1 WHERE user_id = ? AND exam_id = ?")
                ->execute([$user_id, $exam_id]);
        } else {
            $pdo->prepare("INSERT INTO exam_participants (user_id, exam_id, payment_status) VALUES (?, ?, 1)")
                ->execute([$user_id, $exam_id]);
        }

        header("Location: available_exams.php?onay=1");
        exit;
    } else {
        $error = "Girdiğiniz bilgiler sınavla uyuşmuyor.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Ödemeyi Onayla</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f7f7f7; padding: 40px; }
    .form { max-width: 500px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
    input[type=text], button {
        width: 100%; padding: 12px; margin-bottom: 16px; border-radius: 6px; border: 1px solid #ccc;
    }
    button {
        background-color: #28a745; color: white; font-weight: bold; border: none;
    }
    button:hover { background-color: #218838; }
    h2 { text-align: center; color: #007BFF; }
    .error { color: red; text-align: center; }
  </style>
</head>
<body>

<div class="form">
  <h2>Ödemeyi Onayla</h2>
  <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
  <form method="POST">
    <label>WhatsApp’tan gelen sınav başlığını aynen yaz:</label>
    <input type="text" name="title" required placeholder="Örn: Sınav 1">

    <label>WhatsApp’taki ödeme tutarını yaz (sadece sayı):</label>
    <input type="text" name="price" required placeholder="Örn: 100">

    <button type="submit">Onayla ✅</button>
  </form>
</div>

</body>
</html>
