<?php
/**
 * Module CRUD API Endpoint
 *
 * Handles Create, Read, Update, Delete operations for modules.
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
$upload_dir = '../../uploads/materials/';

// --- Handle GET requests (for fetching single module) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_module') {
    if (!isset($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'Module ID not provided.']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("SELECT * FROM modules WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $module = $stmt->fetch();
        if ($module) {
            echo json_encode(['success' => true, 'data' => $module]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Module not found.']);
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- Handle POST requests (for add, edit, delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'add_module':
                // Validation
                if (empty($_POST['title']) || !isset($_POST['module_order'])) {
                    $response['message'] = 'Title and Module Order are required.';
                    break;
                }
                // Check for unique module order
                $stmt = $pdo->prepare("SELECT id FROM modules WHERE module_order = ?");
                $stmt->execute([$_POST['module_order']]);
                if ($stmt->fetch()) {
                    $response['message'] = 'This module order number is already in use.';
                    break;
                }

                $pdf_path = null;
                if (isset($_FILES['pdf_material']) && $_FILES['pdf_material']['error'] === UPLOAD_ERR_OK) {
                    $file_name = time() . '_' . basename($_FILES['pdf_material']['name']);
                    if (move_uploaded_file($_FILES['pdf_material']['tmp_name'], $upload_dir . $file_name)) {
                        $pdf_path = $file_name;
                    } else {
                        throw new Exception('Failed to move uploaded file.');
                    }
                }

                $sql = "INSERT INTO modules (title, description, module_order, pdf_material_path) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_POST['title'], $_POST['description'], $_POST['module_order'], $pdf_path]);
                $response = ['success' => true, 'message' => 'Module added successfully.'];
                break;

            case 'edit_module':
                // Validation
                if (empty($_POST['title']) || !isset($_POST['module_order']) || empty($_POST['module_id'])) {
                    $response['message'] = 'Title, Module Order, and Module ID are required.';
                    break;
                }
                // Check for unique module order (excluding self)
                $stmt = $pdo->prepare("SELECT id FROM modules WHERE module_order = ? AND id != ?");
                $stmt->execute([$_POST['module_order'], $_POST['module_id']]);
                if ($stmt->fetch()) {
                    $response['message'] = 'This module order number is already in use.';
                    break;
                }
                
                // Fetch current PDF path
                $stmt = $pdo->prepare("SELECT pdf_material_path FROM modules WHERE id = ?");
                $stmt->execute([$_POST['module_id']]);
                $current_pdf = $stmt->fetchColumn();
                
                $pdf_path = $current_pdf;
                if (isset($_FILES['pdf_material']) && $_FILES['pdf_material']['error'] === UPLOAD_ERR_OK) {
                    // Delete old file if it exists
                    if ($current_pdf && file_exists($upload_dir . $current_pdf)) {
                        unlink($upload_dir . $current_pdf);
                    }
                    // Upload new file
                    $file_name = time() . '_' . basename($_FILES['pdf_material']['name']);
                    if (move_uploaded_file($_FILES['pdf_material']['tmp_name'], $upload_dir . $file_name)) {
                        $pdf_path = $file_name;
                    } else {
                        throw new Exception('Failed to move uploaded file.');
                    }
                }

                $sql = "UPDATE modules SET title = ?, description = ?, module_order = ?, pdf_material_path = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_POST['title'], $_POST['description'], $_POST['module_order'], $pdf_path, $_POST['module_id']]);
                $response = ['success' => true, 'message' => 'Module updated successfully.'];
                break;

            case 'delete_module':
                 if (empty($_POST['module_id'])) {
                    $response['message'] = 'Module ID is required.';
                    break;
                }
                // Fetch PDF path to delete the file
                $stmt = $pdo->prepare("SELECT pdf_material_path FROM modules WHERE id = ?");
                $stmt->execute([$_POST['module_id']]);
                $pdf_to_delete = $stmt->fetchColumn();

                $stmt = $pdo->prepare("DELETE FROM modules WHERE id = ?");
                $stmt->execute([$_POST['module_id']]);
                
                if ($stmt->rowCount() > 0) {
                    if ($pdf_to_delete && file_exists($upload_dir . $pdf_to_delete)) {
                        unlink($upload_dir . $pdf_to_delete);
                    }
                    $response = ['success' => true, 'message' => 'Module deleted successfully.'];
                } else {
                     $response['message'] = 'Module not found or could not be deleted.';
                }
                break;
        }
    } catch (PDOException | Exception $e) {
        error_log($e->getMessage());
        $response['message'] = 'A server error occurred: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>
