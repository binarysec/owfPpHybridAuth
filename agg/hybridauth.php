<?php

define("OWF_HYBRIDAUTH_PROVIDER_FACEBOOK",	"fb");
define("OWF_HYBRIDAUTH_PROVIDER_GOOGLE",	"gplus");
define("OWF_HYBRIDAUTH_PROVIDER_LINKEDIN",	"li");

class hybridauth_dao extends core_dao_form_db {
	
	public function add($data) {
		$data["user_id"] = intval($this->wf->session()->session_me["id"]);
		$data["update_time"] = time();
		return parent::add($data);
	}
	
	public function modify($where, $data) {
		$where["user_id"] = intval($this->wf->session()->session_me["id"]);
		$data["update_time"] = time();
		return parent::modify($where, $data);
	}
	
	public function remove($where = array()) {
		$where["user_id"] = intval($this->wf->session()->session_me["id"]);
		return parent::remove($where);
	}
	
	public function get($where = NULL, $order = NULL, $limit = -1, $offset = -1) {
		$where["user_id"] = intval($this->wf->session()->session_me["id"]);
		return parent::get($where, $order, $limit, $offset);
	}
	
}

class hybridauth extends wf_agg {
	
	public $lib_dir;
	public $config = array("providers" => array());
	public $providers = array(
		"Facebook" => array(
			/* optionals */
			"scope" => "public_profile,email",
			//email,user_about_me,user_birthday,user_hometown
			"display" => "popup"
		),
		"Google" => array(
			"scope" => "https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email",
		),
		"LinkedIn" => array(),
		"Twitter" => array (),
		//"Live" => array (),
		//"Yahoo" => array (),
		//"MySpace" => array (),
		//"OpenID" => array (
			//"enabled" => true
		//),
		//"AOL"  => array ( 
			//"enabled" => true 
		//),
	);
	
	public function loader() {
		
		/* dao table */
		$this->wf->core_dao();
		$this->a_session = $this->wf->session();
		$this->lang = $this->wf->core_lang()->get_context("hybridauth");
		$this->ts();
		$this->struct = array(
			"form" => array(
				"perm" => array("session:admin"),
			),
			"data" => array(
				"id" => array(
					"type" => WF_PRI,
				),
				"user_id" => array(
					"type" => WF_INT,
					"perm" => array("session:admin"),
					"name" => $this->lang->ts("User"),
					"kind" => OWF_DAO_LINK_MANY_TO_ONE,
					"dao" => array($this->a_session->user, "get"),
					"field-id" => "id",
					"field-name" => "email",
				),
				"hybridauth_session" => array(
					"type" => WF_VARCHAR,
					"perm" => array("session:admin"),
					"name" => $this->lang->ts("Hybridauth session"),
					"kind" => OWF_DAO_INPUT,
				),
				"update_time" => array(
					"type" => WF_BIGINT,
					"perm" => array("session:admin"),
					"name" => $this->lang->ts("Update time"),
					"kind" => OWF_DAO_DATETIME
				),
			),
		);
			
		$this->dao = new hybridauth_dao(
			$this->wf,
			'session_hybridauth',
			OWF_DAO_FORBIDDEN,
			$this->struct,
			'session_hybridauth',
			"Hybridauth session table"
		);
		
		/* build configuration */
		$this->config["base_url"] = $this->wf->linker("/hybridauth/", true);
		$this->config["debug_mode"] = false;
		$this->config["debug_file"] = "";
		
		/* build providers configuration from ini file */
		$ini = &$this->wf->ini_arr["hybridauth"];
		foreach($this->providers as $p => $data) {
			if(isset($ini[$p."_id"], $ini[$p."_secret"])) {
				$this->config["providers"][$p] = array(
					"enabled" => true,
					"keys" => array("id" => $ini[$p."_id"], "key" => $ini[$p."_id"], "secret" => $ini[$p."_secret"])
				);
				
				if(isset($ini[$p."_scope"]))
					$this->config["providers"][$p]["scope"] = $ini[$p."_scope"];
				elseif(isset($data["scope"]))
					$this->config["providers"][$p]["scope"] = $data["scope"];
				if(isset($data["display"]))
					$this->config["providers"][$p]["display"] = $data["display"];
			}
		}
		
		$popup = $this->wf->get_var("popup");
		if($popup && $popup == "false")
			unset($this->providers["Facebook"]["display"]);
		
		/* load the library */
		$this->lib_dir = dirname($this->wf->locate_file('deps/hybridauth/Hybrid/Auth.php'));
		$this->require_lib("Auth.php");
		$this->require_lib("thirdparty/OAuth/OAuth.php");
		//$logger = "$dir/Logger.php";
		//require_once($logger);
		
		try {
			Hybrid_Auth::initialize($this->config);
		}
		catch(Exception $e) {
			$this->wf->display_login($e->getMessage());
			exit(0);
		}	
	}
	
