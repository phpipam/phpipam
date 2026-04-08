<?php

/**
*
*	phpipam Mail class to send mails
*
*	wrapper for phpmailer
*
*/

class phpipam_mail extends Common_functions {

	/**
	 * (obj) mail settings
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access private
	 */
	private $mail_settings = null;

	/**
	 * Php_mailer object
	 *
	 * @var mixed
	 * @access public
	 */
	public $Php_mailer;






	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $settings
	 */
	public function __construct ($settings, $mail_settings) {
		# set settings and mailsettings
		$this->settings = $settings;
		$this->mail_settings = $mail_settings;

		# we need phpmailer
		if(file_exists(dirname(__FILE__).'/../PHPMailer/PHPMailerAutoload.php')) {
			// legacy versions
			require_once( dirname(__FILE__).'/../PHPMailer/PHPMailerAutoload.php');

			# initialize object
			$this->Php_mailer = new PHPMailer(true);			//localhost by default
		}
		elseif (file_exists(dirname(__FILE__).'/../PHPMailer/src/Exception.php')) {
			require_once( dirname(__FILE__).'/../PHPMailer/src/Exception.php');
			require_once( dirname(__FILE__).'/../PHPMailer/src/PHPMailer.php');
			require_once( dirname(__FILE__).'/../PHPMailer/src/SMTP.php');

			$this->Php_mailer = new PHPMailer\PHPMailer\PHPMailer();
		} else {
			throw new Exception(_('PHPMailer submodule is missing.'));
		}

		$this->Php_mailer->CharSet="UTF-8";					//set utf8
		$this->Php_mailer->SMTPDebug = 0;					//default no debugging

		# localhost or smtp?
		if ($this->mail_settings->mtype=="smtp")    { $this->set_smtp(); }
	}

	/**
	 * Sets SMTP parameters
	 *
	 * @access private
	 * @return void
	 */
	private function set_smtp() {
		//set smtp
		$this->Php_mailer->isSMTP();
		//tls, ssl?
		if($this->mail_settings->msecure!='none') {
			$this->Php_mailer->SMTPAutoTLS = true;
			$this->Php_mailer->SMTPSecure = $this->mail_settings->msecure=='ssl' ? 'ssl' : 'tls';
		}
		else {
			$this->Php_mailer->SMTPAutoTLS = false;
			$this->Php_mailer->SMTPSecure = '';
		}
		//server
		$this->Php_mailer->Host = $this->mail_settings->mserver;
		$this->Php_mailer->Port = $this->mail_settings->mport;
		//permit self-signed certs and don't verify certs
		$this->Php_mailer->SMTPOptions = array("ssl"=>array("verify_peer"=>false, "verify_peer_name"=>false, "allow_self_signed"=>true));
		// uncomment this to disable AUTOTLS if security is set to none
		$this->Php_mailer->SMTPAutoTLS = false;
		//set smtp auth
		$this->set_smtp_auth();
	}

	/**
	 * Set SMTP login parameters
	 *
	 * @access private
	 * @return void
	 */
	private function set_smtp_auth() {
		if ($this->mail_settings->mauth == "yes") {
			$this->Php_mailer->SMTPAuth = true;
			$this->Php_mailer->Username = $this->mail_settings->muser;
			$this->Php_mailer->Password = $this->mail_settings->mpass;
		} else {
			$this->Php_mailer->SMTPAuth = false;
		}
	}

	/**
	 * Overrides mail settings in database. For sending test emails.
	 *
	 * @access public
	 * @param mixed $override_settings
	 * @return void
	 */
	public function override_settings($override_settings) {
		foreach ($override_settings as $k=>$s) {
			$this->mail_settings->{$k} = $s;
		}
	}

	/**
	 * Resets SMTP debugging
	 *
	 * @access public
	 * @param int $level (default: 2)
	 * @return void
	 */
	public function set_debugging ($level = 2) {
		$this->Php_mailer->SMTPDebug = $level == 1 ? 1 : 2;
		// output
		$this->Php_mailer->Debugoutput = 'html';
	}








	/**
	 * Generates mail message
	 *
	 * @access public
	 * @param string $body
	 * @return string
	 */
	public function generate_message ($body) {
    	$html = array();
		$html[] = $this->set_header ();			//set header
		$html[] = $this->set_body_start ();		//start body
		$html[] = $body;						//set body
		$html[] = $this->set_footer ();			//set footer
		$html[] = $this->set_body_end ();		//end
		# return
		return implode("\n", $html);
	}

	/**
	 * Generates plain text mail
	 *
	 * @access public
	 * @param mixed $body
	 * @return void
	 */
	public function generate_message_plain ($body) {
    	$html = array();
		$html[] = $body;						//set body
		$html[] = $this->set_footer_plain ();	//set footer
	}

	/**
	 * set_header function.
	 *
	 * @access private
	 * @return string
	 */
	private function set_header () {
    	$html = array();
		$html[] = "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>";
		$html[] = "<html><head>";
		$html[] = "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
		$html[] = "<meta name='viewport' content='width=device-width, initial-scale=0.7, maximum-scale=1, user-scalable=no'>";
		$html[] = "</head>";
		# return
		return implode("\n", $html);
	}

	/**
	 * Begins message body
	 *
	 * @access private
	 * @return string
	 */
	private function set_body_start () {
		# read config
		$config = Config::ValueOf('config');

		// set width
		$logo_width = isset($config['logo_width']) ? $config['logo_width'] : 220;

    	$html = array();
    	$html[] = "<body style='margin:0px;padding:0px;background:#f9f9f9;border-collapse:collapse;'>";
    	# logo
    	if(!file_exists( dirname(__FILE__)."/../../css/images/logo/logo.png")) {
			$img = ''; // Load built-in image
			require( dirname(__FILE__).'/../../app/admin/settings/logo/logo-builtin.php' );
			$html[] = $img;
		}
		else {
			$html[] = "<img style='max-width:".$logo_width."px;margin-top:15px;margin-bottom:20px;' alt='phpipam' src='data:image/png;base64,".base64_encode(file_get_contents(dirname(__FILE__)."/../../css/images/logo/logo.png"))."'>";
		}

		# return
		return implode("\n", $html);
	}

	/**
	 * Sets message body
	 *
	 * @access public
	 * @param mixed $body
	 * @return void
	 */
	public function set_body ($body) {
		return is_array($body) ? implode("\n", $body) : $body;
	}

	/**
	 * ends message body and html
	 *
	 * @access private
	 * @return string
	 */
	private function set_body_end () {
		return "</body></html>";
	}

	/**
	 * Sets footer
	 *
	 * @access public
	 * @return string
	 */
	public function set_footer () {
    	$html = array();
		$html[] = "<hr style='margin-left:10px;width:300px;height:0px;margin-top:40px;margin-left:0px;border-top:0px;border-bottom:1px solid #ddd;'>";
		$html[] = "<div class='padding-left:10px;'>";
		$html[] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; $this->mail_font_style_light "._("This email was automatically generated. You can change your notification settings in account details")."!</font><br>";
		$html[] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a href='".$this->settings->siteURL."' font-size:'11px;'>$this->mail_font_style_href ".$this->settings->siteURL."</font></a><br>";
		$html[] = "</div>";

		# return
		return implode("\n", $html);
	}

	/**
	 * Sets plain footer
	 *
	 * @access public
	 * @return string
	 */
	public function set_footer_plain () {
		return "\r\n------------------------------\r\n".$this->settings->siteAdminName." (".$this->settings->siteAdminMail.") :: ".$this->settings->siteURL;
	}

}
