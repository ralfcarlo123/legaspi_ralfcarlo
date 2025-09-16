<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Add Person</title>
  <?php require __DIR__.'/_styles.php'; ?>
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="title">Add Person</div>
      <div style="display: flex; gap: 10px; align-items: center;">
        <a class="btn btn-ghost" href="<?php echo site_url('dashboard'); ?>">
          <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <a class="btn btn-ghost" href="<?php echo site_url('people'); ?>">Back to People</a>
      </div>
    </div>

    <div class="card">
      <?php $LAVA = lava_instance(); echo $LAVA->form_validation->errors(); ?>
      <form class="form" method="post" action="<?php echo site_url('people/create'); ?>">
        <div>
          <label class="label">First name</label>
          <input class="input" type="text" name="first_name" required>
        </div>
        <div>
          <label class="label">Last name</label>
          <input class="input" type="text" name="last_name" required>
        </div>
        <div>
          <label class="label">Email</label>
          <input class="input" type="email" name="email" required>
        </div>
        <div>
          <label class="label">Password</label>
          <input class="input" type="password" name="password" required minlength="6">
          <small class="text-muted">Minimum 6 characters</small>
        </div>
        <div>
          <label class="label">Confirm Password</label>
          <input class="input" type="password" name="confirm_password" required minlength="6">
        </div>
        <div class="actions">
          <button class="btn btn-primary" type="submit">Save</button>
          <a class="btn btn-ghost" href="<?php echo site_url('people'); ?>">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
