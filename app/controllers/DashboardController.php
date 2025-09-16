<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class DashboardController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->call->model('UserModel', 'user');
        $this->call->model('PeopleModel', 'student');
        $this->call->library('session');
        $this->call->library('form_validation');
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
    }

    /**
     * Main dashboard page
     */
    public function index() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            redirect('auth/login');
        }

        $current_user = $this->get_current_user();
        $student_data = null;
        
        // Get student data if user is a student
        if ($current_user['student_id']) {
            // Use session data if available (for updated photos), otherwise fetch from database
            if (isset($_SESSION['student_data'])) {
                $student_data = $_SESSION['student_data'];
            } else {
                $student_data = $this->student->get($current_user['student_id']);
                // Cache the data in session for future use
                $_SESSION['student_data'] = $student_data;
            }
        }

        $data = [
            'user' => $current_user,
            'student' => $student_data
        ];

        $this->call->view('dashboard/index', $data);
    }

    /**
     * Update user profile
     */
    public function update_profile() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            redirect('auth/login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->form_validation
                ->name('name')->required()->min_length(2)
                ->name('email')->required()->valid_email()
                ->name('first_name')->required()->min_length(2)
                ->name('last_name')->required()->min_length(2);

            // Only validate password if provided
            if (!empty($_POST['password'])) {
                $this->form_validation
                    ->name('password')->min_length(6)
                    ->name('confirm_password')->matches('password');
            }

            if ($this->form_validation->run() === FALSE) {
                $current_user = $this->get_current_user();
                $student_data = null;
                
                // Get student data if user is a student
                if ($current_user['student_id']) {
                    $student_data = $this->student->get($current_user['student_id']);
                }

                $data = [
                    'user' => $current_user,
                    'student' => $student_data,
                    'validation_errors' => $this->form_validation->errors()
                ];
                $this->call->view('dashboard/index', $data);
                return;
            }

            $user_id = $_SESSION['user_id'];
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $password = !empty($_POST['password']) ? $_POST['password'] : null;
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);

            // Check if email already exists (excluding current user)
            if ($this->user->email_exists($email, $user_id)) {
                $_SESSION['error'] = 'Email already exists';
                $this->index();
                return;
            }

            // Handle file upload
            $photo = null;
            if (isset($_FILES['photo']) && 
                $_FILES['photo']['error'] === UPLOAD_ERR_OK && 
                !empty($_FILES['photo']['tmp_name']) && 
                is_uploaded_file($_FILES['photo']['tmp_name']) &&
                $_FILES['photo']['size'] > 0) {
                
                // Validate file type and size before using upload library
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_type = $_FILES['photo']['type'];
                $file_size = $_FILES['photo']['size'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
                    // Only initialize upload library if we have a valid file
                    $this->call->library('upload', $_FILES['photo']);
                    $this->upload
                        ->max_size(5) // 5MB
                        ->min_size(0) // No minimum size requirement
                        ->set_dir('public/uploads')
                        ->allowed_extensions(['jpg', 'jpeg', 'png', 'gif'])
                        ->allowed_mimes(['image/jpeg', 'image/png', 'image/gif'])
                        ->is_image()
                        ->encrypt_name();

                    if ($this->upload->do_upload()) {
                        $photo = $this->upload->get_filename();
                    } else {
                        $errors = $this->upload->get_errors();
                        $_SESSION['error'] = 'Photo upload failed: ' . implode(', ', $errors);
                        $this->index();
                        return;
                    }
                } else {
                    if (!in_array($file_type, $allowed_types)) {
                        $_SESSION['error'] = 'Invalid file type. Please upload a valid image file (JPG, PNG, GIF).';
                    } else {
                        $_SESSION['error'] = 'File too large. Maximum size is 5MB.';
                    }
                    $this->index();
                    return;
                }
            }

            // Start transaction using Database class directly
            $this->call->database();
            $this->db->transaction();
            
            try {
                // Update user
                $user_updated = $this->user->update_profile($user_id, $name, $email, $password);
                
                if ($user_updated !== false) {
                    // Update student record
                    $student_updated = $this->student->update_person(
                        $_SESSION['student_id'], 
                        $first_name, 
                        $last_name, 
                        $email, 
                        $photo
                    );
                    
                    if ($student_updated !== false) {
                        $this->db->commit();
                        
                        // Update session data
                        $_SESSION['user_name'] = $name;
                        $_SESSION['user_email'] = $email;
                        if ($photo) {
                            $_SESSION['student_photo'] = $photo;
                        }
                        
                        $_SESSION['success'] = 'Profile updated successfully!';
                        
                        // Refresh student data to include the new photo
                        if ($_SESSION['student_id']) {
                            $updated_student = $this->student->get($_SESSION['student_id']);
                            if ($updated_student) {
                                $_SESSION['student_data'] = $updated_student;
                            }
                        }
                    } else {
                        $this->db->roll_back();
                        $_SESSION['error'] = 'Failed to update student record';
                    }
                } else {
                    $this->db->roll_back();
                    $_SESSION['error'] = 'Failed to update user profile';
                }
            } catch (Exception $e) {
                $this->db->roll_back();
                $_SESSION['error'] = 'Update failed. Please try again.';
            }
            
            redirect('dashboard');
        } else {
            redirect('dashboard');
        }
    }

    /**
     * Get current user data
     */
    private function get_current_user() {
        return get_current_user_data();
    }
}
