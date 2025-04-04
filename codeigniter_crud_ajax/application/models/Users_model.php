<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users_model extends CI_Model {

	function __construct(){
		parent::__construct();
		$this->load->database();
	}

	public function show($page = 1, $per_page = 10, $search_text = '', $search_status = ''){
		$offset = ($page - 1) * $per_page;
		
		if(!empty($search_text)) {
			$this->db->group_start();
			$this->db->like('fname', $search_text);
			$this->db->or_like('email', $search_text);
			$this->db->group_end();
		}
		
		if(!empty($search_status)) {
			$this->db->where('status', $search_status);
		}
		
		$this->db->limit($per_page, $offset);
		$query = $this->db->get('users');
		return $query->result();
	}

	public function count_records($search_text = '', $search_status = ''){
		if(!empty($search_text)) {
			$this->db->group_start();
			$this->db->like('fname', $search_text);
			$this->db->or_like('email', $search_text);
			$this->db->group_end();
		}
		
		if(!empty($search_status)) {
			$this->db->where('status', $search_status);
		}
		
		return $this->db->count_all_results('users');
	}

	public function insert($user){
		return $this->db->insert('users', $user);
	}

	public function getuser($id){
		$query = $this->db->get_where('users', array('id' => $id));
		return $query->row();
	}

	public function updateuser($user, $id){
		return $this->db->update('users', $user, array('id' => $id));
	}

	public function delete($id){
		return $this->db->delete('users', array('id' => $id));
	}
}
?>