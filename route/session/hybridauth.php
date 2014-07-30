<?php

class wfr_ppHybridauth_session_hybridauth extends wf_route_request {
	
	public function __construct($wf) {
		$this->wf = $wf;
		$this->a_session = $this->wf->session();
		$this->lang = $this->wf->core_lang()->get_context("tpl/hybridauth/login");
		$this->hybrid = $this->wf->hybridauth();
		$this->ts();
	}
	
	private function ts($text = "") {
		$ret = array(
			"API not supported" => $this->lang->ts("API not supported"),
			"You don't have any email address associated with this account" => $this->lang->ts("You don't have any email address associated with this account"),
			"Error creating your account" => $this->lang->ts("Error creating your account"),
			"Authentication failed" => $this->lang->ts("Authentication failed"),
		);
		if(!$text)
			return false;
		return isset($ret[$text]) ? $ret[$text] : $this->lang->ts($text);
	}
	
	public function login() {
		
		$back = $this->wf->core_cipher()->get_var("back");
		
		/* authenticate */
		$user_profile = $this->hybrid->auth();
		
		/* get the user email */
		$email = $user_profile->emailVerified;
		if(!$email) {
			$email = $user_profile->email;
		}
		if(!$email) {
			//$this->wf->display_error(500, $this->ts("You don't have any email address associated with this account"), true);
			$this->wf->display_login($this->ts("You don't have any email address associated with this account"));
			exit(0);
		}
		
		/* check if user exists */
		$user = current($this->a_session->user->get(array("email" => $email)));
		if(!$user) {
			/* create user */
			$uid = $this->hybrid->add_user($email, $user_profile);
			$user = current($this->a_session->user->get(array("id" => $uid)));
			
			if(!$user)
				$this->wf->display_error(500, $this->ts("Error creating your account"), true);
		}
		
		/* login user */
		$sessid = $this->hybrid->generate_session_id();
		$update = array(
			"session_id"        => $sessid,
			"session_time"      => time(),
			"session_time_auth" => time()
		);
		$this->a_session->user->modify($update, (int)$user["id"]);
		$this->a_session->setcookie(
			$this->a_session->session_var,
			$sessid,
			time() + $this->a_session->session_timeout
		);
		
		/* save hybridauth session */
		$this->hybrid->save();
		
		/* redirect */
		$redirect_url = $this->wf->linker('/');
		if(isset($uid))
			// redirect url should be editable
			$redirect_url = $this->wf->linker('/account/secure');
		$redirect_url = $back ? $back : $redirect_url;
		
		$this->wf->redirector($redirect_url);
	}
	
}

//object(Hybrid_User_Profile)#66 (24) {
//["identifier"]=> string(17) "10152508393079337" 
//["webSiteURL"]=> string(61) "Breath of War (carte Warcraft III) : http://bow.belboudou.com" 
//["profileURL"]=> string(62) "https://www.facebook.com/app_scoped_user_id/10152508393079337/" 
//["photoURL"]=> string(73) "https://graph.facebook.com/10152508393079337/picture?width=150&height=150" 
//["displayName"]=> string(16) "Olivier Leclercq" 
//["description"]=> string(0) "" 
//["region"]=> string(0) "" 
//["city"]=> NULL 
//["username"]=> string(0) "" 
//["coverInfoURL"]=> string(57) "https://graph.facebook.com/10152508393079337?fields=cover" } 
		
		///* get the user email */
		//$email = $user_profile->emailVerified;
		//if(!$email) {
			//$email = $user_profile->email;
		//}
		
		//if(!$email) {
			//$this->wf->display_error(500, $this->ts("You don't have any email associated with your account"));
		//}
		
		///* check if user exists */
		//$user = current($this->a_session->user->get(array("email" => $email)));
		//if(!$user) {
			///* create user */
			//$uid = $this->a_session->user->sc_register(
				//$user_profile->firstName,
				//$user_profile->lastName,
				//$email,
				//"",
				//"",
				//"true"
			//);
			//$user = current($this->a_session->user->get(array("id" => $uid)));
			
			//if(!$user)
				//$this->wf->display_error(500, $this->ts("Error creating your Slowcontrol account"));
			
			///* add some more user info */
			//$owffields = array(
				//"genre" => function($user_profile) {
					//if($user_profile->gender == "male")
						//return SESSION_USER_GENRE_MALE;
					//elseif($user_profile->gender == "female")
						//return SESSION_USER_GENRE_FEMALE;
					//return SESSION_USER_GENRE_OTHER;
				//},
				//"phone" => "phone",
				//"address" => "address",
				//"country" => "country",
				//"postal_code" => "zip",
				//"birthday" => function($user_profile) {
					//$dt = new DateTime();
					//$dt->setDate($user_profile->birthYear, $user_profile->birthMonth , $user_profile->birthDay);
					//return $dt && $dt->format("DD/MM/YYYY") == $user_profile->birthYear."/".$user_profile->birthMonth."/".$user_profile->birthDay;
				//}
			//);
			//$owfuser = array();
			//foreach($owffields as $owf => $hybrid) {
				//$var = is_callable($hybrid) ? $hybrid($user_profile) : $user_profile->$hybrid;
				//if($var) {
					//$owfuser[$owf] = $var;
				//}
			//}
			//$this->a_session->user->modify($owfuser, $uid);
		//}
		
		///* login user */
		//$sessid = $this->a_session->generate_session_id();
		//$update = array(
			//"session_id"        => $sessid,
			//"session_time"      => time(),
			//"session_time_auth" => time()
		//);
		//$this->a_session->user->modify($update, (int)$user["id"]);
		//$this->a_session->setcookie(
			//$this->a_session->session_var,
			//$sessid,
			//time() + $this->a_session->session_timeout
		//);
		
		///* redirect */
		//$redirect_url = $this->wf->linker('/');
		//if(isset($uid))
			//$redirect_url = $this->wf->linker('/account/secure');
		
		//$this->wf->redirector($redirect_url);
	//}
//}

//"lang" => WF_VARCHAR,
//"city" => WF_VARCHAR,
//"password_recovery" => WF_VARCHAR,
//"tz" => WF_VARCHAR,
//"picture" => WF_INT,
//"banner" => WF_INT,
//"device_type" => WF_INT,

//"username"			=> $uid,
//"activated"			=> $activated ? "true" : $this->generate_validation_code(),
//"create_time"		=> time(),
//"tz"				=> "Europe/Paris",
//"policy_world"		=> false,
//"policy_device"		=> SC_CORE_POLICY_ME,
//"policy_results"	=> SC_CORE_POLICY_NETWORK,
//"mails_forks"		=> true,
//"mails_meals"		=> true,
//"mails_network"		=> true,
//"mails_timeline"	=> true,
//"lang"				=> $this->wf->core_lang()->find_lang()

        //public $identifier = NULL;
        //public $profileURL = NULL;
        //public $photoURL = NULL;
        //public $displayName = NULL;
        //public $description = NULL;
        //public $language = NULL;
        //public $region = NULL;
        //public $city = NULL;
