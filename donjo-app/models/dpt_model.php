<?php class Dpt_Model extends Penduduk_Model{

	function __construct(){
		parent::__construct();

		$this->load->model('keluarga_model');
	}

	function autocomplete(){
		$sql   = "SELECT nama FROM tweb_penduduk";
		$query = $this->db->query($sql);
		$data  = $query->result_array();

		$i=0;
		$outp='';
		while($i<count($data)){
			$outp .= ',"'.$data[$i]['nama'].'"';
			$i++;
		}
		$outp = substr($outp, 1);
		$outp = '[' .$outp. ']';
		return $outp;
	}


	function search_sql(){
		if(isset($_SESSION['cari'])){
			$cari = $_SESSION['cari'];
			$kw = $this->db->escape_like_str($cari);
			$kw = '%' .$kw. '%';
			$search_sql= " AND (u.nama LIKE '$kw' OR u.nik LIKE '$kw')";
			return $search_sql;
		}
	}

	function sex_sql(){
		if(isset($_SESSION['sex'])){
			$kf = $_SESSION['sex'];
			$sex_sql= " AND u.sex = $kf";
		return $sex_sql;
		}
	}

	function dusun_sql(){
		if(isset($_SESSION['dusun'])){
			$kf = $_SESSION['dusun'];
			$dusun_sql= " AND a.dusun = '$kf'";
		return $dusun_sql;
		}
	}

	function rw_sql(){
		if(isset($_SESSION['rw'])){
			$kf = $_SESSION['rw'];
			$rw_sql= " AND a.rw = '$kf'";
		return $rw_sql;
		}
	}

	function rt_sql(){
		if(isset($_SESSION['rt'])){
			$kf = $_SESSION['rt'];
			$rt_sql= " AND a.rt = '$kf'";
		return $rt_sql;
		}
	}

	function get_sql_kolom_kode($kode_session,$kode_kolom){
		if(isset($_SESSION[$kode_session])){
			$kf = $_SESSION[$kode_session];
			if ($kf == BELUM_MENGISI)
				$sql = " AND (u.".$kode_kolom." IS NULL OR u.".$kode_kolom." = '')";
			else
				$sql= " AND u.".$kode_kolom." = $kf";
		return $sql;
		}
	}

	function akta_kelahiran_sql(){
		if(isset($_SESSION['akta_kelahiran'])){
			$kf = $_SESSION['akta_kelahiran'];
			if ($kf == BELUM_MENGISI)
				$sql = " AND (u.akta_lahir IS NULL OR u.akta_lahir = '')";
			else
				$sql= " AND u.akta_lahir<>''";
		return $sql;
		}
	}

	function cacatx_sql(){
		if(isset($_SESSION['cacatx'])){
			$kf = $_SESSION['cacatx'];
			$cacatx_sql= " AND u.cacat_id <> $kf AND u.cacat_id is not null and u.cacat_id<>''";
		return $cacatx_sql;
		}
	}

	function menahunx_sql(){
		if(isset($_SESSION['menahunx'])){
			$kf = $_SESSION['menahunx'];
			$menahunx_sql= " AND u.sakit_menahun_id <> $kf and u.sakit_menahun_id is not null and u.sakit_menahun_id<>'0' ";
		return $menahunx_sql;
		}
	}

	function hamil_sql(){
		if(isset($_SESSION['hamil'])){
			$kf = $_SESSION['hamil'];
			$hamil_sql= " AND u.hamil = $kf";
		return $hamil_sql;
		}
	}

	function umur_max_sql(){
		if(isset($_SESSION['umur_max'])){
			$kf = $_SESSION['umur_max'];
			$umur_max_sql= " AND (SELECT DATE_FORMAT(FROM_DAYS(TO_DAYS(NOW())-TO_DAYS(`tanggallahir`)), '%Y')+0 FROM tweb_penduduk WHERE id = u.id) <= $kf ";
		return $umur_max_sql;
		}
	}

	function umur_min_sql(){
		if(isset($_SESSION['umur_min'])){
			$kf = $_SESSION['umur_min'];
			$umur_min_sql= " AND (SELECT DATE_FORMAT(FROM_DAYS(TO_DAYS(NOW())-TO_DAYS(`tanggallahir`)), '%Y')+0 FROM tweb_penduduk WHERE id = u.id) >= $kf ";
		return $umur_min_sql;
		}
	}

	function umur_sql(){
		if(isset($_SESSION['umurx'])){
			$kf = $_SESSION['umurx'];
			if ($kf != BELUM_MENGISI)
				$umur_sql= " AND (SELECT DATE_FORMAT(FROM_DAYS(TO_DAYS(NOW())-TO_DAYS(`tanggallahir`)), '%Y')+0 FROM tweb_penduduk WHERE id = u.id) >= (SELECT dari FROM tweb_penduduk_umur WHERE id=$kf ) AND (SELECT DATE_FORMAT(FROM_DAYS(TO_DAYS(NOW())-TO_DAYS(`tanggallahir`)), '%Y')+0 FROM tweb_penduduk WHERE id = u.id) <= (SELECT sampai FROM tweb_penduduk_umur WHERE id=$kf ) ";
			else $umur_sql = '';
		return $umur_sql;
		}
	}

	/*
		Syarat calon pemilih:
		1. Status dasar = HIDUP
		2. Status penduduk = TETAP
		3. Warganegara = WNI
		4. Umur >= 17 tahun pada tanggal pemilihan ATAU sudah/pernah kawain (status kawin = KAWIN, CERAI HIDUP atau CERAI MATI)
	*/
	function syarat_dpt_sql(){
		if($this->input->post('tanggal_pemilihan')){
			$tanggal_pemilihan = $this->input->post('tanggal_pemilihan');
			$_SESSION['tanggal_pemilihan'] = $tanggal_pemilihan;
		}
		elseif(isset($_SESSION['tanggal_pemilihan'])){
			$tanggal_pemilihan = $_SESSION['tanggal_pemilihan'];
		} else {
			$_SESSION['tanggal_pemilihan'] = date("d-m-Y");
			$tanggal_pemilihan = date("d-m-Y");
		}
		$sql = " AND u.status_dasar = 1 AND u.status = 1 AND u.warganegara_id = 1 ";
		$sql .= " AND (((SELECT DATE_FORMAT(FROM_DAYS(TO_DAYS(STR_TO_DATE('$tanggal_pemilihan','%d-%m-%Y'))-TO_DAYS(`tanggallahir`)), '%Y')+0 FROM tweb_penduduk WHERE id = u.id) >= 17) OR u.status_kawin IN (2,3,4))";
		return $sql;
	}

	function get_alamat_wilayah($id) {
		// Alamat anggota keluarga diambil dari tabel keluarga
		$this->db->select('id_kk');
		$this->db->where('id', $id);
		$q = $this->db->get('tweb_penduduk');
		$penduduk = $q->row_array();
		if ($penduduk['id_kk'] > 0) {
			return $this->keluarga_model->get_alamat_wilayah($penduduk['id_kk']);
		}
		// Alamat penduduk lepas diambil dari kolom alamat_sekarang
		$sql = "SELECT a.dusun,a.rw,a.rt,u.alamat_sekarang as alamat
				FROM tweb_penduduk u
				LEFT JOIN tweb_wil_clusterdesa a ON u.id_cluster = a.id
				WHERE u.id=?";
		$query = $this->db->query($sql,$id);
		$data  = $query->row_array();

		$alamat_wilayah= trim("$data[alamat] RT $data[rt] / RW $data[rw] ".ikut_case($data['dusun'],$this->setting->sebutan_dusun)." $data[dusun]");
		return $alamat_wilayah;
	}

	function paging($p=1,$o=0,$log=0){

		$list_data_sql = $this->list_data_sql($log);
		$sql = "SELECT COUNT(u.id) AS id ".$list_data_sql;
		$query    = $this->db->query($sql);
		$row      = $query->row_array();
		$jml_data = $row['id'];

		$this->load->library('paging');
		$cfg['page']     = $p;
		$cfg['per_page'] = $_SESSION['per_page'];
		$cfg['num_rows'] = $jml_data;
		$this->paging->init($cfg);

		return $this->paging;
	}

	// Digunakan untuk paging dan query utama supaya jumlah data selalu sama
	private function list_data_sql($log) {
		$sql = "
		FROM tweb_penduduk u
		LEFT JOIN tweb_keluarga d ON u.id_kk = d.id
		LEFT JOIN tweb_wil_clusterdesa a ON d.id_cluster = a.id
		LEFT JOIN tweb_penduduk_pendidikan_kk n ON u.pendidikan_kk_id = n.id
		LEFT JOIN tweb_penduduk_pendidikan sd ON u.pendidikan_sedang_id = sd.id
		LEFT JOIN tweb_penduduk_pekerjaan p ON u.pekerjaan_id = p.id
		LEFT JOIN tweb_penduduk_kawin k ON u.status_kawin = k.id
		LEFT JOIN tweb_penduduk_sex x ON u.sex = x.id
		LEFT JOIN tweb_penduduk_agama g ON u.agama_id = g.id
		LEFT JOIN tweb_penduduk_warganegara v ON u.warganegara_id = v.id
		LEFT JOIN tweb_golongan_darah m ON u.golongan_darah_id = m.id
		LEFT JOIN tweb_cacat f ON u.cacat_id = f.id
		LEFT JOIN tweb_penduduk_hubungan hub ON u.kk_level = hub.id
		LEFT JOIN tweb_sakit_menahun j ON u.sakit_menahun_id = j.id
		LEFT JOIN log_penduduk log ON u.id = log.id_pend
		WHERE 1 ";

		$sql .= $this->syarat_dpt_sql();
		$sql .= $this->search_sql();
		$sql .= $this->filter_sql();
		$sql .= $this->sex_sql();
		$sql .= $this->dusun_sql();
		$sql .= $this->rw_sql();
		$sql .= $this->rt_sql();

		$kolom_kode = array(
			array('cacat','cacat_id'),
			array('cara_kb_id','cara_kb_id'),
			array('menahun','sakit_menahun_id'),
			array('status','status_kawin'),
			array('pendidikan_kk_id','pendidikan_kk_id'),
			array('pendidikan_sedang_id','pendidikan_sedang_id'),
			array('status_penduduk','status'),
			array('pekerjaan_id','pekerjaan_id'),
			array('agama','agama_id'),
			array('warganegara','warganegara_id'),
			array('golongan_darah','golongan_darah_id')
		);
		foreach ($kolom_kode as $kolom){
			$sql .= $this->get_sql_kolom_kode($kolom[0],$kolom[1]);
		}

		$sql .= $this->cacatx_sql();
		$sql .= $this->akta_kelahiran_sql();
		$sql .= $this->menahunx_sql();
		$sql .= $this->umur_min_sql();
		$sql .= $this->umur_max_sql();
		$sql .= $this->umur_sql();
		$sql .= $this->hamil_sql();

		return $sql;
	}

	function list_data($o=0,$offset=0,$limit=500){
		$select_sql = "SELECT DISTINCT u.id,u.nik,u.tanggallahir,u.tempatlahir,u.status,u.status_dasar,u.id_kk,u.nama,u.nama_ayah,u.nama_ibu,a.dusun,a.rw,a.rt,d.alamat,d.no_kk AS no_kk,
			(SELECT DATE_FORMAT(FROM_DAYS(TO_DAYS(NOW())-TO_DAYS(`tanggallahir`)), '%Y')+0 FROM tweb_penduduk WHERE id = u.id) AS umur,x.nama AS sex,sd.nama AS pendidikan_sedang,n.nama AS pendidikan,p.nama AS pekerjaan,k.nama AS kawin,g.nama AS agama,m.nama AS gol_darah,hub.nama AS hubungan
			";
		//Main Query
		$list_data_sql = $this->list_data_sql($log);
		$sql = $select_sql." ".$list_data_sql;

		//Ordering SQL
		switch($o){
			case 1: $order_sql = ' ORDER BY u.nik'; break;
			case 2: $order_sql = ' ORDER BY u.nik DESC'; break;
			case 3: $order_sql = ' ORDER BY u.nama'; break;
			case 4: $order_sql = ' ORDER BY u.nama DESC'; break;
			case 5: $order_sql = ' ORDER BY d.no_kk'; break;
			case 6: $order_sql = ' ORDER BY d.no_kk DESC'; break;
			case 7: $order_sql = ' ORDER BY umur'; break;
			case 8: $order_sql = ' ORDER BY umur DESC'; break;
			// Untuk Log Penduduk
			case 9: $order_sql = ' ORDER BY log.tgl_peristiwa'; break;
			case 10: $order_sql = ' ORDER BY log.tgl_peristiwa DESC'; break;
			default:$order_sql = '';
		}

		//Paging SQL
		$paging_sql = ' LIMIT ' .$offset. ',' .$limit;

		$sql .= $order_sql;
		$sql .= $paging_sql;

		$query = $this->db->query($sql);
		$data=$query->result_array();

		//Formating Output
		$i=0;
		$j=$offset;
		while($i<count($data)){

			// Ubah alamat penduduk lepas
			if (!$data[$i]['id_kk'] OR $data[$i]['id_kk'] == 0) {
				// Ambil alamat penduduk
				$sql = "SELECT p.id_cluster, p.alamat_sekarang, c.dusun, c.rw, c.rt
					FROM tweb_penduduk p
					LEFT JOIN tweb_wil_clusterdesa c on p.id_cluster = c.id
					WHERE p.id = ?
					";
				$query = $this->db->query($sql, $data[$i]['id']);
				$penduduk = $query->row_array();
				$data[$i]['alamat'] = $penduduk['alamat_sekarang'];
				$data[$i]['dusun'] = $penduduk['dusun'];
				$data[$i]['rw'] = $penduduk['rw'];
				$data[$i]['rt'] = $penduduk['rt'];
			}

			$data[$i]['no']=$j+1;

			$i++;
			$j++;
		}
		return $data;
	}

	function adv_search_proses(){
		UNSET($_POST['umur1']);
		UNSET($_POST['umur2']);

		UNSET($_POST['dusun']);
		UNSET($_POST['rt']);
		UNSET($_POST['rw']);
		$i=0;
		while($i++ < count($_POST)){
			$col[$i] = key($_POST);
				next($_POST);
		}
		$i=0;
		while($i++ < count($col)){
			if($_POST[$col[$i]]=="")
				UNSET($_POST[$col[$i]]);
		}

		$data=$_POST;
		$this->db->where($data);
		return  $this->db->get('tweb_penduduk');
	}

}