<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}
include 'db.php';

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = md5(trim($_POST['password'] ?? ''));

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['user'] = $result->fetch_assoc();
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AHON-BRGY — Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #0b0f1a; --card: #151d2e; --border: #1f2d45;
      --accent: #3b82f6; --accent2: #60a5fa; --accent3: #1d4ed8;
      --green: #10b981; --purple: #8b5cf6;
      --text: #f1f5f9; --text2: #94a3b8; --text3: #475569;
      --red: #ef4444;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }

    /* Animated background */
    .bg-orbs {
      position: fixed; inset: 0; pointer-events: none; z-index: 0;
    }
    .orb {
      position: absolute; border-radius: 50%;
      filter: blur(80px); opacity: 0.12;
      animation: float 12s ease-in-out infinite;
    }
    .orb1 { width: 500px; height: 500px; background: var(--accent); top: -150px; left: -100px; animation-delay: 0s; }
    .orb2 { width: 400px; height: 400px; background: var(--purple); bottom: -100px; right: -80px; animation-delay: 4s; }
    .orb3 { width: 300px; height: 300px; background: var(--green); top: 40%; left: 60%; animation-delay: 8s; }
    @keyframes float {
      0%, 100% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(20px, -20px) scale(1.05); }
    }

    .login-wrap {
      position: relative; z-index: 1;
      width: 100%; max-width: 440px;
      padding: 20px;
    }

    .login-brand {
      text-align: center;
      margin-bottom: 32px;
    }
    .brand-icon {
      width: 64px; height: 64px;
      background: linear-gradient(135deg, var(--accent), var(--purple));
      border-radius: 18px;
      display: inline-flex; align-items: center; justify-content: center;
      font-size: 30px;
      margin-bottom: 16px;
      box-shadow: 0 8px 32px rgba(59,130,246,0.4);
    }
    .brand-title {
      font-family: 'Sora', sans-serif;
      font-size: 26px; font-weight: 800;
      background: linear-gradient(135deg, var(--text), var(--accent2));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 6px;
    }
    .brand-sub {
      font-size: 13px; color: var(--text3);
      line-height: 1.5;
    }

    .login-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 36px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    }
    .login-card h2 {
      font-family: 'Sora', sans-serif;
      font-size: 18px; font-weight: 700;
      margin-bottom: 4px;
    }
    .login-card .sub {
      font-size: 13px; color: var(--text3); margin-bottom: 28px;
    }

    .form-group { margin-bottom: 18px; }
    label {
      display: block;
      font-size: 12px; font-weight: 600;
      color: var(--text2); letter-spacing: 0.5px;
      margin-bottom: 6px;
    }
    .input-wrap { position: relative; }
    .input-wrap .input-icon {
      position: absolute; left: 14px; top: 50%;
      transform: translateY(-50%);
      font-size: 15px; color: var(--text3); pointer-events: none;
    }
    input {
      width: 100%;
      background: #0d1625;
      border: 1px solid var(--border);
      border-radius: 10px;
      color: var(--text);
      padding: 12px 14px 12px 42px;
      font-size: 14px;
      font-family: 'DM Sans', sans-serif;
      outline: none;
      transition: border-color .2s, box-shadow .2s;
    }
    input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
    }

    .error-msg {
      background: rgba(239,68,68,0.1);
      border: 1px solid rgba(239,68,68,0.3);
      color: #fca5a5;
      padding: 12px 16px;
      border-radius: 10px;
      font-size: 13px;
      margin-bottom: 18px;
      display: flex; align-items: center; gap: 8px;
    }

    .btn-login {
      width: 100%;
      background: linear-gradient(135deg, var(--accent), var(--accent3));
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 13px;
      font-size: 15px; font-weight: 600;
      font-family: 'Sora', sans-serif;
      cursor: pointer;
      box-shadow: 0 6px 20px rgba(59,130,246,0.4);
      transition: all .2s;
      margin-top: 6px;
    }
    .btn-login:hover { transform: translateY(-1px); box-shadow: 0 8px 28px rgba(59,130,246,0.5); }
    .btn-login:active { transform: none; }

    .demo-creds {
      margin-top: 20px;
      padding: 12px 16px;
      background: rgba(59,130,246,0.06);
      border: 1px solid rgba(59,130,246,0.15);
      border-radius: 10px;
      font-size: 12px; color: var(--text3);
      text-align: center;
    }
    .demo-creds strong { color: var(--accent2); }

    .sdg-strip {
      text-align: center;
      margin-top: 24px;
      font-size: 11px; color: var(--text3);
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .sdg-dot {
      width: 6px; height: 6px; border-radius: 50%;
      background: var(--accent);
      display: inline-block;
    }
  </style>
</head>
<body>
  <div class="bg-orbs">
    <div class="orb orb1"></div>
    <div class="orb orb2"></div>
    <div class="orb orb3"></div>
  </div>

  <div class="login-wrap">
    <div class="login-brand">
      <div class="brand-icon">🏘️</div>
      <div class="brand-title">AHON-BRGY</div>
      <div class="brand-sub">Web-Based Poverty Profiling &amp;<br>Assistance Monitoring System</div>
    </div>

    <div class="login-card">
      <h2>Welcome !</h2>
      <p class="sub">Sign in to your account to continue</p>

      <?php if ($error): ?>
      <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label>USERNAME</label>
          <div class="input-wrap">
            <span class="input-icon">👤</span>
            <input type="text" name="username" placeholder="Enter your username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
          </div>
        </div>
        <div class="form-group">
          <label>PASSWORD</label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <input type="password" name="password" placeholder="Enter your password" required>
          </div>
        </div>
        <button type="submit" class="btn-login">Sign In →</button>
      </form>

      <div class="demo-creds">
        Demo credentials — Username: <strong>admin</strong> &nbsp;|&nbsp; Password: <strong>admin123</strong>
      </div>
    </div>

    <div class="sdg-strip">
      <span class="sdg-dot"></span>
      Supporting SDG 1 · SDG 10 · SDG 16
      <span class="sdg-dot"></span>
    </div>
  </div>
</body>
</html>
