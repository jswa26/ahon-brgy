<?php
// Determine active page
$current = basename($_SERVER['PHP_SELF']);
function isActive($page) {
    global $current;
    return $current === $page ? 'active' : '';
}
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">🏘️</div>
    <h1>AHON-BRGY</h1>
    <p>Poverty Profiling &amp; Assistance Monitoring</p>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>

    <a href="dashboard.php" class="nav-link <?= isActive('dashboard.php') ?>">
      <span class="icon">📊</span>
      Dashboard
    </a>

    <a href="households.php" class="nav-link <?= isActive('households.php') ?>">
      <span class="icon">🏠</span>
      Households
    </a>

    <a href="assistance.php" class="nav-link <?= isActive('assistance.php') ?>">
      <span class="icon">🤝</span>
      Assistance Records
    </a>

    <div class="nav-section-label" style="margin-top:8px">Management</div>

    <a href="reports.php" class="nav-link <?= isActive('reports.php') ?>">
      <span class="icon">📋</span>
      Reports
    </a>

    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
    <a href="users.php" class="nav-link <?= isActive('users.php') ?>">
      <span class="icon">👤</span>
      Users
    </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar">
        <?= strtoupper(substr($_SESSION['user']['username'] ?? 'U', 0, 1)) ?>
      </div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($_SESSION['user']['username'] ?? '') ?></div>
        <div class="user-role"><?= ucfirst($_SESSION['user']['role'] ?? '') ?></div>
      </div>
      <a href="logout.php" class="logout-btn" title="Logout">⏻</a>
    </div>
  </div>
</aside>
