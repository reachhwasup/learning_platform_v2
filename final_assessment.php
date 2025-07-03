<?php
// 1. Authentication and Initialization
require_once 'includes/auth_check.php';
require_once 'includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$can_take_assessment = false;
$reason = '';

try {
    // 2. Check if user has completed all modules
    $total_modules = $pdo->query("SELECT COUNT(*) FROM modules")->fetchColumn();
    
    $stmt_progress = $pdo->prepare("SELECT COUNT(*) FROM user_progress WHERE user_id = ?");
    $stmt_progress->execute([$user_id]);
    $completed_modules = $stmt_progress->fetchColumn();

    if ($total_modules > 0 && $completed_modules >= $total_modules) {
        $can_take_assessment = true;
    } else {
        $reason = "You must complete all learning modules before taking the final assessment.";
    }

    // 3. Check if user has already passed the assessment
    if ($can_take_assessment) {
        $stmt_passed = $pdo->prepare("SELECT id FROM final_assessments WHERE user_id = ? AND status = 'passed'");
        $stmt_passed->execute([$user_id]);
        if ($stmt_passed->fetch()) {
            $can_take_assessment = false;
            $reason = "Congratulations! You have already passed the final assessment.";
        }
    }

    // 4. Fetch questions if user is eligible
    $questions = [];
    $options = [];
    if ($can_take_assessment) {
        $sql_questions = "SELECT id, question_text, question_type 
                          FROM questions 
                          WHERE is_final_exam_question = 1 
                          ORDER BY RAND() 
                          LIMIT 20";
        $stmt_questions = $pdo->query($sql_questions);
        $questions = $stmt_questions->fetchAll();

        $question_ids = array_column($questions, 'id');
        if (!empty($question_ids)) {
            $sql_options = "SELECT id, question_id, option_text FROM question_options WHERE question_id IN (" . implode(',', $question_ids) . ")";
            $stmt_options = $pdo->query($sql_options);
            while ($row = $stmt_options->fetch()) {
                $options[$row['question_id']][] = $row;
            }
        }
    }

} catch (PDOException $e) {
    error_log("Final Assessment Page Error: " . $e->getMessage());
    die("An error occurred while loading the assessment. Please try again later.");
}

$page_title = 'Final Assessment';
require_once 'includes/header.php';
?>

