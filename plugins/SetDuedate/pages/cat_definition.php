<?php
auth_reauthenticate();
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );
layout_page_header( lang_get( 'plugin_format_title' ) );
layout_page_begin();

print_manage_menu();
$link=plugin_page('config');
$cat_table= db_get_table( 'category' );
$prj_table= db_get_table( 'project' );
$cat_def_table	= plugin_table('defined');
?>
<div class="col-md-12 col-xs-12">
<div class="space-10"></div>
<div class="form-container" > 
<br/>
<form action="<?php echo plugin_page( 'due_cat_add' ) ?>" method="post">
<div class="widget-box widget-color-blue2">
<div class="widget-header widget-header-small">
	<h4 class="widget-title lighter">
		<i class="ace-icon fa fa-text-width"></i>
		<?php echo lang_get( 'plugin_format_title' ) . ': ' . lang_get( 'plugin_format_config' )?>
	</h4>
</div>
<div class="widget-body">
<div class="widget-main no-padding">
<div class="table-responsive"> 
<table class="table table-bordered table-condensed table-striped"> 
<input type="hidden" name="table" value="<?php echo $cat_def_table;  ?>">

<tr>
<td class="form-title" colspan="8">
<a href="<?php echo $link ?>"><?php echo lang_get( 'prio_definition' ) ?></a>
</td>
</tr>
<br>
<tr class="row-category">
<td></td>
<td><div align="center"><?php echo lang_get( 'cat_title' ); ?></div>
<td><?php echo MantisEnum::getLabel( lang_get( 'priority_enum_string' ), 10); ?></td>
<td><?php echo MantisEnum::getLabel( lang_get( 'priority_enum_string' ), 20); ?></td>
<td><?php echo MantisEnum::getLabel( lang_get( 'priority_enum_string' ), 30); ?></td>
<td><?php echo MantisEnum::getLabel( lang_get( 'priority_enum_string' ), 40); ?></td>
<td><?php echo MantisEnum::getLabel( lang_get( 'priority_enum_string' ), 50); ?></td>
<td><?php echo MantisEnum::getLabel( lang_get( 'priority_enum_string' ), 60); ?></td>
<td>&nbsp;</td>
</tr
<tr >

<td></td>
<td>
<?php
$query="select a.id,b.name as prjname,a.name as catname from $cat_table as a,$prj_table as b where a.project_id=b.id order by prjname,catname";
$result=db_query($query);
echo '<select name="sel_cat">';
while ($row1= db_fetch_array($result)){
	$projcatname  = $row1['prjname'];
	$projcatname .= '<=>';
	$projcatname .= $row1['catname'];
	echo '<option value="'. $row1['id'] .'"';
	echo '>' . $projcatname . '</option>'; 
}
echo '</select>';
?>
</td>
<td><input name="days10" type="text" size=5 maxlength=5 ></td>
<td><input name="days20" type="text" size=5 maxlength=5 ></td>
<td><input name="days30" type="text" size=5 maxlength=5 ></td>
<td><input name="days40" type="text" size=5 maxlength=5 ></td>
<td><input name="days50" type="text" size=5 maxlength=5 ></td>
<td><input name="days60" type="text" size=5 maxlength=5 ></td>
<td><input name="<?php echo lang_get( 'due_submit' ) ?>" type="submit" value="<?php echo lang_get( 'due_submit' ) ?>">
</td>
</tr>
<?php
	# Pull all Record entries
	$query = "SELECT a.id,c.name as prjname,b.name as catname,a.days10,a.days20,a.days30,a.days40,a.days50,a.days60 FROM $cat_def_table as a,$cat_table as b,$prj_table as c  WHERE a.id =b.id and b.project_id=c.id order by prjname,catname";
	$result = db_query($query);
	while ($row = db_fetch_array($result)) {
		$projcatname  = $row['prjname'];
		$projcatname .= '<=>';
		$projcatname .= $row['catname'];
		?>
		<tr>
		<td><?php echo $row['id'] ?></td>
		<td><?php echo $projcatname ?></td>
		<td><div align="center"><?php echo $row['days10'] ?></td>
		<td><div align="center"><?php echo $row['days20'] ?></td>
		<td><div align="center"><?php echo $row['days30'] ?></td>
		<td><div align="center"><?php echo $row['days40'] ?></td>
		<td><div align="center"><?php echo $row['days50'] ?></td>
		<td><div align="center"><?php echo $row['days60'] ?></td>
		<td>
		<a href="plugins/SetDuedate/pages/duedate_delete.php?delete_id=<?php echo $row["id"]; ?>&table=<?php echo $cat_def_table; ?>"><?php echo lang_get( 'due_remove' ) ?></a>

		</tr>
		<?php
	}
?>


</table>
</div>
</div>
<div class="widget-toolbox padding-8 clearfix">
	<input type="submit" class="btn btn-primary btn-white btn-round" value="<?php echo lang_get( 'change_configuration' )?>" />
</div>
</div>
</div>
</form>
</div>
</div>
<?php
layout_page_end( );