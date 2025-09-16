<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class UserModel extends Model {
    protected $table = 'users';
    protected $primary_key = 'id';
    protected $fillable = ['name', 'email', 'password_hash', 'role'];

    /**
     * Create a new user
     */
    public function create_user($name, $email, $password, $role = 'student') {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $result = $this->db->table($this->table)->insert([
            'name' => $name,
            'email' => $email,
            'password_hash' => $password_hash,
            'role' => $role,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Return the inserted ID
        return $result ? $this->db->last_id() : false;
    }

    /**
     * Authenticate user login
     */
    public function authenticate($email, $password) {
        $user = $this->db->table($this->table)
                         ->where('email', $email)
                         ->get();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        
        return false;
    }

    /**
     * Get user by email
     */
    public function get_by_email($email) {
        return $this->db->table($this->table)
                        ->where('email', $email)
                        ->get();
    }

    /**
     * Get user by ID
     */
    public function get_by_id($id) {
        return $this->db->table($this->table)
                        ->where($this->primary_key, $id)
                        ->get();
    }

    /**
     * Update user profile
     */
    public function update_profile($id, $name, $email, $password = null) {
        $data = [
            'name' => $name,
            'email' => $email,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($password) {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        return $this->db->table($this->table)
                        ->where($this->primary_key, $id)
                        ->update($data);
    }

    /**
     * Check if email exists
     */
    public function email_exists($email, $exclude_id = null) {
        $query = $this->db->table($this->table)->where('email', $email);
        
        if ($exclude_id) {
            $query->where($this->primary_key, '!=', $exclude_id);
        }
        
        return $query->get() ? true : false;
    }

    /**
     * Get all users (for admin)
     */
    public function get_all_users() {
        return $this->db->table($this->table)
                        ->order_by('name', 'ASC')
                        ->get_all();
    }

    /**
     * Delete user
     */
    public function delete_user($id) {
        return $this->db->table($this->table)
                        ->where($this->primary_key, $id)
                        ->delete();
    }
}
