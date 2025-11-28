<?php
session_start();
require 'db.php';

// Güvenlik: Öğrenci mi?
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogrenci') {
    header("Location: login.php");
    exit;
}

$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : null;
$user_id = $_SESSION['user']['id'];

// Zaman Dilimi Ayarı (Önemli)
date_default_timezone_set('Europe/Istanbul');

if (!$exam_id) {
    die("Geçerli bir sınav seçilmedi.");
}

// 1. Sınav Bilgisini Çek
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die("Sınav bulunamadı.");
}

// Tarih Kontrolü
$now = new DateTime();
$start_time = new DateTime($exam['start_time']);
$end_time = new DateTime($exam['end_time']);

// --- SENARYO 1: SINAV HENÜZ BAŞLAMADI (BEKLEME EKRANI) ---
if ($now < $start_time) {
    $remaining_seconds = $start_time->getTimestamp() - $now->getTimestamp();
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <title>Sınav Bekleniyor</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body { background-color: #f3f4f6; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: 'Segoe UI', sans-serif; }
            .wait-card { background: white; padding: 40px; border-radius: 15px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 500px; width: 100%; }
            .countdown { font-size: 2.5rem; font-weight: bold; color: #0d6efd; margin: 20px 0; font-family: monospace; }
        </style>
    </head>
    <body>
        <div class="wait-card">
            <i class="fa-solid fa-clock fa-3x text-warning mb-3"></i>
            <h3>Sınav Henüz Başlamadı</h3>
            <p class="text-muted"><?= htmlspecialchars($exam['title']) ?> sınavı için başlangıç zamanı:</p>
            <h5 class="fw-bold"><?= $start_time->format('d.m.Y H:i') ?></h5>
            
            <div class="countdown" id="countdown">Hesaplanıyor...</div>
            
            <p class="small text-muted">Sayfa süre dolduğunda otomatik yenilenecektir.</p>
            <a href="dashboard.php" class="btn btn-outline-secondary mt-2">Panele Dön</a>
        </div>

        <script>
            let seconds = <?= $remaining_seconds ?>;
            
            function updateTimer() {
                const timer = document.getElementById('countdown');
                if (seconds <= 0) {
                    timer.innerHTML = "Başlıyor...";
                    setTimeout(() => location.reload(), 2000); // Sayfayı yenile
                    return;
                }
                
                let h = Math.floor(seconds / 3600);
                let m = Math.floor((seconds % 3600) / 60);
                let s = seconds % 60;
                
                timer.innerHTML = 
                    (h < 10 ? "0" + h : h) + ":" + 
                    (m < 10 ? "0" + m : m) + ":" + 
                    (s < 10 ? "0" + s : s);
                
                seconds--;
            }
            setInterval(updateTimer, 1000);
            updateTimer();
        </script>
    </body>
    </html>
    <?php
    exit;
}

// --- SENARYO 2: SINAV SÜRESİ DOLDU ---
if ($now > $end_time) {
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>body { background-color: #f3f4f6; display: flex; align-items: center; justify-content: center; height: 100vh; }</style>
    </head>
    <body>
        <div class="card p-5 text-center shadow border-0" style="border-radius: 15px;">
            <div class="text-danger mb-3" style="font-size: 3rem;">⚠️</div>
            <h3>Sınav Süresi Doldu</h3>
            <p>Bu sınavın katılım süresi sona ermiştir.</p>
            <a href="dashboard.php" class="btn btn-primary">Panele Dön</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- SENARYO 3: SINAV AKTİF (SINAV SAYFASI) ---

// Katılım Kontrolü (Daha önce girmiş mi?)
$checkStmt = $pdo->prepare("SELECT COUNT(*) FROM answers WHERE user_id = ? AND exam_id = ?");
$checkStmt->execute([$user_id, $exam_id]);
if ($checkStmt->fetchColumn() > 0) {
    header("Location: exam_results.php?exam_id=$exam_id");
    exit;
}

// Soruları Çek
$stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id ASC");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($questions)) {
    die("Bu sınav için soru bulunmamaktadır.");
}

// Süre Hesaplama (Kalan Süre)
// Sınavın bitiş tarihi ile öğrencinin kullanabileceği süre (duration) arasındaki en yakın zamanı al.
$duration_seconds = $exam['duration_minutes'] * 60;
$server_now = time();
$absolute_end_time = $end_time->getTimestamp(); // Sınavın veritabanındaki kesin bitiş saati
$calculated_end_time = $server_now + $duration_seconds; // Öğrenci şu an başlarsa biteceği saat

// Eğer öğrencinin süresi sınavın kapanış saatini geçiyorsa, süreyi kısalt (Sınav kapanışına kadar süre ver)
$final_end_timestamp = min($calculated_end_time, $absolute_end_time);

// Session kontrolü (Sayfa yenilenirse süre sıfırlanmasın)
if (!isset($_SESSION['exam_end_' . $exam_id])) {
    $_SESSION['exam_end_' . $exam_id] = $final_end_timestamp;
}
$end_timestamp = $_SESSION['exam_end_' . $exam_id];

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($exam['title']) ?> | Sınav</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* (Önceki CSS kodları aynı kalacak) */
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f3f4f6; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            user-select: none; /* Metin seçimini engelle */
        }

        .exam-wrapper {
            background: white;
            width: 100%;
            max-width: 800px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
            position: relative;
        }

        .exam-header {
            background: #fff;
            border-bottom: 1px solid #eee;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .exam-title { font-weight: 700; font-size: 1.2rem; color: #333; margin: 0; }
        
        .timer-box {
            background: #eef2ff;
            color: #4f46e5;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 700;
            font-family: monospace;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .timer-warning { background: #fee2e2; color: #dc2626; animation: pulse 1s infinite; }

        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }

        .question-area { padding: 40px 30px; min-height: 300px; }
        .question-text { font-size: 1.25rem; font-weight: 500; color: #1f2937; margin-bottom: 30px; line-height: 1.6; }
        
        .option-label {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 10px;
        }
        .option-label:hover { border-color: #4f46e5; background: #eef2ff; }
        .option-label input { margin-right: 15px; transform: scale(1.3); }
        .option-label.selected { border-color: #4f46e5; background: #e0e7ff; color: #312e81; font-weight: 600; }

        textarea.form-control { resize: none; border: 2px solid #e5e7eb; border-radius: 10px; padding: 15px; font-size: 1rem; }
        textarea.form-control:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }

        .exam-footer {
            border-top: 1px solid #eee;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f9fafb;
        }
        
        .btn-custom { padding: 10px 25px; border-radius: 8px; font-weight: 600; transition: 0.2s; }
        .btn-next { background: #4f46e5; color: white; border: none; }
        .btn-prev { background: #fff; color: #4b5563; border: 1px solid #d1d5db; }
        .btn-finish { background: #059669; color: white; border: none; }

        .question-palette { display: flex; gap: 5px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 5px; }
        .q-dot { 
            width: 30px; height: 30px; 
            border-radius: 50%; 
            background: #e5e7eb; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 0.8rem; font-weight: 600; color: #6b7280;
            flex-shrink: 0; cursor: pointer;
        }
        .q-dot.active { background: #4f46e5; color: white; }
        .q-dot.answered { background: #d1fae5; color: #065f46; border: 1px solid #059669; }
    </style>
</head>
<body>

<div class="exam-wrapper">
    
    <div class="exam-header">
        <h1 class="exam-title"><?= htmlspecialchars($exam['title']) ?></h1>
        <div class="timer-box" id="timer">
            <i class="fa-regular fa-clock"></i> <span id="timeDisplay">--:--</span>
        </div>
    </div>

    <form id="examForm" method="POST" action="submit_exam.php">
        <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
        
        <div class="question-area">
            <div class="question-palette" id="palette"></div>

            <h4 id="qNumber" class="text-muted mb-3 small fw-bold text-uppercase">Soru 1 / <?= count($questions) ?></h4>
            <div id="qText" class="question-text">Soru yükleniyor...</div>
            
            <div id="optionsContainer"></div>
        </div>

        <div class="exam-footer">
            <button type="button" class="btn btn-custom btn-prev" id="prevBtn" disabled>
                <i class="fa-solid fa-arrow-left me-2"></i> Önceki
            </button>
            <button type="button" class="btn btn-custom btn-next" id="nextBtn">
                Sonraki <i class="fa-solid fa-arrow-right ms-2"></i>
            </button>
            <button type="submit" class="btn btn-custom btn-finish" id="submitBtn" style="display:none;">
                Sınavı Bitir <i class="fa-solid fa-check ms-2"></i>
            </button>
        </div>
    </form>

</div>

<script>
    const questions = <?= json_encode($questions) ?>;
    const endTimestamp = <?= $end_timestamp ?>; 

    let currentIndex = 0;
    let userAnswers = {}; 

    // DOM Elementleri
    const qText = document.getElementById('qText');
    const qNumber = document.getElementById('qNumber');
    const optionsContainer = document.getElementById('optionsContainer');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const timerDisplay = document.getElementById('timeDisplay');
    const timerBox = document.getElementById('timer');
    const palette = document.getElementById('palette');
    const examForm = document.getElementById('examForm');

    function renderQuestion(index) {
        const q = questions[index];
        qNumber.innerText = `SORU ${index + 1} / ${questions.length}`;
        qText.innerText = q.question_text;
        optionsContainer.innerHTML = '';

        if (q.question_type === 'coktan_secimli') {
            const opts = JSON.parse(q.options || '{}');
            ['A', 'B', 'C', 'D', 'E'].forEach(key => {
                if (!opts[key]) return; 

                const label = document.createElement('label');
                label.className = 'option-label';
                
                const input = document.createElement('input');
                input.type = 'radio';
                input.name = `ans_${q.id}`;
                input.value = key;
                
                if (userAnswers[q.id] === key) {
                    input.checked = true;
                    label.classList.add('selected');
                }

                input.addEventListener('change', () => {
                    userAnswers[q.id] = key;
                    document.querySelectorAll('.option-label').forEach(l => l.classList.remove('selected'));
                    label.classList.add('selected');
                    updatePalette();
                });

                label.appendChild(input);
                label.appendChild(document.createTextNode(`${key}) ${opts[key]}`));
                optionsContainer.appendChild(label);
            });
        } else {
            const textarea = document.createElement('textarea');
            textarea.className = 'form-control';
            textarea.rows = 6;
            textarea.placeholder = 'Cevabınızı buraya yazınız...';
            
            if (userAnswers[q.id]) textarea.value = userAnswers[q.id];

            textarea.addEventListener('input', (e) => {
                userAnswers[q.id] = e.target.value;
                updatePalette();
            });

            optionsContainer.appendChild(textarea);
        }

        prevBtn.disabled = (index === 0);
        
        if (index === questions.length - 1) {
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'inline-block';
        } else {
            nextBtn.style.display = 'inline-block';
            submitBtn.style.display = 'none';
        }

        updatePalette();
    }

    function initPalette() {
        palette.innerHTML = '';
        questions.forEach((q, i) => {
            const dot = document.createElement('div');
            dot.className = 'q-dot';
            dot.innerText = i + 1;
            dot.onclick = () => { currentIndex = i; renderQuestion(i); };
            palette.appendChild(dot);
        });
    }

    function updatePalette() {
        document.querySelectorAll('.q-dot').forEach((dot, i) => {
            dot.classList.remove('active', 'answered');
            if (i === currentIndex) dot.classList.add('active');
            if (userAnswers[questions[i].id]) dot.classList.add('answered');
        });
    }

    prevBtn.onclick = () => { if (currentIndex > 0) { currentIndex--; renderQuestion(currentIndex); } };
    nextBtn.onclick = () => { if (currentIndex < questions.length - 1) { currentIndex++; renderQuestion(currentIndex); } };

    // Sayaç
    function startTimer() {
        const interval = setInterval(() => {
            const now = Math.floor(Date.now() / 1000);
            const remaining = endTimestamp - now;

            if (remaining <= 0) {
                clearInterval(interval);
                timerDisplay.innerText = "00:00";
                alert("Süre doldu! Sınavınız otomatik olarak gönderiliyor.");
                submitRealForm();
            } else {
                const m = Math.floor(remaining / 60);
                const s = remaining % 60;
                timerDisplay.innerText = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;

                if (remaining < 60) timerBox.classList.add('timer-warning');
            }
        }, 1000);
    }

    function submitRealForm() {
        for (const [qid, val] of Object.entries(userAnswers)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `answers[${qid}]`;
            input.value = val;
            examForm.appendChild(input);
        }
        examForm.submit();
    }

    examForm.addEventListener('submit', (e) => {
        e.preventDefault();
        if (confirm('Sınavı bitirmek istediğinize emin misiniz?')) {
            submitRealForm();
        }
    });

    initPalette();
    renderQuestion(0);
    startTimer();

</script>

</body>
</html>