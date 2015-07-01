<?php
class Scheduler extends CI_Controller {
	public function __construct() {
        parent::__construct();
		$this->load->library('session');
		if ($this->session->userdata('officerID') === null) 
			redirect('login/logout');
        $this->load->model('scheduler_model');
    }
	
	public function set_data() {
		$data['title'] = 'Scheduler';
	    $data['page'] = 'Schedule';
		$data['name'] = 'Schedule Officer';
		$data['rank'] = '';
		$data['functions'] = ['Schedule', 'Alter Schedule'];
		return $data;
	}
	
	public function index() {
		$data = $this->set_data();	
		$this->load->helper('form');
		$data['locations'] = $this->scheduler_model->get_locations();
		$data['shifts'] = $this->scheduler_model->get_shifts();
		$data['officers'] = [];
		$data['unavailable_officers'] = [];
		$data['schedule_officers'] = [];
		$data['display_s'] = "None";
		$data['display_l'] = "None";
		// Rotating algorithm
		$shifts = ["Morning"=>"Afternoon", "Afternoon"=>"Night", "Night"=>"Morning"];
		
		// The beginning of the process
		if ($this->input->post('get-schedule')) {
			$data['selected_location'] = $this->input->post('location');
			$data['selected_shift'] = $this->input->post('shift');
			$data['last_shift'] = $shifts[$data['selected_shift']];
			$data['officers'] = $this->scheduler_model->get_officers($data['selected_location'],
			 	$data['last_shift']);
			
			$num_officers = count($data['officers']); // To prevent everything from breaking
			if (!(empty($data['officers']))) {
				for ($i = 0; $i < $num_officers; $i++) {
					$officerID = $data['officers'][$i]['officer_id'];
					$data['officers'][$i]['officer_name'] = $this->scheduler_model->get_officer_name(
						$officerID);
					$leaveStatus = $this->scheduler_model->get_leave_status($officerID);
					$leaves = $this->scheduler_model->get_officer_leaves($officerID);
					for ($j = 0; $j < count($leaves); $j++) {
						// Checks if the officer should be marked as being on leave or as returned
						if ($leaveStatus['leave_status'] == 0 && $leaves[$j]['approved_status'] == 1 &&
							 strtotime($leaves[$j]['returning_date']) > strtotime(date('Y-m-d'))) {
							$this->scheduler_model->set_leave_status($officerID, 1);
							$data['officers'][$i]['returning_date'] = $leaves[$j]['returning_date'];
							$data['unavailable_officers'][] = $data['officers'][$i];
							unset($data['officers'][$i]);
							break;
						}
					}
					// For officers whose leaves are over
					if ($leaveStatus['leave_status'] == 1) {
						$recentLeave = $this->scheduler_model->get_most_recent_leave($officerID);
						if (!empty($recentLeave) && strtotime($recentLeave['returning_date'])
							 < strtotime(date('Y-m-d')))
							$this->scheduler_model->set_leave_status($officerID, 0);
						else {
							$data['officers'][$i]['returning_date'] = $recentLeave['returning_date'];
							$data['unavailable_officers'][] = $data['officers'][$i];	
							unset($data['officers'][$i]);
						}					
					}
					
				}
			}
			// For the officer schedule... It's confusing
			$data['schedule_officers'] = $this->scheduler_model->get_officers_schedule($data['selected_location'],
					 $data['last_shift']);
					 
			if (empty($data['schedule_officers'])) {
				foreach ($data['officers'] as $officer) {
					$officerID = $officer['officer_id'];
					$leaveStatus = $this->scheduler_model->get_leave_status($officerID);
					$this->scheduler_model->create_officer_schedule($officerID, $data['selected_location'],
						 $data['selected_shift'], date('Y-m-d', strtotime("this Sunday")));
				}
				$data['schedule_officers'] = $this->scheduler_model->get_officers_schedule($data['selected_location'],
					 $data['last_shift']);
			}
			
			// Gives the officers names
			for($k = 0; $k < count($data['schedule_officers']); $k++)
				$data['schedule_officers'][$k]['officer_name'] = $this->scheduler_model->get_officer_name(
						$data['schedule_officers'][$k]['officer_id']);
		}
		$data['display_s'] = (empty($data['officers'])) ? $data['display_s'] : "";
		$data['display_l'] = (empty($data['unavailable_officers'])) ? $data['display_l'] : "";
		$data['workdays'] = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
		$this->load->view('templates/header', $data);
	    $this->load->view('templates/nav');
	    $this->load->view('scheduler/index');
	    $this->load->view('templates/footer');
	}
	
	public function schedule() {
		redirect('scheduler');
	}
	
	public function add_schedule() {
		$this->load->helper('form');
	}
	
}