<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Geen toegang.', TMP_TEXT_DOMAIN ) );

settings_errors( 'tablemaster' );
$s = TableMaster_Settings::get();
?>
<div class="wrap tmp-wrap">
    <h1><?php esc_html_e( 'TableMaster Pro — Instellingen', TMP_TEXT_DOMAIN ); ?></h1>
    <form method="post">
        <?php wp_nonce_field( 'tablemaster_save_settings', 'tablemaster_settings_nonce' ); ?>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'Standaard items per pagina', TMP_TEXT_DOMAIN ); ?></th>
                <td>
                    <select name="default_per_page">
                        <?php foreach ( array( 5, 10, 25, 50, 100 ) as $v ) : ?>
                            <option value="<?php echo esc_attr( $v ); ?>" <?php selected( $s['default_per_page'], $v ); ?>>
                                <?php echo esc_html( $v ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Tabel border-radius (px)', TMP_TEXT_DOMAIN ); ?></th>
                <td>
                    <input type="number" name="border_radius" value="<?php echo esc_attr( $s['border_radius'] ); ?>" min="0" max="50" step="1" style="width:80px;"> px
                    <p class="description"><?php esc_html_e( 'Hoekafronding van alle tabellen. Geldt direct voor alle tabellen op de frontend.', TMP_TEXT_DOMAIN ); ?></p>
                    <div id="tmp-radius-preview" style="margin-top:10px;width:280px;height:60px;border:2px solid #2e7d32;background:#f1f8e9;transition:border-radius .2s;border-radius:<?php echo intval( $s['border_radius'] ); ?>px;display:flex;align-items:center;justify-content:center;color:#555;font-size:13px;">
                        <?php esc_html_e( 'Voorbeeld border-radius', TMP_TEXT_DOMAIN ); ?>
                    </div>
                    <script>
                    jQuery(function($){
                        $('input[name="border_radius"]').on('input change', function(){
                            $('#tmp-radius-preview').css('border-radius', $(this).val() + 'px');
                        });
                    });
                    </script>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Licentiecode', TMP_TEXT_DOMAIN ); ?></th>
                <td>
                    <input type="text" name="license_key" value="<?php echo esc_attr( $s['license_key'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Voer uw licentiecode in', TMP_TEXT_DOMAIN ); ?>">
                    <p class="description"><?php esc_html_e( 'Voer uw licentiecode in om automatische updates te activeren.', TMP_TEXT_DOMAIN ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Data verwijderen bij deïnstallatie', TMP_TEXT_DOMAIN ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked( $s['delete_data_on_uninstall'], '1' ); ?>>
                        <?php esc_html_e( 'Alle tabellen en gegevens permanent verwijderen wanneer de plugin wordt verwijderd', TMP_TEXT_DOMAIN ); ?>
                    </label>
                    <p class="description" style="color:#d63638;"><?php esc_html_e( 'Let op: als deze optie is uitgeschakeld (standaard), blijven al uw tabellen en data bewaard in de database — ook na het verwijderen en opnieuw installeren van de plugin.', TMP_TEXT_DOMAIN ); ?></p>
                </td>
            </tr>
        </table>
        <?php submit_button( __( 'Instellingen opslaan', TMP_TEXT_DOMAIN ) ); ?>
    </form>
    <hr>
    <h2><?php esc_html_e( 'Plugin-informatie', TMP_TEXT_DOMAIN ); ?></h2>
    <p><?php printf( esc_html__( 'Versie: %s', TMP_TEXT_DOMAIN ), esc_html( TMP_VERSION ) ); ?></p>
    <p><?php printf( esc_html__( 'Database versie: %s', TMP_TEXT_DOMAIN ), esc_html( get_option( 'tablemaster_db_version', 'n/a' ) ) ); ?></p>
    <?php
        $raw_settings  = get_option( 'tablemaster_settings', array() );
        $saved_license = isset( $raw_settings['license_key'] ) ? $raw_settings['license_key'] : '';
        $active_url    = TableMaster_Settings::get_update_url();
    ?>
    <?php if ( ! empty( $saved_license ) ) : ?>
        <p><?php esc_html_e( 'Licentie: actief', TMP_TEXT_DOMAIN ); ?> ✅</p>
    <?php else : ?>
        <p style="color:#d63638;"><?php esc_html_e( 'Licentie: geen licentiecode ingevuld — automatische updates zijn uitgeschakeld', TMP_TEXT_DOMAIN ); ?></p>
    <?php endif; ?>
    <?php if ( ! empty( $active_url ) ) : ?>
        <p><?php esc_html_e( 'Update server: verbonden', TMP_TEXT_DOMAIN ); ?> ✅</p>
    <?php else : ?>
        <p style="color:#d63638;"><?php esc_html_e( 'Update server: niet geconfigureerd', TMP_TEXT_DOMAIN ); ?></p>
    <?php endif; ?>
</div>
