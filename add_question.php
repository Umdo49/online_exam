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

// URL'den gelen sınav ID'si (Varsa)
$selected_exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : null;

// Sınav Bilgisi ve Soru Sayısı (Opsiyonel Bilgi İçin)
$exam_info = null;
if ($selected_exam_id) {
    $stmt = $pdo->prepare("SELECT title, (SELECT COUNT(*) FROM questions WHERE exam_id = exams.id) as q_count FROM exams WHERE id = ? AND creator_id = ?");
    $stmt->execute([$selected_exam_id, $ogretmen_id]);
    $exam_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Öğretmenin sınavlarını getir (Selectbox için)
$stmt = $pdo->prepare("SELECT id, title FROM exams WHERE creator_id = ? ORDER BY created_at DESC");
$stmt->execute([$ogretmen_id]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Form Gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_id = $_POST['exam_id'];
    $question_text = $_POST['question_text'];
    $question_type = $_POST['question_type'];
    
    // Doğru Cevap
    $correct_answer = !empty($_POST['correct_answer']) ? $_POST['correct_answer'] : null;
    $options_json = null;

    if ($question_type === 'coktan_secimli') {
        $options = [
            'A' => $_POST['option_a'],
            'B' => $_POST['option_b'],
            'C' => $_POST['option_c'],
            'D' => $_POST['option_d'],
            'E' => $_POST['option_e']
        ];
        $options_json = json_encode($options, JSON_UNESCAPED_UNICODE);
    }

    $sql = "INSERT INTO questions (exam_id, question_text, question_type, correct_answer, options) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$exam_id, $question_text, $question_type, $correct_answer, $options_json])) {
        $mesaj = "Soru başarıyla eklendi.";
        // Sayfa yenilenince tekrar post etmesin diye redirect yapabiliriz veya aynı sayfada kalırız.
        // Aynı sınav seçili kalsın ve mesaj görünsün diye işlem burada bitiyor.
        
        // Soru sayısını güncellemek için exam_info'yu tekrar çekebiliriz (opsiyonel)
        if ($selected_exam_id) {
             $stmt = $pdo->prepare("SELECT title, (SELECT COUNT(*) FROM questions WHERE exam_id = exams.id) as q_count FROM exams WHERE id = ? AND creator_id = ?");
             $stmt->execute([$selected_exam_id, $ogretmen_id]);
             $exam_info = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } else {
        $hata = "Soru eklenirken bir hata oluştu.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Soru Ekle</title>
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

        /* Kart */
        .card-custom { border: none; border-radius: 12px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.03); padding: 30px; }
        
        .form-label { font-weight: 500; color: #495057; }
        .form-control, .form-select { padding: 10px 15px; border-radius: 8px; border: 1px solid #dee2e6; }
        .form-control:focus { box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1); border-color: #0d6efd; }

        /* Seçenekler Alanı */
        .options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: #f8f9fa; padding: 20px; border-radius: 10px; border: 1px solid #e9ecef; margin-bottom: 20px; }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .options-grid { grid-template-columns: 1fr; }
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
        <a href="manage_exams.php" class="active"><i class="fa-solid fa-list-check"></i> Sınavları Yönet</a>
        <a href="student_results.php"><i class="fa-solid fa-chart-line"></i> Öğrenci Sonuçları</a>
        <a href="upload_material.php"><i class="fa-solid fa-upload"></i> Materyal Yükle</a>
        <a href="logout.php" class="text-danger mt-3"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a>
    </div>

    <div class="main-content">
        <div class="container">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark m-0">Soru Ekle</h2>
                    <?php if($exam_info): ?>
                        <p class="text-muted m-0">Sınav: <strong><?= htmlspecialchars($exam_info['title']) ?></strong> | Mevcut Soru: <?= $exam_info['q_count'] ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <a href="manage_exams.php" class="btn btn-outline-secondary me-2"><i class="fa-solid fa-arrow-left"></i> Listeye Dön</a>
                    <a href="dashboard.php" class="btn btn-primary"><i class="fa-solid fa-check"></i> Bitir</a>
                </div>
            </div>

            <?php if ($mesaj): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> <?= $mesaj ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($hata): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?= $hata ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card card-custom">
                <form method="POST">
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Hangi Sınava Eklenecek?</label>
                            <select name="exam_id" class="form-select" required>
                                <option value="">Sınav Seçiniz...</option>
                                <?php foreach ($exams as $exam): ?>
                                    <option value="<?= $exam['id'] ?>" <?= ($selected_exam_id == $exam['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($exam['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Soru Tipi</label>
                            <select name="question_type" id="question_type" class="form-select" onchange="toggleOptions()" required>
                                <option value="coktan_secimli">Çoktan Seçmeli (Test)</option>
                                <option value="klasik">Klasik (Açık Uçlu)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Soru Metni</label>
                            <textarea name="question_text" class="form-control" rows="4" placeholder="Sorunuzu buraya yazın..." required></textarea>
                        </div>

                        <div class="col-12" id="multiple_choice_section">
                            <label class="form-label mb-2">Şıklar</label>
                            <div class="options-grid">
                                <div class="input-group">
                                    <span class="input-group-text fw-bold">A</span>
                                    <input type="text" name="option_a" class="form-control" placeholder="A seçeneği">
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold">B</span>
                                    <input type="text" name="option_b" class="form-control" placeholder="B seçeneği">
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold">C</span>
                                    <input type="text" name="option_c" class="form-control" placeholder="C seçeneği">
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold">D</span>
                                    <input type="text" name="option_d" class="form-control" placeholder="D seçeneği">
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold">E</span>
                                    <input type="text" name="option_e" class="form-control" placeholder="E seçeneği">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6" id="correct_answer_div">
                            <label class="form-label">Doğru Cevap</label>
                            <div id="answer_input_container">
                                <select name="correct_answer" class="form-select">
                                    <option value="">Doğru Şıkkı Seçiniz</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                    <option value="E">E</option>
                                </select>
                            </div>
                            <div class="form-text text-muted">Klasik sorularda cevap anahtarı veya not yazabilirsiniz.</div>
                        </div>

                        <div class="col-12 text-end mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="fa-solid fa-plus"></i> Soruyu Kaydet ve Yeni Ekle
                            </button>
                        </div>
                    </div>

                </form>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleOptions() {
            const type = document.getElementById('question_type').value;
            const optionsSection = document.getElementById('multiple_choice_section');
            const answerContainer = document.getElementById('answer_input_container');

            if (type === 'coktan_secimli') {
                optionsSection.style.display = 'block';
                // Doğru cevap alanı selectbox olsun
                answerContainer.innerHTML = `
                    <select name="correct_answer" class="form-select" required>
                        <option value="">Doğru Şıkkı Seçiniz</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                    </select>
                `;
            } else {
                optionsSection.style.display = 'none';
                // Klasik ise textarea olsun
                answerContainer.innerHTML = `
                    <textarea name="correct_answer" class="form-control" rows="2" placeholder="Cevap anahtarı (Öğrenci görmez)"></textarea>
                `;
            }
        }

        // Sayfa yüklendiğinde çalıştır
        document.addEventListener('DOMContentLoaded', toggleOptions);
    </script>

</body>
</html>