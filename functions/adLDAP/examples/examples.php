<?
/*
Examples file

To test any of the functions, just change the 0 to a 1.
*/

//error_reporting(E_ALL ^ E_NOTICE);

include (dirname(__FILE__) . "/../src/adLDAP.php");
try {
    $adldap = new adLDAP($options);
}
catch (adLDAPException $e) {
    echo $e;
    exit();   
}
//var_dump($ldap);

echo ("<pre>\n");

// authenticate a username/password
if (0) {
	$result = $adldap->authenticate("username", "password");
	var_dump($result);
}

// add a group to a group
if (0) {
	$result = $adldap->group()->addGroup("Parent Group Name", "Child Group Name");
	var_dump($result);
}

// add a user to a group
if (0) {
	$result = $adldap->group()->addUser("Group Name", "username");
	var_dump($result);
}

// create a group
if (0) {
	$attributes=array(
		"group_name"=>"Test Group",
		"description"=>"Just Testing",
		"container"=>array("Groups","A Container"),
	);
	$result = $adldap->group()->create($attributes);
	var_dump($result);
}

// retrieve information about a group
if (0) {
    // Raw data array returned
	$result = $adldap->group()->info("Group Name");
	var_dump($result);
}

// create a user account
if (0) {
	$attributes=array(
		"username"=>"freds",
		"logon_name"=>"freds@mydomain.local",
		"firstname"=>"Fred",
		"surname"=>"Smith",
		"company"=>"My Company",
		"department"=>"My Department",
		"email"=>"freds@mydomain.local",
		"container"=>array("Container Parent","Container Child"),
		"enabled"=>1,
		"password"=>"Password123",
	);
	
    try {
    	$result = $adldap->user()->create($attributes);
	    var_dump($result);
    }
    catch (adLDAPException $e) {
        echo $e;
        exit();   
    }
}

// retrieve the group membership for a user
if (0) {
	$result = $adldap->user()->groups("username");
	print_r($result);
}

// retrieve information about a user
if (0) {
    // Raw data array returned
	$result = $adldap->user()->info("username");
	print_r($result);
}

// check if a user is a member of a group
if (0) {
	$result = $adldap->user()->inGroup("username","Group Name");
	var_dump($result);
}

// modify a user account (this example will set "user must change password at next logon")
if (0) {
	$attributes=array(
		"change_password"=>1,
	);
	$result = $adldap->user()->modify("username",$attributes);
	var_dump($result);
}

// change the password of a user. It must meet your domain's password policy
if (0) {
    try {
        $result = $adldap->user()->password("username","Password123");
        var_dump($result);
    }
    catch (adLDAPException $e) {
        echo $e; 
        exit();   
    }
}

// see a user's last logon time
if (0) {
    try {
        $result = $adldap->user()->getLastLogon("username");
        var_dump(date('Y-m-d H:i:s', $result));
    }
    catch (adLDAPException $e) {
        echo $e; 
        exit();   
    }
}

// list the contents of the Users OU
if (0) {
    $result=$adldap->folder()->listing(array('Users'), adLDAP::ADLDAP_FOLDER, false);
    var_dump ($result);   
}
?>