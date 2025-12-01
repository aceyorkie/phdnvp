<?php
session_start();
if (!isset($_SESSION['id_no'])) {
    http_response_code(403);
    echo "Not authorized";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['request_id'])) {
    http_response_code(400);
    echo "Invalid request";
    exit();
}

$request_id = $_POST['request_id'];

// Connect to both databases
$signatoryDb = new mysqli('localhost', 'root', '', 'practice_db');
$orgDb = new mysqli('localhost', 'root', '', 'practice_db');

if ($signatoryDb->connect_error || $orgDb->connect_error) {
    http_response_code(500);
    echo "Database connection failed";
    exit();
}


$signatoryDb->query("DELETE FROM signature_flow WHERE request_id = '$request_id'");
$orgDb->query("DELETE FROM document_files WHERE request_id = '$request_id'");
$orgDb->query("DELETE FROM notifications WHERE request_id = '$request_id'");

$signatoryDb->close();
$orgDb->close();

echo "Documents and signature flow reset for request $request_id.";
?>