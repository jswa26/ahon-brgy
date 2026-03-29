<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
include 'db.php';

// Stats
$total_hh   = $conn->query("SELECT COUNT(*) c FROM households")->fetch_assoc()['c'];
$indigent    = $conn->query("SELECT COUNT(*) c FROM households WHERE poverty_level='Indigent'")->fetch_assoc()['c'];
$total_aid   = $conn->query("SELECT COUNT(*) c FROM assistance")->fetch_assoc()['c'];
$total_users = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];

// Recent households
$recent_hh = $conn->query("SELECT * FROM households ORDER BY created_at DESC LIMIT 5");

// Assistance summary
$aid_summary = $conn->query("
  SELECT assistance_type, COUNT(*) cnt
  FROM assistance
  GROUP BY assistance_type
  ORDER BY cnt DESC
  LIMIT 5
");

// Poverty breakdown
$pov_breakdown = $conn->query("
  SELECT poverty_level, COUNT(*) cnt
  FROM households
  GROUP BY poverty_level
");
$pov_data = [];
while($r = $pov_breakdown->fetch_assoc()) $pov_data[$r['poverty_level']] = $r['cnt'];

// Recent assistance
$recent_aid = $conn->query("
  SELECT a.*, h.household_head
  FROM assistance a
  JOIN households h ON a.household_id = h.id
  ORDER BY a.date_given DESC
  LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Dashboard — AHON-BRGY</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
  <?php include 'sidebar.php'; ?>

  <div class="main">
    <header class="topbar">
      <div class="topbar-left">
        <h2>Dashboard</h2>
        <p>Overview of barangay poverty data &amp; assistance</p>
      </div>
      <div class="topbar-right">
        <span class="text-muted text-sm"><?= date('F j, Y') ?></span>
        <a href="households.php?action=add" class="btn btn-primary btn-sm">+ Register Household</a>
      </div>
    </header>

    <div class="content">
      <div class="sdg-bar">
        🎯 <strong>SDG 1 — No Poverty</strong> &nbsp;·&nbsp; SDG 10 — Reduced Inequalities &nbsp;·&nbsp; SDG 16 — Strong Institutions
      </div>

      <!-- Stat Cards -->
      <div class="stat-grid">
        <div class="stat-card blue">
          <div class="stat-icon">🏠</div>
          <div class="stat-value"><?= $total_hh ?></div>
          <div class="stat-label">Total Households</div>
          <div class="stat-sub">Registered in system</div>
        </div>
        <div class="stat-card red" style="--accent2:#fca5a5">
          <div class="stat-icon" style="background:rgba(239,68,68,0.15)">⚠️</div>
          <div class="stat-value" style="color:#fca5a5"><?= $indigent ?></div>
          <div class="stat-label">Indigent Households</div>
          <div class="stat-sub">Highest priority level</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon">🤝</div>
          <div class="stat-value"><?= $total_aid ?></div>
          <div class="stat-label">Assistance Records</div>
          <div class="stat-sub">Total aid distributed</div>
        </div>
        <div class="stat-card purple">
          <div class="stat-icon">👥</div>
          <div class="stat-value"><?= $total_users ?></div>
          <div class="stat-label">System Users</div>
          <div class="stat-sub">Staff &amp; administrators</div>
        </div>
      </div>

      <!-- Two-column layout -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">

        <!-- Poverty Breakdown -->
        <div class="card">
          <div class="section-title">📊 Poverty Level Breakdown</div>
          <?php
            $levels = ['Indigent'=>['#ef4444','⚠️'], 'Low Income'=>['#f59e0b','📉'], 'Near Poor'=>['#3b82f6','📊']];
            foreach($levels as $lv => $meta):
              $cnt = $pov_data[$lv] ?? 0;
              $pct = $total_hh > 0 ? round(($cnt / $total_hh) * 100) : 0;
          ?>
          <div style="margin-bottom:14px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
              <span style="font-size:13px;font-weight:500;color:var(--text2)"><?= $meta[1] ?> <?= $lv ?></span>
              <span style="font-size:13px;font-weight:700;color:var(--text)"><?= $cnt ?> <span style="color:var(--text3);font-weight:400">(<?= $pct ?>%)</span></span>
            </div>
            <div style="height:6px;background:var(--border);border-radius:3px;overflow:hidden">
              <div style="height:100%;width:<?= $pct ?>%;background:<?= $meta[0] ?>;border-radius:3px;transition:width .8s ease"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Assistance by Type -->
        <div class="card">
          <div class="section-title">🤝 Assistance by Type</div>
          <?php
          $aid_types = [];
          while($r = $aid_summary->fetch_assoc()) $aid_types[] = $r;
          if (empty($aid_types)):
          ?>
          <div class="empty-state" style="padding:30px 20px">
            <div class="empty-icon">📦</div>
            <h4>No assistance yet</h4>
            <p>Add assistance records to see summary</p>
          </div>
          <?php else:
          $aid_total = array_sum(array_column($aid_types, 'cnt'));
          $aid_colors = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444'];
          foreach($aid_types as $i => $at):
            $pct = $aid_total > 0 ? round(($at['cnt']/$aid_total)*100) : 0;
            $color = $aid_colors[$i % count($aid_colors)];
          ?>
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
            <div style="width:8px;height:8px;border-radius:50%;background:<?= $color ?>;flex-shrink:0"></div>
            <div style="flex:1;font-size:13px;color:var(--text2)"><?= htmlspecialchars($at['assistance_type']) ?></div>
            <div style="font-size:13px;font-weight:600;color:var(--text)"><?= $at['cnt'] ?></div>
            <div style="font-size:11px;color:var(--text3);min-width:32px;text-align:right"><?= $pct ?>%</div>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- Recent rows -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

        <!-- Recent Households -->
        <div class="card">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <div class="section-title" style="margin:0;border:none;padding:0">🏠 Recently Registered</div>
            <a href="households.php" class="btn btn-ghost btn-sm">View All</a>
          </div>
          <?php if ($recent_hh->num_rows === 0): ?>
          <div class="empty-state" style="padding:30px 20px">
            <div class="empty-icon">🏠</div>
            <h4>No households yet</h4>
            <p><a href="households.php?action=add" style="color:var(--accent2)">Register the first one</a></p>
          </div>
          <?php else: ?>
          <div class="table-wrap">
          <table>
            <thead><tr><th>Household Head</th><th>Level</th><th>Date</th></tr></thead>
            <tbody>
            <?php while($r = $recent_hh->fetch_assoc()): ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:8px">
                  <div class="avatar"><?= strtoupper(substr($r['household_head'],0,1)) ?></div>
                  <?= htmlspecialchars($r['household_head']) ?>
                </div>
              </td>
              <td>
                <?php
                  $bmap = ['Indigent'=>'badge-red','Low Income'=>'badge-amber','Near Poor'=>'badge-blue'];
                  echo '<span class="badge '.($bmap[$r['poverty_level']]??'badge-blue').'">'.$r['poverty_level'].'</span>';
                ?>
              </td>
              <td style="color:var(--text3)"><?= date('M j', strtotime($r['created_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
          </div>
          <?php endif; ?>
        </div>

        <!-- Recent Assistance -->
        <div class="card">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <div class="section-title" style="margin:0;border:none;padding:0">🤝 Recent Assistance</div>
            <a href="assistance.php" class="btn btn-ghost btn-sm">View All</a>
          </div>
          <?php if ($recent_aid->num_rows === 0): ?>
          <div class="empty-state" style="padding:30px 20px">
            <div class="empty-icon">🤝</div>
            <h4>No assistance yet</h4>
            <p><a href="assistance.php?action=add" style="color:var(--accent2)">Add a record</a></p>
          </div>
          <?php else: ?>
          <div class="table-wrap">
          <table>
            <thead><tr><th>Household</th><th>Type</th><th>Date</th></tr></thead>
            <tbody>
            <?php while($r = $recent_aid->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($r['household_head']) ?></td>
              <td><span class="badge badge-green"><?= htmlspecialchars($r['assistance_type']) ?></span></td>
              <td style="color:var(--text3)"><?= date('M j', strtotime($r['date_given'])) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
          </div>
          <?php endif; ?>
        </div>

      </div>
    </div><!-- /content -->
  </div><!-- /main -->
</div><!-- /layout -->
</body>
</html>
