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

class Payroll extends MY_Controller {
	
	protected $limit = 25;
	
	public function __construct()
	{
		parent::__construct();
		
		//Load Models
		$this->load->model('hr/payroll_model','pr');
		$this->load->model('hr/payroll_extra_model','pem');
		$this->load->model('hr/employees_model','emp');
		$this->load->model('hr/task_model','tsk');
		$this->load->model('production/joborders_model','jo');
		$this->load->model('orders/co_model','co');
		$this->load->model('orders/cod_model','cod');
	}
	
	public function index($query_id = 0,$sort_by = 'dateofentry', $sort_order = 'desc', $offset = 0)
	{	
		//Heading
		$this->data['heading'] = uif::lng('app.pay_pays');
		
		// Generating dropdown menu's
		$this->data['employees'] = $this->emp->generateDropdown();
		
		//Columns which can be sorted by
		$this->data['columns'] = [	
			'employee'    => uif::lng('attr.employee'),
			'date_from'   => uif::lng('attr.date_from'),
			'date_to'     => uif::lng('attr.date_to'),
			'acc_wage'    => uif::lng('attr.accumulated'),
			'bonuses'     => uif::lng('attr.bonuses'),
			'gross_wage'  => uif::lng('attr.gross'),
			'fixed_wage'  => uif::lng('attr.net'),
			'expenses'    => uif::lng('attr.expenses'),
			'paid_wage'   => uif::lng('attr.paid'),
			'dateofentry' => uif::lng('attr.doe')
		];

		$this->input->load_query($query_id);
		
		$query_array = [
			'employee_fk' => $this->input->get('employee_fk'),
			'for_month'   => $this->input->get('for_month')
		];
		
		//Validates Sort by and Sort Order
		$sort_order = ($sort_order == 'desc') ? 'desc' : 'asc';
		$sort_by_array = ['employee','for_month','date_from','date_to',
								'acc_wage','social_cont','comp_mobile_sub','bonuses',
								'gross_wage','fixed_wage','expenses','paid_wage','dateofentry'];
		$sort_by = (in_array($sort_by, $sort_by_array)) ? $sort_by : 'dateofentry';

		//Retreive data from Model
		$temp = $this->pr->select($query_array, $sort_by, $sort_order, $this->limit, $offset);
		
		//Results
		$this->data['results'] = $temp['results'];
		//Total Number of Rows in this Table
		$this->data['num_rows'] = $temp['num_rows'];
		
		//Pagination
		$this->data['pagination'] = 
		paginate("payroll/index/{$query_id}/{$sort_by}/{$sort_order}",
			$this->data['num_rows'],$this->limit,6);
		
		$this->data['sort_by']    = $sort_by;
		$this->data['sort_order'] = $sort_order;
		$this->data['query_id']   = $query_id;
	}
	
	public function search()
	{
		$query_array = array(
			'employee_fk' => $this->input->post('employee_fk')
		);	
		$query_id = $this->input->save_query($query_array);
		redirect("payroll/index/{$query_id}");
	}
	
	public function insert()
	{
		if(!$_POST) show_404();
		
		//Defining Validation Rules
		$this->form_validation->set_rules('employee_fk','','trim|required');
		$this->form_validation->set_rules('date_from','','trim|required');
		$this->form_validation->set_rules('date_to','','trim|required');
		
		$this->form_validation->set_rules('acc_wage','','trim|required|numeric');
		$this->form_validation->set_rules('social_cont','','trim|required|numeric');
		$this->form_validation->set_rules('comp_mobile_sub','','trim|required|numeric');
		$this->form_validation->set_rules('bonuses','','trim|required|numeric');
		$this->form_validation->set_rules('gross_wage','','trim|required|numeric');
		
		$this->form_validation->set_rules('fixed_wage','','trim|required|numeric');
		$this->form_validation->set_rules('expenses','','trim|required|numeric');
		$this->form_validation->set_rules('paid_wage','','trim|required|numeric');
		
		$this->form_validation->set_rules('fixed_wage_only','','trim|required|numeric');
		$this->form_validation->set_rules('is_distributer','','trim|required|numeric');
		
		//Check if form has been submitted
		if ($this->form_validation->run())
		{
			/*
			 * Creates a payroll entry and
			 * takes the ID inserted stored in the
			 * POST var. for further use
			 */
			$_POST['payroll_fk'] = $this->pr->insert($_POST);
			
			if($_POST['payroll_fk'])
			{
				air::flash('add');
				echo site_url('payroll/view/'.$_POST['payroll_fk']);
			}
		}
		exit;	
	}
	
