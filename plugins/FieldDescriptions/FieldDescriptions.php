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

        echo <<<HTML
<script>
(function() {
    var isFormPage     = {$is_form_js};
    var isListPage     = {$is_list_js};
    var viewSelectors  = {$view_selectors_json};
    var listSelectors  = {$list_selectors_json};
    var defaultLabels  = {$default_labels_json};
    var customFields   = {$custom_fields_json};

    function merge(base, override) {
        var result = {};
        Object.keys(base).forEach(function(k) { result[k] = base[k]; });
        Object.keys(override).forEach(function(k) { result[k] = override[k]; });
        return result;
    }
    var global  = {$global_json};
    var project = {$proj_json};
    var labels       = merge(global.labels,       project.labels);
    var descriptions = merge(global.descriptions, project.descriptions);
    var placeholders = merge(global.placeholders, project.placeholders);

    function applyEnhancements() {
        var allFields = Object.keys(labels).concat(Object.keys(descriptions)).concat(Object.keys(placeholders))
            .filter(function(v, i, a) { return a.indexOf(v) === i; });

        allFields.forEach(function(name) {
            if (isFormPage) {
                // Form pages: find by input[name]
                var aliases = {'additional_info': 'additional_information'};
                var altName = aliases[name] || null;
                var el = document.querySelector(
                    '[name="' + name + '"], [name="' + name + '[]"]' +
                    (altName ? ', [name="' + altName + '"], [name="' + altName + '[]"]' : '')
                );
                if (!el) {
                    // Field is read-only (no input) — update td.category by matching default label text
                    if (labels[name] && defaultLabels[name]) {
                        var defaultText = defaultLabels[name];
                        var tds = document.querySelectorAll('td.category');
                        for (var i = 0; i < tds.length; i++) {
                            if (tds[i].children.length === 0 && tds[i].textContent.trim() === defaultText) {
                                tds[i].textContent = labels[name];
                                break;
                            }
                        }
                    }
                    return;
                }

                // Resolve label element first — needed for both label update and hint placement
                var labelEl = document.querySelector('label[for="' + el.id + '"]');
                if (!labelEl && altName) {
                    labelEl = document.querySelector('label[for="' + altName + '"]');
                }

                if (placeholders[name]) {
                    el.placeholder = placeholders[name];
                }

                if (descriptions[name]) {
                    // Insert hint below the field label (in the label cell), fallback to after the input
                    var hintParent = labelEl ? labelEl.parentNode : el.parentNode;
                    var hintAfter  = labelEl ? labelEl.nextSibling  : el.nextSibling;
                    if (!hintParent.querySelector('.fd-hint')) {
                        var hint = document.createElement('p');
                        hint.className = 'fd-hint';
                        hint.style.cssText = 'color:#777;font-size:11px;margin:3px 0 0;line-height:1.4;';
                        hint.textContent = descriptions[name];
                        hintParent.insertBefore(hint, hintAfter);
                    }
                }

                if (labels[name] && labelEl) {
                    labelEl.textContent = labels[name];
                }

            } else if (isListPage) {
                // Issue list page: th.column-* headers contain a sort <a> link
                if (!labels[name]) return;
                var sel = listSelectors[name];
                if (!sel) return;
                document.querySelectorAll(sel).forEach(function(thEl) {
                    var link = thEl.querySelector('a');
                    if (link) {
                        // Update only the text node, preserve the sort icon inside <a>
                        for (var i = 0; i < link.childNodes.length; i++) {
                            if (link.childNodes[i].nodeType === 3) {
                                link.childNodes[i].textContent = labels[name];
                                break;
                            }
                        }
                    } else {
                        thEl.textContent = labels[name];
                    }
                });
            } else {
                // View pages: use CSS class selector on <th>
                if (!labels[name]) return;
                var sel = viewSelectors[name];
                if (!sel) return;
                document.querySelectorAll(sel).forEach(function(el) {
                    el.textContent = labels[name];
                });
            }
        });
    }

    function applyCustomFields() {
        customFields.forEach(function(cf) {
            if (!cf.label && !cf.desc && !cf.ph) return;

            if (isFormPage) {
                var el = document.querySelector('[name="custom_field_' + cf.id + '"], [name="custom_field_' + cf.id + '[]"]');
                if (!el) return;
                var labelEl = document.querySelector('label[for="custom_field_' + cf.id + '"]');

                if (cf.ph) el.placeholder = cf.ph;

                if (cf.desc) {
                    var hintParent = labelEl ? labelEl.parentNode : el.parentNode;
                    var hintAfter  = labelEl ? labelEl.nextSibling  : el.nextSibling;
                    if (!hintParent.querySelector('.fd-hint')) {
                        var hint = document.createElement('p');
                        hint.className = 'fd-hint';
                        hint.style.cssText = 'color:#777;font-size:11px;margin:3px 0 0;line-height:1.4;';
                        hint.textContent = cf.desc;
                        hintParent.insertBefore(hint, hintAfter);
                    }
                }

                if (cf.label && labelEl) labelEl.textContent = cf.label;

            } else if (isListPage) {
                if (!cf.label) return;
                var sel = 'th.column-custom-' + cf.cssName;
                document.querySelectorAll(sel).forEach(function(thEl) {
                    var link = thEl.querySelector('a');
                    if (link) {
                        for (var i = 0; i < link.childNodes.length; i++) {
                            if (link.childNodes[i].nodeType === 3) {
                                link.childNodes[i].textContent = cf.label;
                                break;
                            }
                        }
                    } else {
                        thEl.textContent = cf.label;
                    }
                });

            } else {
                // View page: all custom field labels use th.bug-custom-field.category — match by text
                if (!cf.label) return;
                document.querySelectorAll('th.bug-custom-field.category').forEach(function(th) {
                    if (th.textContent.trim() === cf.name) th.textContent = cf.label;
                });
            }
        });
    }

    function run() {
        applyEnhancements();
        applyCustomFields();
    }

    if (document.readyState === 'complete') {
        run();
    } else {
        window.addEventListener('load', run);
    }
})();
</script>
HTML;
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
