<?php

/**
 *	Generate XLS file
 *********************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');
require( dirname(__FILE__) . '/../../../functions/PEAR/Spreadsheet/Excel/Writer.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();



// Create a workbook
$filename = "phpipam_logs_export_". date("Y-m-d") .".xls";
$workbook = new Spreadsheet_Excel_Writer();

//increase memory size
ini_set('memory_limit', '1024M');

//fetch sections, and for each section write new tab, inside tab write all values!
$logs = $Admin->fetch_all_objects ("logs", "id");

//formatting headers
$format_header =& $workbook->addFormat();
$format_header->setBold();
$format_header->setColor('white');
$format_header->setFgColor('black');

//formatting titles
$format_title =& $workbook->addFormat();
$format_title->setColor('black');
$format_title->setFgColor(22);			//light gray
$format_title->setBottom(2);
$format_title->setLeft(1);
$format_title->setRight(1);
$format_title->setTop(1);
$format_title->setAlign('left');

//formatting content - borders around IP addresses
$format_right =& $workbook->addFormat();
$format_right->setRight(1);
$format_left =& $workbook->addFormat();
$format_left->setLeft(1);
$format_top =& $workbook->addFormat();
$format_top->setTop(1);



// Create a worksheet
$worksheet =& $workbook->addWorksheet('phpipam logs');

$lineCount = 0;
//Write titles

//write headers
$worksheet->write($lineCount, 0, _('id'),$format_title);
$worksheet->write($lineCount, 1, _('Severity'),$format_title);
$worksheet->write($lineCount, 2, _('Date'),$format_title);
$worksheet->write($lineCount, 3, _('Username'),$format_title);
$worksheet->write($lineCount, 4, _('Command'),$format_title);
$worksheet->write($lineCount, 5, _('Details'),$format_title);

$lineCount++;

foreach ($logs as $log) {
	//cast
	$log = (array) $log;

	//we need to reformat severity!
	switch($log['severity']) {
		case 0: $log['severity'] = _("Informational");	break;
		case 1: $log['severity'] = _("Warning");		break;
		case 2: $log['severity'] = _("Critical");		break;
	}

	//remove breaks in details
	$log['details'] = str_replace("<br>", "\n", $log['details']);

	$worksheet->write($lineCount, 0, $log['id'], $format_left);
	$worksheet->write($lineCount, 1, $log['severity']);
	$worksheet->write($lineCount, 2, $log['date']);
	$worksheet->write($lineCount, 3, $log['username']);
	$worksheet->write($lineCount, 4, $log['command']);
	$worksheet->write($lineCount, 5, $log['details'], $format_right);

	$lineCount++;

}


//top border line at bottom of IP addresses
$worksheet->write($lineCount, 0, "", $format_top);
$worksheet->write($lineCount, 1, "", $format_top);
$worksheet->write($lineCount, 2, "", $format_top);
$worksheet->write($lineCount, 3, "", $format_top);
$worksheet->write($lineCount, 4, "", $format_top);
$worksheet->write($lineCount, 5, "", $format_top);


// sending HTTP headers
$workbook->send($filename);

// Let's send the file
$workbook->close();
?>