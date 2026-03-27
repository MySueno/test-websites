<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Geen toegang.', TMP_TEXT_DOMAIN ) );

$tables = TableMaster_DB::get_all_tables();
?>
<div class="wrap tmp-wrap">
    <h1 class="wp-heading-inline">
        <?php esc_html_e( 'TableMaster Pro', TMP_TEXT_DOMAIN ); ?>
    </h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=tablemaster-new' ) ); ?>" class="page-title-action">
        <?php esc_html_e( 'Nieuwe Tabel', TMP_TEXT_DOMAIN ); ?>
    </a>
    <hr class="wp-header-end">

    <?php if ( empty( $tables ) ) : ?>
        <div class="tmp-empty-state">
            <span class="dashicons dashicons-editor-table tmp-empty-icon"></span>
            <h2><?php esc_html_e( 'Nog geen tabellen.', TMP_TEXT_DOMAIN ); ?></h2>
            <p><?php esc_html_e( 'Maak uw eerste tabel aan om te beginnen.', TMP_TEXT_DOMAIN ); ?></p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=tablemaster-new' ) ); ?>" class="button button-primary button-hero">
                <?php esc_html_e( 'Eerste tabel aanmaken', TMP_TEXT_DOMAIN ); ?>
            </a>
        </div>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped tmp-table-list">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Naam', TMP_TEXT_DOMAIN ); ?></th>
                    <th><?php esc_html_e( 'Shortcode', TMP_TEXT_DOMAIN ); ?></th>
                    <th><?php esc_html_e( 'Thema', TMP_TEXT_DOMAIN ); ?></th>
                    <th><?php esc_html_e( 'Aangemaakt op', TMP_TEXT_DOMAIN ); ?></th>
                    <th><?php esc_html_e( 'Acties', TMP_TEXT_DOMAIN ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $tables as $t ) :
                    $settings = json_decode( $t->settings, true );
                    $theme    = $settings['theme'] ?? 'custom';
                    $dot_color = '';
                    switch ( $theme ) {
                        case 'green': $dot_color = '#2e7d32'; break;
                        case 'red':   $dot_color = '#D32637'; break;
                        case 'blue':  $dot_color = '#1565c0'; break;
                        case 'grey':  $dot_color = '#424242'; break;
                        default:      $dot_color = $settings['colors']['header_bg'] ?? '#888888';
                    }
                ?>
                    <tr>
                        <td>
                            <strong>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=tablemaster-edit&id=' . $t->id ) ); ?>">
                                    <?php echo esc_html( $t->name ); ?>
                                </a>
                            </strong>
                        </td>
                        <td>
                            <code class="tmp-shortcode-display"><?php echo esc_html( '[tablemaster id="' . $t->id . '"]' ); ?></code>
                            <button class="button button-small tmp-copy-btn" data-shortcode='[tablemaster id="<?php echo esc_attr( $t->id ); ?>"]'>
                                <?php esc_html_e( 'Kopiëren', TMP_TEXT_DOMAIN ); ?>
                            </button>
                        </td>
                        <td>
                            <span class="tmp-theme-dot" style="background:<?php echo esc_attr( $dot_color ); ?>"></span>
                            <?php echo esc_html( ucfirst( $theme ) ); ?>
                        </td>
                        <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $t->created_at ) ) ); ?></td>
                        <td class="tmp-actions">
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=tablemaster-edit&id=' . $t->id ) ); ?>" class="button button-small">
                                <?php esc_html_e( 'Bewerken', TMP_TEXT_DOMAIN ); ?>
                            </a>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=tablemaster-preview&id=' . $t->id ) ); ?>" class="button button-small" target="_blank">
                                <?php esc_html_e( 'Voorbeeld', TMP_TEXT_DOMAIN ); ?>
                            </a>
                            <button class="button button-small tmp-duplicate-btn" data-id="<?php echo esc_attr( $t->id ); ?>">
                                <?php esc_html_e( 'Dupliceren', TMP_TEXT_DOMAIN ); ?>
                            </button>
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?tablemaster_export_csv=' . $t->id ), 'tablemaster_export_csv' ) ); ?>" class="button button-small" title="<?php esc_attr_e( 'Exporteer als CSV', TMP_TEXT_DOMAIN ); ?>">
                                <?php esc_html_e( 'CSV', TMP_TEXT_DOMAIN ); ?>
                            </a>
                            <?php if ( TableMaster_WPML::is_active() ) :
                                $non_default = TableMaster_WPML::get_non_default_languages();
                                if ( ! empty( $non_default ) ) :
                                    foreach ( $non_default as $lcode => $linfo ) :
                                        $prog = TableMaster_WPML::get_translation_progress( $t->id, $lcode );
                                        $lang_label = strtoupper( $lcode );
                                        if ( $prog['percent'] >= 100 ) {
                                            $badge_style = 'background:#46b450;color:#fff;';
                                            $badge_icon  = '&#10003;';
                                        } elseif ( $prog['translated'] > 0 ) {
                                            $badge_style = 'background:#ffb900;color:#fff;';
                                            $badge_icon  = $prog['percent'] . '%';
                                        } else {
                                            $badge_style = 'background:#dc3232;color:#fff;';
                                            $badge_icon  = '&#10005;';
                                        }
                            ?>
                                <a href="<?php echo esc_url( TableMaster_WPML::get_translate_url( $t->id ) . '&lang=' . $lcode ); ?>" class="button button-small" title="<?php echo esc_attr( sprintf( __( 'Vertalen naar %s — %d%%', TMP_TEXT_DOMAIN ), $linfo['native_name'], $prog['percent'] ) ); ?>" style="position:relative;">
                                    <?php echo esc_html( $lang_label ); ?>
                                    <span style="<?php echo esc_attr( $badge_style ); ?>display:inline-block;font-size:10px;line-height:1;padding:2px 4px;border-radius:3px;margin-left:3px;"><?php echo wp_kses( $badge_icon, array() ); ?></span>
                                </a>
                                    <?php endforeach;
                                else : ?>
                                <a href="<?php echo esc_url( TableMaster_WPML::get_translate_url( $t->id ) ); ?>" class="button button-small" title="<?php esc_attr_e( 'Vertaal deze tabel', TMP_TEXT_DOMAIN ); ?>">
                                    <?php esc_html_e( 'Vertalen', TMP_TEXT_DOMAIN ); ?>
                                </a>
                                <?php endif;
                            endif; ?>
                            <button class="button button-small button-link-delete tmp-delete-btn" data-id="<?php echo esc_attr( $t->id ); ?>">
                                <?php esc_html_e( 'Verwijderen', TMP_TEXT_DOMAIN ); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
