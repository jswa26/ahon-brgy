<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
include 'db.php';

$action  = $_GET['action'] ?? 'list';
$success = '';
$error   = '';

// Aid types preset list
$aid_types_list = ['Food Pack','Cash Assistance','Medical Assistance','Educational Assistance','Livelihood Kit','Disaster Relief','Senior Citizen Pension','PWD Assistance','Solo Parent Aid','Other'];

// ── ADD
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $hid     = intval($_POST['household_id'] ?? 0);
    $type    = trim($_POST['assistance_type'] ?? '');
    $custom  = trim($_POST['custom_type'] ?? '');
    if ($type === 'Other' && $custom) $type = $custom;
    $date    = $_POST['date_given'] ?? date('Y-m-d');
    $by      = trim($_POST['given_by'] ?? $_SESSION['user']['username']);
    $notes   = trim($_POST['notes'] ?? '');
    $amount  = floatval($_POST['amount'] ?? 0);

    // Duplicate check: same household, same type, same month
    $month = date('Y-m', strtotime($date));
    $dupchk = $conn->prepare("SELECT id FROM assistance WHERE household_id=? AND assistance_type=? AND DATE_FORMAT(date_given,'%Y-%m')=?");
    $dupchk->bind_param("iss", $hid, $type, $month);
    $dupchk->execute();
    if ($dupchk->get_result()->num_rows > 0) {
        $error = "⚠️ Duplicate detected! This household already received '$type' this month.";
    } else {
        $stmt = $conn->prepare("INSERT INTO assistance (household_id, assistance_type, date_given, given_by, notes, amount) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("issssd", $hid, $type, $date, $by, $notes, $amount);
        if ($stmt->execute()) {
            $success = "✅ Assistance record added!";
            $action = 'list';
        } else {
            $error = "Failed to save. Please try again.";
        }
    }
}

// ── DELETE
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM assistance WHERE id=$id");
    $success = "🗑️ Record deleted.";
    $action = 'list';
}

// ── FETCH all households for dropdown
$hh_list = $conn->query("SELECT id, household_head FROM households ORDER BY household_head ASC");
$hh_map = [];
while($r = $hh_list->fetch_assoc()) $hh_map[$r['id']] = $r['household_head'];

// Pre-fill household from query param
$pre_hid = intval($_GET['hid'] ?? 0);

// ── LIST with search/filter
$search = trim($_GET['search'] ?? '');
$filter_type = trim($_GET['filter_type'] ?? '');
$filter_month = trim($_GET['filter_month'] ?? '');

$sql = "SELECT a.*, h.household_head, h.poverty_level FROM assistance a JOIN households h ON a.household_id=h.id WHERE 1=1";
if ($search) $sql .= " AND h.household_head LIKE '%".mysqli_real_escape_string($conn,$search)."%'";
if ($filter_type) $sql .= " AND a.assistance_type='".mysqli_real_escape_string($conn,$filter_type)."'";
if ($filter_month) $sql .= " AND DATE_FORMAT(a.date_given,'%Y-%m')='".mysqli_real_escape_string($conn,$filter_month)."'";
$sql .= " ORDER BY a.date_given DESC, a.id DESC";
$rows = $conn->query($sql);
$total = $rows->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Assistance — AHON-BRGY</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .custom-type-wrap { display:none; }
    .custom-type-wrap.show { display:block; }
  </style>
