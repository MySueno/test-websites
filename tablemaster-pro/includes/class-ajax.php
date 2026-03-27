<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TableMaster_Ajax {

    public function register() {
        $actions = array(
            'tablemaster_save_table'        => 'save_table',
            'tablemaster_delete_table'      => 'delete_table',
            'tablemaster_duplicate_table'   => 'duplicate_table',
            'tablemaster_save_structure'    => 'save_structure',
            'tablemaster_get_structure'     => 'get_structure',
            'tablemaster_save_translations' => 'save_translations',
            'tablemaster_search_posts'      => 'search_posts',
        );

        foreach ( $actions as $hook => $method ) {
            add_action( 'wp_ajax_' . $hook, array( $this, $method ) );
        }
    }

    private function verify_nonce() {
        if ( ! check_ajax_referer( 'tablemaster_admin', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Beveiligingscontrole mislukt.', TMP_TEXT_DOMAIN ) ), 403 );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Onvoldoende rechten.', TMP_TEXT_DOMAIN ) ), 403 );
        }
    }

    public function save_table() {
        $this->verify_nonce();

        $id       = ! empty( $_POST['id'] )   ? intval( $_POST['id'] )   : 0;
        $name     = ! empty( $_POST['name'] )  ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $settings = ! empty( $_POST['settings'] ) ? json_decode( wp_unslash( $_POST['settings'] ), true ) : array();

        if ( ! $name ) {
            wp_send_json_error( array( 'message' => __( 'Naam is verplicht.', TMP_TEXT_DOMAIN ) ) );
        }
        if ( mb_strlen( $name ) > 200 ) {
            wp_send_json_error( array( 'message' => __( 'Naam is te lang (max 200 tekens).', TMP_TEXT_DOMAIN ) ) );
        }
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        $settings = self::sanitize_table_settings( $settings );

        $new_id = TableMaster_DB::save_table( array(
            'id'       => $id,
            'name'     => $name,
            'settings' => $settings,
        ) );

        do_action( 'tablemaster_after_save_table', $new_id );
        wp_send_json_success( array( 'id' => $new_id ) );
    }

    private static function sanitize_table_settings( $settings ) {
        $allowed_themes      = array( 'red', 'green', 'blue', 'grey', 'custom' );
        $allowed_search_pos  = array( 'left', 'right', 'top', 'bottom' );
        $allowed_mobile      = array( 'scroll' );
        $allowed_sort_dir    = array( 'asc', 'desc' );

        $clean = array();
        $clean['caption']            = isset( $settings['caption'] ) ? sanitize_text_field( $settings['caption'] ) : '';
        $clean['search']             = ! empty( $settings['search'] );
        $clean['search_position']    = in_array( $settings['search_position'] ?? '', $allowed_search_pos, true ) ? $settings['search_position'] : 'right';
        $clean['pagination']         = ! empty( $settings['pagination'] );
        $clean['per_page']           = min( 500, max( -1, intval( $settings['per_page'] ?? 10 ) ) );
        $clean['per_page_selector']  = ! empty( $settings['per_page_selector'] );
        $clean['collapsible_groups'] = ! empty( $settings['collapsible_groups'] );
        $clean['mobile_mode']        = in_array( $settings['mobile_mode'] ?? '', $allowed_mobile, true ) ? $settings['mobile_mode'] : 'scroll';
        $clean['default_sort_col']   = sanitize_text_field( $settings['default_sort_col'] ?? '' );
        $clean['default_sort_dir']   = in_array( $settings['default_sort_dir'] ?? '', $allowed_sort_dir, true ) ? $settings['default_sort_dir'] : 'asc';
        $clean['inline_html']        = ! empty( $settings['inline_html'] );
        $clean['sortable']           = isset( $settings['sortable'] ) ? ! empty( $settings['sortable'] ) : true;
        $clean['column_filters']     = ! empty( $settings['column_filters'] );
        $clean['enable_export']      = ! empty( $settings['enable_export'] );
        $clean['sticky_first_col']   = ! empty( $settings['sticky_first_col'] );
        $clean['sticky_header']      = ! empty( $settings['sticky_header'] );
        $clean['theme']              = in_array( $settings['theme'] ?? '', $allowed_themes, true ) ? $settings['theme'] : 'custom';

        $dcw = trim( $settings['default_col_width'] ?? '' );
        if ( $dcw !== '' && ! preg_match( '/^\d{1,4}(px|em|rem|%)$/', $dcw ) ) {
            $dcw = '';
        }
        $clean['default_col_width']  = $dcw;

        $fcw = trim( $settings['first_col_width'] ?? '' );
        if ( $fcw !== '' && ! preg_match( '/^\d{1,4}(px|em|rem|%)$/', $fcw ) ) {
            $fcw = '';
        }
        $clean['first_col_width'] = $fcw;

        $mw = trim( $settings['max_width'] ?? '' );
        if ( $mw !== '' && ! preg_match( '/^\d{1,4}(px|em|rem|%|vw)$/', $mw ) ) {
            $mw = '';
        }
        $clean['max_width'] = $mw;

        $mh = trim( $settings['max_height'] ?? '' );
        if ( $mh !== '' && ! preg_match( '/^\d{1,4}(px|em|rem|%|vh)$/', $mh ) ) {
            $mh = '';
        }
        $clean['max_height'] = $mh;

        if ( isset( $settings['colors'] ) && is_array( $settings['colors'] ) ) {
            $clean['colors'] = TableMaster_Settings::sanitize_colors( $settings['colors'] );
        }

        $allowed_font_keys  = array( 'header', 'group_1', 'group_2', 'group_3', 'footer', 'data' );
        $allowed_font_sizes = array( '', '10px', '11px', '12px', '13px', '14px', '16px', '18px', '20px', '22px', '24px' );
        $clean['fonts'] = array();
        if ( isset( $settings['fonts'] ) && is_array( $settings['fonts'] ) ) {
            foreach ( $settings['fonts'] as $fk => $fv ) {
                if ( ! in_array( $fk, $allowed_font_keys, true ) || ! is_array( $fv ) ) continue;
                $clean['fonts'][ $fk ] = array(
                    'size'   => in_array( $fv['size'] ?? '', $allowed_font_sizes, true ) ? $fv['size'] : '',
                    'bold'   => ! empty( $fv['bold'] ),
                    'italic' => ! empty( $fv['italic'] ),
                );
            }
        }

        return $clean;
    }

    public function delete_table() {
        $this->verify_nonce();
        $id = intval( $_POST['id'] ?? 0 );
        if ( ! $id || ! TableMaster_DB::get_table( $id ) ) {
            wp_send_json_error( array( 'message' => __( 'Tabel niet gevonden.', TMP_TEXT_DOMAIN ) ), 404 );
        }
        TableMaster_DB::delete_table( $id );
        wp_send_json_success();
    }

    public function duplicate_table() {
        $this->verify_nonce();
        $id = intval( $_POST['id'] ?? 0 );
        if ( ! $id || ! TableMaster_DB::get_table( $id ) ) {
            wp_send_json_error( array( 'message' => __( 'Tabel niet gevonden.', TMP_TEXT_DOMAIN ) ), 404 );
        }
        $new_id = TableMaster_DB::duplicate_table( $id );
        if ( ! $new_id ) {
            wp_send_json_error( array( 'message' => __( 'Dupliceren mislukt.', TMP_TEXT_DOMAIN ) ) );
        }
        wp_send_json_success( array( 'id' => $new_id ) );
    }

    public function save_structure() {
        $this->verify_nonce();

        $table_id    = intval( $_POST['table_id'] ?? 0 );
        $columns_raw = wp_unslash( $_POST['columns'] ?? '[]' );
        $rows_raw    = wp_unslash( $_POST['rows']    ?? '[]' );

        if ( strlen( $columns_raw ) > 1048576 || strlen( $rows_raw ) > 10485760 ) {
            wp_send_json_error( array( 'message' => __( 'Data te groot.', TMP_TEXT_DOMAIN ) ), 413 );
        }

        $columns     = json_decode( $columns_raw, true );
        $rows        = json_decode( $rows_raw, true );
        $lang        = sanitize_text_field( wp_unslash( $_POST['lang'] ?? '' ) );

        if ( ! $table_id || ! TableMaster_DB::get_table( $table_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Tabel niet gevonden.', TMP_TEXT_DOMAIN ) ), 404 );
        }
        if ( ! is_array( $columns ) || ! is_array( $rows ) ) {
            wp_send_json_error( array( 'message' => __( 'Ongeldige data.', TMP_TEXT_DOMAIN ) ) );
        }
        if ( count( $columns ) > 100 ) {
            wp_send_json_error( array( 'message' => __( 'Te veel kolommen (max 100).', TMP_TEXT_DOMAIN ) ) );
        }
        if ( count( $rows ) > 10000 ) {
            wp_send_json_error( array( 'message' => __( 'Te veel rijen (max 10.000).', TMP_TEXT_DOMAIN ) ) );
        }

        foreach ( $columns as &$col ) {
            $col['label'] = isset( $col['label'] ) ? mb_substr( wp_kses_post( $col['label'] ), 0, 500 ) : '';
            $allowed_types = array( 'text', 'number', 'date', 'link', 'image', 'html' );
            $col['type']  = isset( $col['type'] ) && in_array( $col['type'], $allowed_types, true ) ? $col['type'] : 'text';
        }
        unset( $col );

        $allowed_aligns = array( 'left', 'center', 'right' );
        foreach ( $rows as &$row ) {
            if ( isset( $row['cells'] ) && is_array( $row['cells'] ) ) {
                foreach ( $row['cells'] as $cell_key => &$cell_val ) {
                    $cell_val = wp_kses_post( $cell_val );
                }
                unset( $cell_val );
            }
            if ( isset( $row['cell_aligns'] ) && is_array( $row['cell_aligns'] ) ) {
                foreach ( $row['cell_aligns'] as $ca_key => &$ca_val ) {
                    $ca_val = in_array( $ca_val, $allowed_aligns, true ) ? $ca_val : '';
                }
                unset( $ca_val );
            }
            if ( isset( $row['cell_merges'] ) && is_array( $row['cell_merges'] ) ) {
                foreach ( $row['cell_merges'] as $cm_key => &$cm_val ) {
                    $cm_val = max( 1, intval( $cm_val ) );
                }
                unset( $cm_val );
            }
        }
        unset( $row );

        TableMaster_DB::save_table_structure( $table_id, $columns, $rows, $lang );
        do_action( 'tablemaster_after_save_structure', $table_id );
        wp_send_json_success();
    }

    public function get_structure() {
        $this->verify_nonce();
        $table_id = intval( $_POST['table_id'] ?? 0 );
        $lang     = sanitize_text_field( wp_unslash( $_POST['lang'] ?? '' ) );
        $data     = TableMaster_DB::get_table_data( $table_id, $lang );
        wp_send_json_success( $data );
    }

    public function save_translations() {
        $this->verify_nonce();

        if ( ! defined( 'WPML_ST_VERSION' ) ) {
            wp_send_json_error( array( 'message' => 'WPML String Translation is niet actief.' ) );
        }

        global $wpdb;

        $table_id     = intval( $_POST['table_id'] ?? 0 );
        $lang         = sanitize_text_field( wp_unslash( $_POST['lang'] ?? '' ) );
        $translations = json_decode( wp_unslash( $_POST['translations'] ?? '{}' ), true );

        if ( ! $table_id || ! $lang || ! is_array( $translations ) ) {
            wp_send_json_error( array( 'message' => 'Ongeldige data.' ) );
        }

        if ( ! TableMaster_DB::get_table( $table_id ) ) {
            wp_send_json_error( array( 'message' => 'Tabel niet gevonden.' ), 404 );
        }

        $active_langs = apply_filters( 'wpml_active_languages', array() );
        $valid_codes  = is_array( $active_langs ) ? array_keys( $active_langs ) : array();
        if ( ! empty( $valid_codes ) && ! in_array( $lang, $valid_codes, true ) ) {
            wp_send_json_error( array( 'message' => 'Ongeldige doeltaal.' ) );
        }

        $context = TableMaster_WPML::get_context( $table_id );

        $strings_table      = $wpdb->prefix . 'icl_strings';
        $translations_table = $wpdb->prefix . 'icl_string_translations';

        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $strings_table ) ) !== $strings_table ) {
            wp_send_json_error( array( 'message' => 'WPML-databasetabellen niet gevonden.' ) );
        }
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $translations_table ) ) !== $translations_table ) {
            wp_send_json_error( array( 'message' => 'WPML-databasetabellen niet gevonden.' ) );
        }

        $saved   = 0;
        $cleared = 0;
        foreach ( $translations as $name => $value ) {
            $name  = sanitize_text_field( $name );
            $value = trim( $value );

            $string_id = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$strings_table} WHERE context = %s AND name = %s",
                $context, $name
            ) );

            if ( ! $string_id ) continue;

            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$translations_table} WHERE string_id = %d AND language = %s",
                $string_id, $lang
            ) );

            if ( $value === '' ) {
                if ( $existing ) {
                    $wpdb->delete(
                        $translations_table,
                        array( 'id' => $existing ),
                        array( '%d' )
                    );
                    $cleared++;
                }
                continue;
            }

            if ( strpos( $name, 'row_' ) === 0 ) {
                $value = wp_kses_post( $value );
            } else {
                $value = sanitize_text_field( $value );
            }

            if ( $existing ) {
                $wpdb->update(
                    $translations_table,
                    array( 'value' => $value, 'status' => 10 ),
                    array( 'id' => $existing ),
                    array( '%s', '%d' ),
                    array( '%d' )
                );
            } else {
                $wpdb->insert(
                    $translations_table,
                    array(
                        'string_id'        => $string_id,
                        'language'         => $lang,
                        'value'            => $value,
                        'status'           => 10,
                        'translator_id'    => get_current_user_id(),
                        'translation_date' => current_time( 'mysql' ),
                    ),
                    array( '%d', '%s', '%s', '%d', '%d', '%s' )
                );
            }
            $saved++;
        }

        TableMaster_DB::flush_table_cache( $table_id );

        wp_send_json_success( array( 'saved' => $saved, 'cleared' => $cleared ) );
    }

    public function search_posts() {
        $this->verify_nonce();

        $user_id = get_current_user_id();
        $transient_key = 'tmp_search_rate_' . $user_id;
        $requests = (int) get_transient( $transient_key );
        if ( $requests >= 30 ) {
            wp_send_json_error( array( 'message' => __( 'Te veel verzoeken. Probeer het later opnieuw.', TMP_TEXT_DOMAIN ) ), 429 );
        }
        set_transient( $transient_key, $requests + 1, 60 );

        $search = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
        if ( strlen( $search ) < 2 ) {
            wp_send_json_success( array( 'results' => array() ) );
        }

        $args = array(
            'post_type'      => array( 'post', 'page' ),
            'post_status'    => 'publish',
            's'              => $search,
            'posts_per_page' => 10,
            'orderby'        => 'relevance',
            'order'          => 'DESC',
        );

        $query   = new WP_Query( $args );
        $results = array();

        foreach ( $query->posts as $post ) {
            $results[] = array(
                'id'    => $post->ID,
                'title' => $post->post_title ?: __( '(geen titel)', TMP_TEXT_DOMAIN ),
                'type'  => $post->post_type,
                'url'   => get_permalink( $post->ID ),
            );
        }

        wp_send_json_success( array( 'results' => $results ) );
    }
}
