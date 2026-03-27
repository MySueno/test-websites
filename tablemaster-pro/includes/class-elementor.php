<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TableMaster_Elementor {

    public function register() {
        if ( ! did_action( 'elementor/loaded' ) && ! class_exists( '\Elementor\Plugin' ) ) {
            return;
        }

        add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
        add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue_preview_assets' ) );
        add_action( 'elementor/preview/enqueue_scripts', array( $this, 'enqueue_preview_assets' ) );
        add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_editor_styles' ) );
        add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor_styles' ) );
    }

    public function register_widget( $widgets_manager ) {
        require_once TMP_PLUGIN_DIR . 'includes/class-elementor-widget.php';

        if ( ! class_exists( 'TableMaster_Elementor_Widget' ) ) {
            return;
        }

        if ( method_exists( $widgets_manager, 'register' ) ) {
            $widgets_manager->register( new TableMaster_Elementor_Widget() );
        } elseif ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
            $widgets_manager->register_widget_type( new TableMaster_Elementor_Widget() );
        }
    }

    public function enqueue_preview_assets() {
        TableMaster::enqueue_frontend_assets();
        wp_add_inline_style( 'tablemaster-frontend', self::get_elementor_overrides() );
    }

    public function enqueue_editor_styles() {
        TableMaster::enqueue_frontend_assets();
        wp_add_inline_style( 'tablemaster-frontend', self::get_elementor_overrides() );
    }

    public static function get_elementor_overrides() {
        return '
            .elementor-widget-tablemaster_table .tmp-wrapper {
                margin: 0;
            }
            .elementor-widget-tablemaster_table .tmp-wrapper,
            .elementor-widget-tablemaster_table .tmp-wrapper * {
                box-sizing: border-box;
            }
            .elementor-widget-tablemaster_table .tmp-table {
                border-collapse: collapse;
                table-layout: fixed;
                width: 100%;
            }
            .elementor-widget-tablemaster_table .tmp-th,
            .elementor-widget-tablemaster_table .tmp-td {
                border: none;
                border-bottom: 1px solid var(--tmp-border, #e8e8e8);
            }
            .elementor-widget-tablemaster_table .tmp-table-scroll-wrapper {
                overflow: hidden;
                border: 1px solid var(--tmp-border, #e8e8e8);
                border-radius: var(--tmp-radius, 12px);
            }
            .elementor-widget-tablemaster_table .tmp-search-wrap input.tmp-search {
                width: 250px;
                max-width: 100%;
            }
            .elementor-editor-active .elementor-widget-tablemaster_table .tmp-wrapper {
                min-height: 60px;
            }
            .elementor-editor-active .elementor-widget-tablemaster_table .tablemaster-placeholder {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100px;
                background: #f8f8f8;
                border: 2px dashed #ddd;
                border-radius: 8px;
                color: #888;
                font-size: 14px;
                font-style: italic;
                padding: 20px;
                text-align: center;
            }
            .elementor-editor-active .elementor-widget-tablemaster_table .tablemaster-placeholder .dashicons {
                font-size: 24px;
                width: 24px;
                height: 24px;
                margin-right: 8px;
                color: #D32637;
            }
        ';
    }
}
