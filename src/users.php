<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
if ($_SESSION['user']['role'] !== 'admin') { header("Location: dashboard.php"); exit; }
include 'db.php';

$action  = $_GET['action'] ?? 'list';
$success = '';
$error   = '';

// ── ADD USER
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = trim($_POST['username'] ?? '');
    $pass  = trim($_POST['password'] ?? '');
    $role  = $_POST['role'] ?? 'staff';

    $chk = $conn->prepare("SELECT id FROM users WHERE username=?");
    $chk->bind_param("s", $uname);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $error = "Username already exists.";
    } else {
        $hashed = md5($pass);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?,?,?)");
        $stmt->bind_param("sss", $uname, $hashed, $role);
        if ($stmt->execute()) { $success = "✅ User created!"; $action = 'list'; }
        else $error = "Failed to create user.";
    }
}

// ── DELETE
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id == $_SESSION['user']['id']) {
        $error = "You cannot delete your own account.";
    } else {
        $conn->query("DELETE FROM users WHERE id=$id");
        $success = "🗑️ User deleted.";
    }
    $action = 'list';
}

$users = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Users — AHON-BRGY</title>
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
        <h2>User Management</h2>
        <p>Admin-only: manage system accounts</p>
      </div>
      <div class="topbar-right">
        <a href="users.php?action=add" class="btn btn-primary btn-sm">+ Add User</a>
      </div>
    </header>

    <div class="content">
      <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <?php if ($action === 'add'): ?>
      <div class="breadcrumb"><a href="users.php">Users</a> › <span>Add User</span></div>
      <div class="card" style="max-width:440px">
        <div class="section-title">👤 Create New User</div>
        <form method="POST" action="users.php?action=add">
          <div class="form-grid full">
            <div class="form-group">
              <label>USERNAME *</label>
              <input type="text" name="username" required placeholder="e.g. jdelacruz">
            </div>
            <div class="form-group">
              <label>PASSWORD *</label>
              <input type="password" name="password" required placeholder="Minimum 6 characters">
            </div>
            <div class="form-group">
              <label>ROLE</label>
              <select name="role">
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
              </select>
            </div>
          </div>
          <div class="divider"></div>
          <div style="display:flex;gap:10px">
            <button type="submit" class="btn btn-success">✅ Create User</button>
            <a href="users.php" class="btn btn-ghost">Cancel</a>
          </div>
        </form>
      </div>

      <?php else: ?>
      <div class="card" style="padding:0">
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>#</th><th>Username</th><th>Role</th><th>Date Created</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php $i=1; while($r=$users->fetch_assoc()): ?>
            <tr>
              <td style="color:var(--text3)"><?= $i++ ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:10px">
                  <div class="avatar"><?= strtoupper(substr($r['username'],0,1)) ?></div>
                  <?= htmlspecialchars($r['username']) ?>
                  <?php if($r['id']==$_SESSION['user']['id']): ?>
                  <span class="badge badge-blue" style="font-size:10px">You</span>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <span class="badge <?= $r['role']==='admin'?'badge-purple':'badge-green' ?>">
                  <?= ucfirst($r['role']) ?>
                </span>
              </td>
              <td style="color:var(--text3)"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
              <td>
                <?php if($r['id']!=$_SESSION['user']['id']): ?>
                <a href="users.php?action=delete&id=<?= $r['id'] ?>"
                   onclick="return confirm('Delete user <?= htmlspecialchars($r['username']) ?>?')"
                   class="btn btn-danger btn-sm">🗑️ Delete</a>
                <?php else: ?>
                <span class="text-muted text-sm">—</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
