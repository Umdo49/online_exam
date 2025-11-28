<?php
session_start();
require 'db.php';

// Güvenlik: Öğretmen mi?
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogretmen') {
    header("Location: login.php");
    exit;
}

$hata = "";
$basari = "";

// Form gönderildiğinde (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $duration = $_POST['duration'];
    $format = $_POST['format'];
    $is_paid = isset($_POST['is_paid']) ? 1 : 0;
    $price = ($is_paid && !empty($_POST['price'])) ? $_POST['price'] : 0;
    $creator = $_SESSION['user']['id'];

    // Tarih kontrolü
    if (strtotime($start) >= strtotime($end)) {
        $hata = "Bitiş tarihi, başlangıç tarihinden sonra olmalıdır!";
    } else {
        // Kayıt İşlemi
        $sql = "INSERT INTO exams (title, description, start_time, end_time, duration_minutes, creator_id, format, is_paid, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$title, $desc, $start, $end, $duration, $creator, $format, $is_paid, $price])) {
            $exam_id = $pdo->lastInsertId();
            // Başarılı ise soru ekleme sayfasına yönlendir
            header("Location: add_question.php?exam_id=$exam_id"); // add_question_step1.php yerine daha genel bir isim kullandım
            exit;
        } else {
            $hata = "Sınav oluşturulurken bir sorun oluştu.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sınav Oluştur</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        
        /* Sidebar (Dashboard ile Aynı) */
        .sidebar { width: 260px; height: 100vh; background: #212529; color: #fff; position: fixed; top: 0; left: 0; padding-top: 20px; z-index: 1000; }
        .sidebar-header { padding: 15px 25px; border-bottom: 1px solid #343a40; margin-bottom: 20px; }
        .sidebar a { padding: 12px 25px; text-decoration: none; font-size: 16px; color: #adb5bd; display: block; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { color: #fff; background-color: #0d6efd; border-radius: 0 25px 25px 0; }
        .sidebar i { width: 25px; }

        .main-content { margin-left: 260px; padding: 30px; }

        /* Form Kartı */
        .card-custom { border: none; border-radius: 12px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.03); padding: 30px; }
        
        .form-label { font-weight: 500; color: #495057; }
        .form-control, .form-select { padding: 10px 15px; border-radius: 8px; border: 1px solid #dee2e6; }
        .form-control:focus { box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1); border-color: #0d6efd; }

        /* İlerleme Çubuğu */
        .progress { height: 10px; border-radius: 5px; background-color: #e9ecef; margin-bottom: 25px; }
        .progress-bar { background-color: #198754; transition: width 0.4s ease; }

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
        <a href="create_exam.php" class="active"><i class="fa-solid fa-plus-circle"></i> Sınav Oluştur</a>
        <a href="manage_exams.php"><i class="fa-solid fa-list-check"></i> Sınavları Yönet</a>
        <a href="student_results.php"><i class="fa-solid fa-chart-line"></i> Öğrenci Sonuçları</a>
        <a href="logout.php" class="text-danger mt-3"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a>
    </div>

    <div class="main-content">
        <div class="container">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-dark m-0">Yeni Sınav Oluştur</h2>
                <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Geri</a>
            </div>

            <?php if ($hata): ?>
                <div class="alert alert-danger shadow-sm border-0"><?= $hata ?></div>
            <?php endif; ?>

            <div class="card card-custom">
                
                <div class="d-flex justify-content-between mb-1">
                    <small class="text-muted fw-bold">Form Doluluk Oranı</small>
                    <small class="text-success fw-bold" id="progressText">0%</small>
                </div>
                <div class="progress">
                    <div id="progressBar" class="progress-bar" style="width: 0%"></div>
                </div>

                <form method="POST" id="examForm" onsubmit="return showPreview(event)">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Sınav Başlığı</label>
                            <input type="text" class="form-control" name="title" placeholder="Örn: Matematik Vize Sınavı" required oninput="updateProgress()">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Sınav Açıklaması</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Sınav hakkında kısa bilgi..." required oninput="updateProgress()"></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Başlangıç Zamanı</label>
                            <input type="datetime-local" class="form-control" name="start_time" required oninput="updateProgress()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bitiş Zamanı</label>
                            <input type="datetime-local" class="form-control" name="end_time" required oninput="updateProgress()">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Süre (Dakika)</label>
                            <input type="number" class="form-control" name="duration" placeholder="60" required oninput="updateProgress()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sınav Formatı</label>
                            <select class="form-select" name="format" required onchange="updateProgress()">
                                <option value="">Seçiniz...</option>
                                <option value="coktan_secimli">Çoktan Seçmeli (Test)</option>
                                <option value="klasik">Klasik</option>
                                <option value="karisik">Karışık (Test + Klasik)</option>
                            </select>
                        </div>

                        <div class="col-12 mt-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_paid" name="is_paid" onchange="togglePrice()">
                                <label class="form-check-label fw-bold" for="is_paid">Bu sınav ücretli mi?</label>
                            </div>
                        </div>
                        <div class="col-md-4 mt-2" id="priceDiv" style="display:none;">
                            <label class="form-label">Sınav Ücreti (TL)</label>
                            <div class="input-group">
                                <span class="input-group-text">₺</span>
                                <input type="number" class="form-control" name="price" placeholder="0">
                            </div>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                                <i class="fa-solid fa-eye"></i> Önizle ve Kaydet
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa-solid fa-list-check"></i> Sınav Özeti</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="previewBody">
                    </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Düzenle</button>
                    <button type="button" class="btn btn-success fw-bold" onclick="submitRealForm()">
                        <i class="fa-solid fa-check"></i> Onayla ve Oluştur
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // İlerleme Çubuğu
        function updateProgress() {
            const inputs = document.querySelectorAll('#examForm input[required], #examForm textarea[required], #examForm select[required]');
            let filled = 0;
            inputs.forEach(input => { if(input.value.trim() !== '') filled++; });
            
            let percent = Math.round((filled / inputs.length) * 100);
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressText').innerText = percent + '%';
        }

        // Ücret Alanı Göster/Gizle
        function togglePrice() {
            const isChecked = document.getElementById('is_paid').checked;
            document.getElementById('priceDiv').style.display = isChecked ? 'block' : 'none';
        }

        // Önizleme Modalı
        function showPreview(event) {
            event.preventDefault(); // Formu hemen gönderme
            
            const form = document.forms['examForm'];
            const title = form['title'].value;
            const desc = form['description'].value;
            const start = form['start_time'].value.replace('T', ' ');
            const end = form['end_time'].value.replace('T', ' ');
            const duration = form['duration'].value;
            const format = form['format'].options[form['format'].selectedIndex].text;
            const isPaid = form['is_paid'].checked;
            const price = isPaid ? form['price'].value + ' TL' : '<span class="badge bg-success">Ücretsiz</span>';

            const html = `
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Başlık:</strong> ${title}</li>
                    <li class="list-group-item"><strong>Açıklama:</strong> <br><small class="text-muted">${desc}</small></li>
                    <li class="list-group-item"><strong>Tarih:</strong> ${start} - ${end}</li>
                    <li class="list-group-item"><strong>Süre:</strong> ${duration} Dakika</li>
                    <li class="list-group-item"><strong>Format:</strong> ${format}</li>
                    <li class="list-group-item"><strong>Ücret:</strong> ${price}</li>
                </ul>
            `;
            
            document.getElementById('previewBody').innerHTML = html;
            new bootstrap.Modal(document.getElementById('previewModal')).show();
        }

        // Formu Gerçekten Gönder
        function submitRealForm() {
            document.getElementById('examForm').submit();
        }
    </script>

</body>
</html>