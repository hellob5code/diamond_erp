<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *   Diamond ERP - Complete ERP for SMBs
 *   
 *   @author Marko Aleksic <psybaron@gmail.com>
 *   @copyright Copyright (C) 2013  Marko Aleksic
 *   @link https://github.com/psybaron/diamond_erp
 *   @license http://opensource.org/licenses/GPL-3.0
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>
 */

class Employees extends MY_Controller {
	
	protected $limit = 25;
	
	public function __construct()
	{
		parent::__construct();
		
		//Load Models
		$this->load->model('acl/roles_model','rol');
		$this->load->model('hr/employees_model','emp');	
		$this->load->model('hr/emp_tasks_model','empt');		
		$this->load->model('hr/positions_model','pos');
		$this->load->model('hr/payroll_model','pr');		
		$this->load->model('hr/task_model','tsk');
		$this->load->model('regional/postalcode_model','pcode');	
		$this->load->model('regional/location_model','loc');	
	}
	
	public function index($query_id = 0,$sort_by = 'employee', $sort_order = 'asc', $offset = 0)
	{	
		//Heading
		$this->data['heading'] = uif::lng('app.emp_emps');
		
		// Generating dropdown menu's
		$this->data['possitions'] = $this->pos->dropdown('id', 'position');	
		$this->data['roles']      = $this->rol->dropdown('id', 'name');
		
		//Columns which can be sorted by
		$this->data['columns'] = [	
			'employee'        => uif::lng('attr.employee'),
			'comp_mobile'     => uif::lng('attr.company_mobile'),
			'position'        => uif::lng('attr.position'),
			'department'      => uif::lng('attr.department'),
			'fixed_wage_only' => uif::lng('attr.fixed'),
			'is_manager'      => uif::lng('attr.manager'),
			'is_distributer'  => uif::lng('attr.distributor'),
			'fixed_wage'      => uif::lng('attr.net'),
			'status'          => uif::lng('attr.status'),
		];

		$this->input->load_query($query_id);
		
		$query_array = [
			'poss_fk' => $this->input->get('poss_fk'),
			'role_id' => $this->input->get('role_id')
		];
		
		//Validates Sort by and Sort Order
		$sort_order = ($sort_order == 'desc') ? 'desc' : 'asc';
		$sort_by_array = ['employee','comp_mobile','position','department','fixed_wage_only',
								'is_manager','is_distributer','fixed_wage','comp_mobile_sub','status'];
		$sort_by = (in_array($sort_by, $sort_by_array)) ? $sort_by : 'employee';

		//Retreive data from Model
		$temp = $this->emp->select($query_array, $sort_by, $sort_order, $this->limit, $offset);
		
		//Results
		$this->data['results'] = $temp['results'];
		//Total Number of Rows in this Table
		$this->data['num_rows'] = $temp['num_rows'];
		
		//Pagination
		$this->data['pagination'] = 
		paginate("employees/index/{$query_id}/{$sort_by}/{$sort_order}",
			$this->data['num_rows'],$this->limit,6);
		
		$this->data['sort_by']    = $sort_by;
		$this->data['sort_order'] = $sort_order;
		$this->data['query_id']   = $query_id;
	}
	
	public function search()
	{
		$query_array = [
			'poss_fk' => $this->input->post('poss_fk'),
			'role_id' => $this->input->post('role_id')
		];	
		$query_id = $this->input->save_query($query_array);
		redirect("employees/index/{$query_id}");
	}
	
