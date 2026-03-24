<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
include 'db.php';

// Overall stats
$total_hh     = $conn->query("SELECT COUNT(*) c FROM households")->fetch_assoc()['c'];
$total_aid    = $conn->query("SELECT COUNT(*) c FROM assistance")->fetch_assoc()['c'];
$total_amount = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM assistance")->fetch_assoc()['s'];
$indigent_ct  = $conn->query("SELECT COUNT(*) c FROM households WHERE poverty_level='Indigent'")->fetch_assoc()['c'];

// Poverty distribution
$pov_dist = $conn->query("SELECT poverty_level, COUNT(*) cnt FROM households GROUP BY poverty_level ORDER BY cnt DESC");

// Assistance by type
$aid_by_type = $conn->query("SELECT assistance_type, COUNT(*) cnt, COALESCE(SUM(amount),0) total FROM assistance GROUP BY assistance_type ORDER BY cnt DESC");

// Monthly trend (last 6 months)
$monthly = $conn->query("
  SELECT DATE_FORMAT(date_given,'%b %Y') mo,
         DATE_FORMAT(date_given,'%Y-%m') mo_sort,
         COUNT(*) cnt
  FROM assistance
  WHERE date_given >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  GROUP BY mo_sort, mo
  ORDER BY mo_sort ASC
");
$monthly_data = [];
while($r = $monthly->fetch_assoc()) $monthly_data[] = $r;

// Aid to households with no recent (3 months) assistance
$no_recent = $conn->query("
  SELECT h.household_head, h.poverty_level, h.address,
         MAX(a.date_given) last_aid
  FROM households h
  LEFT JOIN assistance a ON a.household_id = h.id
  GROUP BY h.id
  HAVING last_aid IS NULL OR last_aid < DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
  ORDER BY h.poverty_level = 'Indigent' DESC, last_aid ASC
  LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Reports — AHON-BRGY</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    @media print {
      .sidebar, .topbar, .no-print { display: none !important; }
      .main { margin-left: 0 !important; }
      .content { padding: 20px !important; }
      .card { break-inside: avoid; border: 1px solid #ccc !important; }
      body { background: #fff !important; color: #000 !important; }
    }
  </style>
</head>
<body>
<div class="layout">
  <?php include 'sidebar.php'; ?>
  <div class="main">
    <header class="topbar">
      <div class="topbar-left">
        <h2>Reports</h2>
        <p>Data summary and statistics</p>
      </div>
      <div class="topbar-right no-print">
        <button onclick="window.print()" class="btn btn-ghost btn-sm">🖨️ Print Report</button>
      </div>
    </header>

    <div class="content">
      <div class="sdg-bar">
        📋 Generated on: <strong><?= date('F j, Y \a\t g:i A') ?></strong> &nbsp;·&nbsp; By: <?= htmlspecialchars($_SESSION['user']['username']) ?>
      </div>

      <!-- Summary stats -->
      <div class="stat-grid" style="margin-bottom:24px">
        <div class="stat-card blue">
          <div class="stat-icon">🏠</div>
          <div class="stat-value"><?= $total_hh ?></div>
          <div class="stat-label">Total Households</div>
        </div>
        <div class="stat-card red" style="">
          <div class="stat-icon" style="background:rgba(239,68,68,0.15)">⚠️</div>
          <div class="stat-value" style="color:#fca5a5"><?= $indigent_ct ?></div>
          <div class="stat-label">Indigent Households</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon">🤝</div>
          <div class="stat-value"><?= $total_aid ?></div>
          <div class="stat-label">Total Assistance Given</div>
        </div>
        <div class="stat-card amber">
          <div class="stat-icon">💰</div>
          <div class="stat-value" style="font-size:22px">₱<?= number_format($total_amount,0) ?></div>
          <div class="stat-label">Total Value Distributed</div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">

        <!-- Poverty Distribution -->
        <div class="card">
          <div class="section-title">📊 Poverty Level Distribution</div>
          <?php
          $pov_rows = [];
          while($r = $pov_dist->fetch_assoc()) $pov_rows[] = $r;
          $lv_colors = ['Indigent'=>'#ef4444','Low Income'=>'#f59e0b','Near Poor'=>'#3b82f6'];
          foreach($pov_rows as $r):
            $pct = $total_hh > 0 ? round(($r['cnt']/$total_hh)*100) : 0;
            $col = $lv_colors[$r['poverty_level']] ?? '#3b82f6';
          ?>
          <div style="margin-bottom:18px">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;align-items:center">
              <span style="font-size:14px;font-weight:600;color:var(--text)"><?= $r['poverty_level'] ?></span>
              <span style="font-size:20px;font-weight:800;font-family:'Sora',sans-serif;color:<?= $col ?>"><?= $r['cnt'] ?></span>
            </div>
            <div style="height:10px;background:var(--border);border-radius:5px;overflow:hidden">
              <div style="height:100%;width:<?= $pct ?>%;background:<?= $col ?>;border-radius:5px"></div>
            </div>
            <div style="font-size:11px;color:var(--text3);margin-top:4px"><?= $pct ?>% of total households</div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Aid by Type -->
        <div class="card">
          <div class="section-title">🤝 Assistance by Type</div>
          <?php
          $aid_rows = [];
          while($r = $aid_by_type->fetch_assoc()) $aid_rows[] = $r;
          if(empty($aid_rows)): ?>
          <div class="empty-state" style="padding:30px">
            <div class="empty-icon">📦</div>
            <h4>No data yet</h4>
          </div>
          <?php else:
          $aid_max = max(array_column($aid_rows,'cnt'));
          $colors = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#ec4899'];
          foreach($aid_rows as $i => $r):
            $pct = $aid_max > 0 ? round(($r['cnt']/$aid_max)*100) : 0;
            $col = $colors[$i % count($colors)];
          ?>
          <div style="margin-bottom:14px">
            <div style="display:flex;justify-content:space-between;margin-bottom:5px">
              <span style="font-size:13px;color:var(--text2)"><?= htmlspecialchars($r['assistance_type']) ?></span>
              <span style="font-size:13px;font-weight:700;color:var(--text)"><?= $r['cnt'] ?> <span style="color:var(--text3);font-weight:400">records</span></span>
            </div>
            <div style="height:6px;background:var(--border);border-radius:3px;overflow:hidden">
              <div style="height:100%;width:<?= $pct ?>%;background:<?= $col ?>;border-radius:3px"></div>
            </div>
            <?php if($r['total'] > 0): ?>
            <div style="font-size:11px;color:var(--text3);margin-top:3px">Value: ₱<?= number_format($r['total'],2) ?></div>
            <?php endif; ?>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- Monthly trend -->
      <div class="card" style="margin-bottom:24px">
        <div class="section-title">📅 Monthly Assistance Trend (Last 6 Months)</div>
        <?php if(empty($monthly_data)): ?>
        <div class="empty-state" style="padding:30px">
          <div class="empty-icon">📅</div>
          <h4>No monthly data yet</h4>
        </div>
        <?php else:
        $max_cnt = max(array_column($monthly_data,'cnt'));
        ?>
        <div style="display:flex;align-items:flex-end;gap:12px;height:140px;padding-bottom:8px">
          <?php foreach($monthly_data as $m):
            $h = $max_cnt > 0 ? round(($m['cnt']/$max_cnt)*120) : 0;
          ?>
          <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px">
            <span style="font-size:11px;color:var(--text3)"><?= $m['cnt'] ?></span>
            <div style="width:100%;height:<?= $h ?>px;background:linear-gradient(180deg,var(--accent),var(--accent3));border-radius:4px 4px 0 0;min-height:4px"></div>
            <span style="font-size:10px;color:var(--text3);text-align:center"><?= $m['mo'] ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Households needing attention -->
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
          <div class="section-title" style="margin:0;border:none;padding:0">⚠️ Households Needing Attention</div>
          <span class="badge badge-amber">Not aided in 3+ months</span>
        </div>
        <?php if($no_recent->num_rows === 0): ?>
        <div class="empty-state" style="padding:30px">
          <div class="empty-icon">✅</div>
          <h4>All households recently assisted</h4>
        </div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Household Head</th><th>Poverty Level</th><th>Address</th><th>Last Assistance</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php while($r = $no_recent->fetch_assoc()): ?>
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
              <td><?= htmlspecialchars($r['address']) ?></td>
              <td style="color:var(--red)">
                <?= $r['last_aid'] ? date('M j, Y', strtotime($r['last_aid'])) : '<span class="badge badge-red">Never</span>' ?>
              </td>
              <td>
                <a href="assistance.php?action=add" class="btn btn-success btn-sm no-print">🤝 Add Aid</a>
              </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>
</body>
</html>
