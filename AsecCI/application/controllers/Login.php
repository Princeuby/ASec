<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {
	public function __construct() {
        parent::__construct();
        $this->load->model('login_model');
    }

	public function index() {
	    $this->load->helper('form');
	    $this->load->library('form_validation');
	    $this->load->library('session');
		
		if ($this->session->userdata('home'))
			redirect($this->session->userdata('home'));			

	    $data['title'] = 'Login';
	    $data['officer']['officer_id'] = '';

	    $this->form_validation->set_rules('id', 'Username', 'required');
	    $this->form_validation->set_rules('password', 'Password', 'required');

	    if ($this->form_validation->run() === FALSE) {
	        $this->load->view('index', $data);

	    }
	    else { // Redirects to correct controller after validation
			$user = strip_tags($this->input->post('id'));
			$pass = strip_tags($this->input->post('password'));
			$data['officer'] = $this->login_model->get_officer($user);
			if (empty($data['officer'])) {
            	$this->session->set_flashdata('error','Invalid Username or Password.');
            	redirect('');
			}
            
            if (($user == $data['officer']['officer_id']) &&
				 (password_verify($pass, $data['officer']['password']))) {
				$officerFullName = $data['officer']['first_name'] . " " .
								   $data['officer']['last_name'];
				$newdata = array(
        			'officerID'  => $data['officer']['officer_id'],
        			'officerFullName'     => $officerFullName,
        			'officerRank' => $data['officer']['rank'],
        			'officerDesignation' => $data['officer']['designation']
				);
				$this->session->set_userdata($newdata);

				if ($data['officer']['designation'] == "Rank and File" ||
					 $data['officer']['designation'] == "Assistant Supervisor") {
					$this->session->set_userdata('home','officer');
				}
				elseif ($data['officer']['designation'] == "Supervisor") {
					$this->session->set_userdata('home','supervisor');
				}
				elseif ($data['officer']['designation'] == "CSO" || $data['officer']['designation'] == "ACSO") {
					$this->session->set_userdata('home','cso');
				}
				elseif ($data['officer']['designation'] == "Scheduler") {
					$this->session->set_userdata('home','scheduler');
				}
				elseif ($data['officer']['designation'] == "Committee") {
					$this->session->set_userdata('home','committee');
				}
				elseif ($data['officer']['designation'] == "AVP") {
					$this->session->set_userdata('home','avp');
				}
				elseif ($data['officer']['designation'] == "Admin") {
					$this->session->set_userdata('home','admin');
				}
				else {
					$this->session->set_userdata('home','other');
				}
				redirect($this->session->userdata('home'));
			}
			else {
            	$this->session->set_flashdata('error','Invalid Username or Password.');
	        	$this->load->view('index',$data);
			}	
	    }
	}
	
	public function logout($message=null) {
		$this->load->library('session');
		$this->session->sess_destroy();
		if ($message == 'password_changed') {
        	$this->session->set_flashdata('message','Login with your new password');
		}
		$this->index();
	}
}	
	
?>