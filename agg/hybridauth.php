<?php

class hybridauth extends wf_agg {
	
	public $lib_dir;
	public $config = array("providers" => array());
	public $providers = array(
		"Facebook" => array(
			"scope" => "email, user_about_me, user_birthday, user_hometown", // optional
			"display" => "popup" // optional
		)
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
					"keys" => array("id" => $ini[$p."_id"], "secret" => $ini[$p."_secret"])
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
	
}