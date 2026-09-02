<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Session expired. Please try again.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $conn = getDbConnection();
        $stmt = $conn->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            logAction('login', 'User logged in');
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Authority Login — RESQZONE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="console-grid">
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-brand">
      <i class="fa-solid fa-tower-broadcast"></i>
      <h4 class="font-display mt-2 mb-0 text-white">RESQZONE</h4>
      <div class="text-dim small">Authority / Admin Sign In</div>
    </div>

    <?php if ($error): ?><div class="alert alert-danger py-2 small"><?= sanitize($error) ?></div><?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'forbidden'): ?>
      <div class="alert alert-warning py-2 small">You don't have permission to access that page.</div>
    <?php endif; ?>
    <?php if (isset($_GET['logged_out'])): ?>
      <div class="alert alert-info py-2 small">You have been signed out.</div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <div class="mb-3">
        <label class="hz-label">Email</label>
        <input type="email" name="email" class="form-control hz-form-control" placeholder="you@resqzone.gov.demo" required autofocus>
      </div>
      <div class="mb-3">
        <label class="hz-label">Password</label>
        <input type="password" name="password" class="form-control hz-form-control" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-hz-primary w-100 py-2 mt-2">Sign In <i class="fa-solid fa-arrow-right-to-bracket ms-1"></i></button>
    </form>

    <div class="demo-cred-box mt-4">
      <div class="fw-bold mb-1"><i class="fa-solid fa-circle-info me-1"></i>Demo credentials</div>
      Admin: admin@resqzone.gov.demo<br>
      Authority: authority@resqzone.gov.demo<br>
      Password (both): <strong>ResQzone@123</strong>
    </div>

    <div class="text-center mt-4"><a href="index.php" class="text-dim small"><i class="fa-solid fa-arrow-left me-1"></i>Back to homepage</a></div>
  </div>
</div>
</body>
</html>
