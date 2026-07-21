<?php
// authenticate
auth_reauthenticate();
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );
// Read results
$f_duedate_days_default = gpc_get_int( 'duedate_days_default', 7 );
$f_duedate_days_priority_10 = gpc_get_int( 'duedate_days_priority_10', 28 );
$f_duedate_days_priority_20 = gpc_get_int( 'duedate_days_priority_20', 21 );
$f_duedate_days_priority_30 = gpc_get_int( 'duedate_days_priority_30', 14 );
$f_duedate_days_priority_40 = gpc_get_int( 'duedate_days_priority_40', 7 );
$f_duedate_days_priority_50 = gpc_get_int( 'duedate_days_priority_50', 3 );
$f_duedate_days_priority_60 = gpc_get_int( 'duedate_days_priority_60', 1 );
$f_duedate_overrule			= gpc_get_int( 'duedate_overrule', OFF );
$f_duedate_skip				= gpc_get_int( 'duedate_skip', OFF );


// update results
plugin_config_set( 'duedate_days_default', $f_duedate_days_default );
plugin_config_set( 'duedate_days_priority_10', $f_duedate_days_priority_10 );
plugin_config_set( 'duedate_days_priority_20', $f_duedate_days_priority_20 );
plugin_config_set( 'duedate_days_priority_30', $f_duedate_days_priority_30 );
plugin_config_set( 'duedate_days_priority_40', $f_duedate_days_priority_40 );
plugin_config_set( 'duedate_days_priority_50', $f_duedate_days_priority_50 );
plugin_config_set( 'duedate_days_priority_60', $f_duedate_days_priority_60 );
plugin_config_set( 'duedate_overrule', $f_duedate_overrule );
plugin_config_set( 'duedate_skip', $f_duedate_skip );

// redirect
print_header_redirect( plugin_page( 'config',TRUE ) );