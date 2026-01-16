<?php
session_start();

if (!isset($_SESSION['id_no'])) {
    header("Location:ph_login.php");
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

$sql = "SELECT id_no, name, department, course, profile_image 
        FROM programhead 
        WHERE id_no='$id_no'";
$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    die("Query failed (orgportal): " . $conn->error);
}

$row_account = $result->fetch_assoc();
$raw_course = $row_account['course'];   
$course = htmlspecialchars($raw_course);
$cleanCourse = $conn->real_escape_string($raw_course);

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

$sql_events = "
    SELECT organization, COUNT(*) AS total_events
    FROM organizational_events
    WHERE event_course = '$cleanCourse'
    GROUP BY organization
    ORDER BY total_events DESC
";

$result_events = $conn->query($sql_events);

$orgNames = [];
$orgEventCounts = [];

while ($row = $result_events->fetch_assoc()) {
    $orgNames[] = $row['organization'];
    $orgEventCounts[] = $row['total_events'];
}

$sql_members = "
    SELECT u.organization_name, COUNT(*) AS total_members
    FROM user_organizations u
    INNER JOIN dtp_organization o 
        ON u.organization_name = o.org_code
    WHERE o.org_course = '$cleanCourse'
      AND o.org_status = 'approved'
    GROUP BY u.organization_name
    ORDER BY total_members DESC
";


$result_members = $conn->query($sql_members);

$memberOrgNames = [];
$memberCounts = [];

while ($row = $result_members->fetch_assoc()) {
    $memberOrgNames[] = $row['organization_name'];
    $memberCounts[] = $row['total_members'];
}

$orgs = [];
$sql_orgs = "
    SELECT *
    FROM dtp_organization
    WHERE org_status = 'approved'
      AND org_course = '$course'
";
$result_orgs = $conn->query($sql_orgs);

if ($result_orgs && $result_orgs->num_rows > 0) {
    while ($row = $result_orgs->fetch_assoc()) {
        $orgs[] = $row;
    }
}

