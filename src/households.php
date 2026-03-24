<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
include 'db.php';

$action  = $_GET['action'] ?? 'list';
$success = '';
$error   = '';

// ── ADD
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $head    = trim($_POST['household_head'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $members = intval($_POST['members'] ?? 1);
    $income  = floatval($_POST['monthly_income'] ?? 0);
    $level   = $_POST['poverty_level'] ?? 'Low Income';
    $contact = trim($_POST['contact'] ?? '');
    $notes   = trim($_POST['notes'] ?? '');

    // Duplicate check
    $chk = $conn->prepare("SELECT id FROM households WHERE LOWER(household_head) = LOWER(?)");
    $chk->bind_param("s", $head);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $error = "⚠️ A household with that name already exists. Please check for duplicates.";
    } else {
        $stmt = $conn->prepare("INSERT INTO households (household_head, address, members, monthly_income, poverty_level, contact, notes) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("ssiidss", $head, $address, $members, $income, $level, $contact, $notes);
        if ($stmt->execute()) {
            $success = "✅ Household registered successfully!";
            $action = 'list';
        } else {
            $error = "Failed to register. Please try again.";
        }
    }
}

// ── EDIT
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = intval($_POST['id']);
    $head    = trim($_POST['household_head'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $members = intval($_POST['members'] ?? 1);
    $income  = floatval($_POST['monthly_income'] ?? 0);
    $level   = $_POST['poverty_level'] ?? 'Low Income';
    $contact = trim($_POST['contact'] ?? '');
    $notes   = trim($_POST['notes'] ?? '');

    $stmt = $conn->prepare("UPDATE households SET household_head=?, address=?, members=?, monthly_income=?, poverty_level=?, contact=?, notes=? WHERE id=?");
    $stmt->bind_param("ssidsssi", $head, $address, $members, $income, $level, $contact, $notes, $id);
    if ($stmt->execute()) {
        $success = "✅ Household updated successfully!";
        $action = 'list';
    } else {
        $error = "Update failed.";
    }
}

// ── DELETE
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM assistance WHERE household_id = $id");
    $conn->query("DELETE FROM households WHERE id = $id");
    $success = "🗑️ Household deleted.";
    $action = 'list';
}

// ── FETCH EDIT RECORD
$edit_row = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $r  = $conn->query("SELECT * FROM households WHERE id=$id");
    $edit_row = $r->fetch_assoc();
}

