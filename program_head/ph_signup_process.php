<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'orgportal';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_no = $_POST['id_no'];
    $name = $_POST['name'];
    $department = $_POST['department'];
    $course = $_POST['course'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check_query = $conn->prepare("SELECT id_no FROM programhead WHERE id_no=?");
    $check_query->bind_param("s", $id_no);
    $check_query->execute();
    $result = $check_query->get_result();

    if ($result->num_rows > 0) {
        echo "ID No. already exists.";
    } else {
        $sql = $conn->prepare("INSERT INTO programhead (id_no, name, password, department, course) VALUES (?, ?, ?, ?, ?)");
        $sql->bind_param("sssss", $id_no, $name, $password, $department, $course);

        if ($sql->execute()) {
            $_SESSION['id_no'] = $id_no;
            $_SESSION['name'] = $name;
            header("Location: ph_home.php");
            exit();
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

$conn->close();
?>
