<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
        }
        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .profile-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #e9ecef;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo site_url('dashboard'); ?>">
                <i class="fas fa-graduation-cap me-2"></i>Student Portal
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <?php if ($user['student_photo']): ?>
                            <img src="<?php echo base_url() . '/public/uploads/' . $user['student_photo']; ?>" 
                                 alt="Profile" class="profile-img me-2">
                        <?php else: ?>
                            <i class="fas fa-user-circle fa-2x me-2"></i>
                        <?php endif; ?>
                        <span><?php echo htmlspecialchars($user['name']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo site_url('dashboard'); ?>">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a></li>
                        <?php if ($user['role'] === 'admin'): ?>
                        <li><a class="dropdown-item" href="<?php echo site_url('people'); ?>">
                            <i class="fas fa-users me-2"></i>Manage Students
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="<?php echo site_url('auth/logout'); ?>">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($validation_errors) && $validation_errors): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $validation_errors; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-user-edit me-2"></i>My Profile
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($validation_errors) && $validation_errors): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $validation_errors; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo site_url('dashboard/update_profile'); ?>" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-4 text-center mb-4">
                                    <div class="mb-3">
                        <?php 
                        // Use session data directly for photo display
                        $profile_photo = $user['student_photo'] ?? ($student['photo'] ?? null);
                        ?>
                        <?php if ($profile_photo): ?>
                            <img src="<?php echo base_url() . '/public/uploads/' . $profile_photo; ?>" 
                                 alt="Profile" class="profile-preview" id="profilePreview">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/120x120/667eea/ffffff?text=No+Photo" 
                                 alt="Profile" class="profile-preview" id="profilePreview">
                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3">
                                        <label for="photo" class="form-label">
                                            <i class="fas fa-camera me-2"></i>Profile Picture
                                        </label>
                                        <input type="file" class="form-control" id="photo" name="photo" 
                                               accept="image/*" onchange="previewImage(this)">
                                        <div class="form-text">Max size: 5MB. Allowed: JPG, PNG, GIF</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="first_name" class="form-label">
                                                <i class="fas fa-user me-2"></i>First Name
                                            </label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?php echo $student ? htmlspecialchars($student['first_name']) : ''; ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="last_name" class="form-label">
                                                <i class="fas fa-user me-2"></i>Last Name
                                            </label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                                   value="<?php echo $student ? htmlspecialchars($student['last_name']) : ''; ?>" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="name" class="form-label">
                                            <i class="fas fa-id-card me-2"></i>Display Name
                                        </label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope me-2"></i>Email Address
                                        </label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="password" class="form-label">
                                                <i class="fas fa-lock me-2"></i>New Password
                                            </label>
                                            <input type="password" class="form-control" id="password" name="password">
                                            <div class="form-text">Leave blank to keep current password</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="confirm_password" class="form-label">
                                                <i class="fas fa-lock me-2"></i>Confirm Password
                                            </label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-shield-alt me-2"></i>Role
                                        </label>
                                        <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
