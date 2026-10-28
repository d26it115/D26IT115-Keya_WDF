<?php
/**
 * StudentHub - View Registered Students
 * Reads and displays records from data/registrations.csv
 */

$csvFile = __DIR__ . "/../data/registrations.csv";
$registrations = [];

if (file_exists($csvFile)) {
    if (($handle = fopen($csvFile, "r")) !== false) {
        $header = fgetcsv($handle); // Read and skip header row
        while (($row = fgetcsv($handle)) !== false) {
            if (!empty($row) && count($row) >= 7) {
                $registrations[] = $row;
            }
        }
        fclose($handle);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Registered Students</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <nav class="navbar" aria-label="Main navigation">
            <a href="../pages/index.html" class="logo-link">
                <img src="../images/logo.png" alt="StudentHub Logo" class="logo-image">
                <span class="logo-text">StudentHub</span>
            </a>

            <button id="menuButton" class="hamburger" type="button" aria-expanded="false" aria-label="Open navigation menu">
                ☰
            </button>

            <ul id="navigationMenu" class="navigation-menu">
                <li><a href="../pages/index.html">Home</a></li>
                <li><a href="../pages/about.html">About</a></li>
                <li><a href="../pages/events.html">Events</a></li>
                <li><a href="../pages/faq.html">FAQ</a></li>
                <li><a href="../pages/contact.html">Contact</a></li>
                <li><a href="../pages/feedback.html">Feedback</a></li>
                <li><a href="../pages/login.html">Login</a></li>
                <li><a href="../pages/register.html">Register</a></li>
                <li><a href="../pages/admin.html">Admin</a></li>
                <li>
                    <div class="theme-toggle-wrapper">
                        <span class="theme-icon" aria-hidden="true">☀️</span>
                        <label class="theme-toggle" for="themeToggle">
                            <input type="checkbox" id="themeToggle" aria-label="Toggle dark mode">
                            <span class="theme-slider"></span>
                        </label>
                        <span class="theme-icon" aria-hidden="true">🌙</span>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="page-heading">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h1>Registered Students</h1>
                    <p>Records stored in the campus registration database (<code>data/registrations.csv</code>).</p>
                </div>
                <a href="../pages/admin.html" class="hero-button" style="padding: 9px 18px; font-size: 14px;">
                    &larr; Back to Admin Panel
                </a>
            </div>
        </section>

        <section class="content-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                <p style="margin: 0; font-weight: 600; color: var(--text);">
                    Total Registered Students: <span style="color: var(--primary); font-size: 18px;"><?php echo count($registrations); ?></span>
                </p>
                <input
                    type="search"
                    id="tableSearch"
                    placeholder="Search registrations..."
                    style="max-width: 260px; padding: 8px 12px; font-size: 14px;"
                >
            </div>

            <?php if (empty($registrations)): ?>
                <div class="events-message">
                    No registrations found. New registrations will appear here once submitted.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table" id="registrationsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th>Course</th>
                                <th>Academic Year</th>
                                <th>Gender</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $index => $student): ?>
                                <tr>
                                    <td><strong><?php echo $index + 1; ?></strong></td>
                                    <td><?php echo htmlspecialchars($student[0]); ?></td>
                                    <td><?php echo htmlspecialchars($student[1]); ?></td>
                                    <td><?php echo htmlspecialchars($student[2]); ?></td>
                                    <td><span class="event-category" style="margin: 0;"><?php echo htmlspecialchars($student[4]); ?></span></td>
                                    <td><?php echo htmlspecialchars($student[5]); ?></td>
                                    <td><?php echo htmlspecialchars($student[6]); ?></td>
                                    <td><span style="color: #16a34a; font-weight: 600;">Active</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 StudentHub. All rights reserved.</p>
    </footer>

    <script src="../js/script.js"></script>
    <script>
        // Instant search filter for registrations table
        const tableSearch = document.getElementById("tableSearch");
        if (tableSearch) {
            tableSearch.addEventListener("input", () => {
                const query = tableSearch.value.toLowerCase().trim();
                const rows = document.querySelectorAll("#registrationsTable tbody tr");
                rows.forEach((row) => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(query) ? "" : "none";
                });
            });
        }
    </script>
</body>
</html>