	public function view($id)
	{		
		//Retreives data from MASTER Model - Payroll info
		$this->data['master'] = $this->pr->select_single($id);
		
		//If there is nothing, redirects
		if(!$this->data['master']) air::flash('void','payroll');

		if($this->data['master']->is_distributer == 1)
		{
			/*
			 * Retreives all the customer Orders which
			 * this distributor has distributed them,by
			 * payroll id
			 */
			$ids = $this->co->get_by_payroll($this->data['master']->id);
			if($ids)
				$this->data['distribution'] = $this->cod->total_distributed($ids);
		}

		//Shows the basis for the Wage calculation (Job Orders)
		$this->data['results'] = $this->jo->select_by_payroll($id);
								
		//Retrevies all Payroll extras where Is_expense = 0
		$this->data['extras_plus'] = $this->pem->select_by_payroll($id,0);
						
		//Retrevies all Payroll extras where Is_expense = 1
		$this->data['extras_minus'] = $this->pem->select_by_payroll($id,1);
		
		//Heading
		$this->data['heading'] = uif::lng('app.pay_pay');
	}
	
	public function payroll_pdf($id)
	{
		if(!$id) show_404();

		$this->load->helper('dompdf');
			
		//Retreives data from MASTER Model - Payroll info
		$this->data['master'] = $this->pr->select_single($id);
		
		//If there is nothing, redirects
		if(!$this->data['master']) air::flash('void','payroll');
		
		//Display
		$html = $this->load->view('payroll/payroll_pdf',$this->data, true);
		
		$file_name = $this->data['master']->employee_fk.'_'.$this->data['master']->date_from;

		header("Content-type: application/pdf");
		header("Content-Disposition: attachment; filename='{$file_name}'");
		
		mkpdf($html,$file_name);

		exit;
	}
	
