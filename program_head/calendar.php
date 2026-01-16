<?php
session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'orgportal';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['id_no'] ?? null;

if (!$user_id) {
    die("Not logged in.");
}

// Fetch Program Head course
$course = null;
$stmt = $conn->prepare("SELECT course FROM programhead WHERE id_no = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($course);
$stmt->fetch();
$stmt->close();

if (!$course) {
    die("Program Head course not found.");
}


/* -------------------------------------
   2. FETCH INSTITUTIONAL EVENTS
-------------------------------------- */

$institutional = [];
$sql = "SELECT event_name AS title, event_date AS start, event_location AS location, 'Institutional' AS type 
        FROM institutional_events 
        WHERE status = 'approved'";

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $row['color'] = '#800000';
    $institutional[] = $row;
}

$organizational = [];
$sql = "
    SELECT 
        e.event_name AS title,
        e.event_date AS start,
        e.event_location AS location,
        e.organization,
        'Organizational' AS type
    FROM organizational_events e
    INNER JOIN dtp_organization o
        ON e.organization = o.org_code
    WHERE e.status = 'approved'
      AND o.org_status = 'approved'
      AND o.org_course = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $course);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $row['color'] = '#b03060';
    $organizational[] = $row;
}

$stmt->close();

$events = array_merge($institutional, $organizational);

if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode($events);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Event Calendar</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <link rel="stylesheet" href="/userHomeCopy/UserHome/css/calendar.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f9f9f9;
            margin: 0;
            padding: 20px;
        }
        #calendar {
            max-width: 90%;
            margin: 40px auto;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav>
        <div class="nav-left">
            <img src="/osaDashboard/greetings/umdc-logo.png" alt="Logo" class="logo">
        </div>
        <div class="nav-center">
            <a href="ph_home.php">Home</a>
            <a href="calendar.php">Calendar</a>
            <a href="#about" onclick="about()">About</a>
        </div>
    </nav>
    <h1 style="text-align:center;color:maroon;">Event Calendar</h1>
    <div id="calendar"></div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            themeSystem: 'standard',
            events: 'calendar.php?json=1',
            eventClick: function(info) {
            const e = info.event.extendedProps;
            alert(
                `📌 ${info.event.title}
                Type: ${e.type}
                Organization: ${e.organization ?? '—'}
                Location: ${e.location}
                Date: ${info.event.start.toLocaleDateString()}`
                );
            },
            headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            height: 'auto'
        });

        calendar.render();
        });
    </script>

</body>
</html>
