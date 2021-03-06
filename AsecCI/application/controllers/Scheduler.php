<?php
require 'Cso.php';
class Scheduler extends Cso {
	
	// Prevents other user types from coming to this page
	protected function protectPage() {
		return ($this->session->userdata('home') === 'scheduler');
	}
	
	public function set_data($page='Schedule') {
		$data = parent::set_data($page);
		$data['functions'] = ['Schedule', 'Created Schedules', 'Alter Schedule', 'manage account'];
		return $data;
	}
	
	public function index() {
		$data = $this->set_data();	
		$this->load->helper('form');
		$data['locations'] = $this->{$this->session->userdata('model')}->get_locations();
		$data['shifts'] = $this->{$this->session->userdata('model')}->get_shifts();
		$data['officers'] = [];
		$data['unavailable_officers'] = [];
		$data['schedule_officers'] = [];
		$data['display_s'] = "None";
		$data['display_l'] = "None";
		$data['disabled'] = '';
		$data['color_class'] = '';
		$data['status'] = '';
		// Rotating algorithm
		$shifts = ["Morning"=>"Afternoon", "Afternoon"=>"Night", "Night"=>"Morning"];
		$data['workdays'] = ['None', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
		
		// Sets officer's off days		
		if ($this->input->post('set-schedule')) {
			$status = $this->{$this->session->userdata('model')}->get_schedule_status(
				$this->session->userdata('location'), $this->session->userdata('selected_shift'), 
				$data['weekStart']);
		
			if ($status['approved'] == 1) // Has already been approved
				redirect($this->session->userdata('home'));
				
			$off_days_1 = $this->input->post('off-day-1');
			$off_days_2 = $this->input->post('off-day-2');
			$officers = $this->{$this->session->userdata('model')}->get_officers_schedule(
				$this->session->userdata('location'), $this->session->userdata('last_shift'),
				$data['weekStart']);
			for ($i = 0; $i < count($officers); $i++) {
				$off_days_1[$i] = intval($off_days_1[$i]) % count($data['workdays']); 
				$off_days_2[$i] = intval($off_days_2[$i]) % count($data['workdays']); 
				$this->{$this->session->userdata('model')}->update_officer_schedule($officers[$i]['officer_id'],
					$data['workdays'][$off_days_1[$i]], $data['workdays'][$off_days_2[$i]]);
			}
		}
		
		// Checks if the form was submitted or the session variables were set
		$getSchedule = false; 
		if ($this->input->post('get-schedule')) {
			$data['location'] = $this->input->post('location');
			$data['selected_shift'] = $this->input->post('shift');
			$data['last_shift'] = $shifts[$data['selected_shift']];
			$getSchedule = true; 
		}
		else if ($this->session->userdata('location') && $this->session->userdata('last_shift')) {
			$data['location'] = $this->session->userdata('location');
			$data['selected_shift'] = $this->session->userdata('selected_shift');
			$data['last_shift'] = $this->session->userdata('last_shift');
			$getSchedule = true;
		}
		
		if ($getSchedule) {	
			// Session variables for some sort of security
			$this->session->set_userdata('location', $data['location']);
			$this->session->set_userdata('last_shift', $data['last_shift']);
			$this->session->set_userdata('selected_shift', $data['selected_shift']);
			
			$data['officers'] = $this->officers($data['location'], $data['selected_shift'],
				$data['last_shift'], "get_officers");
					
			$weekEnd = date('Y-m-d', strtotime($data['weekStart']." + 1 week - 1 day"));
			$num_officers = count($data['officers']); // To prevent everything from breaking
			if (!(empty($data['officers']))) {
				for ($i = 0; $i < $num_officers; $i++) {
					$officerID = $data['officers'][$i]['officer_id'];
					$data['officers'][$i]['officer_name'] = $this->{$this->session->userdata('model')}->get_officer_name(
						$officerID);
					$leaveStatus = $this->{$this->session->userdata('model')}->get_leave_status($officerID);
					$leaves = $this->{$this->session->userdata('model')}->get_officer_leaves($officerID);
					if ($leaveStatus['leave_status'] == 0) {
						for ($j = 0; $j < count($leaves); $j++) {
							// Checks if the officer should be marked as being on leave or as returned
							if ($this->isNotAvailable($leaves[$j], $data['weekStart'], $weekEnd)) {
								$this->{$this->session->userdata('model')}->set_leave_status($officerID, 1);
								$data['officers'][$i]['returning_date'] = $leaves[$j]['returning_date'];
								$data['unavailable_officers'][] = $data['officers'][$i];
								$this->{$this->session->userdata('model')}->delete_officer_schedule($officerID, $data['location'],
									 $data['selected_shift'], $data['weekStart']);
								unset($data['officers'][$i]);
								break;
							}
						}
					}					
					else { 
						for ($j = 0; $j < count($leaves); $j++) {
							// Checks if the officer should be marked as being on leave or as returned
							$available = $this->isNotAvailable($leaves[$j], $data['weekStart'], $weekEnd);
							if ($available) {
								$returningDate = $leaves[$j]['returning_date'];
								break;
							 }
						}		
						
						if (!$available) 
							$this->{$this->session->userdata('model')}->set_leave_status($officerID, 0);	
						else {
							$this->{$this->session->userdata('model')}->set_leave_status($officerID, 1);
							$data['officers'][$i]['returning_date'] = $returningDate;
							$data['unavailable_officers'][] = $data['officers'][$i];
							$this->{$this->session->userdata('model')}->delete_officer_schedule($officerID, $data['location'],
								 $data['selected_shift'], $data['weekStart']);
							unset($data['officers'][$i]);
							break;
						}
					}
				}
			} 
			// For the officer schedule... It's confusing
			$data['schedule_officers'] = $this->officers($data['location'], $data['selected_shift'],
				$data['last_shift']);
					 
			if (count($data['schedule_officers']) < count($data['officers'])) {
				foreach ($data['officers'] as $officer) {
					$officerID = $officer['officer_id'];
					$leaveStatus = $this->{$this->session->userdata('model')}->get_leave_status($officerID);
					if (empty($this->{$this->session->userdata('model')}->get_schedule($officerID, $data['weekStart'], 0)))
						$this->{$this->session->userdata('model')}->create_officer_schedule($officerID, $data['location'],
							 $data['selected_shift'], $data['weekStart']);
				}
				$data['schedule_officers'] = $this->officers($data['location'], $data['selected_shift'],
				$data['last_shift']);
			}
			
			// Set the status and disabled property of the set schedule form
			if (!empty($data['schedule_officers'])) {
				$status = $this->{$this->session->userdata('model')}->get_schedule_status(
					$data['location'], $data['selected_shift'], $data['weekStart']);
				if ($status['approved'] === null) {
					$data['color_class'] = 'blue-text';
					$data['status'] = 'Pending';
				}
				else if ($status['approved'] == 0) {
					$data['status'] = 'Not Approved';
					$data['color_class'] = 'red-text';
				}
				else if ($status['approved'] == 1) {
					$data['disabled'] = 'disabled';
					$data['status'] = 'Approved';
					$data['color_class'] = 'green-text';
				}
			}
			
			// Gives the officers names
			for($k = 0; $k < count($data['schedule_officers']); $k++)
				$data['schedule_officers'][$k]['officer_name'] = $this->{$this->session->userdata('model')}->get_officer_name(
						$data['schedule_officers'][$k]['officer_id']);
		}
		
		$data['display_s'] = (empty($data['officers'])) ? $data['display_s'] : "";
		$data['display_l'] = (empty($data['unavailable_officers'])) ? $data['display_l'] : "";
		
		$this->load->view('templates/header', $data);
	    $this->load->view('templates/nav');
	    $this->load->view($this->session->userdata('home').'/index');
	    $this->load->view('templates/footer');
	}
	
	public function schedule() {
		redirect($this->session->userdata('home'));
	}
	
	// Shows the schedule
	public function show_schedule() {
		$data = $this->set_data();
		$data['location'] = $this->session->userdata('location');
		$data['selected_shift'] = $this->session->userdata('selected_shift');

		$officers = $this->officers($data['location'], $data['selected_shift'],
			$this->session->userdata('last_shift'));
		
		$data['days'] = $this->get_working_days($officers);
		
		$this->load->view('templates/header', $data);
	    $this->load->view('templates/nav');
	    $this->load->view($this->session->userdata('home').'/schedule');
	    $this->load->view('templates/footer');
	}
	
	public function created_schedules() {
		$data = $this->set_data('Created Schedules');	
		$data['not_approved'] = $this->{$this->session->userdata('model')}->get_schedules(0, $data['weekStart']);
		$data['approved'] = $this->{$this->session->userdata('model')}->get_schedules(1, $data['weekStart']);
		$data['pending'] = $this->{$this->session->userdata('model')}->get_schedules(null, $data['weekStart']);
		
		// For showing or hiding the tables
		$data['display_n'] = '';
		$data['display_a'] = '';
		$data['display_p'] = '';
		
		if (empty($data['not_approved']))
			$data['display_n'] = 'None';
		if (empty($data['approved']))
			$data['display_a'] = 'None';
		if (empty($data['pending']))
			$data['display_p'] = 'None';
			
		$clicked = false;
		if ($this->input->post('fix-schedule')) {
			list($location, $shift) = explode('.', $this->input->post('fix-schedule'));			
			$redirect = $this->session->userdata('home');
			$clicked = true;
		} 
		
		else if ($this->input->post('show-schedule')) {
			list($location, $shift) = explode('.', $this->input->post('show-schedule'));
			$redirect = $this->session->userdata('home').'/show_schedule';
			$clicked = true;			
		}
		
		if ($clicked) {
			$shifts = ["Morning"=>"Afternoon", "Afternoon"=>"Night", "Night"=>"Morning"];
			$this->session->set_userdata('location', $location);
			$this->session->set_userdata('selected_shift', $shift);
			$this->session->set_userdata('last_shift',$shifts[$shift]);
			
			redirect($redirect);
		}
			
		$this->load->view('templates/header', $data);
	    $this->load->view('templates/nav');
	    $this->load->view($this->session->userdata('home').'/created_schedules');
	    $this->load->view('templates/footer');
	}
	
	public function alter_schedule() {
		$data = $this->set_data('Alter Schedule');	
		$data['weekStart'] = date('Y-m-d', strtotime($data['weekStart'].' - 1 week'));
		if ($this->session->userdata('selected_officer_id'))
			$data['officerID'] = $this->session->userdata('selected_officer_id');
		else	
			$data['officerID'] = null;
			
		if ($this->session->userdata('options'))
			$data['options'] = $this->session->userdata('options');
		else	
			$data['options'] = null;
			
		$data['schedules'] = [];
			
		$schedule = [];
		if ($this->input->post('get-schedule')) {
			$data['officerID'] = $this->input->post('officer-id');
			$schedule = $this->{$this->session->userdata('model')}->
				get_schedule($data['officerID'], $data['weekStart']);
			$this->session->set_userdata('selected_officer_id', $data['officerID']);
		}
		if (!empty($schedule)) {
			$schedule['officer_name'] = $this->{$this->session->userdata('model')}->
				get_officer_name($data['officerID']);
			$data['options'] = $this->{$this->session->userdata('model')}->
				get_approved_officers_schedule($schedule['location'], $schedule['shift'],
				 $data['weekStart'], true);
			foreach($data['options'] as $key => $value) {
				$data['options'][$key]['officer_name'] = $this->{$this->session->userdata('model')}->
				get_officer_name($data['options'][$key]['officer_id']);
			}
			$this->session->set_userdata('options', $data['options']);
			$this->session->set_userdata('location', $schedule['location']);
		}
		if ($this->input->post('get-other-officer')) {
			$otherOfficerID = $this->input->post('second-officer');
			$this->session->set_userdata('officer-two', $otherOfficerID);
			$data['schedules'][0] = $this->{$this->session->userdata('model')}->
				get_schedule($data['officerID'], $data['weekStart']);
			$data['schedules'][0]['officer_name'] = $this->{$this->session->userdata('model')}->
				get_officer_name($data['officerID']);
			$data['schedules'][1] = $this->{$this->session->userdata('model')}->
				get_schedule($otherOfficerID, $data['weekStart']);
			$data['schedules'][1]['officer_name'] = $this->{$this->session->userdata('model')}->
				get_officer_name($otherOfficerID);

		}
		if ($this->input->post('swap')) {
			$officerID = $this->session->userdata('selected_officer_id');
			$otherOfficerID = $this->session->userdata('officer-two');
			$location = $this->session->userdata('location');
			$this->{$this->session->userdata('model')}->swap_schedules(
				$officerID, $location, $data['id'], $data['weekStart']);
				// die();
			$this->{$this->session->userdata('model')}->swap_schedules(
				$otherOfficerID, $location, $officerID, $data['weekStart']);
			$this->{$this->session->userdata('model')}->swap_schedules(
				$data['id'], $location, $otherOfficerID, $data['weekStart']);
        	$this->session->set_flashdata('success','Successfully swapped the schedules.');
			$this->session->unset_userdata('selected_officer_id');
			$this->session->unset_userdata('officer-two');
			$this->session->unset_userdata('location');
			$this->session->unset_userdata('options');
			redirect($this->session->userdata('home').'/alter_schedule');
		}
		$this->load->view('templates/header', $data);
	    $this->load->view('templates/nav');
	    $this->load->view($this->session->userdata('home').'/alter_schedule');
	    $this->load->view('templates/footer');
	}
	
	// Checks if an officer is available
	protected function isNotAvailable($leave, $weekStart, $weekEnd) {
		return ($leave['approved_status'] == 1 &&
			   ((strtotime($weekEnd) >= strtotime($leave['proceeding_date']) &&
				 strtotime($leave['proceeding_date']) >= strtotime($weekStart)) ||
			    (strtotime($weekEnd) >= strtotime($leave['returning_date']) && 
			     strtotime($leave['returning_date']) >= strtotime($weekStart))  ||
				(strtotime($leave['returning_date']) >= strtotime($weekEnd) && 
			     strtotime($leave['proceeding_date']) <= strtotime($weekStart))));
	}	
	
	// Finds the right schedule
	protected function officers($location, $selected_shift, $last_shift, $method=null) {
		$weekStart = date('Y-m-d', strtotime("this Sunday"));	
		if ($weekStart === date('Y-m-d'))
			$weekStart = date('Y-m-d', strtotime("next Sunday"));
			
		$status = $this->{$this->session->userdata('model')}->get_schedule_status(
				$location, $selected_shift, $weekStart);
		
		if ($status['approved'] == 1)
			$officers = $this->{$this->session->userdata('model')}->get_approved_officers_schedule(
				$location, $selected_shift, $weekStart);
		else {
			if ($method != null)
				$officers = $this->{$this->session->userdata('model')}->{$method}(
					$location, $last_shift);
			else
				$officers = $this->{$this->session->userdata('model')}->get_officers_schedule(
					$location, $last_shift, $weekStart);
		}
				
		return $officers;
	}
}