	public function insert()
	{
		//Defining Validation Rules
		$this->form_validation->set_rules('fname',uif::lng('attr.first_name'),'trim|required');
		$this->form_validation->set_rules('lname',uif::lng('attr.last_name'),'trim|required');
		$this->form_validation->set_rules('dateofbirth',uif::lng('attr.dob'),'trim|required');
		$this->form_validation->set_rules('postcode_fk',uif::lng('attr.city'),'trim|required');
		$this->form_validation->set_rules('poss_fk',uif::lng('attr.position'),'trim|required');
		$this->form_validation->set_rules('code',uif::lng('attr.code'),'trim|max_lenth[5]');
		$this->form_validation->set_rules('ssn',uif::lng('attr.ssn'),'trim|required|exact_length[13]|numeric');
		$this->form_validation->set_rules('username',uif::lng('common.username'),'min_length[5]|max_lenth[15]');
		$this->form_validation->set_rules('password',uif::lng('common.password'),'min_length[6]');
		$this->form_validation->set_rules('email',uif::lng('attr.email'),'trim|valid_email');
		$this->form_validation->set_rules('phone',uif::lng('attr.phone'),'trim|numeric');
		$this->form_validation->set_rules('mobile',uif::lng('attr.mobile'),'trim|numeric');
		$this->form_validation->set_rules('comp_mobile',uif::lng('attr.company_mobile'),'trim|numeric');
		$this->form_validation->set_rules('comp_mobile_sub',uif::lng('attr.subvention'),'trim|numeric');
		$this->form_validation->set_rules('bank',uif::lng('attr.bank'),'trim');
		$this->form_validation->set_rules('account_no',uif::lng('attr.account_number'),'trim|numeric');
		$this->form_validation->set_rules('fixed_wage',uif::lng('attr.fixed'),'trim|numeric');
		$this->form_validation->set_rules('social_cont',uif::lng('attr.social_contribution'),'trim|numeric');
		$this->form_validation->set_rules('address',uif::lng('attr.address'),'trim');
		$this->form_validation->set_rules('note',uif::lng('attr.note'),'trim');
		$this->form_validation->set_rules('name_fk',uif::lng('attr.role'),'trim');
		$this->form_validation->set_rules('start_date',uif::lng('attr.date_start'),'trim');
		$this->form_validation->set_rules('stop_date',uif::lng('attr.date_end'),'trim');
		
		//Check if form has passed validation
		if ($this->form_validation->run())
		{
			//Successful insertion
			if($this->emp->insert($_POST))
				air::flash('add','employees');
			else
				air::flash('error','employees');
		}
		
		// Generating dropdown menu's
		$this->data['postalcodes'] = $this->pcode->generateDropdown();	
		$this->data['managers']    = $this->emp->generateDropdown(['is_manager'=>1]);
		$this->data['positions']   = $this->pos->dropdown('id', 'position');	
		$this->data['roles']       = $this->rol->dropdown('id', 'name');
		$this->data['locations']   = $this->loc->dropdown('id', 'name');	

		//Heading
		$this->data['heading'] = uif::lng('app.emp_new');
	}
	
