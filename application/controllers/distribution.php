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

class Distribution extends MY_Controller {
	
	protected $limit = 25;
	
	public function __construct()
	{
		parent::__construct();
			
		//Load Models
		$this->load->model('distribution/warehouse_model','whr');
		$this->load->model('products/products_model','prod');
		$this->load->model('hr/employees_model','emp');
	}
	
	public function index()
	{
		//Heading
		$this->data['heading'] = 'Магацин: Готови Производи';
		
		$this->data['results'] = $this->whr->levels();
	}
	
	public function insert_inbound()
	{
		/*
		 * Inserts entries into the
		 * finished Goods warehouse
		 * eg. Storing finished goods
		 */
		
		//Defining Validation Rules
		$this->form_validation->set_rules('prodname_fk','product','trim|required');
		$this->form_validation->set_rules('quantity','quantity','greater_than[0]|required');
		$this->form_validation->set_rules('ext_doc','external document','trim');
		$this->form_validation->set_rules('note','comments','trim');
		
		//Check if form has been submited
		if ($this->form_validation->run())
		{	
			$this->db->trans_start();

			//Inserts into databse and reports outcome
			$warehouse_id = $this->whr->insert($_POST);
			
			$this->_inventory_use($warehouse_id, $_POST['prodname_fk'], $_POST['quantity']);

			$this->db->trans_complete();

			if($this->db->trans_status() === false)
			{
				air::flash('error','distribution/inbounds');
			}
			else
			{
				air::flash('add','distribution/inbounds');
			}			
		}

		//Heading
		$this->data['heading'] = 'Влез во Магацин';
	}
	
	public function insert_outbound()
	{
		/*
		 * Inserts outgoings into
		 * finished Goods warehouse
		 * eg. Distributor reservations,direct sales,deduction etc.
		 */
		//Defining Validation Rules
		$this->form_validation->set_rules('prodname_fk','product','trim|required');
		$this->form_validation->set_rules('quantity','quantity','required');
		$this->form_validation->set_rules('distributor_fk','distributor','numeric|trim');
		$this->form_validation->set_rules('ext_doc','external document','trim');
		$this->form_validation->set_rules('note','comments','trim');
		
		//Check if form has been submited
		if ($this->form_validation->run())
		{	
			/*
			 * Sets is_out flag to 1, making
			 * this entry's quantity negative
			 * in the model side
			 */
			$_POST['is_out'] = 1;
			$_POST['is_return'] = null;
			
			//Inserts into databse and reports outcome
			if($this->whr->insert($_POST))
				air::flash('add','distribution/outbounds');
			else
				air::flash('error','distribution/outbounds');
		}

		//Heading
		$this->data['heading'] = 'Излез од Магацин';

		$this->data['distributors']  = $this->emp->generateDropdown(['is_distributer' => 1]);
	}
	
	public function insert_return()
	{
		/*
		 * Inserts entries into the
		 * finished Goods warehouse
		 * eg. Storing finished goods
		 */
		//Defining Validation Rules
		$this->form_validation->set_rules('prodname_fk','product','trim|required');
		$this->form_validation->set_rules('quantity','quantity','greater_than[0]|required');
		$this->form_validation->set_rules('ext_doc','external document','trim');
		$this->form_validation->set_rules('note','comments','trim');
		
		//Check if form has been submited
		if ($this->form_validation->run())
		{	
			$_POST['is_return'] = 1;
			$_POST['is_out'] = null;
			
			//Inserts into databse and reports outcome
			if($this->whr->insert($_POST))
				air::flash('add','distribution/returns');		
			else
				air::flash('error','distribution/returns');
		}

		//Heading
		$this->data['heading'] = 'Повраток во Магацин';
		
		$this->data['distributors']  = $this->emp->generateDropdown(['is_distributer' => 1]);
	}
	
