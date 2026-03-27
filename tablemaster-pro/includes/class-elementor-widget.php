<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( '\Elementor\Widget_Base' ) ) return;

class TableMaster_Elementor_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'tablemaster_table';
    }

    public function get_title() {
        return 'TableMaster Pro';
    }

    public function get_icon() {
        return 'eicon-table';
    }

    public function get_categories() {
        return array( 'general' );
    }

    public function get_keywords() {
        return array( 'table', 'tabel', 'tablemaster', 'grid', 'data' );
    }

    public function get_style_depends() {
        return array( 'tablemaster-frontend' );
    }

    public function get_script_depends() {
        return array( 'tablemaster-frontend-js' );
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_table',
            array(
                'label' => 'TableMaster Pro',
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $tables = $this->get_tables_list();

        $this->add_control(
            'table_id',
            array(
                'label'       => __( 'Kies een tabel', TMP_TEXT_DOMAIN ),
                'type'        => \Elementor\Controls_Manager::SELECT,
                'default'     => '',
                'options'     => $tables,
                'label_block' => true,
                'description' => __( 'Selecteer de tabel die u wilt tonen. Maak tabellen aan via TableMaster > Tabellen.', TMP_TEXT_DOMAIN ),
            )
        );

        $this->add_control(
            'edit_table_notice',
            array(
                'type'            => \Elementor\Controls_Manager::RAW_HTML,
                'raw'             => '<a href="' . esc_url( admin_url( 'admin.php?page=tablemaster' ) ) . '" target="_blank" style="color:#D32637;text-decoration:none;font-weight:600;">' .
                                     '<span class="dashicons dashicons-external" style="font-size:14px;line-height:1.4;"></span> ' .
                                     __( 'Tabellen beheren', TMP_TEXT_DOMAIN ) . '</a>',
                'content_classes' => 'elementor-panel-alert',
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_override',
            array(
                'label' => __( 'Weergave', TMP_TEXT_DOMAIN ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_responsive_control(
            'table_max_width',
            array(
                'label'      => __( 'Maximale breedte', TMP_TEXT_DOMAIN ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array( 'px', '%', 'vw' ),
                'range'      => array(
                    'px' => array( 'min' => 200, 'max' => 2000 ),
                    '%'  => array( 'min' => 10, 'max' => 100 ),
                    'vw' => array( 'min' => 10, 'max' => 100 ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .tmp-wrapper' => 'max-width: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'table_font_size',
            array(
                'label'      => __( 'Lettergrootte', TMP_TEXT_DOMAIN ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array( 'px', 'em', 'rem' ),
                'range'      => array(
                    'px'  => array( 'min' => 10, 'max' => 24 ),
                    'em'  => array( 'min' => 0.6, 'max' => 2, 'step' => 0.1 ),
                    'rem' => array( 'min' => 0.6, 'max' => 2, 'step' => 0.1 ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .tmp-wrapper' => 'font-size: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'table_alignment',
            array(
                'label'   => __( 'Uitlijning', TMP_TEXT_DOMAIN ),
                'type'    => \Elementor\Controls_Manager::CHOOSE,
                'options' => array(
                    'left'   => array( 'title' => __( 'Links', TMP_TEXT_DOMAIN ), 'icon' => 'eicon-text-align-left' ),
                    'center' => array( 'title' => __( 'Midden', TMP_TEXT_DOMAIN ), 'icon' => 'eicon-text-align-center' ),
                    'right'  => array( 'title' => __( 'Rechts', TMP_TEXT_DOMAIN ), 'icon' => 'eicon-text-align-right' ),
                ),
                'selectors_dictionary' => array(
                    'left'   => 'margin-left: 0; margin-right: auto;',
                    'center' => 'margin-left: auto; margin-right: auto;',
                    'right'  => 'margin-left: auto; margin-right: 0;',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .tmp-wrapper' => '{{VALUE}}',
                ),
            )
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $table_id = intval( $settings['table_id'] ?? 0 );

        if ( ! $table_id ) {
            $is_edit = false;
            if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->editor ) ) {
                $is_edit = \Elementor\Plugin::$instance->editor->is_edit_mode();
            }
            if ( $is_edit ) {
                echo '<div class="tablemaster-placeholder">';
                echo '<span class="dashicons dashicons-editor-table"></span>';
                echo esc_html__( 'Selecteer een tabel in het paneel links.', TMP_TEXT_DOMAIN );
                echo '</div>';
            }
            return;
        }

        $shortcode = new TableMaster_Shortcode();
        $output = $shortcode->render( array( 'id' => $table_id ) );
        echo $output;
    }

    protected function content_template() {
    }

    private function get_tables_list() {
        global $wpdb;
        $list = array( '' => __( '— Selecteer een tabel —', TMP_TEXT_DOMAIN ) );

        $table_name = $wpdb->prefix . 'tablemaster_tables';

        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name ) {
            $tables = $wpdb->get_results( "SELECT id, name FROM {$table_name} ORDER BY name ASC" );
            if ( $tables ) {
                foreach ( $tables as $t ) {
                    $list[ $t->id ] = sprintf( '#%d — %s', $t->id, $t->name );
                }
            }
        }

        return $list;
    }
}