	public function calculate()
	{	
		/*
		 * By default, function opens
		 * wage calculation before submission,
		 * hence, its not submitted
		 */
		$this->data['submitted'] = false;
		
		/*
		 * Calculates the payroll based on
		 * supplied POST variables:
		 * -Employee ID
		 * -Date From - Date To
		 * -Month
		 */
		if($_POST)
		{

			
			/*
			 *  Checks if there is any Payroll for this specific
			 *  employee, in specified dates
			 */
		
			//Defining Validation Rules
			$this->form_validation->set_rules('employee',uif::lng('attr.employee'),'trim|required');
			$this->form_validation->set_rules('datefrom',uif::lng('attr.date_from'),'trim|required');
			$this->form_validation->set_rules('dateto',uif::lng('attr.date_to'),'trim|required');
			
			
			if($this->form_validation->run())
			{
				/*
				 * Saves the passed calculation variables
				 * (employee,dates,month) back to view for use
				 */			
				$this->data['datefrom'] = $_POST['datefrom'];	
				$this->data['dateto']   = $_POST['dateto'];
				$this->data['submitted'] = true;
				
				$this->data['employee_master'] = $this->emp->get($_POST['employee']);

				if(!$this->data['employee_master']) show_404();

				$this->data['employee'] = $this->data['employee_master']->id;
				
				/*
				 * Looks for Job Orders assigned to
				 * this employee, if employee is not marked
				 * as Fixed Wage Only 
				 */
				$this->data['fixed_wage_only'] = $this->data['employee_master']->fixed_wage_only;
				$this->data['acc_wage'] = 0;
				$this->data['is_distributer'] = $this->data['employee_master']->is_distributer;
				
				/*
				 * Calcuation for Job Orders Employees
				 */
				if(!$this->data['fixed_wage_only'] AND !$this->data['is_distributer'])
				{
					$this->data['job_orders'] = $this->jo->payroll([
								'assigned_to' => $this->data['employee'],
								'datefrom'    => $this->data['datefrom'],
								'dateto'      => $this->data['dateto']
							]);

					/*
					 * Calculates total accumulated wage by
					 * going through Job Orders assigned,
					 * and calculating with standard rate per unit.
					 */
					foreach ($this->data['job_orders'] as $row)
					{
						$this->data['acc_wage'] += round($row->rate_per_unit * $row->final_quantity,2);
					}
				}
				
				/*
				 * Calcuation for Distributors
				 */
				if($this->data['is_distributer'])
				{
					/*
					 * Retreives all the customer Orders which
					 * this distributor has distributed them, and get
					 * the total distribution for that period
					 */
					$ids = $this->co->get_by_distributor(
						$this->data['employee'],$this->data['datefrom'],$this->data['dateto']);

					$this->data['distribution'] = $this->cod->total_distributed($ids);
					/*
					 * Calculates total accumulated wage by
					 * going through customer orders Orderder assigned,
					 * and calculating the total qty by commision per unit.
					 */
					if($this->data['distribution'])
					{
						foreach ($this->data['distribution'] as $row)
							$this->data['acc_wage'] += round($row->quantity * $row->commision,2);		
					}
				}
				
				/*
				 * If employee uses company mobile contract,
				 * and has subsidy by the company, it is retrevied
				 * here.
				 */
				$this->data['comp_mobile_sub'] = $this->data['employee_master']->comp_mobile_sub;
				
				/*
				 * If employee has fixed wage
				 * it is retrevied here.
				 */
				$this->data['fixed_wage'] = $this->data['employee_master']->fixed_wage;

				/*
				 * Finds the social contribution for employee
				 * from the payroll extras table, by looking
				 * at the months
				 */
				//$this->data['social_cont'] = $this->data['employee_master']->social_cont;
				$this->data['social_cont'] = $this->pem->get_soc_contr($this->data['employee'],
					['datefrom' => $this->data['datefrom'],'dateto' => $this->data['dateto']]);
				
				/*
				 * Retrevies all payroll bonuses
				 */
				$this->data['extras_plus'] = $this->pem->calc_extras(array(
								'employee_fk' => $this->data['employee'],
								'datefrom'    => $this->data['datefrom'],
								'dateto'      => $this->data['dateto']	
								),0);
					$this->data['bonuses'] = 0;
					foreach ($this->data['extras_plus'] as $item)
					{
						$this->data['bonuses'] += $item->amount;
					}

				/*
				 * GROSS WAGE CALCULATIONS
				 */
				$this->data['gross_wage'] = 0;
				/*
				 * To calculate gross wage, adds:
				 * 1.If employee on fixed wage only, add fixed wage,
				 *   else adds accumulated wage (based on Job Orders)
				 * 2.Social Contribution for that month
				 * 3.Company Mobile subsidy
				 * 4.Other Bonuses
				 */
				$this->data['gross_wage'] += $this->data['acc_wage'];
				
				/*
				 * If employee has fixed wage only, add fixed wage into
				 * gross calculation
				 */
				if($this->data['fixed_wage_only'])
				{
					$this->data['gross_wage'] += $this->data['fixed_wage'];
				}
					
				$this->data['gross_wage'] += $this->data['social_cont'];
				$this->data['gross_wage'] += $this->data['comp_mobile_sub'];
				$this->data['gross_wage'] += $this->data['bonuses'];
				
				/*
				 * Retrevies all payroll expenses
				 */
				$this->data['extras_minus'] = $this->pem->calc_extras([
								'employee_fk' => $this->data['employee'],
								'datefrom'    => $this->data['datefrom'],
								'dateto'      => $this->data['dateto']	
								],1);

					$this->data['expenses'] = 0;
					foreach ($this->data['extras_minus'] as $item)
					{
						$this->data['expenses'] -= $item->amount;
					}
					/*
					 * Expenses are expressed in negative numbers
					 */
					$this->data['expenses'] *= -1;
						
				/*
				 * GROSS EXPENSES CALCULATIONS
				 */
				$this->data['gross_exp'] = 0;
				/**
				 * If employee has fixed wage only, does not
				 * subtract it from the gross expenses 
				 */
				if(!$this->data['fixed_wage_only'])
				{
					$this->data['gross_exp'] -= $this->data['fixed_wage'];
				}
				$this->data['gross_exp'] += $this->data['expenses'];
				$this->data['gross_exp'] -= $this->data['social_cont'];
						
				/*
				 * NET WAGE CALCUATIONS
				 */
				$this->data['paid_wage'] = 0;
				$this->data['paid_wage'] += $this->data['gross_wage'];
				/**
				 * If employee has fixed wage only, reduce it from
				 * the wage that has to be paid additionally (paid_wage)
				 */
				if($this->data['fixed_wage_only'])
				{
					$this->data['paid_wage'] -= $this->data['fixed_wage'];
				}
				$this->data['paid_wage'] += $this->data['gross_exp'];		
			}		
		}

		//Heading
		$this->data['heading'] = uif::lng('app.pay_calculate');
		
		$this->data['employees'] = $this->emp->generateDropdown();
	}

