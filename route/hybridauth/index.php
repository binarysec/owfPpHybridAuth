<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

// ------------------------------------------------------------------------
//	HybridAuth End Point
// ------------------------------------------------------------------------

class wfr_ppHybridauth_hybridauth_index extends wf_route_request {
	
	public function __construct($wf) {
		$this->wf = $wf;
		$this->hybrid = $this->wf->hybridauth();
	}
	
	public function index() {
		$this->hybrid->require_lib("Endpoint.php");
		Hybrid_Endpoint::process();
	}
	
}
