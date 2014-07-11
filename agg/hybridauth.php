<?php

class hybridauth extends wf_agg {
	
	public $lib_dir;
	public $config = array("providers" => array());
	public $providers = array(
		"Facebook" => array(
			/* optionals */
			"scope" => "public_profile,email",
			//email,user_about_me,user_birthday,user_hometown
			//"display" => "popup"
		),
		"Google" => array(
			/* optional */
			"scope" => "https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email",
		),
		"LinkedIn" => array(),
		"Twitter" => array (),
	);
	
	public function loader() {
		
		/* build configuration */
		$this->config["base_url"] = $this->wf->linker("/hybridauth", true);
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
				if(isset($data["scope"]))
					$this->config["providers"][$p]["scope"] = $data["scope"];
				if(isset($data["display"]))
					$this->config["providers"][$p]["display"] = $data["display"];
			}
		}
		
		/* load the library */
		$this->lib_dir = dirname($this->wf->locate_file('deps/hybridauth/Hybrid/Auth.php'));
		$this->require_lib("Auth.php");
		$this->require_lib("thirdparty/OAuth/OAuth.php");
		Hybrid_Auth::initialize($this->config);
		//$logger = "$dir/Logger.php";
		//require_once($logger);
	}
	
	public function require_lib($fname) {
		return require_once($this->lib_dir."/$fname");
	}
	
	public function get_login_tpl() {
		$tpl = new core_tpl($this->wf);
		$tpl->set("config", $this->config);
		return $tpl->fetch('hybridauth/login');
	}
	
	public function add_user($email, $user_profile) {
		$ret = $this->wf->execute_hook("hybridauth_adduser");
		$ret = end($ret);
		
		if(!is_callable($ret))
			throw new wf_exception("hybridauth_adduser hook does not return a callable");
		
		return $ret($email, $user_profile);
	}
	
	public function generate_session_id() {
		$s1 = $this->wf->get_rand();
		$s2 = $this->wf->get_rand();
		return("E".$this->wf->hash($s1).$this->wf->hash($s2));
	}
	
	public function get_supported_providers($r = "") {
		$ret = array(
			"fb" => "Facebook",
			"gplus" => "Google",
			"li" => "LinkedIn",
			//"tw" => "Twitter",
		);
		return isset($ret[$r]) ? $ret[$r] : $ret;
	}
}
