<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>People</title>
  <?php require __DIR__.'/_styles.php'; ?>
</head>
<body>
  <div class="container">
    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success'])): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
      </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
      </div>
    <?php endif; ?>

    <div class="header">
      <div class="title">People <span class="badge"><?php echo isset($total) ? (int)$total : (int)count($rows ?? []); ?> total</span></div>
      <div style="display: flex; gap: 10px; align-items: center;">
        <a class="btn btn-ghost" href="<?php echo site_url('dashboard'); ?>">
          <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <a class="btn btn-primary" href="<?php echo site_url('people/create'); ?>">Add Person</a>
      </div>
    </div>

    <div class="card">
      <form method="get" action="<?php echo site_url('people'); ?>" style="display:flex; gap:8px; align-items:center; padding: 8px 12px; border-bottom:1px solid #eee;">
        <input class="input" style="max-width:320px;" type="text" name="q" value="<?php echo htmlspecialchars($q ?? ''); ?>" placeholder="Search name or email...">
        <button class="btn" type="submit">Search</button>
        <?php if (!empty($q)): ?>
          <a class="btn btn-ghost" href="<?php echo site_url('people'); ?>">Clear</a>
        <?php endif; ?>
        <div style="margin-left:auto; display:flex; align-items:center; gap:8px;">
          <label class="label" for="per_page">Per page</label>
          <select class="input" style="width:auto; padding:8px 10px;" id="per_page" name="per_page" onchange="this.form.submit()">
            <?php foreach ([10,25,50,100] as $n): ?>
              <option value="<?php echo $n; ?>" <?php echo (int)($per_page ?? 10) === $n ? 'selected' : ''; ?>><?php echo $n; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
      <table class="table">
        <thead>
          <tr>
            <th style="width:70px;">ID</th>
            <th>First name</th>
            <th>Last name</th>
            <th>Email</th>
            <th style="width:180px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?php echo $r['id']; ?></td>
                <td><?php echo htmlspecialchars($r['first_name']); ?></td>
                <td><?php echo htmlspecialchars($r['last_name']); ?></td>
                <td><?php echo htmlspecialchars($r['email']); ?></td>
                <td class="row-actions">
                  <a class="link" href="<?php echo site_url('people/edit/'.$r['id']); ?>">Edit</a>
                  <a class="link" href="<?php echo site_url('people/delete/'.$r['id']); ?>" onclick="return confirm('Delete this record?')">Delete</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="5" class="empty">No records found</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
      <div class="pagination">
        <?php echo $pagination_links ?? ''; ?>
        <span class="page-status"><?php echo htmlspecialchars($pageMeta['info'] ?? ''); ?></span>
      </div>
    </div>
  </div>
</body>
</html>
