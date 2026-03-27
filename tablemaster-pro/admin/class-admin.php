<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TableMaster_Admin {

    public function register() {
        add_action( 'admin_menu',            array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_init',            array( $this, 'maybe_render_preview' ) );
        add_action( 'admin_init',            array( $this, 'maybe_export_csv' ) );
        add_action( 'admin_init',            array( $this, 'maybe_export_translated_csv' ) );
    }

    public function maybe_render_preview() {
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'tablemaster-preview' ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Geen toegang.', TMP_TEXT_DOMAIN ) );
        }
        include TMP_PLUGIN_DIR . 'admin/views/table-preview.php';
        exit;
    }

    public function add_menu() {
        add_menu_page(
            __( 'TableMaster Pro', TMP_TEXT_DOMAIN ),
            __( 'TableMaster', TMP_TEXT_DOMAIN ),
            'manage_options',
            'tablemaster',
            array( $this, 'page_list' ),
            'dashicons-editor-table',
            30
        );

        add_submenu_page(
            'tablemaster',
            __( 'Alle Tabellen', TMP_TEXT_DOMAIN ),
            __( 'Alle Tabellen', TMP_TEXT_DOMAIN ),
            'manage_options',
            'tablemaster',
            array( $this, 'page_list' )
        );

        add_submenu_page(
            'tablemaster',
            __( 'Nieuwe Tabel', TMP_TEXT_DOMAIN ),
            __( 'Nieuwe Tabel', TMP_TEXT_DOMAIN ),
            'manage_options',
            'tablemaster-new',
            array( $this, 'page_edit' )
        );

        add_submenu_page(
            'tablemaster',
            __( 'Instellingen', TMP_TEXT_DOMAIN ),
            __( 'Instellingen', TMP_TEXT_DOMAIN ),
            'manage_options',
            'tablemaster-settings',
            array( $this, 'page_settings' )
        );

        add_submenu_page(
            null,
            __( 'Tabel Bewerken', TMP_TEXT_DOMAIN ),
            __( 'Tabel Bewerken', TMP_TEXT_DOMAIN ),
            'manage_options',
            'tablemaster-edit',
            array( $this, 'page_edit' )
        );

        add_submenu_page(
            null,
            __( 'Tabel Preview', TMP_TEXT_DOMAIN ),
            __( 'Tabel Preview', TMP_TEXT_DOMAIN ),
            'manage_options',
            'tablemaster-preview',
            array( $this, 'page_preview' )
        );

        add_submenu_page(
            null,
            __( 'Tabel Vertalen', TMP_TEXT_DOMAIN ),
            __( 'Tabel Vertalen', TMP_TEXT_DOMAIN ),
            'manage_options',
            'tablemaster-translate',
            array( $this, 'page_translate' )
        );
    }

    public function enqueue_assets( $hook ) {
        $tm_pages = array(
            'toplevel_page_tablemaster',
            'tablemaster_page_tablemaster-new',
            'tablemaster_page_tablemaster-settings',
            'admin_page_tablemaster-edit',
            'admin_page_tablemaster-translate',
        );

        if ( ! in_array( $hook, $tm_pages, true ) ) {
            return;
        }

        wp_enqueue_style(
            'tablemaster-admin',
            TMP_PLUGIN_URL . 'assets/css/admin.css',
            array( 'wp-color-picker' ),
            TMP_VERSION
        );

        wp_enqueue_style( 'wp-color-picker' );

        wp_enqueue_script(
            'tablemaster-admin',
            TMP_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ),
            TMP_VERSION,
            true
        );

        $table_id  = intval( $_GET['id'] ?? 0 );
        $table     = $table_id ? TableMaster_DB::get_table( $table_id ) : null;
        $settings  = $table ? json_decode( $table->settings, true ) : array();
        $site_domains = array( wp_parse_url( home_url(), PHP_URL_HOST ) );
        if ( function_exists( 'icl_get_languages' ) ) {
            $wpml_langs = icl_get_languages( 'skip_missing=0' );
            if ( is_array( $wpml_langs ) ) {
                foreach ( $wpml_langs as $lang_info ) {
                    if ( ! empty( $lang_info['url'] ) ) {
                        $host = wp_parse_url( $lang_info['url'], PHP_URL_HOST );
                        if ( $host ) {
                            $site_domains[] = $host;
                        }
                    }
                }
            }
        }
        $site_domains = array_values( array_unique( $site_domains ) );

        wp_localize_script( 'tablemaster-admin', 'tableMasterAdmin', array(
            'ajaxurl'      => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'tablemaster_admin' ),
            'table_id'     => $table_id,
            'table_name'   => $table ? $table->name : '',
            'settings'     => $settings,
            'list_url'     => admin_url( 'admin.php?page=tablemaster' ),
            'edit_url'     => admin_url( 'admin.php?page=tablemaster-edit&id=' ),
            'lang'         => TableMaster_WPML::get_current_language(),
            'site_url'     => home_url(),
            'site_domains' => $site_domains,
            'i18n'         => array(
                'confirm_delete'    => __( 'Weet je zeker dat je deze tabel wilt verwijderen? Dit kan niet ongedaan worden gemaakt.', TMP_TEXT_DOMAIN ),
                'saved'             => __( 'Opgeslagen!', TMP_TEXT_DOMAIN ),
                'error'             => __( 'Er is een fout opgetreden.', TMP_TEXT_DOMAIN ),
                'add_column'        => __( 'Kolom', TMP_TEXT_DOMAIN ),
                'add_row'           => __( '+ Rij', TMP_TEXT_DOMAIN ),
                'add_group1'        => __( '+ Groep niveau 1', TMP_TEXT_DOMAIN ),
                'add_group2'        => __( '+ Groep niveau 2', TMP_TEXT_DOMAIN ),
                'add_group3'        => __( '+ Groep niveau 3', TMP_TEXT_DOMAIN ),
                'delete_row'        => __( 'Rij verwijderen', TMP_TEXT_DOMAIN ),
                'delete_col'        => __( 'Kolom verwijderen', TMP_TEXT_DOMAIN ),
                'no_columns'        => __( 'Voeg eerst kolommen toe.', TMP_TEXT_DOMAIN ),
                'copy_shortcode'    => __( 'Shortcode gekopieerd!', TMP_TEXT_DOMAIN ),
                'unsaved_changes'   => __( 'Je hebt niet-opgeslagen wijzigingen. Weet je zeker dat je de pagina wilt verlaten?', TMP_TEXT_DOMAIN ),
            ),
        ) );

        wp_enqueue_script( 'jquery-ui-sortable' );
    }

    public function page_list() {
        include TMP_PLUGIN_DIR . 'admin/views/table-list.php';
    }

    public function page_edit() {
        include TMP_PLUGIN_DIR . 'admin/views/table-edit.php';
    }

    public function page_preview() {
        include TMP_PLUGIN_DIR . 'admin/views/table-preview.php';
    }

    public function page_translate() {
        include TMP_PLUGIN_DIR . 'admin/views/table-translate.php';
    }

    public function page_settings() {
        if ( isset( $_POST['tablemaster_settings_nonce'] ) &&
             wp_verify_nonce( $_POST['tablemaster_settings_nonce'], 'tablemaster_save_settings' ) &&
             current_user_can( 'manage_options' ) ) {
            TableMaster_Settings::save( $_POST );
            add_settings_error( 'tablemaster', 'saved', __( 'Instellingen opgeslagen.', TMP_TEXT_DOMAIN ), 'updated' );
        }
        include TMP_PLUGIN_DIR . 'admin/views/settings.php';
    }

    public function maybe_export_csv() {
        if ( ! isset( $_GET['tablemaster_export_csv'] ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Geen toegang.', TMP_TEXT_DOMAIN ) );
        }
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'tablemaster_export_csv' ) ) {
            wp_die( esc_html__( 'Beveiligingscontrole mislukt.', TMP_TEXT_DOMAIN ) );
        }

        $table_id = intval( $_GET['tablemaster_export_csv'] );
        $table    = TableMaster_DB::get_table( $table_id );
        if ( ! $table ) {
            wp_die( esc_html__( 'Tabel niet gevonden.', TMP_TEXT_DOMAIN ) );
        }

        $data    = TableMaster_DB::get_table_data( $table_id, '' );
        $columns = $data['columns'] ?? array();
        $rows    = $data['rows']    ?? array();

        $slug     = sanitize_file_name( $table->name );
        $slug     = $slug ? $slug : 'table-' . $table_id;
        $filename = $slug . '-' . gmdate( 'Y-m-d' ) . '.csv';

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $output = fopen( 'php://output', 'w' );
        fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

        $header_row = array();
        foreach ( $columns as $col ) {
            $header_row[] = $col->label;
        }
        fputcsv( $output, $header_row, ';' );

        foreach ( $rows as $row ) {
            $csv_row = array();
            foreach ( $columns as $col ) {
                $csv_row[] = $row->cells[ $col->id ] ?? '';
            }
            fputcsv( $output, $csv_row, ';' );
        }

        fclose( $output );
        exit;
    }

    public function maybe_export_translated_csv() {
        if ( ! isset( $_GET['tablemaster_export_translated_csv'] ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Geen toegang.', TMP_TEXT_DOMAIN ) );
        }
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'tablemaster_export_translated_csv' ) ) {
            wp_die( esc_html__( 'Beveiligingscontrole mislukt.', TMP_TEXT_DOMAIN ) );
        }

        $table_id    = intval( $_GET['tablemaster_export_translated_csv'] );
        $target_lang = isset( $_GET['lang'] ) ? sanitize_text_field( $_GET['lang'] ) : '';

        $table = TableMaster_DB::get_table( $table_id );
        if ( ! $table ) {
            wp_die( esc_html__( 'Tabel niet gevonden.', TMP_TEXT_DOMAIN ) );
        }
        if ( ! $target_lang ) {
            wp_die( esc_html__( 'Geen doeltaal opgegeven.', TMP_TEXT_DOMAIN ) );
        }

        $data    = TableMaster_DB::get_table_data( $table_id, '' );
        $columns = $data['columns'] ?? array();
        $rows    = $data['rows']    ?? array();
        $context = TableMaster_WPML::get_context( $table_id );

        $slug     = sanitize_file_name( $table->name );
        $slug     = $slug ? $slug : 'table-' . $table_id;
        $filename = $slug . '-' . $target_lang . '-' . gmdate( 'Y-m-d' ) . '.csv';

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $output = fopen( 'php://output', 'w' );
        fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

        $header_row = array();
        foreach ( $columns as $col ) {
            $translated_label = $this->get_wpml_translation( $context, 'col_' . $col->id . '_label', $target_lang );
            $header_row[] = $translated_label !== '' ? $translated_label : $col->label;
        }
        fputcsv( $output, $header_row, ';' );

        foreach ( $rows as $row ) {
            $csv_row = array();
            foreach ( $columns as $col ) {
                $original   = $row->cells[ $col->id ] ?? '';
                $translated = $this->get_wpml_translation( $context, 'row_' . $row->id . '_col_' . $col->id, $target_lang );
                $csv_row[]  = $translated !== '' ? $translated : $original;
            }
            fputcsv( $output, $csv_row, ';' );
        }

        fclose( $output );
        exit;
    }

    private function get_wpml_translation( $context, $name, $lang ) {
        global $wpdb;
        if ( ! defined( 'WPML_ST_VERSION' ) ) return '';

        $strings_table      = $wpdb->prefix . 'icl_strings';
        $translations_table = $wpdb->prefix . 'icl_string_translations';

        $string_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$strings_table} WHERE context = %s AND name = %s",
            $context, $name
        ) );
        if ( ! $string_id ) return '';

        $translation = $wpdb->get_var( $wpdb->prepare(
            "SELECT value FROM {$translations_table} WHERE string_id = %d AND language = %s AND status = 10",
            $string_id, $lang
        ) );
        return $translation !== null ? $translation : '';
    }
}
