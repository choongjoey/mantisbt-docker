<?php
auth_reauthenticate();
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );
layout_page_header( lang_get( 'plugin_format_title' ) );
layout_page_begin( 'config_page.php' );
print_manage_menu();
$link=plugin_page('cat_definition');

?>
<div class="col-md-12 col-xs-12">
<div class="space-10"></div>
<div class="form-container" > 
<br/>
<form action="<?php echo plugin_page( 'config_edit' ) ?>" method="post">
<div class="widget-box widget-color-blue2">
<div class="widget-header widget-header-small">
	<h4 class="widget-title lighter">
		<i class="ace-icon fa fa-text-width"></i>
		<?php echo lang_get( 'plugin_format_title' ) . ': ' . lang_get( 'duedate_config' )?>
	</h4>
</div>
<div class="widget-body">
<div class="widget-main no-padding">
<div class="table-responsive"> 
<table class="table table-bordered table-condensed table-striped"> 
<tr>
<td class="form-title" colspan="3">
<a href="<?php echo $link ?>"><?php echo lang_get( 'cat_definition' ) ?></a>
</td>
</tr>

<tr >
<td class="category">
<?php echo lang_get( 'duedate_days' ) ?>
</td>
<td class="center">
<input type="text" size="3" maxlength="3" name="duedate_days_default" value="<?php echo plugin_config_get( 'duedate_days_default' )?>"/>
</td>
</tr>

<?php
$t_priority_levels = MantisEnum::getValues( config_get( 'priority_enum_string' ) );

		foreach( $t_priority_levels as $t_priority_level ) 
			{
			$t_priority_string = MantisEnum::getLabel( lang_get( 'priority_enum_string' ), $t_priority_level); 
			?>
			<tr >
			<td class="category">
			<?php echo lang_get( 'duedate_priority' ) . $t_priority_string ?>
			</td>
			<td class="center">
			<input type="text" size="3" maxlength="3" name="<?php echo('duedate_days_priority_' . $t_priority_level)?>" value="<?php echo plugin_config_get( "duedate_days_priority_" . $t_priority_level )?>"/>
			</td>
			</tr>
<td></td>

			<?php
			}
?>

<tr >
<td class="category" width="60%">
<?php echo lang_get( 'overrule_duedate' )?>
</td>
<td class="center" width="20%">
<label><input type="radio" name='duedate_overrule' value="1" <?php echo( ON == plugin_config_get( 'duedate_overrule' ) ) ? 'checked="checked" ' : ''?>/>
<?php echo lang_get( 'duedate_enabled' )?></label>
<label><input type="radio" name='duedate_overrule' value="0" <?php echo( OFF == plugin_config_get( 'duedate_overrule' ) )? 'checked="checked" ' : ''?>/>
<?php echo lang_get( 'duedate_disabled' )?></label>
</td>
</tr> 

<tr >
<td class="category" width="60%">
<?php echo lang_get( 'skip_duedate' )?>
</td>
<td class="center" width="20%">
<label><input type="radio" name='duedate_skip' value="1" <?php echo( ON == plugin_config_get( 'duedate_skip' ) ) ? 'checked="checked" ' : ''?>/>
<?php echo lang_get( 'duedate_enabled' )?></label>
<label><input type="radio" name='duedate_skip' value="0" <?php echo( OFF == plugin_config_get( 'duedate_skip' ) )? 'checked="checked" ' : ''?>/>
<?php echo lang_get( 'duedate_disabled' )?></label>
</td>
</tr> 

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
layout_page_end(  );