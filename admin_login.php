<?php
require_once __DIR__ . '/includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');

    if ($email && $pass) {
        $db   = get_db();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($pass, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = $admin['id'];
            $_SESSION['admin_name']      = $admin['name'];
            $_SESSION['admin_email']     = $admin['email'];
            header('Location: admin.php');
            exit;
        }
        $error = 'Invalid email or password.';
    } else {
        $error = 'Please fill in both fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MMK Hub – Owner Login</title>
  <link rel="icon" href="assets/images/logo.png">
  <link rel="stylesheet" href="css/style.css">
  <style>
    body { background: #0f172a; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .login-card {
      background: #fff; border-radius: 18px; padding: 44px 40px;
      width: 100%; max-width: 420px; box-shadow: 0 24px 60px rgba(0,0,0,.35);
    }
    .login-logo { display: flex; align-items: center; gap: 12px; margin-bottom: 28px; }
    .login-logo img { height: 48px; border-radius: 8px; }
    .login-logo h1 { font-size: 1.35rem; font-weight: 800; color: #0f172a; }
    .login-logo p { font-size: .78rem; color: #64748b; }
    .field { margin-bottom: 16px; }
    .field label { display: block; font-size: .78rem; font-weight: 700; color: #0f172a;
                   text-transform: uppercase; letter-spacing: .4px; margin-bottom: 5px; }
    .field input { width: 100%; padding: 11px 14px; border: 1.5px solid #e2e8f0;
                   border-radius: 8px; font-size: .9rem; font-family: inherit; transition: .2s; }
    .field input:focus { outline: none; border-color: #047857; box-shadow: 0 0 0 3px rgba(4,120,87,.12); }
    .btn-login { width: 100%; padding: 12px; background: #047857; color: #fff;
                 border: none; border-radius: 8px; font-weight: 700; font-size: 1rem;
                 cursor: pointer; transition: .2s; margin-top: 4px; }
    .btn-login:hover { background: #065f46; }
    .error-msg { background: #fee2e2; color: #991b1b; border-radius: 6px;
                 padding: 10px 14px; font-size: .85rem; margin-bottom: 16px; }
    .back-link { text-align: center; margin-top: 18px; font-size: .82rem; color: #64748b; }
    .back-link a { color: #047857; font-weight: 600; }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="login-logo">
      <img src="assets/images/logo.png" alt="MMK Hub">
      <div>
        <h1>MMK HUB</h1>
        <p>Owner Administration Portal</p>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="error-msg">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="field">
        <label for="admin_email">Email Address</label>
        <input type="email" id="admin_email" name="email" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="owner@mmkhub.cm" autocomplete="username">
      </div>
      <div class="field">
        <label for="admin_pass">Password</label>
        <input type="password" id="admin_pass" name="password" required
               placeholder="••••••••" autocomplete="current-password">
      </div>
      <button type="submit" class="btn-login">
        🔐 Sign In to Control Center
      </button>
    </form>

    <div class="back-link">
      <a href="index.php">← Back to MMK Hub Store</a>
    </div>
  </div>
</body>
</html>
