<?php
header('Content-Type: application/json');

error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . json_encode($_POST));
error_log("FILES data: " . json_encode(array_keys($_FILES)));

$host = 'localhost';
$db = 'practice_db';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'DB Connection failed: ' . $conn->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$request_id = intval($_POST['request_id'] ?? 0);
$event_id   = intval($_POST['event_id'] ?? 0);
$role       = trim($_POST['role'] ?? '');

if ($request_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request_id.']);
    exit;
}
if ($event_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid event_id.']);
    exit;
}
if ($role === '') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid role.']);
    exit;
}

/* ---------------------------------------------------------
   PROCESS FILE UPDATE IF A FINAL PDF IS SENT
--------------------------------------------------------- */
if (isset($_FILES['final_pdf']) && $_FILES['final_pdf']['error'] === UPLOAD_ERR_OK) {

    $originalPath = $_POST['original_path'] ?? '';
    $originalName = basename($originalPath);
    $dbPath = $originalPath;

    // Mapping doc_type
    $doc_type_map = [
        'coverletter'           => 'Cover Letter',
        'activityproposal'      => 'Activity Proposal',
        'financialduediligence' => 'Financial Due Diligence',
        'letterofwithdrawal'    => 'Letter of Withdrawal',
        'documentationreport'   => 'Documentation Report',
        'minutesofmeeting'      => 'Minutes of Meeting'
    ];

    $clean_name = strtolower(str_replace([' ', '-'], '', $originalName));
    $matched_doc_type = null;

    foreach ($doc_type_map as $key => $label) {
        if (strpos($clean_name, $key) !== false) {
            $matched_doc_type = $label;
            break;
        }
    }

    if ($matched_doc_type) {
        $stmt = $conn->prepare("
            UPDATE event_document_files
            SET file_path = ?
            WHERE request_id = ? AND doc_type = ? AND event_id = ?
        ");

        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param("sisi", $dbPath, $request_id, $matched_doc_type, $event_id);
        $stmt->execute();
        $stmt->close();
    }
}

/* ---------------------------------------------------------
   UPDATE ONLY THE CURRENT SIGNATORY — NO NEXT ROLE
--------------------------------------------------------- */
$now = date("Y-m-d H:i:s");

$stmt = $conn->prepare("
    UPDATE event_signature_flow
    SET signed_at = ?, status = 'signed'
    WHERE request_id = ? AND role = ? AND event_id = ?
");

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("sisi", $now, $request_id, $role, $event_id);
$stmt->execute();
$stmt->close();

/* ---------------------------------------------------------
   ✔ NO next signatory activation at all
--------------------------------------------------------- */

echo json_encode(['status' => 'success', 'message' => 'Documents submitted successfully']);
$conn->close();
exit;

?>
