<?php

/* prints all subnets in section */

# user must be authenticated
$User->check_user_session ();

# must be numeric
if(!is_numeric($GET->section))	{ $Result->show("danger", _('Invalid ID'), true); }

$section = $Sections->fetch_section (null, $GET->section);

# title
print "<h4>"._('Available subnets')."</h4>";
print $Sections->print_section_subnets_table($User, $GET->section, $section->showSupernetOnly);

# check Available subnets for subsection
$subsections = $Sections->fetch_subsections($GET->section);

# subsection subnets
if(is_array($subsections)) {
    foreach($subsections as $ss) {
        print "<br><br><h4>"._('Available subnets in subsection')." $ss->name [$ss->description]</h4>";
        print $Sections->print_section_subnets_table($User, $ss->id, $ss->showSupernetOnly);
    }
}