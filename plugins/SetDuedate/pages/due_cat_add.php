<?PHP
# what is the table
$table	= gpc_get_string( 'table' );
# Adding definition
$category	= $_REQUEST['sel_cat'];
$days10 		= $_REQUEST['days10'];
$days20 		= $_REQUEST['days20'];
$days30 		= $_REQUEST['days30'];
$days40 		= $_REQUEST['days40'];
$days50 		= $_REQUEST['days50'];
$days60 		= $_REQUEST['days60'];
$query = "INSERT INTO $table ( id,days10,days20,days30,days40,days50,days60 ) VALUES (  '$category','$days10','$days20','$days30','$days40','$days50','$days60')";
if(!db_query($query)){ 
	trigger_error( 'ERROR_DB_QUERY_FAILED', ERROR );
}
$link="/plugin.php?page=SetDuedate/cat_definition";
print_header_redirect( $link);