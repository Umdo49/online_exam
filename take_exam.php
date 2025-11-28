<?php
include 'includes/auth.php';
include 'includes/header.php';

// Sınav ID'sini al (örneğin URL'den)
$exam_id = $_GET['exam_id'] ?? null;

// Sınav ve soruları veritabanından çek
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

$questions = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ?");
$questions->execute([$exam_id]);
?>

<div class="exam-container">
    <h2><?= htmlspecialchars($exam['title']); ?></h2>
    <div id="timer"></div>
    
    <form id="examForm" method="POST" action="submit_exam.php">
        <?php foreach ($questions as $index => $question): ?>
            <div class="question-card">
                <p>Soru <?= $index + 1; ?>: <?= htmlspecialchars($question['question_text']); ?></p>
                
                <?php if ($question['question_type'] === 'multiple_choice'): ?>
                    <?php $options = json_decode($question['options'], true); ?>
                    <?php foreach ($options as $key => $value): ?>
                        <label>
                            <input type="radio" name="answers[<?= $question['id'] ?>]" value="<?= $key ?>">
                            <?= htmlspecialchars($value); ?>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <textarea name="answers[<?= $question['id'] ?>]"></textarea>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <button type="submit">Sınavı Bitir</button>
    </form>
</div>

<script>
// Geri sayım için JavaScript
const duration = <?= $exam['duration'] * 60; ?>; // Saniyeye çevir
startTimer(duration);

function startTimer(duration) {
    let timer = duration, minutes, seconds;
    const interval = setInterval(() => {
        minutes = parseInt(timer / 60, 10);
        seconds = parseInt(timer % 60, 10);
        document.getElementById('timer').textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
        if (--timer < 0) {
            clearInterval(interval);
            document.getElementById('examForm').submit();
        }
    }, 1000);
}
</script>

<?php include 'includes/footer.php'; ?>