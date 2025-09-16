<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class AuthController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->call->model('UserModel', 'user');
        $this->call->model('PeopleModel', 'student');
        $this->call->library('session');
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Show login form
     */
    public function login() {
        // Redirect if already logged in
        if (isset($_SESSION['user_id'])) {
            redirect('dashboard');
        }

        $data = [];
        $this->call->library('form_validation');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->form_validation
                ->name('email')->required()->valid_email()
                ->name('password')->required()->min_length(6);

            if ($this->form_validation->run() === FALSE) {
                $data['validation_errors'] = $this->form_validation->errors();
                $this->call->view('auth/login', $data);
                return;
            }

            $email = trim($_POST['email']);
            $password = $_POST['password'];

            $user = $this->user->authenticate($email, $password);
            
            if ($user) {
                // Set session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Get student data if exists
                $student = $this->student->get_by_user_id($user['id']);
                if ($student) {
                    $_SESSION['student_id'] = $student['id'];
                    $_SESSION['student_photo'] = $student['photo'];
                }
                
                redirect('dashboard');
            } else {
                $_SESSION['error'] = 'Invalid email or password';
                $this->call->view('auth/login', $data);
            }
        } else {
            $this->call->view('auth/login', $data);
        }
    }

    /**
     * Show register form
     */
    public function register() {
        // Redirect if already logged in
        if (isset($_SESSION['user_id'])) {
            redirect('dashboard');
        }

        $data = [];
        $this->call->library('form_validation');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->form_validation
                ->name('name')->required()->min_length(2)
                ->name('email')->required()->valid_email()
                ->name('password')->required()->min_length(6)
                ->name('confirm_password')->required()->matches('password')
                ->name('first_name')->required()->min_length(2)
                ->name('last_name')->required()->min_length(2);

            if ($this->form_validation->run() === FALSE) {
                $data['validation_errors'] = $this->form_validation->errors();
                $this->call->view('auth/register', $data);
                return;
            }

            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);

            // Check if email already exists
            if ($this->user->email_exists($email)) {
                $_SESSION['error'] = 'Email already exists';
                $this->call->view('auth/register', $data);
                return;
            }

            // Start transaction
            $this->user->transaction();
            
            try {
                // Create user
                $user_id = $this->user->create_user($name, $email, $password, 'student');
                
                if ($user_id) {
                    // Create student record
                    $student_id = $this->student->create($user_id, $first_name, $last_name, $email);
                    
                    if ($student_id) {
                        $this->user->commit();
                        
                        $_SESSION['success'] = 'Registration successful! Please login with your credentials.';
                        redirect('auth/login');
                    } else {
                        $this->user->rollback();
                        $_SESSION['error'] = 'Failed to create student record';
                        $this->call->view('auth/register', $data);
                    }
                } else {
                    $this->user->rollback();
                    $_SESSION['error'] = 'Failed to create user account';
                    $this->call->view('auth/register', $data);
                }
            } catch (Exception $e) {
                $this->user->rollback();
                $_SESSION['error'] = 'Registration failed. Please try again.';
                $this->call->view('auth/register', $data);
            }
        } else {
            $this->call->view('auth/register', $data);
        }
    }

    /**
     * Logout user
     */
    public function logout() {
        // Clear session data
        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_role']);
        unset($_SESSION['student_id']);
        unset($_SESSION['student_photo']);
        
        // Destroy session
        session_destroy();
        
        redirect('auth/login');
    }

    /**
     * Check if user is authenticated
     */
    public function check_auth() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user data
     */
    public function get_current_user() {
        if (!$this->check_auth()) {
            return null;
        }
        
        return get_current_user_data();
    }
}
