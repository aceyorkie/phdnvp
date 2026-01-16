<?php
session_start();

if (!isset($_SESSION['id_no'])) {
    header("Location: ph_login.php");
    exit();
}

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'orgportal';

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id_no = $conn->real_escape_string($_SESSION['id_no']);

$sql_head = "SELECT name, department, course, profile_image, id_no 
             FROM programhead 
             WHERE id_no = '$id_no'";
$result_head = $conn->query($sql_head);

if ($result_head && $result_head->num_rows > 0) {
    $row_account = $result_head->fetch_assoc();
    $course = $row_account['course'];
} else {
    $course = "";
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Directory</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Poppins:wght@100..900&display=swap">
    <link rel="stylesheet" href="css/org.css">
</head>
<body>

<!-- TOP NAV -->
<nav>
    <div class="nav-left">
        <img src="/officerDashboardCopy/greetings/umdc-logo.png" alt="Logo" class="logo">
    </div>

    <div class="nav-center">
        <a href="/PH_DN_VP_Dashboard/program_head/ph_home.php" onclick="home()">Home</a>
        <a href="#requests" onclick="calendar()">Calendar</a>
        <a href="#about" onclick="about()">About</a>
    </div>
</nav>

<!-- MAIN DASHBOARD -->
<div class="dashboard-container">

    <!-- SUMMARY CARDS -->
    <div class="analytics-cards">
        <div class="analytics-card">
            <h3>Total Organizations</h3>
            <p><?php echo count($orgs); ?></p>
        </div>

        <div class="analytics-card">
            <h3>Course</h3>
            <p><?php echo htmlspecialchars($course); ?></p>
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
                <canvas id="eventsChart"></canvas>
            </div>

            <div id="graph-insights"
                style="margin-top:15px; padding:15px; background:#fff; border-radius:10px;
                        box-shadow:0 2px 6px rgba(0,0,0,0.1); font-size:14px; color:#333;">
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        let eventsChart = null;

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
                if (eventsChart !== null) {
                    try { eventsChart.destroy(); } catch(e){ console.warn("destroy chart error", e); }
                }

                const ctx = document.getElementById("eventsChart").getContext("2d");

                eventsChart = new Chart(ctx, {
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

</body>
</html>
