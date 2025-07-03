<?php
$page_title = 'Download Materials';
require_once 'includes/auth_check.php';
require_once 'includes/db_connect.php';

$user_id = $_SESSION['user_id'];

try {
    // Fetch all modules
    $stmt_modules = $pdo->query("SELECT id, title, module_order, pdf_material_path FROM modules ORDER BY module_order ASC");
    $all_modules = $stmt_modules->fetchAll();

    // Fetch completed modules for the user
    $stmt_progress = $pdo->prepare("SELECT module_id FROM user_progress WHERE user_id = ?");
    $stmt_progress->execute([$user_id]);
    $completed_modules = $stmt_progress->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Materials Page Error: " . $e->getMessage());
    $all_modules = [];
    $completed_modules = [];
}

require_once 'includes/header.php';
?>

<div class="bg-white shadow-md rounded-lg p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Module Materials</h2>
    <div class="space-y-4">
        <?php if (empty($all_modules)): ?>
            <p class="text-gray-500">No learning materials are available at this time.</p>
        <?php else: ?>
            <?php foreach ($all_modules as $module): ?>
                <?php
                    $is_completed = in_array($module['id'], $completed_modules);
                    $has_material = !empty($module['pdf_material_path']);
                ?>
                <div class="flex items-center justify-between p-4 border rounded-lg <?= $is_completed && $has_material ? 'bg-white' : 'bg-gray-100' ?>">
                    <div>
                        <p class="font-semibold text-lg text-gray-800">Module <?= escape($module['module_order']) ?>: <?= escape($module['title']) ?></p>
                        <?php if (!$has_material): ?>
                            <p class="text-sm text-gray-500">No material available for this module.</p>
                        <?php elseif (!$is_completed): ?>
                            <p class="text-sm text-gray-500">Complete the module to unlock the material.</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($is_completed && $has_material): ?>
                            <a href="uploads/materials/<?= escape($module['pdf_material_path']) ?>" download class="bg-primary text-white font-semibold py-2 px-4 rounded-lg hover:bg-primary-dark transition-colors">
                                Download PDF
                            </a>
                        <?php else: ?>
                            <button disabled class="bg-gray-300 text-gray-500 font-semibold py-2 px-4 rounded-lg cursor-not-allowed">
                                Locked
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