// ── LIST with search
$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? '');
$sql = "SELECT * FROM households WHERE 1=1";
if ($search) $sql .= " AND (household_head LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR address LIKE '%".mysqli_real_escape_string($conn,$search)."%')";
if ($filter) $sql .= " AND poverty_level = '".mysqli_real_escape_string($conn,$filter)."'";
$sql .= " ORDER BY created_at DESC";
$rows = $conn->query($sql);
$total = $rows->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Households — AHON-BRGY</title>
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
        <h2>Households</h2>
        <p>Register and manage household profiles</p>
      </div>
      <div class="topbar-right">
        <a href="households.php?action=add" class="btn btn-primary btn-sm">+ Register Household</a>
      </div>
    </header>

    <div class="content">
      <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <?php if ($action === 'add' || $action === 'edit'): ?>
      <!-- ── FORM ── -->
      <div class="breadcrumb">
        <a href="households.php">Households</a> › 
        <span><?= $action === 'add' ? 'Register New' : 'Edit Record' ?></span>
      </div>
      <div class="card" style="max-width:680px">
        <div class="section-title">
          <?= $action === 'add' ? '🏠 Register New Household' : '✏️ Edit Household' ?>
        </div>
        <form method="POST" action="households.php?action=<?= $action ?><?= $action==='edit'?'&id='.$edit_row['id']:'' ?>">
          <?php if ($action === 'edit'): ?>
          <input type="hidden" name="id" value="<?= $edit_row['id'] ?>">
          <?php endif; ?>

          <div class="form-grid">
            <div class="form-group span2">
              <label>HOUSEHOLD HEAD NAME *</label>
              <input type="text" name="household_head" required
                     value="<?= htmlspecialchars($edit_row['household_head'] ?? '') ?>"
                     placeholder="Full name of household head">
              <span class="field-hint">Used for duplicate detection — enter full legal name</span>
            </div>
            <div class="form-group span2">
              <label>COMPLETE ADDRESS *</label>
              <input type="text" name="address" required
                     value="<?= htmlspecialchars($edit_row['address'] ?? '') ?>"
                     placeholder="Purok, street, barangay">
            </div>
            <div class="form-group">
              <label>NUMBER OF MEMBERS</label>
              <input type="number" name="members" min="1" max="50"
                     value="<?= $edit_row['members'] ?? 1 ?>">
            </div>
            <div class="form-group">
              <label>MONTHLY INCOME (₱)</label>
              <input type="number" name="monthly_income" min="0" step="0.01"
                     value="<?= $edit_row['monthly_income'] ?? 0 ?>"
                     placeholder="0.00">
            </div>
            <div class="form-group">
              <label>POVERTY LEVEL *</label>
              <select name="poverty_level">
                <?php foreach(['Indigent','Low Income','Near Poor'] as $lv): ?>
                <option value="<?= $lv ?>" <?= ($edit_row['poverty_level']??'Low Income')===$lv?'selected':'' ?>><?= $lv ?></option>
                <?php endforeach; ?>
              </select>
              <span class="field-hint">Indigent = highest priority</span>
            </div>
            <div class="form-group">
              <label>CONTACT NUMBER</label>
              <input type="text" name="contact"
                     value="<?= htmlspecialchars($edit_row['contact'] ?? '') ?>"
                     placeholder="09xx-xxx-xxxx">
            </div>
            <div class="form-group span2">
              <label>ADDITIONAL NOTES</label>
              <textarea name="notes" placeholder="Special circumstances, disability, etc."><?= htmlspecialchars($edit_row['notes'] ?? '') ?></textarea>
            </div>
          </div>

          <div class="divider"></div>
          <div style="display:flex;gap:10px">
            <button type="submit" class="btn btn-success">
              <?= $action === 'add' ? '✅ Register Household' : '💾 Save Changes' ?>
            </button>
            <a href="households.php" class="btn btn-ghost">Cancel</a>
          </div>
        </form>
      </div>

      <?php else: ?>
      <!-- ── LIST ── -->
      <div class="page-header">
        <div>
          <h3>All Households</h3>
          <p><?= $total ?> record<?= $total!=1?'s':'' ?> found</p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
          <form method="GET" action="households.php" style="display:flex;gap:8px;align-items:center">
            <div class="search-bar">
              <span class="search-icon">🔍</span>
              <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name or address...">
            </div>
            <select name="filter" style="min-width:130px">
              <option value="">All Levels</option>
              <?php foreach(['Indigent','Low Income','Near Poor'] as $lv): ?>
              <option value="<?= $lv ?>" <?= $filter===$lv?'selected':'' ?>><?= $lv ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-ghost btn-sm">Filter</button>
            <?php if($search||$filter): ?><a href="households.php" class="btn btn-ghost btn-sm">✕ Clear</a><?php endif; ?>
          </form>
        </div>
      </div>

      <div class="card" style="padding:0">
        <?php if ($total === 0): ?>
        <div class="empty-state">
          <div class="empty-icon">🏠</div>
          <h4><?= $search||$filter ? 'No results found' : 'No households registered' ?></h4>
          <p><?= $search||$filter ? 'Try adjusting your search or filter.' : 'Start by registering the first household.' ?></p>
          <?php if (!$search && !$filter): ?>
          <a href="households.php?action=add" class="btn btn-primary" style="margin-top:16px">+ Register Household</a>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Household Head</th>
                <th>Address</th>
                <th>Members</th>
                <th>Monthly Income</th>
                <th>Poverty Level</th>
                <th>Date Added</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php $i=1; while($r = $rows->fetch_assoc()): ?>
            <tr>
              <td style="color:var(--text3)"><?= $i++ ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:10px">
                  <div class="avatar"><?= strtoupper(substr($r['household_head'],0,1)) ?></div>
                  <div>
                    <div style="font-weight:600;color:var(--text)"><?= htmlspecialchars($r['household_head']) ?></div>
                    <?php if($r['contact']): ?>
                    <div style="font-size:11px;color:var(--text3)"><?= htmlspecialchars($r['contact']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td><?= htmlspecialchars($r['address']) ?></td>
              <td><?= $r['members'] ?></td>
              <td>₱<?= number_format($r['monthly_income'], 2) ?></td>
              <td>
                <?php
                  $bmap = ['Indigent'=>'badge-red','Low Income'=>'badge-amber','Near Poor'=>'badge-blue'];
                  echo '<span class="badge '.($bmap[$r['poverty_level']]??'badge-blue').'">'.$r['poverty_level'].'</span>';
                ?>
              </td>
              <td style="color:var(--text3)"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
              <td>
                <div style="display:flex;gap:6px">
                  <a href="households.php?action=edit&id=<?= $r['id'] ?>" class="btn btn-ghost btn-sm">✏️ Edit</a>
                  <a href="assistance.php?action=add&hid=<?= $r['id'] ?>" class="btn btn-ghost btn-sm">🤝 Aid</a>
                  <a href="households.php?action=delete&id=<?= $r['id'] ?>"
                     onclick="return confirm('Delete this household and all its assistance records?')"
                     class="btn btn-danger btn-sm">🗑️</a>
                </div>
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
</body>
</html>
