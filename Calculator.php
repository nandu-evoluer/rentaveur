<?php  
defined('BASEPATH') OR exit('No direct script access allowed');
   class Calculator extends CI_Controller  
	{  
		function __construct() 
		{
			parent::__construct();
				$this->load->library('form_validation');
				$this->load->helper('form','url');
				//$this->load->library('../Admin_calculator_core/CalculatorCore');
		}   
		public function index() 
		{
			$this->load->view('templates/header');
			$this->load->view('customers/calculator_input');
			$this->load->view('templates/footer');			 
		}
			
		public function rental_calculation()
		{   
			//Validating inputs 
			$this->form_validation->set_error_delimiters('<div class="error">','</div>');
			$this->form_validation->set_rules('rent', 'Rental Amount', 'required');
			$this->form_validation->set_rules('enddate', 'Enddate', 'required');
			$this->form_validation->set_rules('plan', 'Surepay Or Pluspay', 'required');
			if ($this->form_validation->run() == FALSE)
			{
				$this->load->view('templates/header');	
				$this->load->view('customers/calculator_input');
				$this->load->view('templates/footer');	
			}
			else
			{       
				//Getting input from view file
				$data['plans']=$this->input->post('plan');  
				$data['rent']= $this->input->post("rent");
				$data['startdate'] = $this->input->post("startdate");
				$data['enddate'] = $this->input->post("enddate");
				$data['account_id'] = $this->input->post("account_id");
				$data['contract_id']="";
				$data['flag']="";
				$data['land_account_id']="";
				$data['account_percentage_allocation'] = $this->input->post("account_percentage_allocation");
				
				
				$allocationArray = array_filter($data['account_percentage_allocation']);
				if(!$allocationArray)
				{
					$data['account_percentage_allocation'][0] =100;
				}
				$this->load->library('admin_calculator_core');
				$result=$this->admin_calculator_core->calculatorcore($data);
				$this->load->view('customers/calculator_result',$result);
			}
		}
		public function contract_calculation()
		{
			$data['contract_id']=1;
			$data['flag']="";
			$data['error_code']=0;
			$this->load->library('admin_calculator_core');
			$result=$this->admin_calculator_core->calculatorcore($data);
			print_r($result);
		}
	}
		
	
?>	 