	public function report()
	{
		$this->data['submitted'] = false;
		
		if($_POST)
		{
			//Defining Validation Rules
			$this->form_validation->set_rules('date_from',uif::lng('attr.date_from'),'trim|required');
			$this->form_validation->set_rules('date_to',uif::lng('attr.date_to'),'trim|required');
			
			if ($this->form_validation->run())
			{
				//Log the report
				$this->input->log_report($_POST);
				
				$this->data['results']   = $this->pr->report($_POST);
				$this->data['date_from'] = $_POST['date_from'];
				$this->data['date_to']   = $_POST['date_to'];
				$this->data['submitted']  = 1;

				if(empty($this->data['results']))
				{
					$this->data['submitted'] = true;
				}
			}			
		}
		
		//Dropdown Menus
		$this->data['employees'] = $this->emp->generateDropdown();
		
		//Heading
		$this->data['heading'] = uif::lng('app.pay_payroll_report');
	}

	public function report_pdf()
	{	
		if(!$_POST) show_404();

		$this->load->helper('dompdf');
		$this->load->helper('file');
		
		$report_data['results']   = $this->pr->report($_POST);
		$report_data['date_from'] = $_POST['date_from'];
		$report_data['date_to']   = $_POST['date_to'];
		

		if(strlen($_POST['employee_fk']))
		{
			$report_data['employee'] = $this->emp->select_single($_POST['employee_fk']);	
		}
		
		if($report_data['results'])
		{
			$html = $this->load->view('payroll/report_pdf',$report_data, true);
		
			$file_name = random_string();
			
			header("Content-type: application/pdf");
			header("Content-Disposition: attachment; filename='{$file_name}'");
			
			mkpdf($html,$file_name);
		}
		exit;
	}

	/**
	 * Deletes payroll entry.
	 * @param  integer $id 
	 * @return redirects with success or error message.
	 */
	public function delete($id)
	{
		if(!$this->pr->get($id)) air::flash('void','payroll');
			
		if($this->pr->delete($id))
			air::flash('delete','payroll');
		else
			air::flash('error','payroll');
	}
}