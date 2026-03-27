<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TableMaster_Shortcode {

    public function register() {
        add_shortcode( 'tablemaster', array( $this, 'render' ) );
    }

    public function render( $atts ) {
        $atts = shortcode_atts( array( 'id' => 0 ), $atts, 'tablemaster' );
        $id   = intval( $atts['id'] );
        if ( ! $id ) {
            return '<p class="tablemaster-error">' . esc_html__( 'Ongeldige tabel ID.', TMP_TEXT_DOMAIN ) . '</p>';
        }

        $table = TableMaster_DB::get_table( $id );
        if ( ! $table ) {
            return '<p class="tablemaster-error">' . esc_html__( 'Tabel niet gevonden.', TMP_TEXT_DOMAIN ) . '</p>';
        }

        TableMaster::enqueue_frontend_assets();

        $settings = json_decode( $table->settings, true );
        $lang     = TableMaster_WPML::get_current_language();
        $default_lang = TableMaster_WPML::get_default_language();

        $use_translation = false;
        if ( TableMaster_WPML::is_active() && $lang && $lang !== $default_lang ) {
            $progress = TableMaster_WPML::get_translation_progress( $id, $lang );
            if ( $progress['percent'] >= 100 ) {
                $use_translation = true;
            }
        }

        $data = TableMaster_DB::get_table_data( $id, $use_translation ? $lang : '' );

        if ( $use_translation ) {
            $context  = TableMaster_WPML::get_context( $id );
            $settings = self::translate_settings( $settings, $context, $lang );
            $data     = self::translate_data( $data, $context, $lang );
        }

        ob_start();
        include TMP_PLUGIN_DIR . 'templates/table-frontend.php';
        return ob_get_clean();
    }

    private static function translate_settings( $settings, $context, $lang ) {
        if ( ! empty( $settings['caption'] ) ) {
            $settings['caption'] = TableMaster_WPML::translate_string(
                $context,
                'caption',
                $settings['caption'],
                $lang
            );
        }
        return $settings;
    }

    private static function translate_data( $data, $context, $lang ) {
        foreach ( $data['columns'] as &$col ) {
            $col->label = TableMaster_WPML::translate_string(
                $context,
                'col_' . $col->id . '_label',
                $col->label,
                $lang
            );

            $cs = json_decode( $col->settings, true );
            $changed = false;
            $g1 = trim( $cs['header_group1'] ?? '' );
            $g2 = trim( $cs['header_group2'] ?? '' );
            if ( $g1 !== '' ) {
                $cs['header_group1'] = TableMaster_WPML::translate_string(
                    $context, 'header_group1_' . md5( $g1 ), $g1, $lang
                );
                $changed = true;
            }
            if ( $g2 !== '' ) {
                $cs['header_group2'] = TableMaster_WPML::translate_string(
                    $context, 'header_group2_' . md5( $g2 ), $g2, $lang
                );
                $changed = true;
            }
            if ( $changed ) {
                $col->settings = wp_json_encode( $cs );
            }
        }
        unset( $col );

        foreach ( $data['rows'] as &$row ) {
            foreach ( $row->cells as $col_id => &$content ) {
                if ( trim( $content ) === '' ) continue;
                $content = TableMaster_WPML::translate_string(
                    $context,
                    'row_' . $row->id . '_col_' . $col_id,
                    $content,
                    $lang
                );
            }
            unset( $content );
        }
        unset( $row );

        return $data;
    }
}
