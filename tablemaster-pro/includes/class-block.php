<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TableMaster_Block {

    public function register() {
        add_action( 'init', array( $this, 'register_block' ) );
    }

    public function register_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        wp_register_script(
            'tablemaster-block',
            TMP_PLUGIN_URL . 'assets/js/block.js',
            array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render' ),
            TMP_VERSION,
            false
        );

        $tables = TableMaster_DB::get_all_tables();
        $options = array();
        foreach ( $tables as $t ) {
            $options[] = array( 'value' => $t->id, 'label' => $t->name );
        }

        wp_localize_script( 'tablemaster-block', 'tableMasterBlock', array(
            'tables'     => $options,
            'editorUrl'  => admin_url( 'admin.php?page=tablemaster-edit&id=' ),
        ) );

        register_block_type( 'tablemaster/table', array(
            'editor_script'   => 'tablemaster-block',
            'render_callback' => array( $this, 'render_block' ),
            'attributes'      => array(
                'tableId' => array( 'type' => 'number', 'default' => 0 ),
            ),
        ) );
    }

    public function render_block( $attributes ) {
        $id = intval( $attributes['tableId'] ?? 0 );
        if ( ! $id ) {
            return '<p>' . esc_html__( 'Selecteer een tabel in het blok paneel.', TMP_TEXT_DOMAIN ) . '</p>';
        }
        ob_start();
        echo do_shortcode( '[tablemaster id="' . $id . '"]' );
        return ob_get_clean();
    }
}
