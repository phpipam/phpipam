<?php

/**
 * Script to verify database structure
 *
 *	should be used from CLI in case login is impossible
 *
 ****************************************/

# functions
require_once( dirname(__FILE__) . '/../functions.php' );

# Classes
$Database = new Database_PDO;
$Tools = new Tools ($Database);

# title
print "\n".'Database structure verification'. "\n--------------------\n";

# check for errors
$errors = $Tools->verify_database();

# print result
if( (!isset($errors['tableError'])) && (!isset($errors['fieldError'])) ) {
	print 'All tables and fields are installed properly'. "\n";
}
else {
	# missing tables
	if (isset($errors['tableError'])) {
		print 'Missing tables:'. "\n";

		foreach ($errors['tableError'] as $table) {
			print " - ".$table."\n";
		}
	}

	# missing fields
	if (isset($errors['fieldError'])) {
		print "\n".'Missing fields'. "\n";

		foreach ($errors['fieldError'] as $table=>$field) {
			print 'Table `'. $table .'`: missing field `'. $field .'`;'."\n";
		}
	}
}
print "\n";
?>