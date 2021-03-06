<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Payroll_extra_model extends MY_Model {
	
	protected $_table = 'exp_cd_payroll_extra';
	
	public function select($query_array, $sort_by, $sort_order, $limit=null, $offset=null)
	{
		//Selects and returns all records from table
		$this->db->select('p.*,pc.name,e.fname,e.lname');
		$this->db->from('exp_cd_payroll_extra AS p');
		$this->db->join('exp_cd_payroll_extra_cat AS pc','pc.id = p.payroll_extra_cat_fk','LEFT');
		$this->db->join('exp_cd_employees AS e','e.id = p.employee_fk','LEFT');
		
		//Filters
		if(strlen($query_array['employee_fk']))
			$this->db->where('p.employee_fk',$query_array['employee_fk']);
		if(strlen($query_array['payroll_extra_cat_fk']))
			$this->db->where('p.payroll_extra_cat_fk',$query_array['payroll_extra_cat_fk']);
		
		//Type of Payroll extra selection
		if(strlen($query_array['is_expense']))
			$this->db->where('pc.is_expense',$query_array['is_expense']);
		if(strlen($query_array['is_contribution']))
			$this->db->where('pc.is_contribution',$query_array['is_contribution']);

		//Sort
		if($sort_by == 'employee')
			$sort_by = "e.fname";
			
		if($sort_by == 'payroll_extra_cat_fk')
			$sort_by = "pc.name";
			
		$this->db->order_by($sort_by,$sort_order);
			
		//Pagination Limit and Offset
		$this->db->limit($limit , $offset);
		
		$data['results'] = $this->db->get()->result();
		
		//Counts the TOTAL rows in the Table------------------------------------------------------------
		
		$this->db->select('COUNT(p.id) AS count',false);
		$this->db->from('exp_cd_payroll_extra AS p');
		$this->db->join('exp_cd_payroll_extra_cat AS pc','pc.id = p.payroll_extra_cat_fk','LEFT');
		$this->db->join('exp_cd_employees AS e','e.id = p.employee_fk','LEFT');
		
		if(strlen($query_array['employee_fk']))
			$this->db->where('p.employee_fk',$query_array['employee_fk']);
		if(strlen($query_array['payroll_extra_cat_fk']))
			$this->db->where('p.payroll_extra_cat_fk',$query_array['payroll_extra_cat_fk']);
			
		//Type of Payroll extra selection
		if(strlen($query_array['is_expense']))
			$this->db->where('pc.is_expense',$query_array['is_expense']);
		if(strlen($query_array['is_contribution']))
			$this->db->where('pc.is_contribution',$query_array['is_contribution']);
		
		$temp = $this->db->get()->row();
		
		$data['num_rows'] = $temp->count;
		//-----------------------------------------------------------------------------------------------
		//Returns the whole data array containing $results and $num_rows
		return $data;
	}
	
	public function select_by_payroll($payroll_id,$type)
	{
		
		//Selects and returns all records from table
		$this->db->select('p.*,pc.name,e.fname,e.lname');
		$this->db->from('exp_cd_payroll_extra AS p');
		$this->db->join('exp_cd_payroll_extra_cat AS pc','pc.id = p.payroll_extra_cat_fk','LEFT');
		$this->db->join('exp_cd_employees AS e','e.id = p.employee_fk','LEFT');

		$this->db->where('p.payroll_fk',$payroll_id);
		$this->db->where('p.locked',1);
		
		//Retrevies Payroll extras by Type (expense or non-expense)
		if($type == 1 OR $type == 0)
		{
			$this->db->where('pc.is_expense',$type);
			$this->db->where('pc.is_contribution',0);
		}
		//Retreives Payroll extras by having attr. is_contribution = 1
		if($type == 3)
			$this->db->where('pc.is_contribution',1);
		
		$this->db->group_by('p.employee_fk');
		$this->db->group_by('pc.name');
		
		return $this->db->get()->result();
	}
	
	public function select_single($id)
	{
		$this->db->select('p.*,pc.name,e.fname,e.lname');
		$this->db->from('exp_cd_payroll_extra AS p');
		$this->db->join('exp_cd_payroll_extra_cat AS pc','pc.id = p.payroll_extra_cat_fk','LEFT');
		$this->db->join('exp_cd_employees AS e','e.id = p.employee_fk','LEFT');
		
		$this->db->where('p.id',$id);
		
		return $this->db->get()->row();
	}
	
	public function calc_extras($options=array(), $type = null)
	{
		$this->db->select('pe.id,pe.employee_fk,pe.amount,pc.name');
		
		$this->db->select_sum('pe.amount');
		
		$this->db->from('exp_cd_payroll_extra as pe');
		$this->db->join('exp_cd_payroll_extra_cat AS pc','pc.id = pe.payroll_extra_cat_fk','LEFT');
		
		$this->db->where('pe.employee_fk',$options['employee_fk']);
		$this->db->where('pe.for_date >=',$options['datefrom']);
		$this->db->where('pe.for_date <=',$options['dateto']);
		$this->db->where('pe.payroll_fk',null);
		$this->db->where('pe.locked',0);
		
		if($type == 1 OR $type == 0)
		{
			$this->db->where('pc.is_expense',$type);
			$this->db->where('pc.is_contribution',0);
		}
		
		if($type == 3)
			$this->db->where('pc.is_contribution',1);

		
		$this->db->group_by('pe.employee_fk');
		$this->db->group_by('pc.name');
		
		return $this->db->get()->result();
	}
	
	public function get_soc_contr($id,$options=array())
	{
		$this->db->select('amount');
		$this->db->from($this->_table);
		
		$this->db->where('for_date >=',$options['datefrom']);
		$this->db->where('for_date <=',$options['dateto']);
		$this->db->where('payroll_extra_cat_fk',7);
		$this->db->where('employee_fk',$id);
		$this->db->where('payroll_fk',null);
		$this->db->where('locked',0);
		
		if($result = $this->db->get()->last_row())
			return $result->amount;
		else
			return 0;
	}
	
	public function check_type($options=array())
	{
		$this->db->select('is_expense');
		$this->db->from('exp_cd_payroll_extra_cat');
		$this->db->where('id',$options['payroll_extra_cat_fk']);
		$this->db->limit(1);
		
		return $this->db->get()->row();
	}
	
	public function dropdown($type = null, $empty = '--')
	{
		$this->db->select('p.id,p.name');
		$this->db->from('exp_cd_payroll_extra_cat as p');
		
		($type=='bonuses') ? $type='bonuses' : $type='expenses';
		
		if($type == 'bonuses')
		{
			$this->db->where('p.is_expense',0);
			$this->db->where('p.is_contribution',0);
			$empty = '- Додаток -';
		}
		if($type == 'expenses')
		{
			$this->db->where('p.is_expense',1);
			$this->db->where('p.is_contribution',0);
			$empty = '- Трошок -';
		}	
		
		$results = $this->db->get();
		
		 $data['']= $empty;
		
		foreach ($results->result() as $row)
            $data[$row->id]= $row->name;
        
        return $data;
	}
}