</head>
<body>
<div class="layout">
  <?php include 'sidebar.php'; ?>
  <div class="main">
    <header class="topbar">
      <div class="topbar-left">
        <h2>Assistance Records</h2>
        <p>Track and monitor assistance distribution</p>
      </div>
      <div class="topbar-right">
        <a href="assistance.php?action=add" class="btn btn-primary btn-sm">+ Add Record</a>
      </div>
    </header>

    <div class="content">
      <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

      <?php if ($action === 'add'): ?>
      <!-- ── ADD FORM ── -->
      <div class="breadcrumb">
        <a href="assistance.php">Assistance</a> › <span>Add Record</span>
      </div>
      <div class="card" style="max-width:600px">
        <div class="section-title">🤝 Record Assistance Given</div>
        <form method="POST" action="assistance.php?action=add">
          <div class="form-grid">
            <div class="form-group span2">
              <label>HOUSEHOLD *</label>
              <select name="household_id" required>
                <option value="">— Select Household —</option>
                <?php foreach($hh_map as $id => $name): ?>
                <option value="<?= $id ?>" <?= $pre_hid===$id?'selected':'' ?>><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>ASSISTANCE TYPE *</label>
              <select name="assistance_type" id="aid-type-select" onchange="toggleCustom(this.value)" required>
                <option value="">— Select Type —</option>
                <?php foreach($aid_types_list as $t): ?>
                <option value="<?= $t ?>"><?= $t ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group custom-type-wrap" id="custom-type-wrap">
              <label>CUSTOM TYPE</label>
              <input type="text" name="custom_type" placeholder="Specify assistance type">
            </div>
            <div class="form-group">
              <label>DATE GIVEN *</label>
              <input type="date" name="date_given" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
              <label>AMOUNT / VALUE (₱)</label>
              <input type="number" name="amount" min="0" step="0.01" placeholder="Optional">
            </div>
            <div class="form-group">
              <label>GIVEN BY</label>
              <input type="text" name="given_by" value="<?= htmlspecialchars($_SESSION['user']['username']) ?>">
            </div>
            <div class="form-group span2">
              <label>NOTES</label>
              <textarea name="notes" placeholder="Additional details about this assistance..."></textarea>
            </div>
          </div>

          <div class="alert alert-info" style="margin-top:16px">
            🛡️ <strong>Duplicate check is active.</strong> The system will warn you if this household already received the same type of assistance this month.
          </div>

          <div class="divider"></div>
          <div style="display:flex;gap:10px">
            <button type="submit" class="btn btn-success">✅ Save Record</button>
            <a href="assistance.php" class="btn btn-ghost">Cancel</a>
          </div>
        </form>
      </div>

      <?php else: ?>
      <!-- ── LIST ── -->
      <div class="page-header">
        <div>
          <h3>All Assistance Records</h3>
          <p><?= $total ?> record<?= $total!=1?'s':'' ?> found</p>
        </div>
      </div>

      <!-- Filter bar -->
      <div class="card" style="padding:16px;margin-bottom:20px">
        <form method="GET" action="assistance.php" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
          <div>
            <label style="display:block;font-size:11px;color:var(--text3);margin-bottom:4px">SEARCH HOUSEHOLD</label>
            <div class="search-bar">
              <span class="search-icon">🔍</span>
              <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Household name...">
            </div>
          </div>
          <div>
            <label style="display:block;font-size:11px;color:var(--text3);margin-bottom:4px">ASSISTANCE TYPE</label>
            <select name="filter_type" style="min-width:160px">
              <option value="">All Types</option>
              <?php foreach($aid_types_list as $t): ?>
              <option value="<?= $t ?>" <?= $filter_type===$t?'selected':'' ?>><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;font-size:11px;color:var(--text3);margin-bottom:4px">MONTH</label>
            <input type="month" name="filter_month" value="<?= $filter_month ?>">
          </div>
          <button type="submit" class="btn btn-primary btn-sm">Apply</button>
          <?php if($search||$filter_type||$filter_month): ?><a href="assistance.php" class="btn btn-ghost btn-sm">✕ Clear</a><?php endif; ?>
        </form>
      </div>

      <div class="card" style="padding:0">
        <?php if ($total === 0): ?>
        <div class="empty-state">
          <div class="empty-icon">🤝</div>
          <h4><?= $search||$filter_type||$filter_month ? 'No results found' : 'No assistance records yet' ?></h4>
          <p><?= $search||$filter_type||$filter_month ? 'Try adjusting your filters.' : 'Start recording assistance given to households.' ?></p>
          <?php if (!$search && !$filter_type && !$filter_month): ?>
          <a href="assistance.php?action=add" class="btn btn-primary" style="margin-top:16px">+ Add Record</a>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Household</th>
                <th>Poverty Level</th>
                <th>Assistance Type</th>
                <th>Amount</th>
                <th>Date Given</th>
                <th>Given By</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php $i=1; while($r = $rows->fetch_assoc()): ?>
            <tr>
              <td style="color:var(--text3)"><?= $i++ ?></td>
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
              <td><span class="badge badge-green"><?= htmlspecialchars($r['assistance_type']) ?></span></td>
              <td><?= $r['amount'] > 0 ? '₱'.number_format($r['amount'],2) : '<span style="color:var(--text3)">—</span>' ?></td>
              <td><?= date('M j, Y', strtotime($r['date_given'])) ?></td>
              <td style="color:var(--text2)"><?= htmlspecialchars($r['given_by']) ?></td>
              <td>
                <a href="assistance.php?action=delete&id=<?= $r['id'] ?>"
                   onclick="return confirm('Delete this assistance record?')"
                   class="btn btn-danger btn-sm">🗑️ Delete</a>
              </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script>
function toggleCustom(val) {
  const wrap = document.getElementById('custom-type-wrap');
  wrap.classList.toggle('show', val === 'Other');
}
</script>
</body>
</html>