<div class="bg-white shadow-md rounded-lg p-6 md:p-8">
    <?php if (!$can_take_assessment): ?>
        <!-- Not eligible message -->
        <div class="text-center">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Access Denied</h2>
            <p class="text-gray-600 mb-6 max-w-xl mx-auto"><?= escape($reason) ?></p>
            <?php if (str_contains($reason, 'Congratulations')): ?>
                 <a href="my_certificates.php" class="bg-green-600 text-white font-semibold py-2 px-6 rounded-lg hover:bg-green-700 transition-colors">View My Certificate</a>
            <?php else: ?>
                <a href="dashboard.php" class="bg-primary text-white font-semibold py-2 px-6 rounded-lg hover:bg-primary-dark transition-colors">Back to Dashboard</a>
            <?php endif; ?>
        </div>
    <?php elseif (empty($questions)): ?>
        <!-- No questions available message -->
        <div class="text-center">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Assessment Not Ready</h2>
            <p class="text-gray-600 mb-6">The final assessment is not available yet as there are no questions. Please contact an administrator.</p>
            <a href="dashboard.php" class="bg-primary text-white font-semibold py-2 px-6 rounded-lg hover:bg-primary-dark transition-colors">Back to Dashboard</a>
        </div>
    <?php else: ?>
        <!-- Assessment Quiz Form -->
        <div id="assessment-quiz">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Final Assessment</h2>
            <p class="text-gray-600 mb-8">Answer the following 20 questions. A score of 80 points or higher is required to pass.</p>
            <form id="assessment-form">
                <div class="space-y-8">
                    <?php foreach ($questions as $q_index => $question): ?>
                        <div class="question-block border-t border-gray-200 pt-6">
                            <p class="font-semibold text-lg text-gray-900"><?= ($q_index + 1) . '. ' . escape($question['question_text']) ?> <span class="text-sm font-normal text-gray-500">(5 Points)</span></p>
                            <div class="mt-4 space-y-2">
                                <?php foreach ($options[$question['id']] as $option): ?>
                                    <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                        <input type="<?= $question['question_type'] === 'single' ? 'radio' : 'checkbox' ?>" 
                                               name="answers[<?= $question['id'] ?>]<?= $question['question_type'] === 'multiple' ? '[]' : '' ?>" 
                                               value="<?= $option['id'] ?>" 
                                               class="h-5 w-5 text-primary focus:ring-primary border-gray-300">
                                        <span class="ml-3 text-gray-700"><?= escape($option['option_text']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-10">
                    <button type="submit" class="w-full bg-green-600 text-white font-bold py-4 px-6 rounded-lg hover:bg-green-700 transition-colors text-xl">
                        Submit Final Assessment
                    </button>
                </div>
            </form>
        </div>

        <!-- Assessment Result - Initially Hidden -->
        <div id="assessment-result" class="hidden text-center py-10">
            <div id="result-icon" class="w-24 h-24 mx-auto rounded-full flex items-center justify-center mb-6">
                <!-- Icon will be inserted by JS -->
            </div>
            <h2 id="result-title" class="text-4xl font-bold mb-4"></h2>
            <p id="result-message" class="text-xl text-gray-700 mb-2"></p>
            <p class="text-5xl font-bold text-gray-800 mb-8">
                <span id="result-score"></span> / <span id="total-score"></span> Points
            </p>
            <div id="result-actions">
                <!-- Action buttons will be inserted by JS -->
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('assessment-form');
    if (!form) return;

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        
        if (!confirm('Are you sure you want to submit your answers? This action cannot be undone.')) {
            return;
        }

        const formData = new FormData(form);
        const quizDiv = document.getElementById('assessment-quiz');
        const resultDiv = document.getElementById('assessment-result');
        
        quizDiv.innerHTML = '<p class="text-center text-2xl font-semibold py-20">Submitting and grading your answers...</p>';

        fetch('api/learning/submit_assessment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            quizDiv.classList.add('hidden');
            resultDiv.classList.remove('hidden');

            const resultIcon = document.getElementById('result-icon');
            const resultTitle = document.getElementById('result-title');
            const resultMessage = document.getElementById('result-message');
            const resultScore = document.getElementById('result-score');
            const totalScore = document.getElementById('total-score');
            const resultActions = document.getElementById('result-actions');

            resultScore.textContent = data.score;
            totalScore.textContent = data.total_score;
            
            if (data.success && data.status === 'passed') {
                resultIcon.className += ' bg-green-100 text-green-600';
                resultIcon.innerHTML = '<svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>';
                resultTitle.textContent = 'Congratulations, you passed!';
                resultTitle.className = 'text-green-600';
                resultMessage.textContent = 'You have successfully completed the Security Awareness training.';
                resultActions.innerHTML = `<a href="my_certificates.php" class="bg-primary text-white font-semibold py-3 px-8 rounded-lg hover:bg-primary-dark transition-colors">View My Certificate</a>`;
            } else {
                resultIcon.className += ' bg-red-100 text-red-600';
                resultIcon.innerHTML = '<svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>';
                resultTitle.textContent = 'Assessment Failed';
                resultTitle.className = 'text-red-600';
                resultMessage.textContent = 'Unfortunately, you did not achieve the passing score of 80 points.';
                resultActions.innerHTML = `<a href="final_assessment.php" class="bg-red-600 text-white font-semibold py-3 px-8 rounded-lg hover:bg-red-700 transition-colors mr-4">Try Again</a>
                                           <a href="dashboard.php" class="bg-gray-200 text-gray-800 font-semibold py-3 px-8 rounded-lg hover:bg-gray-300 transition-colors">Review Modules</a>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            quizDiv.innerHTML = `<p class="text-center text-red-600 text-xl py-20">A server error occurred. Please try again later.</p>`;
        });
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>
