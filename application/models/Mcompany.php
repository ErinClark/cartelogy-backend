<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mcompany extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
	}

	public function all()
	{
		$companies = [];
		$rs = $this->db->limit(1000)->get('senarai_syarikat');

		foreach($rs->result() as $r) {
			$companies[] = $r;
		}

		return $companies;
	}

	public function getCompanyByMofNo($id1, $id2)
	{
		$data['comp1'] = $this->db->get_where('senarai_syarikat', array('MOF_NO' => $id1))->row();
		$data['comp2'] = $this->db->get_where('senarai_syarikat', array('MOF_NO' => $id2))->row();



		// FLAG 1 (Cartel)

		$query1 = $this->db->query("SELECT COUNT(qt_no) AS tot FROM tender WHERE (mof_no = '$id1' OR mof_no = '$id2') GROUP BY qt_no");
		$tender1 = $query1->result();

		$flag1 = false;
		$single_join = 0;
		$double_join = 0;
		$tot = 0;
		$join_rate = 0;
		foreach($tender1 as $r) {
			
			if($r->tot == 1)
				$single_join++;
			else
				$double_join++;	
		}
		$tot = $single_join + $double_join;

		if($tot <> 0)
			$join_rate = $double_join / $tot;
		
		if($join_rate > 0.5)
			$flag1 = true;

		


		// FLAG 2 & FLAG 3 (JOIN > 5 AND WIN RATE >= 8.0)
		$query1 = $this->db->query("SELECT * FROM tender WHERE mof_no = '$id1'");
		$tender_comp1 = $query1->result();

		$query2 = $this->db->query("SELECT * FROM tender WHERE mof_no = '$id2'");
		$tender_comp2 = $query2->result();

		$comp1_flag2 = false;
		$comp1_flag3 = false;
		$tot1 = 0;
		$win1 = 0;
		$lost1 = 0;
		$win1_rate = 0;
		$lost1_rate = 0;
		foreach($tender_comp1 as $r) {
			
			if($r->award == "menang")
				$win1++;
			else
				$lost1++;
			
			$tot1++;
		}

		$data["comp1_flag"]["flag2_rate"] = 0;
		$data["comp1_flag"]["flag3_rate"] = 0;
		if($tot1 > 5)  //Jika penah masuk lebih dari lima kali baru dikira
		{
			$win1_rate = $win1 / $tot1;

			$lost1_rate = $lost1 / $tot1;

			if($win1_rate >= 0.7)
			{
				$comp1_flag2 = true;
				$data["comp1_flag"]["flag2_rate"] = $win1_rate;
			}
			
			if($lost1_rate >= 0.7)  //Flag 3 (Frequent LOST)
			{
				$comp1_flag3 = true;
				$data["comp1_flag"]["flag3_rate"] = $lost1_rate;
			}
		}



		$comp2_flag2 = false;
		$comp2_flag3 = false;
		$tot2 = 0;
		$win2= 0;
		$lost2 = 0;
		$lost2_rate = 0;
		foreach($tender_comp2 as $r) {
			
			if($r->award == "menang")
				$win2++;
			else
				$lost2++;
			
			$tot2++;
		}

		$data["comp2_flag"]["flag2_rate"] = 0;
		$data["comp2_flag"]["flag3_rate"] = 0;
		if($tot2 > 5)  //Jika penah masuk lebih dari lima kali baru dikira
		{
			$win2_rate = $win2 / $tot2;

			$lost2_rate = $lost2 / $tot2;

			if($win2_rate >= 0.7)
			{
				$comp2_flag2 = true;
				$data["comp2_flag"]["flag2_rate"] = $win2_rate;
			}
			
			if($lost2_rate >= 0.7)  //Flag 3 (Frequent LOST)
			{
				$comp2_flag3 = true;
				$data["comp2_flag"]["flag3_rate"] = $lost2_rate;
			}
		}


		

		// FLAG 4 (WIN BY AGENCY)
		$query1 = $this->db->query("SELECT *, COUNT(nombor_tender) as tot FROM senarai_tender WHERE nombor_mof = '$id1' GROUP BY kod_jabatan");
		$tender_comp1 = $query1->result();

		$query2 = $this->db->query("SELECT *, COUNT(nombor_tender) as tot FROM senarai_tender WHERE nombor_mof = '$id2' GROUP BY kod_jabatan");
		$tender_comp2 = $query2->result();

		$comp1_flag4 = false;
		$data["comp1_flag"]["flag4_rate"] = 0;
		foreach($tender_comp1 as $r) {
			
			if($r->tot > 6)
			{
				$comp1_flag4 = true;
				$data["comp1_flag"]["flag4_rate"] = $r->tot * 0.1;
			}
		}

		$comp2_flag4 = false;
		$data["comp2_flag"]["flag4_rate"] = 0;
		foreach($tender_comp2 as $r) {
			
			if($r->tot > 6)
			{
				$comp2_flag4 = true;
				$data["comp2_flag"]["flag4_rate"] = $r->tot * 0.1;
			}	
		}



		//FLAG 5 (SINGGLE BIDDER, IF JOIN & WIN > 0.5)
		$query1 = $this->db->query("SELECT * FROM tender WHERE mof_no = '$id1'");
		$tender_comp1 = $query1->result();

		$query2 = $this->db->query("SELECT * FROM tender WHERE mof_no = '$id2'");
		$tender_comp2 = $query2->result();

		$comp1_flag5 = false;
		$single1_win = 0;
		$tot1 = 0;
		foreach($tender_comp1 as $r) {
			
			if($r->award == "menang") 
			{
				$sub_query = $this->db->query("SELECT count(mof_no) as tot1 FROM tender WHERE qt_no = '$r->qt_no'");
				$sub_query_result = $sub_query->row();

				if($sub_query_result->tot1 == 1)
					$single1_win++;

				$tot1++;
			}	
		}

		$data["comp1_flag"]["flag5_rate"] = 0;
		if($tot1 > 3)
		{
			if($single1_win/$tot1 > 0.5)
			{
				$comp1_flag5 = true;
				$data["comp1_flag"]["flag5_rate"] = $single1_win/$tot1;
			}
		}


		$comp2_flag5 = false;
		$single2_win = 0;
		$tot2 = 0;
		foreach($tender_comp2 as $r) {
			
			if($r->award == "menang") 
			{
				$sub_query = $this->db->query("SELECT count(mof_no) as tot2 FROM tender WHERE qt_no = '$r->qt_no'");
				$sub_query_result = $sub_query->row();

				if($sub_query_result->tot2 == 1)
					$single2_win++;

				$tot2++;
			}	
		}

		$data["comp2_flag"]["flag5_rate"] = 0;
		if($tot2 > 3)
		{
			if($single2_win/$tot2 > 0.5)
			{
				$comp2_flag5 = true;
				$data["comp2_flag"]["flag5_rate"] = $single2_win/$tot2;
			}
		}







		//Combine FLAG
		$data["comp"]["flag1"] = $flag1;
		$data["comp"]["flag_rate"] = $join_rate;

		$data["comp1_flag"]["flag2"] = $comp1_flag2;
		$data["comp2_flag"]["flag2"] = $comp2_flag2;

		$data["comp1_flag"]["flag3"] = $comp1_flag3;
		$data["comp2_flag"]["flag3"] = $comp2_flag3;

		$data["comp1_flag"]["flag4"] = $comp1_flag4;
		$data["comp2_flag"]["flag4"] = $comp2_flag4;
		
		$data["comp1_flag"]["flag5"] = $comp1_flag5;
		$data["comp2_flag"]["flag5"] = $comp2_flag5;

		return $data;
	}


	public function getOCDS()
	{
		$data["data"] = $this->db->get('senarai_tender')->result();
		return $data;
	}

}
