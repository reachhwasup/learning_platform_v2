<?php
// 1. Protect this page: Make sure user is logged in
require_once 'includes/auth_check.php';

// 2. Database Connection
require_once 'includes/db_connect.php';

// 3. Get User ID from session
$user_id = $_SESSION['user_id'];

try {
    // 4. Fetch all modules with their video thumbnails
    $sql_modules = "SELECT m.id, m.title, m.description, m.module_order, v.thumbnail_path 
                    FROM modules m
                    LEFT JOIN videos v ON m.id = v.module_id
                    ORDER BY m.module_order ASC";
    $stmt_modules = $pdo->query($sql_modules);
    $all_modules = $stmt_modules->fetchAll();

    // 5. Fetch all module IDs that the current user has completed
    $sql_progress = "SELECT module_id FROM user_progress WHERE user_id = :user_id";
    $stmt_progress = $pdo->prepare($sql_progress);
    $stmt_progress->execute(['user_id' => $user_id]);
    $completed_modules = $stmt_progress->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    die("An error occurred while loading the dashboard. Please try again later.");
}

$page_title = 'My Learning Dashboard';
require_once 'includes/header.php';
?>

<!-- Page-specific content starts here -->
<div class="container mx-auto">
    <?php if (empty($all_modules)): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
            <p class="font-bold">No Modules Available</p>
            <p>There are currently no learning modules available. Please check back later.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <?php foreach ($all_modules as $index => $module): ?>
                <?php
                    // Determine module status
                    $is_completed = in_array($module['id'], $completed_modules);
                    
                    $is_locked = true;
                    if ($index === 0) {
                        $is_locked = false; // First module is never locked
                    } else {
                        $previous_module_id = $all_modules[$index - 1]['id'];
                        if (in_array($previous_module_id, $completed_modules)) {
                            $is_locked = false; // Unlocked because previous is complete
                        }
                    }
                    if ($is_completed) $is_locked = false;

                    $thumbnail_url = !empty($module['thumbnail_path']) ? 'uploads/thumbnails/' . $module['thumbnail_path'] : 'https://placehold.co/600x400/0052cc/FFFFFF?text=Module+' . $module['module_order'];
                ?>

                <!-- Module Card -->
                <div class="bg-white shadow-lg rounded-lg overflow-hidden transform transition-transform duration-300 hover:scale-105">
                    <a href="<?= !$is_locked ? 'view_module.php?id=' . escape($module['id']) : '#' ?>" class="<?= $is_locked ? 'pointer-events-none' : '' ?>">
                        <div class="relative">
                            <img class="w-full h-48 object-cover" src="<?= escape($thumbnail_url) ?>" alt="Module Thumbnail">
                            
                            <?php if ($is_locked): ?>
                                <div class="absolute inset-0 bg-black bg-opacity-60 flex items-center justify-center">
                                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6.364-6.364l-1.414 1.414M21 12h-2M12 3V1m-6.364 6.364L4.222 7.222M19.778 7.222l-1.414 1.414M12 21a9 9 0 110-18 9 9 0 010 18zM12 9v6"></path></svg>
                                </div>
                            <?php elseif ($is_completed): ?>
                                 <div class="absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full">COMPLETED</div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-900 h-14">
                            Module <?= escape($module['module_order']) ?>: <?= escape($module['title']) ?>
                        </h3>
                        <p class="text-gray-600 mt-2 text-sm h-16 overflow-hidden">
                            <?= escape($module['description']) ?>
                        </p>
                        <div class="mt-4">
                             <?php if ($is_locked): ?>
                                <button disabled class="w-full bg-gray-300 text-gray-500 font-bold py-2 px-4 rounded-lg cursor-not-allowed">Locked</button>
                            <?php else: ?>
                                <a href="view_module.php?id=<?= escape($module['id']) ?>" class="block w-full text-center bg-primary text-white font-bold py-2 px-4 rounded-lg hover:bg-primary-dark transition-colors">
                                    <?= $is_completed ? 'Review Module' : 'Start Module' ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>
