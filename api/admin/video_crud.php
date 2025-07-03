<?php
/**
 * Video CRUD API Endpoint
 *
 * Handles video and thumbnail uploads and updates for modules.
 */

header('Content-Type: application/json');
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// --- Admin Authentication ---
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

$response = ['success' => false, 'message' => 'Invalid request.'];
$video_upload_dir = '../../uploads/videos/';
$thumb_upload_dir = '../../uploads/thumbnails/';
$admin_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $module_id = $_POST['module_id'];
    $video_title = $_POST['video_title'];
    $video_description = $_POST['video_description'];
    $video_id = $_POST['video_id'] ?? null;

    try {
        $pdo->beginTransaction();
        
        $video_path_to_update = null;
        $thumb_path_to_update = null;

        // Fetch existing paths if we are editing
        $existing_paths = ['video_path' => null, 'thumbnail_path' => null];
        if ($action === 'edit_video' && $video_id) {
            $stmt_old = $pdo->prepare("SELECT video_path, thumbnail_path FROM videos WHERE id = ?");
            $stmt_old->execute([$video_id]);
            $existing_paths = $stmt_old->fetch();
        }

        // Handle video file upload
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
            if ($existing_paths['video_path'] && file_exists($video_upload_dir . $existing_paths['video_path'])) {
                unlink($video_upload_dir . $existing_paths['video_path']);
            }
            $file_ext = pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION);
            $file_name = 'module_' . $module_id . '_video_' . time() . '.' . $file_ext;
            if (!move_uploaded_file($_FILES['video_file']['tmp_name'], $video_upload_dir . $file_name)) {
                throw new Exception('Failed to move uploaded video file.');
            }
            $video_path_to_update = $file_name;
        }

        // Handle thumbnail file upload
        if (isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] === UPLOAD_ERR_OK) {
            if ($existing_paths['thumbnail_path'] && file_exists($thumb_upload_dir . $existing_paths['thumbnail_path'])) {
                unlink($thumb_upload_dir . $existing_paths['thumbnail_path']);
            }
            $thumb_ext = pathinfo($_FILES['thumbnail_file']['name'], PATHINFO_EXTENSION);
            $thumb_name = 'module_' . $module_id . '_thumb_' . time() . '.' . $thumb_ext;
            if (!move_uploaded_file($_FILES['thumbnail_file']['tmp_name'], $thumb_upload_dir . $thumb_name)) {
                throw new Exception('Failed to move uploaded thumbnail file.');
            }
            $thumb_path_to_update = $thumb_name;
        }

        if ($action === 'add_video') {
            if (!$video_path_to_update) throw new Exception('A video file is required when adding a new video.');
            
            $sql = "INSERT INTO videos (module_id, title, description, video_path, thumbnail_path, upload_by) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$module_id, $video_title, $video_description, $video_path_to_update, $thumb_path_to_update, $admin_id]);
            $response = ['success' => true, 'message' => 'Video uploaded and saved successfully.'];

        } elseif ($action === 'edit_video' && $video_id) {
            $sql = "UPDATE videos SET title = ?, description = ?";
            $params = [$video_title, $video_description];
            if ($video_path_to_update) {
                $sql .= ", video_path = ?";
                $params[] = $video_path_to_update;
            }
            if ($thumb_path_to_update) {
                $sql .= ", thumbnail_path = ?";
                $params[] = $thumb_path_to_update;
            }
            $sql .= " WHERE id = ?";
            $params[] = $video_id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $response = ['success' => true, 'message' => 'Video information updated successfully.'];
        }

        $pdo->commit();
    } catch (Exception $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        $response['message'] = 'A server error occurred: ' . $e->getMessage();
    }
}

echo json_encode($response);
