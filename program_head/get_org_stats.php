<?php
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'orgportal';

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["error" => "DB connection failed"]));
}

$org_name = $_GET['org'] ?? "";

if ($org_name == "") {
    echo json_encode(["error" => "Missing org"]);
    exit;
}

/* ---- GET TOTAL MEMBERS ---- */
$sql_members = "
    SELECT COUNT(*) AS total_members
    FROM user_organizations
    WHERE organization_name = '$org_name'
      AND status = 'approved'
";

$res_members = $conn->query($sql_members);
$total_members = ($res_members->num_rows > 0) ?
    $res_members->fetch_assoc()['total_members'] : 0;

/* ---- GET EVENTS PER MONTH ---- */
$sql_events = "
    SELECT MONTH(event_date) AS month, COUNT(*) AS total_events
    FROM organizational_events
    WHERE organization = '$org_name'
    GROUP BY MONTH(event_date)
    ORDER BY MONTH(event_date)
";

$res_events = $conn->query($sql_events);

$months = [];
$eventCounts = [];

while ($row = $res_events->fetch_assoc()) {
    $months[] = $row['month'];
    $eventCounts[] = $row['total_events'];
}

/* ---- GET POSTS PER MONTH ---- */
$sql_posts = "
    SELECT MONTH(created_at) AS month, COUNT(*) AS total_posts
    FROM posts
    WHERE organization = '$org_name'
      AND status = 'approved'
    GROUP BY MONTH(created_at)
    ORDER BY MONTH(created_at)
";

$res_posts = $conn->query($sql_posts);

$postMonths = [];
$postCounts = [];

while ($row = $res_posts->fetch_assoc()) {
    $postMonths[] = $row['month'];
    $postCounts[] = $row['total_posts'];
}

echo json_encode([
    "members" => $total_members,
    "months" => $months,
    "eventCounts" => $eventCounts,
    "postMonths" => $postMonths,
    "postCounts" => $postCounts
]);
?>
