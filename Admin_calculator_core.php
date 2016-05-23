<?php  
defined('BASEPATH') OR exit('No direct script access allowed');
   class admin_calculator_core 
	{  
		public function calculatorcore($data)
		{
			$CI =& get_instance();
			$CI->load->model('select');
			$CI->config->load('system_values');
			if($data['contract_id']!="")
			{
				$contract_id=$data['contract_id'];
				$result = $CI->select->contract_calculation($contract_id);
				$account_percentage_allocation=array();
				$account_id=array();
				$land_account_id=array();
				foreach($result -> result() as $row)
				{
					array_push($account_percentage_allocation,$row->payment_percentage1);
					array_push($account_id,$row->account_id);
					array_push($land_account_id,$row->land_account_id);
				}
				$data['plans']= $row->contract_type;
				$data['rent']= $row->Amount;
				$data['startdate']= $row->contract_startdate;
				$data['enddate']= $row->contract_enddate;
				$data['account_id']=$account_id;
				$data['land_account_id']=$land_account_id;
				$data['account_percentage_allocation']=$account_percentage_allocation;
				$allocationArray = array_filter($data['account_percentage_allocation']);
				if(!$allocationArray)
				{
					$data['account_percentage_allocation'][0] =100;
				} 
				$month_startdate = strtotime('-1 month', strtotime($data['startdate']));
				$month_startdate = date ('Y-m-01',$month_startdate);
				$enddate=strtotime($data['enddate']);
				$month_enddate   = $enddate-(31*3600*24);
				$month_enddate   = date ('Y-m-01',$month_enddate);
				$credit_date=strtotime($month_startdate);
				$month_enddate=strtotime($month_enddate);
				$monthly_credit_date=array();
				while ($credit_date <= $month_enddate)
				{
					$credit_date = strtotime("+1 month", $credit_date);
					array_push($monthly_credit_date,date ( 'Y-m-01' , $credit_date));	
					$output['monthly_credit_date']=$monthly_credit_date;
				}
			}
			//Loading config file
			$max_rent= $CI->config->item('max_rent');
			$flat_fee_less = $CI->config->item('flat_fee_less');
			$flat_fee_grt = $CI->config->item('flat_fee_grt');
			$ACH_fee = $CI->config->item('ACH_fee');
			if($data['flag']=="Cancellation_amount_owed")
			{
				$CI->load->model('select'); 
				$credit=$CI->select->cancellation_creditamount_owe();
				$debit =$CI->select->cancellation_debitamount_owe();
				foreach($credit->result() as $row)
				{
					$credit_total_amount =$row->credit_amount;
				}
				foreach($debit->result() as $row1)
				{
					$debit_total_amount= $row1->debit_amount;
				} 
				if($data['rent']<=$max_rent)
				{
					$cancellation_amount=$credit_total_amount-$debit_total_amount+$flat_fee_less+$ACH_fee;
				}
				else
				{
					$cancellation_amount=$credit_total_amount-$debit_total_amount+$flat_fee_grt+$ACH_fee;
				}
				$output['cancellation_amount']=$cancellation_amount;
				return $output;
			}			
			$output['plans']=$data['plans'];
			$output['rent']=$data['rent'];
			$output['startdate']=$data['startdate'];
			$output['enddate']=$data['enddate'];
			$output['account_percentage_allocation']=$data['account_percentage_allocation'];
			$output['account_id']=$data['account_id'];
			$output['land_account_id']=$data['land_account_id'];
			$output['error_code']=0;
			$startdate=date('Y-m-d', strtotime($data['startdate']));
			$enddate=date('Y-m-d', strtotime($data['enddate']));
			$current_date=date('Y-m-d');
			$output['number_of_accounts'] = count(array_filter($data['account_percentage_allocation']));
			$lastdate=date('Y-m-t', strtotime($enddate));
			//Calculate months between currentdate to enddate 
			$diff = abs(strtotime($enddate) - strtotime($current_date));
			$years = floor($diff / (365*60*60*24));
			$months = floor(($diff - $years * 365*60*60*24)/(30*60*60*24));
			
			//Calculation
			$fDay = date('Y-m-00');
			$fifday = date('Y-m-d',(strtotime($fDay)+ (86400 * 15)));
			if($current_date<=$fifday)
			{
				$month=$months-1;
			}
			else
			{
				$month=$months-2;
			}
			
			if($data['plans']=="Pluspay")
			{
				$first_next_date=date('Y-m-01', strtotime($startdate));
				$next_date= strtotime('1 month', strtotime($first_next_date)); 
				$new_date=date('Y-m-01',$next_date);
				$startdate=strtotime($new_date);
				$firstfriday=strtotime("Friday",$startdate);
				$firstfriday_count=strtotime("Friday",$startdate);
				$enddate_new=strtotime($enddate);
				if($data['startdate'] =="") 
					{
						$startdate=$current_date;
					} 
					if($enddate!=$lastdate)
					{	
						$output['error_code']=1;
						return $output;
					}
					if($months<6)	
					{
						$output['error_code']=2;
						return $output;
					}
					$friday=array();
					$count=0;
					while ($firstfriday_count <=  $enddate_new)
					{
						$firstfriday_count = strtotime("+1 week", $firstfriday_count);
						$count++;
					}
					$week_count=1;
					$weeklyamount=array();
					while ($firstfriday <= $enddate_new)
					{
						$result=($data['rent']*$months);
						$Week_debited=$count-4;
						if($data['rent']<=$max_rent)
						{
							
							$rental_amount=round(($result/$Week_debited)+$flat_fee_less);
							$max_cancellation_amount=((2*$data['rent'])-$rental_amount+$flat_fee_less+$ACH_fee);
						}
						else
						{	
							$rental_amount=round(($result/$Week_debited)+$flat_fee_grt);
							$max_cancellation_amount=((2*$data['rent'])-$rental_amount+$flat_fee_grt+$ACH_fee);
						} 
						$payers_count=1;
						foreach($data['account_percentage_allocation'] as $account_percentage_allocation)
							{	
								$weeklyamount[$week_count][$payers_count]=round(($account_percentage_allocation/100)*$rental_amount);
								$payers_count++;
							}
							array_push($friday,date("d-m-Y", $firstfriday));
							$firstfriday = strtotime("+1 week", $firstfriday);
							$week_count++;
					}
				if($data['flag']=="Max_cancellation_amount")
				{
					$output['max_cancellation_amount']=$max_cancellation_amount;
				}
				else
				{
					
					$output['account_percentage_allocation']=$data['account_percentage_allocation'];
					$output['friday']=$friday;	
					$output['weeklyamount']=$weeklyamount;
				}
				return $output;
			}
			//Surepay	
			else if($data['plans']=="Surepay")
			{
				$startdate=date('Y-m-d', strtotime($data['startdate']));
				$enddate=date('Y-m-t', strtotime($data['startdate']));
				$firstdate=date('Y-m-01', strtotime($data['startdate']));
				$first_next_date=date('Y-m-01', strtotime($startdate));
				$next_date= strtotime('-1 month', strtotime($first_next_date)); 
				$prev_date=date('28-m-Y',$next_date);
				if($data['startdate']=="") 
				{
					$startdate=$current_date;
					$before_date = strtotime('-17 day', strtotime($startdate));
					$newdate = date ( 'Y-m-d' , $before_date );
				}
				else 
				{
					$startdate=$startdate;
					$before_date = strtotime('-17 day', strtotime($startdate)); 
					$newdate = date('Y-m-d',$before_date );
				}
				if($startdate!=$firstdate)	
				{
					$output['error_code']=3;
					return $output;
				}
				if($lastdate!=$enddate)
				{	
					$output['error_code']=1;
					return $output;
				}
				if($newdate<$current_date)	
				{
					$output['error_code']=4;
					return $output;
				}
				$startdate=strtotime($startdate);
				$firstfriday_count=strtotime("Friday",$startdate);
				$firstfriday=strtotime("Friday",$startdate);
				$enddate_new=strtotime($enddate);
				$friday=array();
				$count=0;
				while ($firstfriday_count <=  $enddate_new)
				{
					$firstfriday_count = strtotime("+1 week", $firstfriday_count);
					$count++;
				}
				$week_count=1;
				while ($firstfriday <= $enddate_new)
				{	
					if($data['rent']<=$max_rent)
					{	
						$result=($data['rent']/$count)+$flat_fee_less;
						$rental_amount=(($result*100)/100)+$ACH_fee;
						$max_cancellation_amount=$data['rent']-$rental_amount+$flat_fee_less+$ACH_fee;
						$payers_count=1;
					}
					else
					{
						$result=($data['rent']/$count)+$flat_fee_grt;
						$rental_amount=(($result*100)/100)+$ACH_fee;
						$max_cancellation_amount=$data['rent']-$rental_amount+$flat_fee_grt+$ACH_fee;
						$payers_count=1;
					}
						foreach($data['account_percentage_allocation'] as $account_percentage_allocation)
						{
							$weeklyamount[$week_count][$payers_count]=round(($account_percentage_allocation/100)*$rental_amount);
							$payers_count++;
						}
					
						array_push($friday,date("d-m-Y", $firstfriday));
						$firstfriday = strtotime("+1 week", $firstfriday);
						$week_count++;
				}
				if($data['flag']=="Max_cancellation_amount")
				{
					$output['max_cancellation_amount']=$max_cancelation_amount;
				}
				else
				{
					$output['friday']=$friday;
					$output['weeklyamount']=$weeklyamount;
					$output['prev_date']=$prev_date;
				}
				return $output;
			}
			
		}
		
	}