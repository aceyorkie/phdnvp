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

if ($request_id <= 0) {
    $vpRole = 'VP';
    $sql = "SELECT request_id FROM signature_flow WHERE role = ? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $vpRole);
    $stmt->execute();
    $stmt->bind_result($latestRequestId);
    if ($stmt->fetch()) {
        $request_id = $latestRequestId;
    } else {
        echo json_encode([]);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();
}

$adviserStatus = '';
$phStatus = '';
$deanstatus = '';
$statusSql = "SELECT role, status FROM signature_flow WHERE request_id = ? AND role IN ('Adviser', 'Program Head', 'Dean')";
$statusStmt = $conn->prepare($statusSql);
$statusStmt->bind_param("i", $request_id);
$statusStmt->execute();
$statusResult = $statusStmt->get_result();
while ($row = $statusResult->fetch_assoc()) {
    if ($row['role'] === 'Adviser') $adviserStatus = $row['status'];
    if ($row['role'] === 'Program Head') $phStatus = $row['status'];
    if ($row['role'] === 'Dean') $deanstatus = $row['status'];
}
$statusStmt->close();

if ($adviserStatus !== 'signed' || $phStatus !== 'signed' || $deanstatus !== 'signed') {
    echo json_encode([]);
    $conn->close();
    exit;
}

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
