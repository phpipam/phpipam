<?php
/**
 * Print permission badge
 * @method get_badge
 * @param  int $level
 * @return string
 */
function get_badge ($level) {
    global $Subnets;
    // null level
    if(is_null($level)) $level = 0;
    // return
    return $level=="0" ? "<span class='badge badge1 badge5 alert-danger'>"._($Subnets->parse_permissions ($level))."</span>" : "<span class='badge badge1 badge5 alert-success'>"._($Subnets->parse_permissions ($level))."</span>";
}