<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Boms extends MY_Controller {

	protected $limit = 25;
	
	public function __construct()
	{
		parent::__construct();
		
		//Load Models
		$this->load->model('production/boms_model','bom');
		$this->load->model('production/bomdetails_model','bomd');
	}
	
	public function index($sort_by = 'name', $sort_order = 'asc', $offset = 0)
	{			
		//Heading
		$this->data['heading'] = 'Нормативи';
		
		//Columns which can be sorted by
		$this->data['columns'] = array (	
			'name'=>'Назив',
			'quantity'=>'Количина',
			'prodname'=>'Производ',
			'conversion' => 'Конверзија'
		);
		
		//Validates Sort by and Sort Order
		$sort_order = ($sort_order == 'desc') ? 'desc' : 'asc';
		$sort_by_array = array('name','quantity','prodname','conversion');
		$sort_by = (in_array($sort_by, $sort_by_array)) ? $sort_by : 'name';
		
		//Retreive data from Model
		$temp = $this->bom->select($sort_by, $sort_order, $this->limit, $offset);
		
		//Results
		$this->data['results'] = $temp['results'];
		//Total Number of Rows in this Table
		$this->data['num_rows'] = $temp['num_rows'];
		
		//Pagination
		$config['base_url'] = site_url("boms/index/$sort_by/$sort_order");
		$config['total_rows'] = $this->data['num_rows'];
		$config['per_page'] = $this->limit;
		$config['uri_segment'] = 5;
		$config['num_links'] = 3;
		$config['first_link'] = 'Прва';
		$config['last_link'] = 'Последна';
			$this->pagination->initialize($config);
		
		$this->data['pagination'] = $this->pagination->create_links(); 
				
		$this->data['sort_by'] = $sort_by;
		$this->data['sort_order'] = $sort_order;
	}
	
	public function insert()
	{
		//Defining Validation Rules
		$this->form_validation->set_rules('name','name','trim|required');
		$this->form_validation->set_rules('quantity','quantity','trim|required');
		$this->form_validation->set_rules('prodname_fk','product','trim');
		$this->form_validation->set_rules('uname_fk','uom','trim|required');
		$this->form_validation->set_rules('conversion','conversion','trim');
		$this->form_validation->set_rules('description','description','trim');
		
		//Check if form has been submited
		if ($this->form_validation->run())
		{
			$id = $this->bom->insert($_POST);
			
			if($id)
				$this->utilities->flash('add',"boms/view/{$id}");
			else
				$this->utilities->flash('error','boms');
		}

		//Heading
		$this->data['heading'] = 'Внес на Норматив';
		
		$this->data['uoms'] = $this->utilities->get_dropdown('id', 'uname','exp_cd_uom');
	}
	
	public function edit($id)
	{
		//Retreives data from MASTER Model
		$this->data['master'] = $this->bom->select_single($id);
		if(!$this->data['master']) 
			$this->utilities->flash('void','boms');

		//Retreives data from DETAIL Model
		$this->data['details'] = $this->bomd->select_by_bom_id($id);
		
		
		if($_POST)
		{
			//Defining Validation Rules
			$this->form_validation->set_rules('prodname_fk','product','trim|required');
			$this->form_validation->set_rules('quantity','quantity','trim|required');
			$this->form_validation->set_rules('description','description','trim');
			
			
			//Check if updated form has passed validation
			if ($this->form_validation->run())
			{
				if($this->bom->update($id,$_POST))
					$this->utilities->flash('add','boms');
				else
					$this->utilities->flash('error','boms');
			}
		}
		
		//Heading
		$this->data['heading'] = "Корекција на Норматив";
		$this->data['uoms'] = $this->utilities->get_dropdown('id', 'uname','exp_cd_uom');
	}
	
	//AJAX - Adds New Product in Bom Details
	public function addProduct()
	{
		$this->form_validation->set_rules('bom_fk','bom fk','trim|required');
		$this->form_validation->set_rules('prodname_fk','product','trim|required');
		$this->form_validation->set_rules('quantity','quantity','trim|required');

		if ($this->form_validation->run())
		{
			if($this->bomd->insert($_POST))
				$this->utilities->flash('add',"boms/view/".$_POST['bom_fk']);
		}

		$this->utilities->flash('error',"boms/view/".$_POST['bom_fk']);
	}

	public function removeProduct($id)
	{
		if(!$id) show_404();

		if($this->bomd->delete($id))
			$this->utilities->flash('delete',$_SERVER['HTTP_REFERER']);

		$this->utilities->flash('error',$_SERVER['HTTP_REFERER']);
	}
	
	//AJAX - Edits the Quantity of Products from a Bom
	public function ajxEditQty()
	{
		if(!in_array($_POST['name'],['quantity']))
		{
			$this->output->set_status_header(400);
			exit;
		}

		if(!$this->bomd->update($_POST['pk'],[$_POST['name']=>$_POST['value']]))
			$this->output->set_status_header(500);	

		exit;
	}

	public function view($id = false)
	{
		//Retreives data from MASTER Model
		$this->data['master'] = $this->bom->select_single($id);
		if(!$this->data['master'])
			$this->utilities->flash('void','boms');

		//Retreives data from DETAIL Model
		$this->data['details'] = $this->bomd->select_by_bom_id($id);

		//Heading
		$this->data['heading'] = 'Норматив';
	}
	
	public function delete($id = false)
	{
		if(!$this->bom->select_single($id))
			$this->utilities->flash('void','boms');
		
		if($this->bom->delete($id))
			$this->utilities->flash('delete','boms');
		else
			$this->utilities->flash('error','boms');
	}
}