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
	
	public $error = false;
	public $config = array("providers" => array());
	public $providers = array(
		"Facebook" => array(
			/* optionals */
			"scope" => "public_profile,email",
			//email,user_about_me,user_birthday,user_hometown
			"display" => "popup",
			"force" => true
		),
		"Google" => array(
			"scope" => "https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email",
			"force" => true
		),
		"LinkedIn" => array(
			"force" => true
		),
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
		$this->a_cipher = $this->wf->core_cipher();
		$this->a_session = $this->wf->session();
		$this->lang = $this->wf->core_lang()->get_context("hybridauth");
		$this->ts();
		
		/* dao */
		$this->wf->core_dao();
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
					"type" => WF_DATA,
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
		if($popup && $popup == "false") {
			unset($this->providers["Facebook"]["display"]);
			unset($this->config["providers"]["Facebook"]["display"]);
		}
		
		/* load the library */
		$this->lib_dir = dirname($this->wf->locate_file('deps/hybridauth/Hybrid/Auth.php'));
		$this->require_lib("Auth.php");
		$this->require_lib("thirdparty/OAuth/OAuth.php");
		//$logger = "$dir/Logger.php";
		//require_once($logger);	
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Loading
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	
	public function require_lib($fname) {
		return require_once($this->lib_dir."/$fname");
	}
	
	public function initialize($back = "") {
		
		if(!$back)
			$this->get_back_url();
		
		if($back)
			$this->config["providers"]["Google"]["state"] = $back;
		
		$this->error = $this->a_cipher->get_var("hybridauth_error");
		
		try {
			Hybrid_Auth::initialize($this->config);
		}
		catch(Exception $e) {
			$this->throw_error($e->getMessage());
		}
	}
	
	public function auth($api = "") {
		if(!$api)
			$api = $this->get_current_api();
		
		$back = $this->get_back_url();
		
		if($api != "Google" && $back)
			$this->config["base_url"] .= "?back=".$back;
		
		/* load auth library */
		try {
			$this->hybridauth = new Hybrid_Auth($this->config);
			$adapter = $this->hybridauth->authenticate($api);
			$user_profile = $adapter->getUserProfile();
			return $user_profile;
		}
		catch(Exception $e) {
			$this->throw_error($e->getMessage());
		}
	}
	
	/* add user to database after login */
	public function add_user($email, $user_profile) {
		$ret = $this->wf->execute_hook("hybridauth_adduser");
		$ret = end($ret);
		
		if(!is_callable($ret))
			throw new wf_exception("hybridauth_adduser hook does not return a callable");
		
		return $ret($email, $user_profile);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Session
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	
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
		$sdata_db = current($this->dao->get());
		if(!isset($sdata_db["hybridauth_session"]))
			return false;
		$hybridauth = new Hybrid_Auth($this->config);
		$hybridauth->restoreSessionData($sdata_db["hybridauth_session"]);
		return true;
	}
	
	public function logout() {
		$this->load();
		Hybrid_Auth::logoutAllProviders();
		$this->dao->remove();
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Return javascripts tags to include in a page
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	
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
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Getters
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	
	private $api = null;
	public function get_current_api($raw = false) {
		if(!$this->api) {
			$this->api = $this->wf->get_var("owfha_api");
			$api = $this->get_provider($this->api);
			if(is_array($api))
				$this->wf->display_error(500, $this->ts("API not supported"), true);
		}
		$api = $this->get_provider($this->api);
		return $raw ? $this->api : $api;
	}
	
	public function get_back_url() {
		$back = $this->a_cipher->get_var("back");
		if(!$back)
			$back = $this->a_cipher->get_var("state");
		
		return $back;
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Utilities
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	
	public function generate_session_id() {
		$s1 = $this->wf->get_rand();
		$s2 = $this->wf->get_rand();
		return("E".$this->wf->hash($s1).$this->wf->hash($s2));
	}
	
	public function get_provider($r = "") {
		$ret = array(
			OWF_HYBRIDAUTH_PROVIDER_FACEBOOK => "Facebook",
			OWF_HYBRIDAUTH_PROVIDER_GOOGLE => "Google",
			OWF_HYBRIDAUTH_PROVIDER_LINKEDIN => "LinkedIn",
			//"tw" => "Twitter",
		);
		return isset($ret[$r]) ? $ret[$r] : $ret;
	}
	
	public function throw_error($message) {
		$back = $this->get_back_url();
		if($back) {
			$err = $this->a_cipher->encode($message);
			$back .= strchr($back, '?') ? '&' : '?';
			$this->wf->redirector($back."hybridauth_error=".$err);
		}
		$this->wf->display_login($message);
		exit(0);
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
		return isset($ret[$text]) ? $ret[$text] : $this->lang->ts($text);
	}
}
