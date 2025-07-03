<?php
/**
 * Generate Report API
 *
 * This script generates and downloads an Excel (.xlsx) file of assessment results.
 * It requires the PhpSpreadsheet library installed via Composer.
 */

// Authenticate and initialize
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// --- Admin Authentication ---
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die('Access Denied: You must be an administrator to access this feature.');
}

// --- Check for PhpSpreadsheet ---
$vendor_autoload = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($vendor_autoload)) {
    die('Error: The required library PhpSpreadsheet is not installed. Please run "composer install" in the project root.');
}
require_once $vendor_autoload;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// --- Validate Input ---
$status = $_GET['status'] ?? '';
if ($status !== 'passed' && $status !== 'failed') {
    die('Invalid report type specified.');
}

// --- Fetch Data from Database ---
try {
    $sql = "SELECT u.first_name, u.last_name, u.staff_id, u.email, d.name as department_name, fa.score, fa.completed_at
            FROM final_assessments fa
            JOIN users u ON fa.user_id = u.id
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE fa.status = :status
            ORDER BY fa.completed_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['status' => $status]);
    $results = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Report Generation Error: " . $e->getMessage());
    die('A database error occurred while generating the report.');
}

// --- Create Spreadsheet ---
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle(ucfirst($status) . ' Assessments');

// --- Set Headers ---
$headers = ['First Name', 'Last Name', 'Staff ID', 'Email', 'Department', 'Score (%)', 'Completion Date'];
$sheet->fromArray($headers, NULL, 'A1');

// Style the header row
$header_style = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '075985']]
];
$sheet->getStyle('A1:G1')->applyFromArray($header_style);


// --- Populate Data ---
$row_index = 2;
foreach ($results as $row) {
    $sheet->fromArray([
        $row['first_name'],
        $row['last_name'],
        $row['staff_id'],
        $row['email'],
        $row['department_name'] ?? 'N/A',
        number_format($row['score'], 2),
        date('Y-m-d H:i:s', strtotime($row['completed_at']))
    ], NULL, 'A' . $row_index);
    $row_index++;
}

// --- Auto-size columns ---
foreach (range('A', 'G') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}


// --- Set Headers for Download ---
$filename = "assessment_report_{$status}_" . date('Y-m-d') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// --- Output the file ---
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
