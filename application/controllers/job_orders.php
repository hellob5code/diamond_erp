<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Job_orders extends MY_Controller {

	protected $limit = 25;
	
	public function __construct()
	{
		parent::__construct();
		
		//Load Models
		$this->load->model('production/joborders_model','jo');
		$this->load->model('procurement/inventory_model','inv');
		$this->load->model('hr/task_model','tsk');
    }

	public function index($query_id = 0,$sort_by = 'dateofentry', $sort_order = 'desc', $offset = 0)
	{		
		//Page Title Segment and Heading
		$this->data['heading'] = 'Работни Налози';
		
		//Generate dropdown menu data for Filters
		$this->data['employees'] = $this->utilities->get_employees('variable','- Работник -');
		$this->data['tasks'] = $this->utilities->get_dropdown('id','taskname','exp_cd_tasks','- Работна Задача -');


		//Columns which can be sorted by
		$this->data['columns'] = [	
			'datedue'=>'Датум',
			'assigned_to'=>'Работник',
			'task_fk'=>'Работна Задача',
			'assigned_quantity'=>'Кол./Траење',
			'work_hours'=>'Раб.Часови',
			'shift'=>'Смена',
			'dateofentry'=>'Внес'
		];

		$this->input->load_query($query_id);
		
		$query_array = [
			'task_fk' => $this->input->get('task_fk'),
			'assigned_to' => $this->input->get('assigned_to'),
			'shift' => $this->input->get('shift')
		];
		
		//Validates Sort by and Sort Order
		$sort_order = ($sort_order == 'desc') ? 'desc' : 'asc';
		$sort_by_array = ['datedue','task_fk','assigned_to','assigned_quantity',
								'work_hours','shift','dateofentry'];
		$sort_by = (in_array($sort_by, $sort_by_array)) ? $sort_by : 'dateofentry';
		
		//Retreive data from Model
		$temp = $this->jo->select($query_array, $sort_by, $sort_order, $this->limit, $offset);
		
		//Results
		$this->data['results'] = $temp['results'];
		//Total Number of Rows in this Table
		$this->data['num_rows'] = $temp['num_rows'];
		
		$this->data['pagination'] = 
		uif::paginate("job_orders/index/{$query_id}/{$sort_by}/{$sort_order}",
			$this->data['num_rows'],$this->limit,6); 
				
		$this->data['sort_by'] = $sort_by;
		$this->data['sort_order'] = $sort_order;
		$this->data['query_id'] = $query_id;
	}
	
	public function search()
	{
		$query_array = array(
			'task_fk' => $this->input->post('task_fk'),
			'assigned_to' => $this->input->post('assigned_to'),
			'shift' => $this->input->post('shift')
		);	
		$query_id = $this->input->save_query($query_array);
		redirect("job_orders/index/$query_id");
	}

	public function insert()
	{		
		if($_POST)
		{
			if($job_order_id = $this->jo->insert($_POST))
			{
				/*
				 * Check if this task is production and has
				 * assigned BOM
				 */
				$production = $this->tsk->select_single($_POST['task_fk']);
				if($production->is_production AND !is_null($production->bom_fk))
				{
					$this->_inventory_use($job_order_id,$production->id,$_POST['assigned_quantity']);
				}
				
				$this->utilities->flash('add','job_orders');
			}
			/**
			 * @todo Check if insert failed AND there are no validation errors,
			 * then trwo 500 intenral error message with redirect
			 */
		}
		
		//Generate dropdown menu data
		$this->data['employees'] = $this->utilities->get_employees('variable','- Работник -');
				
		//Retreives the Last Inserted Job Order
		$this->data['last'] = $this->jo->get_last();

		//Heading
		$this->data['heading'] = 'Нов Работен Налог';
	}
	
	public function edit($id)
	{
		/*
		 * Retreives the record from the database, if
		 * does not exists, reports void error and redirects
		 */
		$this->data['job_order'] = $this->jo->select_single($id);

		if(!$this->data['job_order']) show_404();
		/*
		 * Prevents from editing locked record
		 */
		if($this->data['job_order']->locked) $this->utilities->flash('deny','job_orders');
		
		if($_POST)
		{
			if($this->jo->update($_POST['id'],$_POST))
			{
				$found = $this->inv->get_many_by(['job_order_fk'=>$_POST['id']]);
				if(!empty($found))
				{
					$ids = [];
					foreach ($found as $row) 
					{
						array_push($ids,$row->id);
					}
					$this->inv->delete_many($ids);
				}
				
				/*
				 * Check if this task is production and has
				 * assigned BOM
				 */
				$production = $this->tsk->get($_POST['task_fk']);

				if($production->is_production AND !is_null($production->bom_fk))
				{
					$this->_inventory_use($_POST['id'],$production->id,$_POST['assigned_quantity']);
				}

				$this->utilities->flash('update','job_orders');
			}
		}
		
		//Generate dropdown menu data
		$this->data['employees'] = $this->utilities->get_employees('variable');
		$this->data['tasks'] = $this->utilities->get_dropdown('id','taskname','exp_cd_tasks','- Работна Задача -');
		
		//Heading
		$this->data['heading'] = 'Корекција на Работен Налог';
	}

	/**
	 * Completes and prepares Job Orders for Payroll Calculation
	 */
	public function ajxComplete()
	{	
		if($this->jo->completeJobOrders(json_decode($_POST['ids'])))
			echo 1;
		exit;	
	}
	
	public function view($id)
	{
		//Heading
		$this->data['heading'] = 'Работен Налог';

		//Retreives data from MASTER Model //Gets the ID of the selected entry from the URL
		$this->data['master'] = $this->jo->select_single($id);

		if(!$this->data['master']) $this->utilities->flash('void','job_orders');

		$this->data['details'] = $this->inv->select_use('job_order_fk',$this->data['master']->id);		
	}

	public function report()
	{
		$this->data['submited'] = 0;
		
		if($_POST)
		{
			//Defining Validation Rules
			$this->form_validation->set_rules('datefrom','date from','trim|required');
			$this->form_validation->set_rules('dateto','date to','trim|required');
			$this->form_validation->set_rules('shift[]','shift','trim');
			
			if ($this->form_validation->run())
			{
				//Log the report
				$this->input->log_report($_POST);

				$this->data['results'] = $this->jo->report($_POST);
				$this->data['datefrom'] = $_POST['datefrom'];
				$this->data['dateto'] = $_POST['dateto'];
				$this->data['submited'] = 1;

				if(empty($this->data['results']))
					$this->data['submited'] = 0;
			}		
			
			/*
			$data = '';
			$categories = '';

			
			foreach ($results as $row)
			{
				$data .= "'$row->sum',";
				$categories .= "'$row->taskname',";
			}

			
			$this->data['json_data'] = substr($data,0,-1);
			$this->data['categories'] = substr($categories,0,-1);
			*/
			/*
			$gdata = array();
			foreach($results as $one)
			{
				array_push($gdata, $one->avg);
			}
			
			$graph = $this->jpgraph->linechart($gdata, 'This is a Line Chart');
			
			$graph_temp_directory = 'temp';  // in the webroot (add directory to .htaccess exclude)
	        $graph_file_name = rand(1,3).time().'.jpg';    
	        
	        $graph_file_location = $graph_temp_directory . '/' . $graph_file_name;
	                
	        $graph->Stroke(base_url().$graph_file_location);  // create the graph and write to file
	        
	        $this->data['graph'] = $graph_file_location;
			*/
			//Runs model public functions and retreives results
			
			//Passes the results
			
		}
		
		//Dropdown Menus
		$this->data['employees'] = $this->utilities->get_employees('variable','- Работник -');
		$this->data['tasks'] = $this->utilities->get_dropdown('id','taskname','exp_cd_tasks','- Работна Задача -');
		
		//Heading
		$this->data['heading'] = 'Рипорт на Производство';
	}
	
	public function report_pdf()
	{
		if(!$_POST) show_404();

		$this->load->helper('dompdf');
		$this->load->helper('file');
		
		$report_data['results'] = $this->jo->report($_POST);
		$report_data['datefrom'] = $_POST['datefrom'];
		$report_data['dateto'] = $_POST['dateto'];
		
		$this->load->model('hr/task_model','tsk');
		$this->load->model('hr/employees_model','emp');

		if(strlen($_POST['assigned_to']))
		{
			$report_data['employee'] = $this->emp->select_single($_POST['assigned_to']);	
		}
		if(strlen($_POST['task_fk']))
		{
			$report_data['task'] = $this->tsk->select_single($_POST['task_fk']);	
		}
		// if(strlen($_POST['shift']))
		// {
		// 	$report_data['shift'] = $_POST['shift'];	
		// }
		
		if($report_data['results'])
		{
			$html = $this->load->view('job_orders/report_pdf',$report_data, true);
		
			$file_name = random_string();
			
			header("Content-type: application/pdf");
			header("Content-Disposition: attachment; filename='{$file_name}'");
			
			mkpdf($html,$file_name);
		}
		exit;
	}
	
	public function delete($id)
	{
		$this->data['job_order'] = $this->jo->get($id);

		if(!$this->data['job_order']) $this->utilities->flash('void','job_orders');
		/*
		 * Prevents from deleting locked Job Orders
		 */
		if($this->data['job_order']->locked) $this->utilities->flash('deny','job_orders');
			
		if($this->jo->delete($id))
			$this->utilities->flash('delete','job_orders');
		else
			$this->utilities->flash('error','job_orders');
	}

	private function _inventory_use($job_order_id,$task_id,$quantity)
	{
		//Loading Models
		$this->load->model('hr/task_model','tsk');
		$this->load->model('production/bomdetails_model','bomd');
		$this->load->model('production/boms_model','bom');
		
		if(!$bom_id = $this->tsk->find_bom($task_id))
			return false;

		$results = $this->inv->has_deducation($job_order_id);
		
		if($results)
		{
			foreach ($results as $row )
				$this->inv->delete($row['id']);
		}

		/*
		 * Retreive all components for specific Bill of Materials (bom_id) 
		 */
		$bom_components = $this->bomd->select_by_bom_id($bom_id);
		/**
		 * MOVE THE FOLLOWING PART TO BOM-DETAILS MODEL
		 */
		foreach ($bom_components as $component)
		{
			$options = [
				'prodname_fk'=> $component->prodname_fk,
				'job_order_fk'=> $job_order_id,
				'quantity' => (($component->quantity * $quantity) * -1),
				'received_by' => $this->session->userdata('userid'),
				'type' => '0',
				'is_use' => 1
			];

			unset($_POST);
				
			$this->inv->insert($options);
		}		
	}
}