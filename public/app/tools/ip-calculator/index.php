<?php
# verify that user is logged in
$User->check_user_session();

# default load ip-calculator
if(!isset($GET->subnetId)) {
    $GET->subnetId = "ip-calculator";
}
?>

<h4><?php print _('IP and bandwidth calculator');?></h4>
<hr>

<!-- tabs -->
<ul class='nav nav-tabs' style='margin-bottom:20px;'>
    <li role='presentation' <?php if($GET->subnetId=="ip-calculator") print "class='active'"; ?>> <a href='<?php print create_link("tools", "ip-calculator", "ip-calculator"); ?>'><?php print _("IP calculator"); ?></a></li>
    <li role='presentation' <?php if($GET->subnetId=="bw-calculator") print "class='active'"; ?>> <a href='<?php print create_link("tools", "ip-calculator", "bw-calculator"); ?>'><?php print _("Bandwidth calculator"); ?></a></li>
</ul>

<!-- details -->
<?php
if($GET->subnetId=="ip-calculator") {
    include("ip-calculator.php");
}
elseif($GET->subnetId=="bw-calculator") {
    include("bw-calculator.php");
}
else {
    $Result->show("danger", _("Invalid request"), false);
}