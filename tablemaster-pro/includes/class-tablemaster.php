<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TableMaster {

    public function run() {
        add_action( 'init',             array( $this, 'load_textdomain' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_frontend_assets' ) );

        $shortcode = new TableMaster_Shortcode();
        $shortcode->register();

        $ajax = new TableMaster_Ajax();
        $ajax->register();

        $wpml = new TableMaster_WPML();
        $wpml->register();

        $block = new TableMaster_Block();
        $block->register();

        if ( did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' ) ) {
            $elementor = new TableMaster_Elementor();
            $elementor->register();
        } else {
            add_action( 'elementor/loaded', function () {
                $elementor = new TableMaster_Elementor();
                $elementor->register();
            } );
        }

        if ( is_admin() ) {
            $admin = new TableMaster_Admin();
            $admin->register();
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain( TMP_TEXT_DOMAIN, false, dirname( plugin_basename( TMP_PLUGIN_FILE ) ) . '/languages' );
    }

    public function maybe_enqueue_frontend_assets() {
        global $post;
        $should_load = false;

        if ( is_a( $post, 'WP_Post' ) ) {
            if ( has_shortcode( $post->post_content, 'tablemaster' ) ) {
                $should_load = true;
            }

            if ( ! $should_load ) {
                $elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
                if ( ! empty( $elementor_data ) && strpos( $elementor_data, 'tablemaster' ) !== false ) {
                    $should_load = true;
                }
            }
        }

        if ( apply_filters( 'tablemaster_force_load_assets', $should_load ) ) {
            self::enqueue_frontend_assets();
        }
    }

    public static function enqueue_frontend_assets() {
        wp_enqueue_style(
            'tablemaster-frontend',
            TMP_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            TMP_VERSION
        );
        wp_enqueue_script(
            'tablemaster-frontend-js',
            TMP_PLUGIN_URL . 'assets/js/frontend.js',
            array(),
            TMP_VERSION,
            true
        );
        wp_localize_script( 'tablemaster-frontend-js', 'tableMasterFrontend', array(
            'version' => TMP_VERSION,
        ) );
    }
}