	public function edit($page, $id)
	{
		/*
		 * Checks if valid page has been passed
		 */
		$pages = ['in','out','ret'];
		
		if(!in_array($page, $pages)) air::flash('void');

		$this->data['page'] = $page;
		/*
		 * Edits inbounds/outbound entry 
		 * into the warehouse, and then redirects
		 * if set, or defaults
		 */
		$this->data['result'] = $this->whr->select_single($id);
		if(!$this->data['result']) air::flash('void');
		
		if($page == 'out')
		{
			$this->data['heading'] = 'Корекција на Испратница';
			$this->data['distributors'] = $this->emp->generateDropdown(['is_distributer' => 1]);
			$redirect = 'outbounds';
			$this->form_validation->set_rules('quantity','quantity','required');
		}
		
		if($page == 'in')
		{
			$this->data['heading'] = 'Корекција на Приемница';
			$redirect = 'inbounds';
			$this->form_validation->set_rules('quantity','quantity','greater_than[0]|required');

		}
		
		if($page == 'ret')
		{
			$this->data['heading'] = 'Корекција на Повратница';
			$this->data['distributors'] = $this->emp->generateDropdown(['is_distributer' => 1]);
			$redirect = 'returns';
			$this->form_validation->set_rules('quantity','quantity','greater_than[0]|required');
		}
		
		//Defining Validation Rules
		$this->form_validation->set_rules('id','product','required');
		$this->form_validation->set_rules('prodname_fk','product','trim|required');
		$this->form_validation->set_rules('note','comments','trim');
		$this->form_validation->set_rules('ext_doc','external document','trim');
		
		//Check if form has been submited
		if ($this->form_validation->run())
		{	
			//Sets is_out flag
			if($page === 'out')
			{
				$_POST['is_out'] = 1;
			}

			$this->db->trans_start();

			//Inserts into databse
			$this->whr->update($_POST['id'],$_POST);

			/*
			 * If an inbound entry has been modified,
			 * and the qty has changed, recalculates all
			 * inventory deductions again for the new quantity
			 * according to the Bill of Materials
			 */
			if($page === 'in')
			{
				$this->_inventory_use($_POST['id'], $_POST['prodname_fk'], $_POST['quantity']);
			}

			$this->db->trans_complete();

			if($this->db->trans_status() === false)
			{
				air::flash('error','distribution/'.$redirect);
			}
			else
			{
				air::flash('update','distribution/'.$redirect);
			}	
		}	
	}
	
	public function digg($id, $offset = null)
	{
		//Heading
		$this->data['heading'] = 'Картица';

		/*
		 * If $id is not supplied, or does not exist
		 * redirect to this controllers index
		 */	
		$temp = $this->whr->select_item($id,$this->limit,$offset);
		if(!$temp) air::flash('void');
				
		//Get product name to be displayed in heading
		$this->data['product'] = $this->prod->select_single($id);
		
		//Results
		$this->data['results'] = $temp['results'];
		//Total Number of Rows in this Table
		$this->data['num_rows'] = $temp['num_rows'];

		//Pagination
		$this->data['pagination'] = 
		paginate("distribution/digg/{$id}",
			$this->data['num_rows'],$this->limit,4);
		
		$this->data['pagination'] = $this->pagination->create_links(); 
	}
	
	public function view($page = false, $id = false)
	{
		/*
		 * Retreives and displayes only
		 * SINGLE inbound/outbound entry as view
		 * 
		 * Type defines whether view to display
		 * Inbound or Outbound
		 */
		$this->data['master'] = $this->whr->select_single($id);

		if(!$this->data['master']) air::flash('void');
			
		$pages = ['in','out','ret'];

		if(!in_array($page, $pages)) air::flash('void');
		
		/*
		 * Pass the page in the view
		 */
		$this->data['page'] = $page;
		
		/*
		 * If this is an Inbound warehouse movement,
		 * details will be present, and contain all
		 * raw material deductions from Inventory
		 */			
		if($page == 'in')
		{
			$this->load->model('procurement/inventory_model','inv');
			$this->data['details'] = $this->inv->select_use('warehouse_fk',$this->data['master']->id);
			$this->data['heading'] = 'Приемница';
		}
		
		if($page == 'out')
			$this->data['heading'] = 'Испратница';
		
		if($page == 'ret')
			$this->data['heading'] = 'Повратница';		
	}
	
