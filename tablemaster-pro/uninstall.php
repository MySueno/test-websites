<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$settings = get_option( 'tablemaster_settings', array() );
$delete_data = isset( $settings['delete_data_on_uninstall'] ) && $settings['delete_data_on_uninstall'] === '1';

if ( $delete_data ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tablemaster_cells" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tablemaster_rows" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tablemaster_columns" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tablemaster_tables" );

    delete_option( 'tablemaster_db_version' );
    delete_option( 'tablemaster_settings' );
    delete_option( 'tablemaster_wpml_registered' );
}

delete_transient( 'tmp_update_check' );

$wpdb->query( $wpdb->prepare(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
    '_transient_tmp_data_%',
    '_transient_timeout_tmp_data_%'
) );
