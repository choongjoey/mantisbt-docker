<?PHP
$reqVar = '_' . $_SERVER['REQUEST_METHOD'];
$form_vars = $$reqVar;
$delete_id = $form_vars['delete_id'] ;
$table = $form_vars['table'] ;
require_once( '../../../core.php' );
# Deleting definition
$query = "DELETE FROM $table WHERE id = $delete_id";        
db_query($query);
$link="/plugin.php?page=SetDuedate/cat_definition";
print_header_redirect( $link);