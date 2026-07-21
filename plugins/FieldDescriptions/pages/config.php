<?php
auth_reauthenticate();
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

$fields     = FieldDescriptionsPlugin::FIELDS;
$project_id = helper_get_current_project();

// Load custom fields — project-linked only when a project is selected
$custom_flds = array();
if ( function_exists( 'custom_field_get_ids' ) ) {
    $use_linked = $project_id !== ALL_PROJECTS && function_exists( 'custom_field_get_linked_ids' );
    $cf_ids = $use_linked ? custom_field_get_linked_ids( $project_id ) : custom_field_get_ids();
    foreach ( $cf_ids as $cf_id ) {
        $cf_name = custom_field_get_field( $cf_id, 'name' );
        $custom_flds[] = array( 'id' => (int) $cf_id, 'name' => $cf_name );
    }
}

$project_name = ( $project_id === ALL_PROJECTS )
    ? 'All Projects'
    : project_get_field( $project_id, 'name' );

// Handle save
if ( isset( $_POST['save'] ) ) {
    form_security_validate( 'plugin_FieldDescriptions_config' );
    foreach ( array_keys( $fields ) as $field ) {
        plugin_config_set( $field . '_label',       gpc_get_string( $field . '_label', '' ),       NO_USER, $project_id );
        plugin_config_set( $field . '_desc',        gpc_get_string( $field . '_desc', '' ),        NO_USER, $project_id );
        plugin_config_set( $field . '_placeholder', gpc_get_string( $field . '_placeholder', '' ), NO_USER, $project_id );
    }
    foreach ( $custom_flds as $cf ) {
        $prefix = 'cf_' . $cf['id'] . '_';
        plugin_config_set( $prefix . 'label',       gpc_get_string( $prefix . 'label', '' ),       NO_USER, $project_id );
        plugin_config_set( $prefix . 'desc',        gpc_get_string( $prefix . 'desc', '' ),        NO_USER, $project_id );
        plugin_config_set( $prefix . 'placeholder', gpc_get_string( $prefix . 'placeholder', '' ), NO_USER, $project_id );
    }
    print_header_redirect( plugin_page( 'config', false, 'FieldDescriptions' ) . '&saved=1' );
}

// Get config value for a field: project-specific first, then global fallback
function fd_get_val( $key, $project_id ) {
    if ( $project_id !== ALL_PROJECTS ) {
        $val = plugin_config_get( $key, '', false, NO_USER, $project_id );
        if ( $val !== '' ) return $val;
    }
    return plugin_config_get( $key, '', false, NO_USER, ALL_PROJECTS );
}

$config_page_url = plugin_page( 'config', false, 'FieldDescriptions' );

layout_page_header( 'Field Descriptions – Configuration' );
layout_page_begin( 'manage_overview_page.php' );
print_manage_menu( 'manage_plugin_page.php' );
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var configUrl = <?php echo json_encode( $config_page_url ); ?>;
    document.querySelectorAll('a.project-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var href = this.getAttribute('href');
            fetch(href, { credentials: 'same-origin' }).finally(function() {
                window.location.href = configUrl;
            });
        });
    });
});
</script>
<?php

$saved = gpc_get_bool( 'saved', false );
?>

<?php if ( $saved ): ?>
<div class="alert alert-success" style="margin:10px 14px 0;">
    Settings saved successfully for <strong><?php echo htmlspecialchars( $project_name ); ?></strong>.
</div>
<?php endif; ?>

<div class="col-md-12 col-xs-12">
<div class="space-10"></div>

<!-- Info note -->
<div class="alert alert-info" style="padding:10px 14px;margin-bottom:10px;">
    <?php if ( $project_id === ALL_PROJECTS ): ?>
    <strong>Note:</strong> These configurations apply to <strong>all projects</strong> as global defaults.
    To configure a specific project, select it from the project dropdown in the top navigation bar.
    <?php else: ?>
    <strong>Note:</strong> These configurations affect only the <strong><?php echo htmlspecialchars( $project_name ); ?></strong> project.
    To configure another project, switch from the project dropdown in the top navigation bar.
    <?php endif; ?>
</div>

<div class="widget-box widget-color-blue2">
<div class="widget-header widget-header-small">
    <h4 class="widget-title">Field Descriptions Configuration — <?php echo htmlspecialchars( $project_name ); ?></h4>
</div>
<div class="widget-body">
<div class="widget-main no-padding">

<form method="post" action="plugin.php?page=FieldDescriptions/config">
<?php echo form_security_field( 'plugin_FieldDescriptions_config' ); ?>

<table class="table table-bordered table-condensed table-hover" style="table-layout:fixed;width:100%;">
<colgroup>
    <col style="width:160px;">
    <col style="width:200px;">
    <col>
    <col>
</colgroup>
<thead>
<tr>
    <th>Field</th>
    <th>Label <small class="text-muted">(overrides default label)</small></th>
    <th>Description</th>
    <th>Placeholder</th>
