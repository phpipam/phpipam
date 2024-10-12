<?php
if (!isset($User)) { exit(); }

/**
 *	print instructions
 **********************************************/

/* fetch instructions and print them in instructions div */
$instructions = $Tools->fetch_instructions(1);

// HSS header
header('X-XSS-Protection:1; mode=block');

if (!isset($i_am_a_widget))
    print '<h4>'. _('Instructions for managing IP addresses'). '</h4><hr>';

// Limit vertical height of instruction widget
$style = isset($i_am_a_widget) ? 'style="display: block; text-overflow: ellipsis; word-wrap: break-word; overflow: hidden; max-height: 16em; line-height: 1em;"' : '';

print '<div class="instructions well">';
print "<div $style>". $instructions. '</div>';
print '</div>';