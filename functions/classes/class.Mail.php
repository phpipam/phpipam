<?php

/**
*
*	phpipam Mail class to send mails
*
*	wrapper for phpmailer
*
*/

class phpipam_mail {

	/**
	 * public variables
	 */
	private $settings = null;						//(obj) phpipam settings
	private $mail_settings = null;					//(obj) mail settings

	/**
	 * private variables
	 */

	/**
	 * protected variables
	 */

	/**
	 * object holders
	 */
	public $Php_mailer;						//for Php mailer object






	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $settings
	 */
	public function __construct ($settings, $mail_settings) {
		# set settings and mailsettings
		$this->settings = $settings;
		$this->mail_settings= $mail_settings;
	}



	/**
	 * Initializes mailer object.
	 *
	 * @access public
	 * @return void
	 */
	public function initialize_mailer () {
		# we need phpmailer
		require_once( dirname(__FILE__) . '/../PHPMailer/PHPMailerAutoload.php');

		# initialize object
		$this->Php_mailer = new PHPMailer(true);			//localhost by default
		$this->Php_mailer->CharSet="UTF-8";					//set utf8
		$this->Php_mailer->SMTPDebug = 0;					//default no debugging

		# localhost or smtp?
		if($this->mail_settings->mtype=="smtp") 	{ $this->set_smtp(); }
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
		if($this->mail_settings->msecure!='none')
		$this->Php_mailer->SMTPSecure = $this->mail_settings->msecure=='ssl' ? 'ssl' : 'tls';
		//server
		$this->Php_mailer->Host = $this->mail_settings->mserver;
		$this->Php_mailer->Port = $this->mail_settings->mport;
		//permit self-signed certs and dont verify certs
		$this->Php_mailer->SMTPOptions = array("ssl"=>array("verify_peer"=>false, "verify_peer_name"=>false, "allow_self_signed"=>true));
		//set smtp auth
		$this->set_smtp_auth();
	}

	/**
	 * Set SMTP login parameters
	 *
	 * @access private
	 * @return void
	 */
	private function set_smtp_auth () {
		if($this->mail_settings->mauth=="yes") {
			$this->Php_mailer->SMTPAuth = true;
			$this->Php_mailer->Username = $this->mail_settings->muser;
			$this->Php_mailer->Password = $this->mail_settings->mpass;
		}
		else {
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
	public function override_settings ($override_settings) {
		foreach($override_settings as $k=>$s) {
			$this->mail_settings->$k = $s;
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
		$this->Php_mailer->SMTPDebug = $level==1 ? 1 : 2;
		// output
		$this->Php_mailer->Debugoutput = 'html';
	}








	/**
	 * Generates mail message
	 *
	 * @access public
	 * @param mixed $body
	 * @return void
	 */
	public function generate_message ($body) {
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
		$html[] = $body;						//set body
		$html[] = $this->set_footer_plain ();	//set footer
	}

	/**
	 * set_header function.
	 *
	 * @access private
	 * @return void
	 */
	private function set_header () {
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
	 * @return void
	 */
	private function set_body_start () {
		return "<body style='margin:0px;padding:0px;background:#f9f9f9;border-collapse:collapse;'>";
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
	 * @return void
	 */
	private function set_body_end () {
		return "</body></html>";
	}

	/**
	 * Sets footer
	 *
	 * @access public
	 * @return void
	 */
	public function set_footer () {
		$html[] = "<table style='margin-left:10px;margin-top:25px;width:auto;padding:0px;border-collapse:collapse;'>";
		$html[] = "<tr>";
		$html[] = "	<td><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'>E-mail</font></td>";
		$html[] = "	<td><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'><a href='mailto:".$this->settings->siteAdminMail."' style='color:#08c;'>".$this->settings->siteAdminName."</a></font></td>";
		$html[] = "</tr>";
		$html[] = "<tr>";
		$html[] = "	<td><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'>www</font></td>";
		$html[] = "	<td><font face='Helvetica, Verdana, Arial, sans-serif' style='font-size:13px;'><a href='".$this->settings->siteURL."' style='color:#08c;'>".$this->settings->siteURL."</a></font></td>";
		$html[] = "</tr>";
		$html[] = "</table>";
		# return
		return implode("\n", $html);
	}

	/**
	 * Sets plain footer
	 *
	 * @access public
	 * @return void
	 */
	public function set_footer_plain () {
		return "\r\n------------------------------\r\n".$this->settings->siteAdminName." (".$this->settings->siteAdminMail.") :: ".$this->settings->siteURL;
	}

}

?>