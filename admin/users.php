<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$conn = getDbConnection();
$pageTitle = 'Manage Users';
$base = '../';
$message = '';

// Handle add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = sanitize($_POST['name']); $email = sanitize($_POST['email']);
        $role = in_array($_POST['role'], ['admin','authority']) ? $_POST['role'] : 'authority';
        $pass = password_hash($_POST['password'] ?: 'ResQzone@123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
        $stmt->bind_param('ssss', $name, $email, $pass, $role);
        if ($stmt->execute()) { $message = 'User added.'; logAction('add_user', "Added user $email"); }
        else { $message = 'Could not add user (email may already exist).'; }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id !== (int)$_SESSION['user_id']) {
            $stmt = $conn->prepare('DELETE FROM users WHERE id=?'); $stmt->bind_param('i', $id); $stmt->execute();
            $message = 'User deleted.'; logAction('delete_user', "Deleted user #$id");
        } else { $message = "You can't delete your own account."; }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id']; $name = sanitize($_POST['name']); $role = in_array($_POST['role'], ['admin','authority']) ? $_POST['role'] : 'authority';
        $stmt = $conn->prepare('UPDATE users SET name=?, role=? WHERE id=?'); $stmt->bind_param('ssi', $name, $role, $id); $stmt->execute();
        $message = 'User updated.'; logAction('edit_user', "Edited user #$id");
    }
}

$users = $conn->query('SELECT * FROM users ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<h3 class="text-white mb-1"><i class="fa-solid fa-users-gear me-2"></i>Manage Users</h3>
<?php if ($message): ?><div class="alert alert-info py-2 small"><?= sanitize($message) ?></div><?php endif; ?>

<div class="d-flex justify-content-end mb-3">
  <button class="btn btn-hz-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="fa-solid fa-plus me-1"></i>Add User</button>
</div>

<div class="hz-panel">
  <div class="table-responsive">
    <table class="hz-table">
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Created</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td class="text-white"><?= sanitize($u['name']) ?></td>
          <td class="text-dim"><?= sanitize($u['email']) ?></td>
          <td><span class="role-pill"><?= sanitize($u['role']) ?></span></td>
          <td class="text-faint small"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          <td class="text-nowrap">
            <button class="btn btn-hz-ghost btn-sm" data-bs-toggle="modal" data-bs-target="#editUser<?= $u['id'] ?>"><i class="fa-solid fa-pen"></i></button>
            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this user?')">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $u['id'] ?>">
              <button class="btn btn-hz-ghost btn-sm text-danger"><i class="fa-solid fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <div class="modal fade hz-modal" id="editUser<?= $u['id'] ?>">
          <div class="modal-dialog"><div class="modal-content">
            <form method="POST">
              <div class="modal-header"><h6 class="modal-title">Edit User</h6><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="<?= $u['id'] ?>">
                <label class="hz-label">Name</label><input name="name" value="<?= sanitize($u['name']) ?>" class="form-control hz-form-control mb-3">
                <label class="hz-label">Role</label>
                <select name="role" class="form-select hz-form-control">
                  <option value="authority" <?= $u['role']==='authority'?'selected':'' ?>>Authority</option>
                  <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                </select>
              </div>
              <div class="modal-footer"><button class="btn btn-hz-primary btn-sm">Save</button></div>
            </form>
          </div></div>
        </div>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade hz-modal" id="addUserModal">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST">
      <div class="modal-header"><h6 class="modal-title">Add User</h6><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="add">
        <label class="hz-label">Name</label><input name="name" required class="form-control hz-form-control mb-3">
        <label class="hz-label">Email</label><input type="email" name="email" required class="form-control hz-form-control mb-3">
        <label class="hz-label">Password (default ResQzone@123 if left blank)</label><input type="password" name="password" class="form-control hz-form-control mb-3">
        <label class="hz-label">Role</label>
        <select name="role" class="form-select hz-form-control"><option value="authority">Authority</option><option value="admin">Admin</option></select>
      </div>
      <div class="modal-footer"><button class="btn btn-hz-primary btn-sm">Create User</button></div>
    </form>
  </div></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
