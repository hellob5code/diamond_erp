<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Warehouses extends MY_Controller {

	protected $limit = 25;
	
	public function __construct()
	{
		parent::__construct();
		$this->load->model('warehouses/warehouses_model','whr');
	}
    
    /**
     * Retreives whole list of entries
     * @param  string  $sort_by    default sorting filed
     * @param  string  $sort_order default sort order
     * @param  integer $offset
     */
	public function index($sort_by = 'wname', $sort_order = 'asc', $offset = 0)
	{	
		//Heading
		$this->data['heading'] = 'Магацини';

		$this->data['columns'] = array (	
			'wname'=>'Назив'
		);

		//Validates Sort by and Sort Order
		$sort_order = ($sort_order == 'desc') ? 'desc' : 'asc';
		$sort_by_array = array('wname');
		$sort_by = (in_array($sort_by, $sort_by_array)) ? $sort_by : 'wname';

		$this->data['results'] = $this->whr->limit($this->limit, $offset)
									->order_by($sort_by,$sort_order)->get_all();

		$this->data['num_rows'] = $this->whr->limit($this->limit, $offset)
									->order_by($sort_by,$sort_order)->count_all();

		//Pagination
		$this->data['pagination'] = 
		paginate("warehouses/index/{$sort_by}/{$sort_order}",
			$this->data['num_rows'],$this->limit,5); 
		
		$this->data['pagination'] = $this->pagination->create_links();

		$this->data['sort_by'] = $sort_by;
		$this->data['sort_order'] = $sort_order;
	}
	/**
	 * Creates new entry
	 * @return redirects with success/error message
	 */
	public function insert()
	{
		//Heading
		$this->data['heading'] = 'Нов на Магацин';

		//Defining Validation Rules
		$this->form_validation->set_rules('wname','warehouse name','trim|required');
		
		//Check if form has been submited
		if ($this->form_validation->run())
		{
			if($this->whr->insert($_POST))
				$this->utilities->flash('add','warehouses');
		}
	}
	/**
	 * Edits entry by passed primary_key
	 * @param  integer $id primary_key
	 * @return redirects with success/error message     
	 */
	public function edit($id)
	{
		//Heading
		$this->data['heading'] = 'Корекција на Магацин';

		$this->data['result'] = $this->whr->get($id);
		if(!$this->data['result'])
			$this->utilities->flash('void','warehouses');
	
		//Defining Validation Rules
		$this->form_validation->set_rules('wname','warehouse name','trim|required');
				
		if ($this->form_validation->run())
		{
			$this->whr->update($_POST['id'],array('wname'=>$_POST['wname']));
				$this->utilities->flash('update','warehouses');
		}
	}
	/**
	 * Deletes entry by passed primary_key
	 * @param  integer $id primary_key
	 * @return redirects with success/error message
	 */
	public function delete($id)
	{
		$this->data['result'] = $this->whr->get($id);
		if(!$this->data['result'])
			$this->utilities->flash('void','warehouses');

		if($this->whr->delete($this->data['result']->id))
			$this->utilities->flash('delete','warehouses');
		else
			$this->utilities->flash('error','warehouses');
	}
}