<?php
session_start();

if (!isset($_SESSION['id_no'])) {
    header("Location:vp_login.php");
    exit();
}

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'orgportal';
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed to orgportal: " . $conn->connect_error);
}

$id_no = $conn->real_escape_string($_SESSION['id_no']);

$sql = "SELECT id_no, name, profile_image FROM vp WHERE id_no='$id_no'";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed (orgportal): " . $conn->error);
}

$db_practice = 'practice_db';
$conn_practice = new mysqli($host, $user, $password, $db_practice);

if ($conn_practice->connect_error) {
    die("Connection failed to practice_db: " . $conn_practice->connect_error);
}

$sql_unread = "SELECT COUNT(*) AS unread_count 
               FROM notifications 
               WHERE recipient_id = '$id_no' 
               AND status = 'unread'";
$result_unread = $conn_practice->query($sql_unread);

$unread_count = 0;
if ($result_unread && $result_unread->num_rows > 0) {
    $row_unread = $result_unread->fetch_assoc();
    $unread_count = $row_unread['unread_count'];
}

$conn->close();
$conn_practice->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VP</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="vp_home.css">
</head>
<body>
    <nav>
        <div class="nav-left">
            <img src="/officerDashboardCopy/greetings/umdc-logo.png" alt="Logo" class="logo">
        </div>
        <div class="nav-center">
            <a href="#home" onclick="home()">Home</a>
            <a href="#requests" onclick="requests()">Requests</a>
            <a href="/PH_DN_VP_Dashboard/vp_branch/vp_notification.php">Notifications</a>
            <a href="#about" onclick="about()">About</a>
        </div>
        <div class="nav-right" style="position: relative;">
            <button class="button" onclick="notification()" style="position: relative;">
                <svg viewBox="0 0 448 512" class="bell" width="24" height="24">
                    <path d="M224 0c-17.7 0-32 14.3-32 32V49.9C119.5 61.4 64 124.2 
                    64 200v33.4c0 45.4-15.5 89.5-43.8 124.9L5.3 377
                    c-5.8 7.2-6.9 17.1-2.9 25.4S14.8 416 24 416H424
                    c9.2 0 17.6-5.3 21.6-13.6s2.9-18.2-2.9-25.4l-14.9-18.6
                    C399.5 322.9 384 278.8 384 233.4V200c0-75.8-55.5-138.6
                    -128-150.1V32c0-17.7-14.3-32-32-32zm0 96h8c57.4 0 104 
                    46.6 104 104v33.4c0 47.9 13.9 94.6 39.7 134.6H72.3
                    C98.1 328 112 281.3 112 233.4V200c0-57.4 46.6-104 
                    104-104h8zm64 352H224 160c0 17 6.7 33.3 18.7 45.3
                    s28.3 18.7 45.3 18.7s33.3-6.7 45.3-18.7
                    s18.7-28.3 18.7-45.3z"/>
                </svg>

                <?php if ($unread_count > 0): ?>
                    <span class="notif-badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </button>

            <div id="notif-dropdown" class="notif-dropdown" style="display: none;">
                <div class="notif-header">Notifications</div>
                <div id="notif-list" class="notif-list">Loading...</div>
            </div>
        </div>
    </nav>

    <script>
        function home() {
            window.location.href = 'vp_home.php'; 
        }
    </script>

        <script>
        function notification() {
            const dropdown = document.getElementById('notif-dropdown');
            dropdown.style.display = (dropdown.style.display === 'none' || dropdown.style.display === '') ? 'block' : 'none';

            if (dropdown.style.display === 'block') {
                // Fetch notifications via AJAX
                fetch('vp_notification.php')
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('notif-list').innerHTML = data;
                    })
                    .catch(err => {
                        document.getElementById('notif-list').innerHTML = '<div style="padding:10px;">Failed to load notifications.</div>';
                    });
            }
        }

        // Optional: close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('notif-dropdown');
            const button = document.querySelector('.button');
            if (!dropdown.contains(e.target) && !button.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    </script>
</body>
</html>