	/* load an api to use */
	public function loadapi($api = "") {
		/* sanatize API */
		if(!$api)
			$api = $this->wf->get_var("owfha_api");
		
		$apiname = $this->get_supported_providers($api);
		if(is_array($apiname))
			$this->wf->display_error(500, $this->ts("API not supported"), true);
		
		return $apiname;
	}
	
	/* try to authenticate */
	public function auth($api = "") {
		if(!$api)
			$api = $this->loadapi();
		
		$back = $this->wf->core_cipher()->get_var("back");
		
		/* load auth library */
		try {
			$this->hybridauth = new Hybrid_Auth($this->config);
			$adapter = $this->hybridauth->authenticate($api);
			$user_profile = $adapter->getUserProfile();
			return $user_profile;
		}
		catch(Exception $e) {
			if($back)
				return $this->redirector($back);
			else
				$this->wf->display_login($e->getMessage());
			exit(0);
		}
	}
	
	public function save() {
		if(!$this->hybridauth)
			return false;
		
		$sdata = $this->hybridauth->getSessionData();
		$sdata_db = current($this->dao->get(array("hybridauth_session" => $sdata)));
		if($sdata_db)
			$this->dao->modify(array("id" => $sdata_db["id"]), array("hybridauth_session" => $sdata));
		else
			$this->dao->add(array("id" => $sdata_db["id"], "hybridauth_session" => $sdata));
		return true;
	}
	
	public function load() {
		$sdata_db = current($this->dao->get(array()));
		if(!$sdata_db["hybridauth_session"])
			return false;
		$hybridauth = new Hybrid_Auth($this->config);
		$hybridauth->restoreSessionData($sdata_db["hybridauth_session"]);
		return true;
	}
	
	/* get javascript */
	public function get_javascript() {
		
		/* sanatize */
		$conf = $this->config;
		foreach($conf["providers"] as $name => $data)
			unset($conf["providers"][$name]["keys"]["secret"]);
		
		$config = json_encode($conf);
		$rootUrl = $this->wf->linker("/");
		$traductions = json_encode($this->ts());
		
		$js =	'<script type="text/javascript" src="'.$this->wf->linker("/data/hybridauth/js/app.js").'"></script>'.
				'<script type="text/javascript">hybridauth.preInit('.$config.', "'.$rootUrl.'", '.$traductions.');</script>';
				
		return $js;
	}
	//deprecated
	public function get_login_tpl() { return $this->get_javascript(); }
	
	/* utilities */
	public function require_lib($fname) {
		return require_once($this->lib_dir."/$fname");
	}
	
	/* add user to database after login */
	public function add_user($email, $user_profile) {
		$ret = $this->wf->execute_hook("hybridauth_adduser");
		$ret = end($ret);
		
		if(!is_callable($ret))
			throw new wf_exception("hybridauth_adduser hook does not return a callable");
		
		return $ret($email, $user_profile);
	}
	
	/* generate the session_id */
	public function generate_session_id() {
		$s1 = $this->wf->get_rand();
		$s2 = $this->wf->get_rand();
		return("E".$this->wf->hash($s1).$this->wf->hash($s2));
	}
	
	/* return a list of supported providers */
	public function get_supported_providers($r = "") {
		$ret = array(
			OWF_HYBRIDAUTH_PROVIDER_FACEBOOK => "Facebook",
			OWF_HYBRIDAUTH_PROVIDER_GOOGLE => "Google",
			OWF_HYBRIDAUTH_PROVIDER_LINKEDIN => "LinkedIn",
			//"tw" => "Twitter",
		);
		return isset($ret[$r]) ? $ret[$r] : $ret;
	}
	
	private function ts($text = null) {
		$ret = array(
			"This library is not loaded" => $this->lang->ts("This library is not loaded"),
			"This library is loading, please wait" => $this->lang->ts("This library is loading, please wait"),
			"Facebook login : please also log into this app." => $this->lang->ts("Facebook login : please also log into this app."),
			"Facebook login : please log." => $this->lang->ts("Facebook login : please log."),
			"Google login error : wrong origin URL" => $this->lang->ts("Google login error : wrong origin URL"),
			"Google login : There was an error: " => $this->lang->ts("Google login : There was an error: "),
		);
		return isset($ret[$text]) ? $ret[$text] : $ret;
	}
}
