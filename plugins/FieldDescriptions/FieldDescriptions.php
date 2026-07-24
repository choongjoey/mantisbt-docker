<?php
class FieldDescriptionsPlugin extends MantisPlugin {

    const FIELDS = array(
        'summary'            => 'Summary',
        'category_id'        => 'Category',
        'reproducibility'    => 'Reproducibility',
        'severity'           => 'Severity',
        'priority'           => 'Priority',
        'description'        => 'Description',
        'steps_to_reproduce' => 'Steps to Reproduce',
        'additional_info'    => 'Additional Information',
        'project_id'         => 'Project',
        'view_state'         => 'View Status',
        'date_submitted'     => 'Date Submitted',
        'last_updated'       => 'Last Update',
        'reporter_id'        => 'Reporter',
        'handler_id'         => 'Assigned To',
        'status'             => 'Status',
        'resolution'         => 'Resolution',
        'tags'               => 'Tags',
        'attach_tags'        => 'Attach Tags',
    );

    // Maps field name → filter label element ID on issue list page
    const FILTER_SELECTORS = array(
        'category_id'        => '#show_category_filter',
        'reproducibility'    => '#show_reproducibility_filter',
        'severity'           => '#show_severity_filter',
        'priority'           => '#show_priority_filter',
        'project_id'         => '#project_id_filter',
        'view_state'         => '#view_state_filter',
        'date_submitted'     => '#do_filter_by_date_filter',
        'last_updated'       => '#do_filter_by_last_updated_date_filter',
        'reporter_id'        => '#reporter_id_filter',
        'handler_id'         => '#handler_id_filter',
        'status'             => '#show_status_filter',
        'resolution'         => '#show_resolution_filter',
        'tags'               => '#tag_string_filter',
    );

    // Maps field name → <th class="column-*"> CSS class on issue list page
    const LIST_SELECTORS = array(
        'summary'            => 'th.column-summary',
        'category_id'        => 'th.column-category',
        'reproducibility'    => 'th.column-reproducibility',
        'severity'           => 'th.column-severity',
        'priority'           => 'th.column-priority',
        'project_id'         => 'th.column-project-id',
        'view_state'         => 'th.column-view-state',
        'date_submitted'     => 'th.column-date-submitted',
        'last_updated'       => 'th.column-last-modified',
        'reporter_id'        => 'th.column-reporter',
        'handler_id'         => 'th.column-assigned-to',
        'status'             => 'th.column-status',
        'resolution'         => 'th.column-resolution',
        'tags'               => 'th.column-tags',
    );

    // Maps field name → <th class="bug-*"> CSS class on view pages
    const VIEW_SELECTORS = array(
        'summary'            => 'th.bug-summary',
        'category_id'        => 'th.bug-category',
        'reproducibility'    => 'th.bug-reproducibility',
        'severity'           => 'th.bug-severity',
        'priority'           => 'th.bug-priority',
        'description'        => 'th.bug-description',
        'steps_to_reproduce' => 'th.bug-steps-to-reproduce',
        'additional_info'    => 'th.bug-additional-info',
        'project_id'         => 'th.bug-project',
        'view_state'         => 'th.bug-view-status',
        'date_submitted'     => 'th.bug-date-submitted',
        'last_updated'       => 'th.bug-last-modified',
        'reporter_id'        => 'th.bug-reporter',
        'handler_id'         => 'th.bug-assigned-to',
        'status'             => 'th.bug-status',
        'resolution'         => 'th.bug-resolution',
        'tags'               => 'th.bug-tags',
        'attach_tags'        => 'th.bug-attach-tags',
    );

    function get_custom_fields( $project_id = null ) {
        if ( !function_exists( 'custom_field_get_ids' ) ) return array();
        try {
            $use_linked = $project_id !== null
                && $project_id !== ALL_PROJECTS
                && function_exists( 'custom_field_get_linked_ids' );
            $ids = $use_linked
                ? custom_field_get_linked_ids( $project_id )
                : custom_field_get_ids();
            $result = array();
            foreach ( $ids as $id ) {
                $name = custom_field_get_field( $id, 'name' );
                $css  = preg_replace( '/[^a-z0-9]+/', '-', strtolower( $name ) );
                $result[] = array(
                    'id'      => (int) $id,
                    'name'    => $name,
                    'cssName' => $css,
                );
            }
            return $result;
        } catch ( Throwable $e ) {
            return array();
        }
    }

    function register() {
        $this->name        = 'Field Descriptions';
        $this->description = 'Configurable labels, descriptions, and placeholders for issue form fields.';
        $this->version     = '1.0.0';
        $this->requires    = array( 'MantisCore' => '2.0.0' );
        $this->author      = 'Internal';
        $this->page        = 'config';
    }

    function config() {
        $defaults = array();
        foreach ( array_keys( self::FIELDS ) as $field ) {
            $defaults[ $field . '_label' ]       = '';
            $defaults[ $field . '_desc' ]        = '';
            $defaults[ $field . '_placeholder' ] = '';
        }
        return $defaults;
    }

    function init() {
        if ( function_exists( 'http_csp_add' ) ) {
            http_csp_add( 'script-src', "'unsafe-inline'" );
        }
    }


    function hooks() {
        return array(
            'EVENT_LAYOUT_PAGE_FOOTER' => 'inject_scripts',
        );
    }

