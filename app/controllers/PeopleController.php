<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class PeopleController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->call->model('PeopleModel','people');
        $this->call->model('UserModel','user');
        $this->call->library('session');
        $this->call->library('form_validation');
    }

    /**
     * Check if user is authenticated
     */
    private function check_auth() {
        if (!isset($_SESSION['user_id'])) {
            redirect('auth/login');
        }
    }

    public function index() {
        $this->check_auth();
        
        $q = isset($_GET['q']) ? $_GET['q'] : null;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1) { $page = 1; }
        // Per-page limiter like in Another project
        $allowed_per_page = [10, 25, 50, 100];
        $per_page = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;
        if (!in_array($per_page, $allowed_per_page, true)) { $per_page = 10; }

        // Use Pagination library like in Another project
        $this->call->library('pagination');
        $baseUrl = 'people';
        $crumbs = 5;
        // Make links like people/?page=2 and persist search query
        if ($q) {
            $this->pagination->set_options(['page_delimiter' => '/?q='.urlencode($q).'&page=']);
        } else {
            $this->pagination->set_options(['page_delimiter' => '/?page=']);
        }
        // Theme + custom classes like Another project
        $this->pagination->set_theme('custom');
        $this->pagination->set_custom_classes([
            'nav'    => 'pagination-nav',
            'ul'     => 'pagination-list',
            'li'     => 'pagination-item',
            'a'      => 'pagination-link',
            'active' => 'active'
        ]);

        if ($q) {
            $total = $this->people->count_search($q);
            $pageMeta = $this->pagination->initialize($total, $per_page, $page, $baseUrl, $crumbs);
            $rows = $this->people->get_search_with_pagination($q, $pageMeta['limit']);
        } else {
            $total = $this->people->count_all();
            $pageMeta = $this->pagination->initialize($total, $per_page, $page, $baseUrl, $crumbs);
            $rows = $this->people->get_with_pagination($pageMeta['limit']);
        }

        $data['rows'] = $rows;
        $data['pageMeta'] = $pageMeta;
        $data['total'] = $total;
        $data['q'] = $q;
        $data['per_page'] = $per_page;
        // Append per_page to pagination links
        $links = $this->pagination->paginate();
        if (strpos($links, 'href=') !== false) {
            $append = [];
            if ($per_page !== 10) { $append['per_page'] = $per_page; }
            if ($q) { $append['q'] = $q; }
            if (!empty($append)) {
                $links = preg_replace_callback('/href=\"([^\"]+)\"/i', function($m) use ($append) {
                    $url = $m[1];
                    $sep = (strpos($url, '?') !== false) ? '&' : '?';
                    foreach ($append as $k => $v) {
                        if (strpos($url, $k.'=') === false) {
                            $url .= $sep.rawurlencode($k).'='.rawurlencode((string)$v);
                            $sep = '&';
                        }
                    }
                    return 'href="'.$url.'"';
                }, $links);
            }
        }
        $data['pagination_links'] = $links;
        $this->call->view('people/index', $data);
    }

    public function create() {
        $this->check_auth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->form_validation
                ->name('first_name')->required()->min_length(2)
                ->name('last_name')->required()->min_length(2)
                ->name('email')->required()->valid_email()
                ->name('password')->required()->min_length(6)
                ->name('confirm_password')->matches('password');

            if ($this->form_validation->run() === FALSE) {
                $this->call->view('people/create');
                return;
            }

            $first_name = trim($_POST['first_name']);
            $last_name  = trim($_POST['last_name']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];

            // Check if email already exists
            if ($this->user->email_exists($email)) {
                $this->form_validation->set_error('email', 'Email already exists');
                $this->call->view('people/create');
                return;
            }

            // Start transaction
            $this->call->database();
            $this->db->transaction();
            
            try {
                // Create user first
                $user_id = $this->user->create_user(
                    $first_name . ' ' . $last_name, // Display name
                    $email,
                    $password,
                    'student' // Role
                );
                
                if ($user_id) {
                    // Create student record
                    $student_id = $this->people->create($user_id, $first_name, $last_name, $email);
                    
                    if ($student_id) {
                        $this->db->commit();
                        $_SESSION['success'] = 'Student created successfully!';
                    } else {
                        $this->db->roll_back();
                        $_SESSION['error'] = 'Failed to create student record';
                    }
                } else {
                    $this->db->roll_back();
                    $_SESSION['error'] = 'Failed to create user account';
                }
            } catch (Exception $e) {
                $this->db->roll_back();
                $_SESSION['error'] = 'Failed to create student. Please try again.';
            }
            
            redirect('people');
        }
        $this->call->view('people/create');
    }

    public function edit($id = null) {
        $this->check_auth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->form_validation
                ->name('first_name')->required()->min_length(2)
                ->name('last_name')->required()->min_length(2)
                ->name('email')->required()->valid_email();

            // Only validate password if provided
            if (!empty($_POST['password'])) {
                $this->form_validation
                    ->name('password')->min_length(6)
                    ->name('confirm_password')->matches('password');
            }

            if ($this->form_validation->run() === FALSE) {
                $data['row'] = $this->people->get($_POST['id']);
                if ($data['row']) {
                    $data['row'] = array_merge($data['row'], $_POST);
                } else {
                    $data['row'] = $_POST;
                }
                $this->call->view('people/edit', $data);
                return;
            }

            $id = $_POST['id'];
            $first_name = trim($_POST['first_name']);
            $last_name  = trim($_POST['last_name']);
            $email = trim($_POST['email']);
            $password = !empty($_POST['password']) ? $_POST['password'] : null;

            // Get current student data to find user_id
            $student = $this->people->get($id);
            if (!$student) {
                $_SESSION['error'] = 'Student not found';
                redirect('people');
            }

            // Check if email already exists (excluding current user)
            if ($this->user->email_exists($email, $student['user_id'])) {
                $this->form_validation->set_error('email', 'Email already exists');
                $data['row'] = $this->people->get($id);
                $this->call->view('people/edit', $data);
                return;
            }

            // Start transaction
            $this->call->database();
            $this->db->transaction();
            
            try {
                // Update user
                $user_updated = $this->user->update_profile(
                    $student['user_id'],
                    $first_name . ' ' . $last_name, // Display name
                    $email,
                    $password
                );
                
                if ($user_updated !== false) {
                    // Update student record
                    $student_updated = $this->people->update_person($id, $first_name, $last_name, $email);
                    
                    if ($student_updated !== false) {
                        $this->db->commit();
                        $_SESSION['success'] = 'Student updated successfully!';
                    } else {
                        $this->db->roll_back();
                        $_SESSION['error'] = 'Failed to update student record';
                    }
                } else {
                    $this->db->roll_back();
                    $_SESSION['error'] = 'Failed to update user account';
                }
            } catch (Exception $e) {
                $this->db->roll_back();
                $_SESSION['error'] = 'Failed to update student. Please try again.';
            }
            
            redirect('people');
        }
        $data['row'] = $this->people->get($id);
        $this->call->view('people/edit', $data);
    }

    public function delete($id) {
        $this->check_auth();
        
        // Get student data to find user_id
        $student = $this->people->get($id);
        if (!$student) {
            $_SESSION['error'] = 'Student not found';
            redirect('people');
        }

        // Start transaction
        $this->call->database();
        $this->db->transaction();
        
        try {
            // Delete student record first
            $student_deleted = $this->people->delete_person($id);
            
            if ($student_deleted) {
                // Delete user account
                $user_deleted = $this->user->delete($student['user_id']);
                
                if ($user_deleted) {
                    $this->db->commit();
                    $_SESSION['success'] = 'Student deleted successfully!';
                } else {
                    $this->db->roll_back();
                    $_SESSION['error'] = 'Failed to delete user account';
                }
            } else {
                $this->db->roll_back();
                $_SESSION['error'] = 'Failed to delete student record';
            }
        } catch (Exception $e) {
            $this->db->roll_back();
            $_SESSION['error'] = 'Failed to delete student. Please try again.';
        }
        
        redirect('people');
    }
}
