<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class PeopleModel extends Model {
    protected $table = 'students';
    protected $primary_key = 'id';
    protected $fillable = ['user_id', 'first_name', 'last_name', 'email', 'photo'];

    public function all($with_deleted = false) {
        if ($with_deleted) {
            return $this->db->table($this->table)
                            ->order_by('last_name','ASC')
                            ->get_all();
        } else {
            return $this->db->table($this->table)
                            ->order_by('last_name','ASC')
                            ->get_all();
        }
    }
    

    public function get($id) {
        return $this->db->table($this->table)->where($this->primary_key, $id)->get();
    }

    public function create($user_id, $first_name, $last_name, $email, $photo = null) {
        $result = $this->db->table($this->table)->insert([
            'user_id'    => $user_id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email,
            'photo'      => $photo
        ]);
        
        // Return the inserted ID
        return $result ? $this->db->last_id() : false;
    }

    public function update_person($id, $first_name, $last_name, $email, $photo = null) {
        $data = [
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email
        ];
        
        if ($photo !== null) {
            $data['photo'] = $photo;
        }
        
        return $this->db->table($this->table)
                        ->where($this->primary_key, $id)
                        ->update($data);
    }

    public function delete_person($id) {
        return $this->db->table($this->table)->where($this->primary_key, $id)->delete();
    }

    /**
     * Get student by user_id
     */
    public function get_by_user_id($user_id) {
        return $this->db->table($this->table)
                        ->where('user_id', $user_id)
                        ->get();
    }

    /**
     * Get student with user information
     */
    public function get_with_user($id) {
        return $this->db->table($this->table . ' s')
                        ->join('users u', 'u.id = s.user_id')
                        ->where('s.' . $this->primary_key, $id)
                        ->select('s.*, u.name as user_name, u.role')
                        ->get();
    }

    /**
     * Get all students with user information
     */
    public function get_all_with_users() {
        return $this->db->table($this->table . ' s')
                        ->join('users u', 'u.id = s.user_id')
                        ->select('s.*, u.name as user_name, u.role')
                        ->order_by('s.last_name', 'ASC')
                        ->get_all();
    }

    // Pagination using ORM-like helper returning unified structure
    public function paginate_list($per_page, $page) {
        $offset = ($page - 1) * $per_page;
        $total = $this->count();
        $data = $this->db->table($this->table)
                         ->order_by('last_name','ASC')
                         ->limit($per_page)
                         ->offset($offset)
                         ->get_all();
        return $this->build_pagination_payload($data, $total, $per_page, $page);
    }

    // Search by first_name, last_name, email with safe LIKEs (Query Builder fallback)
    public function search_paginate($q, $per_page, $page) {
        $offset = ($page - 1) * $per_page;
        // total count on a fresh builder
        $total = (int) ($this->db->table($this->table)
            ->grouped(function($g) use ($q) {
                $g->like('first_name', "%$q%")
                  ->or_like('last_name', "%$q%")
                  ->or_like('email', "%$q%");
            })
            ->select_count($this->primary_key, 'total_row')
            ->get()['total_row'] ?? 0);

        // fetch rows on a separate fresh builder
        $data = $this->db->table($this->table)
                   ->grouped(function($g) use ($q) {
                       $g->like('first_name', "%$q%")
                         ->or_like('last_name', "%$q%")
                         ->or_like('email', "%$q%");
                   })
                   ->order_by('last_name','ASC')
                   ->limit($per_page)
                   ->offset($offset)
                   ->get_all();

        return $this->build_pagination_payload($data, (int)$total, $per_page, $page);
    }

    public function count_all() {
        return (int) $this->db->table($this->table)->select_count($this->primary_key, 'c')->get()['c'] ?? 0;
    }

    public function count_search($q) {
        return (int) ($this->db->table($this->table)
            ->grouped(function($g) use ($q) {
                $g->like('first_name', "%$q%")
                  ->or_like('last_name', "%$q%")
                  ->or_like('email', "%$q%");
            })
            ->select_count($this->primary_key, 'c')
            ->get()['c'] ?? 0);
    }

    public function search_rows($q, $per_page, $page) {
        $offset = ($page - 1) * $per_page;
        return $this->db->table($this->table)
            ->grouped(function($g) use ($q) {
                $g->like('first_name', "%$q%")
                  ->or_like('last_name', "%$q%")
                  ->or_like('email', "%$q%");
            })
            ->order_by('last_name','ASC')
            ->limit($per_page)
            ->offset($offset)
            ->get_all();
    }

    // Use Pagination library's LIMIT clause string for consistency with Another project
    public function get_with_pagination($limit_clause) {
        $sql = "SELECT * FROM {$this->table} ORDER BY last_name ASC {$limit_clause}";
        $result = $this->db->raw($sql);
        return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function get_search_with_pagination($q, $limit_clause) {
        $like = "%{$q}%";
        $sql = "SELECT * FROM {$this->table} 
                WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ?
                ORDER BY last_name ASC {$limit_clause}";
        $result = $this->db->raw($sql, [$like, $like, $like]);
        return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    protected function build_pagination_payload($data, $total, $per_page, $page) {
        $last_page = (int) max(1, ceil($total / max(1, $per_page)));
        return [
            'data' => $data,
            'total' => (int) $total,
            'per_page' => (int) $per_page,
            'current_page' => (int) $page,
            'last_page' => $last_page
        ];
    }
}