    function inject_scripts( $p_event ) {
        try {
        $page = basename( $_SERVER['SCRIPT_NAME'] );
        $form_pages = array( 'bug_report_page.php', 'bug_update_page.php', 'bug_change_status_page.php' );
        $view_pages = array( 'view.php', 'bug_view_page.php', 'bug_view_advanced_page.php' );
        $list_pages = array( 'view_all_bug_page.php' );
        $is_form = in_array( $page, $form_pages );
        $is_view = in_array( $page, $view_pages );
        $is_list = in_array( $page, $list_pages );
        if ( !$is_form && !$is_view && !$is_list ) {
            return;
        }

        $project_id = helper_get_current_project();

        $build = function( $pid ) {
            $labels = $descs = $phs = array();
            foreach ( array_keys( self::FIELDS ) as $field ) {
                $l = plugin_config_get( $field . '_label',       '', false, NO_USER, $pid );
                $d = plugin_config_get( $field . '_desc',        '', false, NO_USER, $pid );
                $p = plugin_config_get( $field . '_placeholder', '', false, NO_USER, $pid );
                if ( $l !== '' ) $labels[ $field ] = $l;
                if ( $d !== '' ) $descs[ $field ]  = $d;
                if ( $p !== '' ) $phs[ $field ]    = $p;
            }
            return array( $labels, $descs, $phs );
        };

        list( $global_labels, $global_descs, $global_phs ) = $build( ALL_PROJECTS );
        list( $proj_labels,   $proj_descs,   $proj_phs   ) = ( $project_id !== ALL_PROJECTS )
            ? $build( $project_id )
            : array( array(), array(), array() );

        // Load custom fields with merged global + project config
        $custom_fields_data = array();
        foreach ( $this->get_custom_fields( $project_id ) as $cf ) {
            $id     = $cf['id'];
            $prefix = 'cf_' . $id . '_';
            $g_l = plugin_config_get( $prefix . 'label',       '', false, NO_USER, ALL_PROJECTS );
            $g_d = plugin_config_get( $prefix . 'desc',        '', false, NO_USER, ALL_PROJECTS );
            $g_p = plugin_config_get( $prefix . 'placeholder', '', false, NO_USER, ALL_PROJECTS );
            $p_l = ( $project_id !== ALL_PROJECTS ) ? plugin_config_get( $prefix . 'label',       '', false, NO_USER, $project_id ) : '';
            $p_d = ( $project_id !== ALL_PROJECTS ) ? plugin_config_get( $prefix . 'desc',        '', false, NO_USER, $project_id ) : '';
            $p_p = ( $project_id !== ALL_PROJECTS ) ? plugin_config_get( $prefix . 'placeholder', '', false, NO_USER, $project_id ) : '';
            $custom_fields_data[] = array(
                'id'      => $id,
                'name'    => $cf['name'],
                'cssName' => $cf['cssName'],
                'label'   => ( $p_l !== '' ) ? $p_l : $g_l,
                'desc'    => ( $p_d !== '' ) ? $p_d : $g_d,
                'ph'      => ( $p_p !== '' ) ? $p_p : $g_p,
            );
        }
        $has_cf = array_filter( $custom_fields_data, function( $cf ) {
            return $cf['label'] !== '' || $cf['desc'] !== '' || $cf['ph'] !== '';
        } );

        if ( empty( $global_labels ) && empty( $global_descs ) && empty( $global_phs )
          && empty( $proj_labels )   && empty( $proj_descs )   && empty( $proj_phs )
          && empty( $has_cf ) ) {
            return;
        }

        $flags = JSON_HEX_TAG | JSON_HEX_AMP;
        $global_json = json_encode( array(
            'labels' => $global_labels, 'descriptions' => $global_descs, 'placeholders' => $global_phs,
        ), $flags );
        $proj_json = json_encode( array(
            'labels' => $proj_labels, 'descriptions' => $proj_descs, 'placeholders' => $proj_phs,
        ), $flags );

        $is_form_js = $is_form ? 'true' : 'false';
        $is_list_js = $is_list ? 'true' : 'false';
        $view_selectors_json  = json_encode( self::VIEW_SELECTORS );
        $list_selectors_json  = json_encode( self::LIST_SELECTORS );
        $default_labels_json  = json_encode( self::FIELDS );
        $custom_fields_json   = json_encode( array_values( $custom_fields_data ), $flags );

        $config_json = json_encode( array(
            'isFormPage'      => $is_form,
            'isListPage'      => $is_list,
            'viewSelectors'   => self::VIEW_SELECTORS,
            'listSelectors'   => self::LIST_SELECTORS,
            'filterSelectors' => self::FILTER_SELECTORS,
            'defaultLabels'   => self::FIELDS,
            'customFields'  => array_values( $custom_fields_data ),
            'global'        => array( 'labels' => $global_labels, 'descriptions' => $global_descs, 'placeholders' => $global_phs ),
            'project'       => array( 'labels' => $proj_labels,   'descriptions' => $proj_descs,   'placeholders' => $proj_phs ),
        ), $flags );
        $js_url = plugin_file( 'field-descriptions.js', false );
        echo '<script type="application/json" id="fd-config">' . $config_json . '</script>' . "\n";
        echo '<script src="' . htmlspecialchars( $js_url ) . '"></script>' . "\n";
        } catch ( Throwable $e ) {
            $msg = htmlspecialchars( $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
            echo '<!-- FieldDescriptions plugin error: ' . $msg . ' -->';
            if ( function_exists( 'access_has_global_level' ) && access_has_global_level( ADMINISTRATOR ) ) {
                echo '<div style="background:#fff3cd;border:1px solid #ffc107;color:#856404;padding:8px 12px;margin:8px;font-size:12px;border-radius:4px;">'
                    . '<strong>[FieldDescriptions plugin error]</strong> ' . $msg . '</div>';
            }
        }
    }
}