$conn->close();
$conn_practice->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Head</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Poppins:wght@100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/ph_home.css">
</head>
<body>

    <nav>
        <div class="nav-left">
            <img src="/officerDashboardCopy/greetings/umdc-logo.png" alt="Logo" class="logo">
        </div>

        <div class="nav-center">
            <a href="#home" onclick="home()">Home</a>
            <a href="calendar.php">Calendar</a>
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
    <div class="dashboard-container">

        <div class="main-profile-section">
            <div class="profile-container">
                <?php
                    $name = htmlspecialchars($row_account['name']);
                    $id_display = htmlspecialchars($row_account['id_no']);
                    $department = htmlspecialchars($row_account['department']);
                    echo "
                        <div style='align-items: center; gap: 20px;'>
                            <div class='grid-container2'>

                                <div class='profile-name' style='position: relative; display: flex; align-items: center; gap: 10px;'>
                                    <div>
                                        <h3 style='font-weight: 800'>$id_display</h3>
                                        <h1>$name</h1>
                                        <h3>Program Head</h3>
                                        <h3>$department</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ";
                ?>
            </div>
        </div>

        <!-- ORGANIZATION LIST -->
        <div class="org-grid">
            <?php foreach ($orgs as $org): ?>
            <div class="org-card" onclick="loadStats('<?php echo $org['org_code']; ?>')">

                <div class="org-header">
                    <img class="org-logo" src="../userHomeCopy/UserHome/<?php echo $org['org_logo']; ?>">
                    <div>
                        <h3><?php echo htmlspecialchars($org['org_code']); ?></h3>
                        <span><?php echo htmlspecialchars($org['org_name']); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div id="stats-section" style="display:none; margin-top:40px;">
            <h2 id="selected-org-title" style="color:maroon;"></h2>

            <div style="background:white; padding:20px; border-radius:15px; margin-bottom:20px;">
                <h3>Total Members</h3>
                <p id="member-count" style="font-size:28px; font-weight:bold; color:#bc0000;"></p>
            </div>

            <div style="background:white; padding:20px; border-radius:15px;">
                <h3>Events Per Month</h3>

                <div style="height:380px; width:100%;"> 
                    <canvas id="orgEventsChart"></canvas>
                </div>

                <div id="graph-insights"
                    style="margin-top:15px; padding:15px; background:#fff; border-radius:10px;
                            box-shadow:0 2px 6px rgba(0,0,0,0.1); font-size:14px; color:#333;">
                </div>
            </div>
        </div>
        <div class="graph-section">

            <!-- Left big container -->
            <div class="graph-box-large">
                <h2 class="graph-title">Most Active Organizations (Events Ranking)</h2>
                <canvas id="eventsRankingChart"></canvas>
            </div>

            <!-- Right small container -->
            <div class="graph-box-small">
                <h2 class="graph-title">Total number of member</h2>
                <canvas id="membersChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        let orgEventsChart = null;

        function loadStats(orgCode) {
            // debug: show requested org
            console.log("Loading stats for:", orgCode);

            fetch("get_org_stats.php?org=" + encodeURIComponent(orgCode))
            .then(response => {
                if (!response.ok) throw new Error("Network response was not ok");
                return response.json();
            })
            .then(data => {
                // debug: inspect server payload
                console.log("get_org_stats response:", data);

                // Basic validation & defaults
                data = data || {};
                const monthsFromServer = Array.isArray(data.months) ? data.months : [];
                const eventCountsFromServer = Array.isArray(data.eventCounts) ? data.eventCounts : [];
                const postMonthsFromServer = Array.isArray(data.postMonths) ? data.postMonths : [];
                const postCountsFromServer = Array.isArray(data.postCounts) ? data.postCounts : [];
                const members = (typeof data.members === "number" || typeof data.members === "string") ? data.members : 0;

                document.getElementById("selected-org-title").innerText = "Organization: " + orgCode;
                document.getElementById("member-count").innerText = members;
                document.getElementById("stats-section").style.display = "block";

                // Always use full 12 month labels
                const monthLabels = [
                    "January","February","March","April","May","June",
                    "July","August","September","October","November","December"
                ];

                // Helper: safe integer parse for month value
                function safeMonth(m) {
                    const mm = Number(m);
                    if (!Number.isInteger(mm)) return null;
                    if (mm < 1 || mm > 12) return null;
                    return mm;
                }

                // Fill arrays of length 12 with zeros
                const eventData = new Array(12).fill(0);
                for (let i = 0; i < monthsFromServer.length; i++) {
                    const m = safeMonth(monthsFromServer[i]);
                    const v = Number(eventCountsFromServer[i]) || 0;
                    if (m !== null) eventData[m - 1] = v;
                }

                const postData = new Array(12).fill(0);
                for (let i = 0; i < postMonthsFromServer.length; i++) {
                    const m = safeMonth(postMonthsFromServer[i]);
                    const v = Number(postCountsFromServer[i]) || 0;
                    if (m !== null) postData[m - 1] = v;
                }

                // Destroy old chart if exists
                if (orgEventsChart !== null) {
                    try { orgEventsChart.destroy(); } catch(e){ console.warn("destroy chart error", e); }
                }

                const ctx = document.getElementById("orgEventsChart").getContext("2d");

                orgEventsChart = new Chart(ctx, {
                    type: "line",
                    data: {
                        labels: monthLabels,
                        datasets: [
                            {
                                label: "Events Per Month",
                                data: eventData,
                                borderColor: "rgba(188, 0, 0, 0.85)",
                                backgroundColor: "rgba(188, 0, 0, 0.2)",
                                tension: 0.3,
                                borderWidth: 3,
                                pointRadius: 5,
                                pointBackgroundColor: "maroon",
                                fill: true
                            },
                            {
                                label: "Posts Per Month",
                                data: postData,
                                borderColor: "rgba(255, 215, 0, 1)",
                                backgroundColor: "rgba(255, 215, 0, 0.25)",
                                tension: 0.3,
                                borderWidth: 3,
                                pointRadius: 5,
                                pointBackgroundColor: "gold",
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });

                /* -------------------------------
                Insights generator (robust)
                --------------------------------*/
                function generateInsights(events, posts, monthLabels) {
                    const totalEvents = events.reduce((a,b) => a + b, 0);
                    const totalPosts = posts.reduce((a,b) => a + b, 0);

                    if (totalEvents + totalPosts === 0) {
                        return "<b>No recorded activity yet for this organization.</b>";
                    }

                    // Build arrays of active months only
                    const activeEvents = events
                        .map((val, idx) => ({ month: monthLabels[idx], value: val }))
                        .filter(x => x.value > 0);

                    const activePosts = posts
                        .map((val, idx) => ({ month: monthLabels[idx], value: val }))
                        .filter(x => x.value > 0);

                    // Safeguard: if nothing active, say so
                    if (activeEvents.length === 0 && activePosts.length === 0) {
                        return "<b>No meaningful activity recorded for this organization.</b>";
                    }

                    // Event peak
                    let eventPeakMonth = "—", eventPeakValue = 0;
                    if (activeEvents.length) {
                        eventPeakValue = Math.max(...activeEvents.map(x => x.value));
                        eventPeakMonth = activeEvents.find(x => x.value === eventPeakValue).month;
                    }

                    // Post peak
                    let postPeakMonth = "—", postPeakValue = 0;
                    if (activePosts.length) {
                        postPeakValue = Math.max(...activePosts.map(x => x.value));
                        postPeakMonth = activePosts.find(x => x.value === postPeakValue).month;
                    }

                    // Combined engagement
                    const combined = events.map((e,i) => ({ month: monthLabels[i], total: e + posts[i] }))
                                        .filter(x => x.total > 0);
                    let combinedSummary = "";
                    if (combined.length) {
                        const highestCombined = Math.max(...combined.map(x => x.total));
                        const highMonth = combined.find(x => x.total === highestCombined).month;
                        combinedSummary = `• The highest combined engagement was in <b>${highMonth}</b>.<br>`;
                    }

                    // Post-before-event check (look for months where posts peak the month before event peak)
                    let postBeforeEventText = "";
                    if (eventPeakMonth !== "—" && postPeakMonth !== "—") {
                        const eventIdx = monthLabels.indexOf(eventPeakMonth);
                        if (eventIdx > 0 && monthLabels[eventIdx - 1] === postPeakMonth) {
                            postBeforeEventText = "• Posting activity increased the month before major events, suggesting posts are used to promote upcoming activities.<br>";
                        }
                    }

                    // Low-activity months (list months with zero both)
                    const lowMonths = monthLabels.filter((m, i) => events[i] === 0 && posts[i] === 0);
                    let lowText = "";
                    if (lowMonths.length >= 3) {
                        lowText = `• Low engagement detected in: <b>${lowMonths.join(", ")}</b>.<br>`;
                    }

                    return `
                        <b>Events:</b> highest in <b>${eventPeakMonth}</b> (${eventPeakValue})<br>
                        <b>Posts:</b> highest in <b>${postPeakMonth}</b> (${postPeakValue})<br>
                        ${combinedSummary}
                        ${postBeforeEventText}
                        ${lowText}
                    `;
                }

                // Insert insights into DOM
                const summaryHtml = generateInsights(eventData, postData, monthLabels);
                const insightEl = document.getElementById("graph-insights");
                if (insightEl) {
                    insightEl.innerHTML = summaryHtml;
                } else {
                    console.warn("#graph-insights element not found");
                }

            })
            .catch(error => {
                console.error("Error fetching stats:", error);
                // show friendly message if needed
                const insightEl = document.getElementById("graph-insights");
                if (insightEl) insightEl.innerHTML = "<b>Error loading stats. Check console.</b>";
            });
        }
    </script>
    <script>
        const eventLabels = <?php echo json_encode($orgNames); ?>;
        const eventData   = <?php echo json_encode($orgEventCounts); ?>;

        new Chart(document.getElementById('eventsRankingChart'), {
            type: 'bar',
            data: {
                labels: eventLabels,
                datasets: [{
                    label: 'Number of Events',
                    data: eventData,
                    backgroundColor:
                        '#7A0000',
                    borderWidth: 1,
                    borderRadius: 8,
                    barThickness: 40,
                    borderRadius: 8,
                    maxBarThickness: 25,   
                    categoryPercentage: 0.6, 
                    barPercentage: 0.6  
                }],
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        const memberLabels = <?php echo json_encode($memberOrgNames); ?>;
        const memberData   = <?php echo json_encode($memberCounts); ?>;

        new Chart(document.getElementById('membersChart'), {
            type: 'doughnut',
            data: {
                labels: memberLabels,
                datasets: [{
                    label: 'Total Members',
                    data: memberData,
                    backgroundColor: [
                        '#800000',  
                        '#3c0000',   
                        '#A00000',   
                        '#B03060',   
                        '#c04050',   
                    ],
                }]
            },
        });
        
    </script>

        <script>
        function notification() {
            const dropdown = document.getElementById('notif-dropdown');
            dropdown.style.display = (dropdown.style.display === 'none' || dropdown.style.display === '') ? 'block' : 'none';

            if (dropdown.style.display === 'block') {
                // Fetch notifications via AJAX
                fetch('ph_notification.php')
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
