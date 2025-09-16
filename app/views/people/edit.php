<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Person</title>
  <?php require __DIR__.'/_styles.php'; ?>
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="title">Edit Person #<?php echo $row['id']; ?></div>
      <div style="display: flex; gap: 10px; align-items: center;">
        <a class="btn btn-ghost" href="<?php echo site_url('dashboard'); ?>">
          <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <a class="btn btn-ghost" href="<?php echo site_url('people'); ?>">Back to People</a>
      </div>
    </div>

    <div class="card">
      <?php $LAVA = lava_instance(); echo $LAVA->form_validation->errors(); ?>
      <form class="form" method="post" action="<?php echo site_url('people/edit/'.$row['id']); ?>">
        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
        <div>
          <label class="label">First name</label>
          <input class="input" type="text" name="first_name" value="<?php echo htmlspecialchars($row['first_name']); ?>" required>
        </div>
        <div>
          <label class="label">Last name</label>
          <input class="input" type="text" name="last_name" value="<?php echo htmlspecialchars($row['last_name']); ?>" required>
        </div>
        <div>
          <label class="label">Email</label>
          <input class="input" type="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" required>
        </div>
        <div>
          <label class="label">New Password</label>
          <input class="input" type="password" name="password" minlength="6">
          <small class="text-muted">Leave blank to keep current password. Minimum 6 characters if changing.</small>
        </div>
        <div>
          <label class="label">Confirm New Password</label>
          <input class="input" type="password" name="confirm_password" minlength="6">
        </div>
        <div class="actions">
          <button class="btn btn-primary" type="submit">Update</button>
          <a class="btn btn-ghost" href="<?php echo site_url('people'); ?>">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
