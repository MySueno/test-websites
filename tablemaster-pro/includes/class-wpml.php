<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TableMaster_WPML {

    public function register() {
        add_action( 'tablemaster_after_save_table',     array( $this, 'register_strings' ) );
        add_action( 'tablemaster_after_save_structure', array( $this, 'register_strings' ) );
        add_action( 'admin_init', array( $this, 'maybe_register_all_strings' ) );
    }

    public static function is_active() {
        return defined( 'ICL_SITEPRESS_VERSION' ) && defined( 'WPML_ST_VERSION' );
    }

    public static function is_string_translation_active() {
        return defined( 'WPML_ST_VERSION' );
    }

    public static function get_translate_url( $table_id ) {
        return admin_url( 'admin.php?page=tablemaster-translate&id=' . intval( $table_id ) );
    }

    public static function get_context( $table_id ) {
        return 'tablemaster-pro - Table ' . $table_id;
    }

    public static function get_current_language() {
        return apply_filters( 'wpml_current_language', '' );
    }

    public static function get_default_language() {
        return apply_filters( 'wpml_default_language', '' );
    }

    private static function wpml_register_string( $context, $name, $value ) {
        do_action( 'wpml_register_single_string', $context, $name, $value );
    }

    private static function wpml_translate_string( $value, $context, $name, $lang = false ) {
        return apply_filters( 'wpml_translate_single_string', $value, $context, $name, $lang );
    }

    private static function wpml_get_active_languages() {
        $langs = apply_filters( 'wpml_active_languages', array(), 'skip_missing=0' );
        return is_array( $langs ) ? $langs : array();
    }

    private static function wpml_table_exists( $table_name ) {
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;
    }

    public function maybe_register_all_strings() {
        if ( ! self::is_active() ) {
            return;
        }

        $registered_version = get_option( 'tablemaster_wpml_registered', '' );
        if ( $registered_version === TMP_VERSION ) {
            return;
        }

        $tables = TableMaster_DB::get_all_tables();
        if ( ! empty( $tables ) ) {
            foreach ( $tables as $table ) {
                $this->register_strings( $table->id );
            }
        }

        update_option( 'tablemaster_wpml_registered', TMP_VERSION );
    }

    public function register_strings( $table_id ) {
        if ( ! self::is_active() ) {
            return;
        }

        $table = TableMaster_DB::get_table( $table_id );
        if ( ! $table ) return;

        $context  = self::get_context( $table_id );
        $settings = json_decode( $table->settings, true );

        $current_names = array();

        self::wpml_register_string( $context, 'table_name', $table->name );
        $current_names[] = 'table_name';

        if ( ! empty( $settings['caption'] ) ) {
            self::wpml_register_string( $context, 'caption', $settings['caption'] );
            $current_names[] = 'caption';
        }

        $data = TableMaster_DB::get_table_data( $table_id, '' );

        foreach ( $data['columns'] as $col ) {
            $name = 'col_' . $col->id . '_label';
            self::wpml_register_string( $context, $name, $col->label );
            $current_names[] = $name;
        }

        $registered_groups = array();
        foreach ( $data['columns'] as $col ) {
            $cs = json_decode( $col->settings, true );
            $g1 = trim( $cs['header_group1'] ?? '' );
            $g2 = trim( $cs['header_group2'] ?? '' );
            if ( $g1 !== '' && ! isset( $registered_groups[ 'g1_' . $g1 ] ) ) {
                $gname = 'header_group1_' . md5( $g1 );
                self::wpml_register_string( $context, $gname, $g1 );
                $current_names[] = $gname;
                $registered_groups[ 'g1_' . $g1 ] = true;
            }
            if ( $g2 !== '' && ! isset( $registered_groups[ 'g2_' . $g2 ] ) ) {
                $gname = 'header_group2_' . md5( $g2 );
                self::wpml_register_string( $context, $gname, $g2 );
                $current_names[] = $gname;
                $registered_groups[ 'g2_' . $g2 ] = true;
            }
        }

        foreach ( $data['rows'] as $row ) {
            foreach ( $row->cells as $col_id => $content ) {
                if ( trim( $content ) === '' ) continue;
                $name = 'row_' . $row->id . '_col_' . $col_id;
                self::wpml_register_string( $context, $name, $content );
                $current_names[] = $name;
            }
        }

        self::cleanup_orphaned_strings( $context, $current_names );
    }

    private static function cleanup_orphaned_strings( $context, $current_names ) {
        global $wpdb;

        if ( empty( $current_names ) ) return;

        $strings_table = $wpdb->prefix . 'icl_strings';
        $translations_table = $wpdb->prefix . 'icl_string_translations';

        if ( ! self::wpml_table_exists( $strings_table ) ) return;

        $placeholders = implode( ',', array_fill( 0, count( $current_names ), '%s' ) );
        $args = array_merge( array( $context ), $current_names );

        $orphaned_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT id FROM {$strings_table} WHERE context = %s AND name NOT IN ($placeholders)",
            $args
        ) );

        if ( ! empty( $orphaned_ids ) ) {
            $in = implode( ',', array_map( 'intval', $orphaned_ids ) );
            if ( self::wpml_table_exists( $translations_table ) ) {
                $wpdb->query( "DELETE FROM {$translations_table} WHERE string_id IN ($in)" );
            }
            $wpdb->query( "DELETE FROM {$strings_table} WHERE id IN ($in)" );
        }
    }

    public static function translate_string( $context, $name, $value, $lang = '' ) {
        if ( ! $lang ) {
            $lang = self::get_current_language();
        }

        $translated = self::wpml_translate_string( $value, $context, $name, $lang );

        if ( $translated === $value && self::is_string_translation_active() ) {
            if ( $lang && $lang !== self::get_default_language() ) {
                $db_translation = self::get_translation_from_db( $context, $name, $lang );
                if ( $db_translation !== '' ) {
                    return $db_translation;
                }
            }
        }

        return $translated;
    }

    private static function get_translation_from_db( $context, $name, $lang ) {
        global $wpdb;

        $strings_table      = $wpdb->prefix . 'icl_strings';
        $translations_table = $wpdb->prefix . 'icl_string_translations';

        $translation = $wpdb->get_var( $wpdb->prepare(
            "SELECT t.value FROM {$strings_table} s
             INNER JOIN {$translations_table} t ON t.string_id = s.id
             WHERE s.context = %s AND s.name = %s AND t.language = %s AND t.status = 10 AND t.value != ''",
            $context, $name, $lang
        ) );

        return $translation !== null ? $translation : '';
    }

    public static function get_translation_progress( $table_id, $lang ) {
        global $wpdb;

        if ( ! self::is_active() || ! self::is_string_translation_active() ) {
            return array( 'total' => 0, 'translated' => 0, 'percent' => 0 );
        }

        $strings_table      = $wpdb->prefix . 'icl_strings';
        $translations_table = $wpdb->prefix . 'icl_string_translations';

        if ( ! self::wpml_table_exists( $strings_table ) || ! self::wpml_table_exists( $translations_table ) ) {
            return array( 'total' => 0, 'translated' => 0, 'percent' => 0 );
        }

        $context = self::get_context( $table_id );

        $total = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$strings_table} WHERE context = %s",
            $context
        ) );

        if ( $total === 0 ) {
            return array( 'total' => 0, 'translated' => 0, 'percent' => 0 );
        }

        $translated = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$strings_table} s
             INNER JOIN {$translations_table} t ON t.string_id = s.id
             WHERE s.context = %s AND t.language = %s AND t.status = 10 AND t.value != ''",
            $context, $lang
        ) );

        $percent = $total > 0 ? round( ( $translated / $total ) * 100 ) : 0;

        return array( 'total' => $total, 'translated' => $translated, 'percent' => $percent );
    }

    public static function get_non_default_languages() {
        $active_langs  = self::wpml_get_active_languages();
        $default_lang  = self::get_default_language();
        $result = array();
        foreach ( $active_langs as $code => $l ) {
            if ( $code !== $default_lang ) {
                $result[ $code ] = $l;
            }
        }
        return $result;
    }
}
