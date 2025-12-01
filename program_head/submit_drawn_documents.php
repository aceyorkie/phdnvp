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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ------------------------------------------------------
       VALIDATE INPUTS
    ------------------------------------------------------ */
    $request_id = intval($_POST['request_id'] ?? 0);
    $role       = trim($_POST['role'] ?? "");

    if ($request_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request_id']);
        exit;
    }
    if ($role === "") {
        echo json_encode(['status' => 'error', 'message' => 'Invalid role']);
        exit;
    }

    error_log("DEBUG: request_id=$request_id, role=$role");

    /* ------------------------------------------------------
       SAVE MERGED PDF (final_pdf)
    ------------------------------------------------------ */
    if (isset($_FILES['final_pdf']) && $_FILES['final_pdf']['error'] === UPLOAD_ERR_OK) {

        $originalPath = $_POST['original_path'] ?? '';
        $originalName = basename($originalPath);

        // storage folder
        $upload_dir = "annotated_docs/request_$request_id/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $target = $upload_dir . $originalName;

        // save file
        if (!move_uploaded_file($_FILES['final_pdf']['tmp_name'], $target)) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save annotated PDF']);
            exit;
        }

        /* ------------------------------------------------------
           MAP doc_type based on ORIGINAL file name
        ------------------------------------------------------ */
        $doc_type_map = [
            'applicationletter'      => 'Application Letter',
            'application letter'     => 'Application Letter',

            'constitutionandby-laws' => 'Constitution and By-laws',
            'constitution and by-laws' => 'Constitution and By-laws',

            'listofofficers'         => 'List of Officers and Members',
            'list of officers'       => 'List of Officers and Members',

            'advisershipletter'      => 'Advisorship Letter',
            'advisership letter'     => 'Advisership Letter',

            'annualoperationalplan'  => 'Annual Operational Plan',
            'annual operational plan'=> 'Annual Operational Plan',

            'additionaldocument'     => 'Additional Document',
            'additional document'    => 'Additional Document'
        ];

        $lower_name = strtolower(str_replace(['_', '-', '.pdf'], '', $originalName));
        $matched_type = null;

        foreach ($doc_type_map as $key => $label) {
            if (strpos($lower_name, str_replace(' ', '', $key)) !== false ||
                strpos($lower_name, $key) !== false) 
            {
                $matched_type = $label;
                break;
            }
        }

        if ($matched_type) {

            // UPDATE document_files
            $stmt = $conn->prepare("
                UPDATE document_files 
                SET file_path = ? 
                WHERE request_id = ? AND doc_type = ?
            ");
            if (!$stmt) {
                echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
                exit;
            }

            $stmt->bind_param("sis", $target, $request_id, $matched_type);

            if (!$stmt->execute()) {
                echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $stmt->error]);
                $stmt->close();
                exit;
            }

            error_log("Updated doc_type = $matched_type with annotated PDF");
            $stmt->close();

        } else {
            error_log("Unknown doc type for filename: $originalName");
        }
    } else {
        error_log("No annotated PDF uploaded or error occurred.");
    }

    /* ------------------------------------------------------
       UPDATE SIGNATURE STATUS
    ------------------------------------------------------ */
    $now = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("
        UPDATE signature_flow 
        SET signed_at = ?, status = 'signed' 
        WHERE request_id = ? AND role = ?
    ");

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("sis", $now, $request_id, $role);

    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Signature update failed: ' . $stmt->error]);
        $stmt->close();
        exit;
    }

    $stmt->close();

    /* ------------------------------------------------------
       GET ORDER OF CURRENT ROLE
    ------------------------------------------------------ */
    $stmt = $conn->prepare("
        SELECT sequence_order 
        FROM signature_flow 
        WHERE request_id = ? AND role = ?
        LIMIT 1
    ");
    $stmt->bind_param("is", $request_id, $role);
    $stmt->execute();
    $stmt->bind_result($order);
    $stmt->fetch();
    $stmt->close();

    if (!empty($order)) {
        $next = $order + 1;

        $stmt = $conn->prepare("
            UPDATE signature_flow 
            SET status = 'pending'
            WHERE request_id = ? AND sequence_order = ?
        ");
        $stmt->bind_param("ii", $request_id, $next);

        if (!$stmt->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Next role update failed: ' . $stmt->error]);
            $stmt->close();
            exit;
        }

        $stmt->close();
    }

    echo json_encode(['status' => 'success', 'message' => 'Document signed and updated.']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
?>
