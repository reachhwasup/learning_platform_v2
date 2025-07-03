<?php
/**
 * User CRUD API Endpoint
 *
 * Handles Create, Read, Update, Delete operations for users by an admin.
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

// --- Handle GET requests (for fetching single user) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_user') {
    if (!isset($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'User ID not provided.']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, staff_id, role, status FROM users WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $user = $stmt->fetch();
        if ($user) {
            echo json_encode(['success' => true, 'data' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
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
            case 'add_user':
                // Validation
                $required = ['first_name', 'last_name', 'email', 'staff_id', 'department_id', 'password', 'role', 'status'];
                foreach ($required as $field) {
                    if (empty($_POST[$field])) {
                        $response['message'] = 'Please fill all required fields.';
                        echo json_encode($response); exit;
                    }
                }
                if (strlen($_POST['password']) < 8) {
                    $response['message'] = 'Password must be at least 8 characters.';
                    echo json_encode($response); exit;
                }
                // Check for duplicate email or staff ID
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR staff_id = ?");
                $stmt->execute([$_POST['email'], $_POST['staff_id']]);
                if ($stmt->fetch()) {
                    $response['message'] = 'Email or Staff ID is already in use.';
                    echo json_encode($response); exit;
                }

                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (first_name, last_name, email, staff_id, password, department_id, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['staff_id'],
                    $hashed_password, $_POST['department_id'], $_POST['role'], $_POST['status']
                ]);
                $response = ['success' => true, 'message' => 'User added successfully.'];
                break;

            case 'edit_user':
                // Validation
                if (empty($_POST['user_id']) || empty($_POST['role']) || empty($_POST['status'])) {
                    $response['message'] = 'User ID, Role, and Status are required.';
                    break;
                }
                // Prevent admin from changing their own role or status
                if ($_POST['user_id'] == $_SESSION['user_id']) {
                    $response['message'] = 'Admins cannot change their own role or status.';
                    break;
                }

                $sql = "UPDATE users SET role = ?, status = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_POST['role'], $_POST['status'], $_POST['user_id']]);
                $response = ['success' => true, 'message' => 'User updated successfully.'];
                break;

            case 'delete_user':
                 if (empty($_POST['user_id'])) {
                    $response['message'] = 'User ID is required.';
                    break;
                }
                // Prevent admin from deleting themselves
                if ($_POST['user_id'] == $_SESSION['user_id']) {
                    $response['message'] = 'Admins cannot delete their own account.';
                    break;
                }

                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$_POST['user_id']]);
                
                if ($stmt->rowCount() > 0) {
                    $response = ['success' => true, 'message' => 'User deleted successfully.'];
                } else {
                     $response['message'] = 'User not found or could not be deleted.';
                }
                break;
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $response['message'] = 'A database error occurred: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>