</tr>
</thead>
<tbody>
<?php foreach ( $fields as $field => $default_label ):
    $has_project_val = ( $project_id !== ALL_PROJECTS ) && (
        plugin_config_get( $field . '_label',       '', false, NO_USER, $project_id ) !== '' ||
        plugin_config_get( $field . '_desc',        '', false, NO_USER, $project_id ) !== '' ||
        plugin_config_get( $field . '_placeholder', '', false, NO_USER, $project_id ) !== ''
    );

    $row_style = '';
    if ( $project_id !== ALL_PROJECTS ) {
        $row_style = $has_project_val ? 'background:#f2fff2;' : 'background:#f2f8ff;';
    }

    $label_val = fd_get_val( $field . '_label',       $project_id );
    $desc_val  = fd_get_val( $field . '_desc',        $project_id );
    $ph_val    = fd_get_val( $field . '_placeholder', $project_id );

    $label_hint = ( $project_id !== ALL_PROJECTS )
        ? ( plugin_config_get( $field . '_label', '', false, NO_USER, ALL_PROJECTS ) ?: $default_label )
        : $default_label;
    $desc_hint  = ( $project_id !== ALL_PROJECTS )
        ? ( plugin_config_get( $field . '_desc', '', false, NO_USER, ALL_PROJECTS ) ?: 'e.g. How consistently does this issue occur?' )
        : 'e.g. How consistently does this issue occur?';
    $ph_hint    = ( $project_id !== ALL_PROJECTS )
        ? ( plugin_config_get( $field . '_placeholder', '', false, NO_USER, ALL_PROJECTS ) ?: 'e.g. Sprint 12 – Login API – ...' )
        : 'e.g. Sprint 12 – Login API – ...';
?>
<tr style="<?php echo $row_style; ?>">
    <td><strong><?php echo htmlspecialchars( $default_label ); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars( $field ); ?></small></td>
    <td>
        <input type="text" class="input-sm form-control"
               name="<?php echo $field; ?>_label"
               value="<?php echo htmlspecialchars( $label_val ); ?>"
               placeholder="<?php echo htmlspecialchars( $label_hint ); ?>" />
    </td>
    <td style="padding:4px;">
        <textarea class="input-sm form-control" rows="2"
                  name="<?php echo $field; ?>_desc"
                  style="resize:vertical;width:100%;box-sizing:border-box;display:block;"><?php echo htmlspecialchars( $desc_val ); ?></textarea>
    </td>
    <td style="padding:4px;">
        <textarea class="input-sm form-control" rows="2"
                  name="<?php echo $field; ?>_placeholder"
                  style="resize:vertical;width:100%;box-sizing:border-box;display:block;"><?php echo htmlspecialchars( $ph_val ); ?></textarea>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php if ( !empty( $custom_flds ) ): ?>
<div style="margin:0 0 0 0;">
    <h5 style="padding:8px 12px;background:#f5f5f5;border-top:1px solid #ddd;border-bottom:1px solid #ddd;margin:0;">
        Custom Fields
    </h5>
    <table class="table table-bordered table-condensed table-hover" style="table-layout:fixed;width:100%;">
    <colgroup>
        <col style="width:160px;">
        <col style="width:200px;">
        <col>
        <col>
    </colgroup>
    <thead>
    <tr>
        <th>Custom Field</th>
        <th>Label <small class="text-muted">(overrides default label)</small></th>
        <th>Description</th>
        <th>Placeholder</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ( $custom_flds as $cf ):
        $prefix    = 'cf_' . $cf['id'] . '_';
        $cf_label  = fd_get_val( $prefix . 'label',       $project_id );
        $cf_desc   = fd_get_val( $prefix . 'desc',        $project_id );
        $cf_ph     = fd_get_val( $prefix . 'placeholder', $project_id );
    ?>
    <tr>
        <td><strong><?php echo htmlspecialchars( $cf['name'] ); ?></strong><br><small class="text-muted">cf_<?php echo $cf['id']; ?></small></td>
        <td>
            <input type="text" class="input-sm form-control"
                   name="<?php echo $prefix; ?>label"
                   value="<?php echo htmlspecialchars( $cf_label ); ?>"
                   placeholder="<?php echo htmlspecialchars( $cf['name'] ); ?>" />
        </td>
        <td style="padding:4px;">
            <textarea class="input-sm form-control" rows="2"
                      name="<?php echo $prefix; ?>desc"
                      style="resize:vertical;width:100%;box-sizing:border-box;display:block;"><?php echo htmlspecialchars( $cf_desc ); ?></textarea>
        </td>
        <td style="padding:4px;">
            <textarea class="input-sm form-control" rows="2"
                      name="<?php echo $prefix; ?>placeholder"
                      style="resize:vertical;width:100%;box-sizing:border-box;display:block;"><?php echo htmlspecialchars( $cf_ph ); ?></textarea>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
</div>
<?php endif; ?>

<div class="widget-toolbox padding-8">
    <button type="submit" name="save" class="btn btn-primary btn-sm">
        Save Configuration for <?php echo htmlspecialchars( $project_name ); ?>
    </button>
</div>

</form>
</div>
</div>
</div>
</div>

<?php
layout_page_end();