	public function edit($id)
	{
		//Retreives ONE product from the database
		$this->data['employee'] = $this->emp->select_single($id);
		
		//If there is nothing, show  404 (void)
		if(!$this->data['employee'])  air::flash('void');
		
		//If Submit has been posted (EDIT form Submitted), runs the code below
		if($_POST)
		{
			$this->form_validation->set_rules('fname',uif::lng('attr.first_name'),'trim|required');
			$this->form_validation->set_rules('lname',uif::lng('attr.last_name'),'trim|required');
			$this->form_validation->set_rules('dateofbirth',uif::lng('attr.dob'),'trim|required');
			$this->form_validation->set_rules('postcode_fk',uif::lng('attr.city'),'trim|required');
			$this->form_validation->set_rules('poss_fk',uif::lng('attr.position'),'trim|required');
			$this->form_validation->set_rules('code',uif::lng('attr.code'),'trim|max_lenth[5]');
			$this->form_validation->set_rules('ssn',uif::lng('attr.ssn'),'trim|required|exact_length[13]|numeric');
			$this->form_validation->set_rules('username',uif::lng('common.username'),'min_length[5]|max_lenth[15]');
			$this->form_validation->set_rules('password',uif::lng('common.password'),'min_length[6]');
			$this->form_validation->set_rules('email',uif::lng('attr.email'),'trim|valid_email');
			$this->form_validation->set_rules('phone',uif::lng('attr.phone'),'trim|numeric');
			$this->form_validation->set_rules('mobile',uif::lng('attr.mobile'),'trim|numeric');
			$this->form_validation->set_rules('comp_mobile',uif::lng('attr.company_mobile'),'trim|numeric');
			$this->form_validation->set_rules('comp_mobile_sub',uif::lng('attr.subvention'),'trim|numeric');
			$this->form_validation->set_rules('bank',uif::lng('attr.bank'),'trim');
			$this->form_validation->set_rules('account_no',uif::lng('attr.account_number'),'trim|numeric');
			$this->form_validation->set_rules('fixed_wage',uif::lng('attr.fixed'),'trim|numeric');
			$this->form_validation->set_rules('social_cont',uif::lng('attr.social_contribution'),'trim|numeric');
			$this->form_validation->set_rules('address',uif::lng('attr.address'),'trim');
			$this->form_validation->set_rules('note',uif::lng('attr.note'),'trim');
			$this->form_validation->set_rules('name_fk',uif::lng('attr.role'),'trim');
			$this->form_validation->set_rules('start_date',uif::lng('attr.date_start'),'trim');
			$this->form_validation->set_rules('stop_date',uif::lng('attr.date_end'),'trim');
			
			//Check if updated form has passed validation
			if ($this->form_validation->run())
			{
				if($this->emp->update($_POST['id'],$_POST))
					air::flash('update','employees');
				else
					air::flash('error','employees');	
			}
			
		}

		// Generating dropdown menu's
		$this->data['postalcodes'] = $this->pcode->generateDropdown();	
		$this->data['managers']    = $this->emp->generateDropdown(['is_manager'=>1]);
		$this->data['positions']   = $this->pos->dropdown('id', 'position');	
		$this->data['roles']       = $this->rol->dropdown('id', 'name');
		$this->data['locations']   = $this->loc->dropdown('id', 'name');

		//Heading
		$this->data['heading'] = uif::lng('app.emp_edit');
	}
	
	public function view($id)
	{
		$this->data['master'] = $this->emp->select_single($id);

		if(!$this->data['master']) air::flash('void');	

		//Heading
		$this->data['heading'] = uif::lng('app.emp_emp');
		
		//Retreives data from MASTER Model
		$this->data['assigned_tasks'] = $this->empt->select($id);
		$this->data['tasks']          = $this->tsk->dropdown('id','taskname');
		$this->data['payrolls'] = $this->pr->limit(6)->order_by('date_from','desc')
			->get_many_by(['employee_fk'=>$id,'status'=>'active']);
	}
	
	public function delete($id)
	{
		//Only Administrator can delete employees
		if(!$this->session->userdata('admin')) air::flash('deny');

		$employee = $this->emp->get($id);

		if(!$employee) air::flash('void');

		//Administrators cannot be deleted
		if($employee->is_admin) air::flash('deny');
			
		if($this->emp->delete($id))
			air::flash('delete','employees');
		else
			air::flash('error','employees');			
	}

	public function assignTask()
	{
		if(!$_POST) show_404();

		$this->form_validation->set_rules('employee_fk',uif::lng('attr.employee'),'trim|required');
		$this->form_validation->set_rules('task_fk',uif::lng('attr.task'),'trim|required');

		if ($this->form_validation->run())
		{
			if($this->empt->insert($_POST))
				air::flash('add',$_SERVER['HTTP_REFERER']);

		}
		air::flash('error',$_SERVER['HTTP_REFERER']);
	}

	public function unassignTask($id)
	{
		if($this->empt->delete($id))
			air::flash('delete',$_SERVER['HTTP_REFERER']);
		else
			air::flash('error',$_SERVER['HTTP_REFERER']);
	}

	public function ajxGetTasks()
	{	
		if(!$_GET['employee']) show_404();
		header('Content-Type: application/json',true); 
		echo $this->empt->dropdown($_GET['employee']);
		exit;
	}
}