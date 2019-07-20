<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ocds extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
	}

	public function pkg()
	{
		// $datetime = new DateTime();

		$baseuri = base_url() . "published/ocds/";

		// $packageId = $datetime->format('YmdHis');

		// $orgProjectId = '001-'.$packageId;

		// $releasesUri = [];
		// $releases = [];

		// $tenders = $this->db->limit(2)->get('senarai_tender');
		$tenders = $this->db->get('senarai_tender');

		$ocdsBil = 0;

		foreach($tenders->result() as $tender) {

			$datetime = new DateTime();

			$releasesUri = [];
			$releases = [];
			$package = [];

			$packageId = $datetime->format('YmdHis');
			$randNo = generate_code();
			$orgProjectId = $randNo.'-'.$packageId;

			$ocid = 'ocds-vesric-'.$orgProjectId; //.'-'.$tender->NOMBOR_TENDER;

			$dateStart = date_create($tender->TARIKH_IKLAN);
			$dateStart = new DateTime($dateStart);

			$dateEnd = date_create($tender->TARIKH_TUTUP);
			$dateEnd = new DateTime($dateEnd);
			// $dateStart = date_format($dateStart, 'Y-m-d\TH:i:s');

			$data =
			[
				"ocid" => $ocid,
				"id" => $ocid."-".$tender->NOMBOR_TENDER,
				"date" => $datetime->format('Y-m-d\TH:i:s').'+08:00',
				"language" => "ms",
				"tag" => [
					"tender",
					"award"
				],
				"initiationType" => "tender",
				"parties" => $this->parties($tender->NOMBOR_TENDER, $tender->NOMBOR_MOF, $tender->KOD_KEMENTERIAN, $tender->KEMENTERIAN, $tender->KOD_JABATAN, $tender->NAMA_JABATAN),
				"buyer" => [
					"id" => $tender->KOD_JABATAN,
					"name" => $tender->NAMA_JABATAN,
				],
				"tender" => [
					"id" => $ocid.'-'.$tender->NOMBOR_TENDER.'-tender',
					"title" => $tender->TAJUK_TENDER,
					// "date" => $datetime->format('Y-m-d\TH:i:sO'),
					"status" => "complete",
					// "value" => "0.00",
					"procurementMethod" => ($tender->JENIS_PEROLEHAN == 'QUOTATION') ? 'limited' : 'open',
					"tenderPeriod" => [
						"startDate" => $dateStart->format('Y-m-d\TH:i:s').'+08:00',
						"endDate" => $dateEnd->format('Y-m-d\TH:i:s').'+08:00'
					],
					"numberOfTenderers" => count($this->tenderers($tender->NOMBOR_TENDER)),
					"tenderers" => $this->tenderers($tender->NOMBOR_TENDER)
				],
				"awards" => $this->awards(
					$ocid.'-'.$tender->NOMBOR_TENDER.'-award',
					$tender->TAJUK_TENDER,
					$tender->AMAUN,
					$tender->NOMBOR_MOF,
					$tender->PEMBEKALAN_RAPATSETIA
				),
			];

			array_push($releases, $data);
			// $releases[] = array_push($releases, $data);

			// print_r(json_encode($releases));

			$data = json_encode($data);

			// write per tender release ocds
			// $file = fopen("ocds/2019/".$ocid.'-'.$tender->NOMBOR_TENDER.'.json', "w");
			// fwrite($file, $data);
			// fclose($file);

			// print_r($data);

			array_push($releasesUri, $baseuri.$ocid.'-'.$tender->NOMBOR_TENDER.'.json');

			// package wrapper
			$packageUri = 'ocds-vesric-'.$orgProjectId;

			$package = [
				"uri" => $baseuri . $packageUri . '.json',
				"version" => "1.1",
				"publishedDate" => $datetime->format('Y-m-d\TH:i:s').'+08:00',
				// "releases" => $releasesUri,
				"releases" => $releases,
				"publisher" => [
					"name" => "MALAYSIAN ADMINISTRATIVE MODERNISATION AND MANAGEMENT PLANNING UNIT",
					"uri" => "https://www.mampu.gov.my"
				]
			];

			$package = json_encode($package);

			$filePkg = fopen("published/ocds/".$packageUri.'.json', "w");
			fwrite($filePkg, $package);
			fclose($filePkg);

			// update senarai_tender table
			$ocds_file = [
				'OCDS' => $packageUri . '.json'
			];
			$this->db->where('ID', $tender->ID);
			$this->db->update('senarai_tender', $ocds_file);

			$ocdsBil++;

		}

		// // package wrapper
		// $packageUri = 'ocds-vesric-'.$orgProjectId;

		// $package = [
		// 	"uri" => $baseuri . $packageUri,
		// 	"version" => "1.1",
		// 	"publishedDate" => $datetime->format('Y-m-d\TH:i:s').'+08:00',
		// 	"releases" => $releasesUri,
		// 	// "releases" => $releases,
		// 	"publisher" => [
		// 		"name" => "MALAYSIAN ADMINISTRATIVE MODERNISATION AND MANAGEMENT PLANNING UNIT",
		// 		"uri" => "https://www.mampu.gov.my"
		// 	]
		// ];

		// $package = json_encode($package);

		// $filePkg = fopen("ocds/2019/".$packageUri.'.json', "w");
		// fwrite($filePkg, $package);
		// fclose($filePkg);

		echo $ocdsBil.' OCDS records generated';
	}

	public function awards($id, $title, $amount, $mof, $name)
	{
		$awards = [];
		$suppliers = [];
		array_push($suppliers, ["id" => $mof,"name" => $name]);

		$data = [
			"id" => $id,
			"title" => $title,
			"status" => "active",
			"value" => [
				"amount" => $amount,
				"currency" => "MYR"
			],
			"suppliers" => $suppliers
		];

		$awards[] = $data;
		return $awards;

	}

	public function tenderers($tenderId = 0)
	{
		$this->db->where('QT_NO', $tenderId);
		$ts = $this->db->get('tender_syarikat');

		$tenderers = [];
		foreach($ts->result() as $t) {
			$data = [
				"id" => $t->MOF_NO,
				"name" => $t->SUPPLIER_NAME
			];

			$tenderers[] = $data;
		}
		return $tenderers;
	}

	public function parties($tenderId = 0, $noMof = 0, $bid, $bname, $pid, $pname)
	{
		$this->db->where('QT_NO', $tenderId);
		$ts = $this->db->get('tender_syarikat');

		$tenderers = [];
		foreach($ts->result() as $t) {
			$data = [
				"id" => $t->MOF_NO,
				"name" => $t->SUPPLIER_NAME,
				"roles" => ["tenderer"]
			];

			if($t->MOF_NO == $noMof) {
				array_push($data["roles"], "supplier");
			}

			$tenderers[] = $data;
		}

		$buyer = [
			"id" => $bid,
			"name" => $bname,
			"roles" => ["buyer"]
		];

		array_push($tenderers, $buyer);

		$procuring = [
			"id" => $pid,
			"name" => $pname,
			"roles" => ["procuringEntity"]
		];

		array_push($tenderers, $procuring);

		return $tenderers;
	}

}
