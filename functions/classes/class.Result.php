<?php

/**
 *
 *	Class for printing outputs and saving logs to database
 *
 *	Severity indexes:
 *		0 = success
 *		1 = warning
 *		2 = error
 *
 */

class Result extends Common_functions {

	/* exit methods - to override for api */
	public $exit_method = "result";			//what to do when failed - result shows result, exception throws exception (for API)

	/**
	 * show result
	 *
	 * @param  [type]  $uclass [result class - danger, success, warning, info]
	 * @param  [type]  $utext  [text to display]
	 * @param  boolean $die    [controls stop of php execution]
	 * @return [type]          [description]
	 */
	public function show($uclass="muted", $utext="No value provided", $die=false, $popup=false, $inline = false) {

		# override for api !
		if($this->exit_method == "exception")  {
			# ok, just return success
			if ($uclass=="success") 	{ return true; }
			else						{ return $this->throw_exception ($utext); }
		}
		else {
			# array
			if(is_array($utext)) {
				if(sizeof( $utext )==1) {							# single value
					$utext1 = $utext;
				}
				else {												# multiple values
					$utext1[] = "Errors:";
					$utext1[] = "<ul>";
					foreach( $utext as $l ) { $utext1[] = "<li>$l</li>"; }
					$utext1[] = "</ul>";
				}
				// join
				$utext = implode("\n", $utext1);
			}

			# print popup or normal
			if($inline) {
				return "<div class='alert alert-".$uclass."'>".$utext."</div>";
			}
			elseif(!$popup) {
				print "<div class='alert alert-".$uclass."'>".$utext."</div>";
			}
			else {
				print '<div class="pHeader">'._(ucwords($uclass)).'</div>';
				print '<div class="pContent">';
				print '<div class="alert alert-'.$uclass.'">'.$utext.'</div>';
				print '</div>';
				print '<div class="pFooter"><button class="btn btn-sm btn-default hidePopups">'._('Close').'</button></div>';
			}

			# die if set
			if($die)	die();
		}
	}

	/**
	 * Shows result for cli functions
	 *
	 * @access public
	 * @param string $utext (default: "No value provided")
	 * @param bool $die (default: false)
	 * @return void
	 */
	public function show_cli ($utext="No value provided", $die=false) {
		# text
		if(!is_array( $utext )) {}
		# array
		elseif( is_array( $utext ) && sizeof( $utext )>0) {
			if(sizeof( $utext )==1) {								# single value
				$utext = $utext[0];
			} else {												# multiple values
				foreach( $utext as $l ) { $utext .= "\t* $l\n"; }
			}
		}

		# print
		print "Error:\n";
		print $utext;

		# die if set
		if($die)	die();
	}

	/**
	 * Exists with exception
	 *
	 * @access public
	 * @param mixed $content
	 * @return void
	 */
	public function throw_exception ($content) {
		// include Exceptions class for API
		include_once( dirname(__FILE__) . '../../../api/v2/controllers/Responses.php' );
		// initialize exceptions
		$Exceptions = new Responses ();
		// throw error
		$Exceptions->throw_exception(500, $content);
	}
}

?>