	public function inbounds($query_id = 0,$sort_by = 'dateofentry', $sort_order = 'desc', $offset = 0)
	{
		/*
		 * Retreives all inbound entires
		 * into the warehouse
		 */
			
		//Heading
		$this->data['heading'] = 'Влез во Магацин';
		
		$this->data['products'] = $this->prod->generateDropdown(['salable'=>1],true);
		
		//Columns which can be sorted by
		$this->data['columns'] = [	
			'dateoforigin' =>'Датум',
			'prodname_fk'  =>'Производ',
			'qty_current'  =>'Старо Салдо',
			'quantity'     =>'Влез',
			'dateofentry'  =>'Внес'
		];
		
		$this->input->load_query($query_id);
		
		$query_array = [
			'prodname_fk' => $this->input->get('prodname_fk')
		];

		//Validates Sort by and Sort Order
		$sort_order = ($sort_order == 'desc') ? 'desc' : 'asc';
		$sort_by_array = ['dateoforigin','prodname_fk','quantity','qty_current',
								'qty_new','dateofentry'];
		$sort_by = (in_array($sort_by, $sort_by_array)) ? $sort_by : 'dateofentry';
		
		//Retreive data from Model
		$temp = $this->whr->select_all_inbound($query_array, $sort_by, $sort_order, $this->limit, $offset);

		//Results
		$this->data['results'] = $temp['results'];
		//Total Number of Rows in this Table
		$this->data['num_rows'] = $temp['num_rows'];
		
		//Pagination
		$this->data['pagination'] = 
		paginate("distribution/inbounds/{$query_id}/{$sort_by}/{$sort_order}",
			$this->data['num_rows'],$this->limit,6);
				
		$this->data['sort_by'] = $sort_by;
		$this->data['sort_order'] = $sort_order;
		$this->data['query_id'] = $query_id;
	}
	
	public function in_search()
	{
		$query_array = array(
			'prodname_fk' => $this->input->post('prodname_fk')
		);	
		$query_id = $this->input->save_query($query_array);
		redirect("distribution/inbounds/{$query_id}");
	}
	
	public function outbounds($query_id = 0,$sort_by = 'dateofentry', $sort_order = 'desc', $offset = 0)
	{
		/*
		 * Retreives all outbound entires
		 * into the warehouse
		 */
		
		//Heading
		$this->data['heading'] = 'Излез од Магацин';
		
		$this->data['products'] = $this->prod->generateDropdown(['salable'=>1],true);
		$this->data['distributors']  = $this->emp->generateDropdown(['is_distributer' => 1]);
		
		//Columns which can be sorted by
		$this->data['columns'] = array (	
			'dateoforigin'   =>'Датум',
			'prodname_fk'    =>'Производ',
			'qty_current'    =>'Старо Салдо',
			'quantity'       =>'Излез',
			'distributor_fk' =>'Дистрибутер',
			'ext_doc'        =>'Документ',
			'dateofentry'    =>'Внес'
		);
		
		$this->input->load_query($query_id);
		
		$query_array = array(
			'prodname_fk'    => $this->input->get('prodname_fk'),
			'distributor_fk' => $this->input->get('distributor_fk')
		);

		//Validates Sort by and Sort Order
		$sort_order = ($sort_order == 'desc') ? 'desc' : 'asc';
		$sort_by_array = array('dateoforigin','prodname_fk','quantity','qty_current',
								'qty_new','distributor_fk','ext_doc','dateofentry');
		$sort_by = (in_array($sort_by, $sort_by_array)) ? $sort_by : 'dateofentry';
		
		//Retreive data from Model
		$temp = $this->whr->select_all_outbound($query_array, $sort_by, $sort_order, $this->limit, $offset);
		
		//Results
		$this->data['results'] = $temp['results'];
		//Total Number of Rows in this Table
		$this->data['num_rows'] = $temp['num_rows'];
		
		//Pagination
		$this->data['pagination'] = 
		paginate("distribution/outbounds/{$query_id}/{$sort_by}/{$sort_order}",
			$this->data['num_rows'],$this->limit,6);
		
		$this->data['pagination'] = $this->pagination->create_links(); 
				
		$this->data['sort_by'] = $sort_by;
		$this->data['sort_order'] = $sort_order;
		$this->data['query_id'] = $query_id;
	}
	
