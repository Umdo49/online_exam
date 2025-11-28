<?php
include 'includes/config.php';
include 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $phone, $password]);
        $_SESSION['success'] = "Kayıt başarılı! Giriş yapabilirsiniz.";
        redirect('login.php');
    } catch(PDOException $e) {
        $_SESSION['error'] = "Kayıt hatası: " . $e->getMessage();
        redirect('register.php');
    }
}
?>