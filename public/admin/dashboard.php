
<?php
$pageTitle = "Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>UCC Admin | <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="icon" type="image/png" href="assets/images/ucc-LOGO.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Libre+Baskerville:wght@700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">

    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body data-page-title="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">

    <div class="dashboard-layout">

        <?php include __DIR__ . '/template/sidebar.php'; ?>

        <main class="main-content">

            <?php include __DIR__ . '/template/header.php'; ?>

            <section class="content-area">

                <div class="heading-row">
                    <div>
                        <h2>Good day, Admin!</h2>
                        <p>Here's an overview of your university website.</p>
                    </div>

                    <div class="tagline">
                        <span>A BRIGHTER TOMORROW TOGETHER</span>
                        <div class="tag-line"></div>
                    </div>
                </div>

                <div class="stats-grid">

                    <div class="stat-card">
                        <div class="icon green">
                            <i data-lucide="clipboard-list"></i>
                        </div>

                        <div class="stat-content">
                            <h4>Total Posts</h4>
                            <h3>128</h3>
                            <p><span class="up">▲ +12%</span> from last month</p>
                        </div>

                        <div class="chart-box">
                            <div id="chartPosts" class="apex-mini"></div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="icon gold">
                            <i data-lucide="clock-3"></i>
                        </div>

                        <div class="stat-content">
                            <h4>Pending Posts</h4>
                            <h3>7</h3>
                            <p><span class="up">▲ +2%</span> from last month</p>
                        </div>

                        <div class="chart-box">
                            <div id="chartPending" class="apex-mini"></div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="icon gray">
                            <i data-lucide="archive"></i>
                        </div>

                        <div class="stat-content">
                            <h4>Archived Posts</h4>
                            <h3>36</h3>
                            <p><span class="down">▼ -5%</span> from last month</p>
                        </div>

                        <div class="chart-box">
                            <div id="chartArchived" class="apex-mini"></div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="icon red">
                            <i data-lucide="users-round"></i>
                        </div>

                        <div class="stat-content">
                            <h4>Users</h4>
                            <h3>1,024</h3>
                            <p><span class="up">▲ +8%</span> from last month</p>
                        </div>

                        <div class="chart-box">
                            <div id="chartUsers" class="apex-mini"></div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="icon blue">
                            <i data-lucide="chart-column-big"></i>
                        </div>

                        <div class="stat-content">
                            <h4>Real-time Visitor</h4>
                            <h3>243</h3>
                            <p><span class="up">▲ +15%</span> from last hour</p>
                        </div>

                        <div class="chart-box">
                            <div id="chartVisitors" class="apex-mini"></div>
                        </div>
                    </div>

                </div>

                <div class="table-card chart-card">

                    <div class="table-head">
                        <div class="table-title">
                            <div class="table-icon">
                                <i data-lucide="trophy"></i>
                            </div>

                            <h3>Top 5 Most Viewed Posts</h3>
                        </div>

                        <a href="Posts.php">
                            View All Posts
                            <i data-lucide="arrow-right"></i>
                        </a>
                    </div>

                    <div id="topPostsChart" class="top-post-chart"></div>

                </div>

            </section>

        </main>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="assets/js/dashboard.js?v=2"></script>
</body>
</html>