	public function out_search()
	{
		$query_array = array(
			'prodname_fk' => $this->input->post('prodname_fk'),
			'distributor_fk' => $this->input->post('distributor_fk')
		);	
		$query_id = $this->input->save_query($query_array);
		redirect("distribution/outbounds/{$query_id}");
	}
	
	public function returns($query_id = 0,$sort_by = 'dateofentry', $sort_order = 'desc', $offset = 0)
	{
		/*
		 * Retreives all returned entires
		 * into the warehouse
		 */
			
		//Heading
		$this->data['heading'] = 'Повраток во Магацин';
		
		$this->data['products'] = $this->prod->generateDropdown(['salable'=>1],true);
		$this->data['distributors']  = $this->emp->generateDropdown(['is_distributer' => 1]);
		
		//Columns which can be sorted by
		$this->data['columns'] = array (	
			'dateoforigin'   =>'Датум',
			'prodname_fk'    =>'Производ',
			'qty_current'    =>'Старо Салдо',
			'quantity'       =>'Влез',
			'distributor_fk' =>'Дистрибутер',
			'dateofentry'    =>'Внес'
		);
		
		$this->input->load_query($query_id);
		
		$query_array = array(
			'prodname_fk'    => $this->input->get('prodname_fk'),
			'distributor_fk' => $this->input->get('distributor_fk')
		);

		//Validates Sort by and Sort Order
		$sort_order = ($sort_order == 'desc') ? 'desc' : 'asc';
		$sort_by_array = array('dateoforigin','prodname_fk','quantity','qty_current',
								'qty_new','distributor_fk','dateofentry');
		$sort_by = (in_array($sort_by, $sort_by_array)) ? $sort_by : 'dateofentry';
		
		//Retreive data from Model
		$temp = $this->whr->select_all_returns($query_array, $sort_by, $sort_order, $this->limit, $offset);
		
		//Results
		$this->data['results'] = $temp['results'];
		//Total Number of Rows in this Table
		$this->data['num_rows'] = $temp['num_rows'];
		
		//Pagination
		$this->data['pagination'] = 
		paginate("distribution/returns/{$query_id}/{$sort_by}/{$sort_order}",
			$this->data['num_rows'],$this->limit,6);
		
		$this->data['pagination'] = $this->pagination->create_links(); 
				
		$this->data['sort_by'] = $sort_by;
		$this->data['sort_order'] = $sort_order;
		$this->data['query_id'] = $query_id;
	}
	
	public function return_search()
	{
		$query_array = array(
			'prodname_fk'    => $this->input->post('prodname_fk'),
			'distributor_fk' => $this->input->post('distributor_fk')
		);	
		$query_id = $this->input->save_query($query_array);
		redirect("distribution/returns/{$query_id}");
	}
	
	public function delete($page, $id)
	{
		$pages = ['in','out','ret'];
		
		if(!in_array($page, $pages)) air::flash('void');
			
		if($page == 'in')
			$redirect = 'inbounds';
		if($page == 'out')
			$redirect = 'outbounds';
		if($page == 'ret')
			$redirect = 'returns';
		
		/*
		 * Deletes the passed ID,
		 * and redirects
		 */
		$this->data['result'] = $this->whr->select_single($id);
		if(!$this->data['result']) air::flash('void');
				
		if($this->whr->delete($id))
			air::flash('delete','distribution/'.$redirect);
		else
			air::flash('error','distribution/'.$redirect);	
	}
	

	private function _inventory_use($warehouse_id,$product_id,$quantity)
	{
		//Loading Models
		$this->load->model('production/boms_model','bom');
		$this->load->model('production/bomdetails_model','bomd');
		$this->load->model('procurement/inventory_model','inv');

		$bomId = $this->bom->get_by(['prodname_fk'=>$product_id]);

		/*
		 * Retreive all components for specific Bill of Materials (bom_id) 
		 */
		$bom_components = $this->bomd->select_by_bom_id($bomId->id);
							
		foreach ($bom_components as $component)
		{
			$options = [
				'prodname_fk'  => $component->prodname_fk,
				'warehouse_fk' => $warehouse_id,
				'quantity'     => (($component->quantity * $quantity) * -1),
				'type'         => '0',
				'is_use'       => 1
			];

			unset($_POST);
				
			$this->inv->insert($options);
		}		
	}
}