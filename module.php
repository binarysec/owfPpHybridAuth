<?php

class wfm_ppHybridauth extends wf_module {
	public function __construct($wf) {
		$this->wf = $wf;
	}
	
	public function get_name() { return("ppHybridauth"); }
	public function get_description()  { return("https://github.com/hybridauth/hybridauth"); }
	public function get_banner()  { return("hybridauth/2.2.0"); }
	public function get_version() { return("2.2.0"); }
	public function get_authors() { return("hybridauth"); }
	public function get_depends() { return(array("session")); }
	
	public function get_actions() {
		return(array(
			"/hybridauth" => array(
				WF_ROUTE_REDIRECT,
				"/hybridauth/index.php",
				"",
				WF_ROUTE_HIDE,
				array("session:ranon")
			),
			"/hybridauth/index.php" => array(
				WF_ROUTE_ACTION,
				"hybridauth/index",
				"index",
				"",
				WF_ROUTE_HIDE,
				array("session:ranon")
			),
			"/session/hybridauth" => array(
				WF_ROUTE_ACTION,
				"session/hybridauth",
				"login",
				"",
				WF_ROUTE_HIDE,
				array("session:ranon")
			),
		));
	}
}
