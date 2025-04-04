<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Controller {

	function __construct(){
		parent::__construct();
		$this->load->helper('url');
		$this->load->model('users_model');
		
		//include modal.php in views
		$this->inc['modal'] = $this->load->view('modal', '', true);
	}

	public function index(){
		$this->load->view('show', $this->inc);
	}

	public function show(){
		$page = $this->input->post('page') ? $this->input->post('page') : 1;
		$per_page = 2; // Number of records per page
		
		// Get search parameters
		$search_text = $this->input->post('search_text');
		$search_status = $this->input->post('search_status');
		
		$data = $this->users_model->show($page, $per_page, $search_text, $search_status);
		$total_records = $this->users_model->count_records($search_text, $search_status);
		$total_pages = ceil($total_records / $per_page);
		
		$output = '';
		if(!empty($data)) {
			foreach($data as $row){
				$output .= '<tr>';
				$output .= '<td>'.htmlspecialchars($row->id).'</td>';
				$output .= '<td>'.htmlspecialchars($row->email).'</td>';
				$output .= '<td>'.htmlspecialchars($row->password).'</td>';
				$output .= '<td>'.htmlspecialchars($row->fname).'</td>';
				$output .= '<td>'.htmlspecialchars(ucfirst($row->status)).'</td>';
				$output .= '<td>';
				if(!empty($row->images)){
					$output .= '<img src="'.base_url('uploads/'.$row->images).'" alt="User Image" style="max-width: 100px; max-height: 100px; object-fit: cover;">';
				} else {
					$output .= 'No image';
				}
				$output .= '</td>';
				$output .= '<td>';
				$output .= '<button class="btn btn-warning edit" data-id="'.htmlspecialchars($row->id).'"><span class="glyphicon glyphicon-edit"></span> Edit</button> ';
				$output .= '<button class="btn btn-danger delete" data-id="'.htmlspecialchars($row->id).'"><span class="glyphicon glyphicon-trash"></span> Delete</button>';
				$output .= '</td>';
				$output .= '</tr>';
			}
		} else {
			$output .= '<tr><td colspan="7" class="text-center">No records found</td></tr>';
		}

		if($total_pages > 1) {
			$output .= '<tr><td colspan="7">';
			$output .= '<div class="pagination" style="text-align: center; margin-top: 10px;">';
			for($i = 1; $i <= $total_pages; $i++){
				$active = ($i == $page) ? 'active' : '';
				$output .= '<a href="#" class="pagination-link btn btn-default '.($active ? 'btn-primary' : '').'" data-page="'.$i.'" style="margin: 0 2px;">'.$i.'</a>';
			}
			$output .= '</div>';
			$output .= '</td></tr>';
		}

		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode(['html' => $output]));
	}

	public function insert(){
		$this->load->helper('form');
		$this->load->library('form_validation');

		// Set validation rules
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email');
		$this->form_validation->set_rules('password', 'Password', 'required');
		$this->form_validation->set_rules('fname', 'Full Name', 'required');
		$this->form_validation->set_rules('status', 'Status', 'required|in_list[good,bad]');

		// Configure upload
		$image_path = realpath(APPPATH . '../uploads');
		$config = [
			'upload_path' => $image_path,
			'allowed_types' => 'gif|jpg|jpeg|png',
			'max_size' => 2048, // 2MB
			'encrypt_name' => TRUE,
		];

		$this->load->library('upload', $config);

		if ($this->form_validation->run() == FALSE) {
			$this->output->set_content_type('application/json');
			$this->output->set_output(json_encode(['status' => 'error', 'message' => validation_errors()]));
			return;
		}

		// Handle file upload
		if (!$this->upload->do_upload('image')) {
			$this->output->set_content_type('application/json');
			$this->output->set_output(json_encode(['status' => 'error', 'message' => $this->upload->display_errors()]));
			return;
		}

		// Get upload data
		$upload_data = $this->upload->data();

		// Prepare user data
		$user = [
			'email' => $this->input->post('email'),
			'password' => $this->input->post('password'),
			'fname' => $this->input->post('fname'),
			'status' => $this->input->post('status'),
			'images' => $upload_data['file_name']
		];

		// Insert into database
		$result = $this->users_model->insert($user);

		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode([
			'status' => 'success',
			'message' => 'User added successfully'
		]));
	}

	public function getuser(){
		$id = $this->input->post('id');
		$data = $this->users_model->getuser($id);
		
		if($data) {
			// Debug log
			log_message('debug', 'User data retrieved: ' . print_r($data, true));
			
			$response = array(
				'status' => 'success',
				'data' => array(
					'id' => $data->id,
					'email' => $data->email,
					'password' => $data->password,
					'fname' => $data->fname,
					'status' => $data->status ? $data->status : 'good', // Set default if null
					'images' => $data->images
				)
			);
		} else {
			$response = array(
				'status' => 'error',
				'message' => 'User not found'
			);
		}
		
		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($response));
	}

	public function update(){
		$this->load->helper('form');
		$this->load->library('form_validation');

		try {
			// Set validation rules
			$this->form_validation->set_rules('email', 'Email', 'required|valid_email');
			$this->form_validation->set_rules('password', 'Password', 'required');
			$this->form_validation->set_rules('fname', 'Full Name', 'required');
			$this->form_validation->set_rules('status', 'Status', 'required|in_list[good,bad]');

			if ($this->form_validation->run() == FALSE) {
				$this->output->set_content_type('application/json');
				$this->output->set_output(json_encode([
					'status' => 'error', 
					'message' => strip_tags(validation_errors())
				]));
				return;
			}

			$id = $this->input->post('id');
			if (!$id) {
				throw new Exception('User ID is required');
			}

			$current_image = $this->input->post('current_image');
			$image_name = $current_image;

			// Handle file upload if a new image is provided
			if (!empty($_FILES['image']['name'])) {
				$image_path = realpath(APPPATH . '../uploads');
				if (!is_dir($image_path) || !is_writable($image_path)) {
					throw new Exception('Upload directory is not writable or does not exist');
				}
				
				// Initialize upload library with configuration
				$config = [
					'upload_path' => $image_path,
					'allowed_types' => 'gif|jpg|jpeg|png',
					'max_size' => 2048, // 2MB
					'encrypt_name' => TRUE,
				];

				$this->load->library('upload');
				$this->upload->initialize($config);

				if (!$this->upload->do_upload('image')) {
					throw new Exception($this->upload->display_errors('', ''));
				}

				$upload_data = $this->upload->data();
				$image_name = $upload_data['file_name'];

				// Delete old image if it exists
				if (!empty($current_image)) {
					$old_image_path = $image_path . DIRECTORY_SEPARATOR . $current_image;
					if (file_exists($old_image_path)) {
						@unlink($old_image_path);
					}
				}
			}

			// Prepare user data
			$user = [
				'email' => $this->input->post('email'),
				'password' => $this->input->post('password'),
				'fname' => $this->input->post('fname'),
				'status' => $this->input->post('status'),
				'images' => $image_name
			];

			// Update database
			$result = $this->users_model->updateuser($user, $id);

			if($result) {
				$this->output->set_content_type('application/json');
				$this->output->set_output(json_encode([
					'status' => 'success',
					'message' => 'User updated successfully'
				]));
			} else {
				throw new Exception('Failed to update user in database');
			}

		} catch (Exception $e) {
			log_message('error', 'Update error: ' . $e->getMessage());
			$this->output->set_status_header(500);
			$this->output->set_content_type('application/json');
			$this->output->set_output(json_encode([
				'status' => 'error',
				'message' => 'Error: ' . $e->getMessage()
			]));
		}
	}

	public function delete(){
		$id = $this->input->post('id');
		$image = $this->input->post('image');

		// Delete image file if it exists
		if (!empty($image)) {
			$image_path = realpath(APPPATH . '../uploads');
			$file_path = $image_path . '/' . $image;
			if (file_exists($file_path)) {
				unlink($file_path);
			}
		}

		// Delete from database
		$result = $this->users_model->delete($id);

		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode([
			'status' => 'success',
			'message' => 'User deleted successfully'
		]));
	}
}