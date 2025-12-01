<?php
header('Content-Type: application/json');

$host = 'localhost';
$db = 'practice_db';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed.']));
}

$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;
$signatory_id = isset($_GET['signatory_id']) ? $conn->real_escape_string($_GET['signatory_id']) : '';

if ($request_id <= 0 || !$signatory_id) {
    echo json_encode(['error' => 'Invalid request ID or Program Head ID.']);
    exit;
}

$checkSql = "SELECT 1 FROM signature_flow WHERE request_id = ? AND role = 'Program Head' AND signatory_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("is", $request_id, $signatory_id);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows === 0) {
    echo json_encode([]);
    $checkStmt->close();
    $conn->close();
    exit;
}
$checkStmt->close();

$basePath = 'http://localhost/officerDashboardCopy/create_org/';

$stmt = $conn->prepare("SELECT file_name, file_path, doc_type FROM document_files WHERE request_id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

$docs = [];
while ($row = $result->fetch_assoc()) {
    $row['file_path'] = $basePath . $row['file_path'];
    $docs[] = $row;
}

echo json_encode($docs);
$stmt->close();
$conn->close();
?>
