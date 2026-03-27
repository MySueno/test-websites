<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="tmp-controls tmp-controls-bottom">
    <?php if ( $show_pagination ) : ?>
        <span class="tmp-info-text"></span>
        <nav class="tmp-pagination" aria-label="<?php esc_attr_e( 'Tabel paginering', TMP_TEXT_DOMAIN ); ?>"></nav>
    <?php endif; ?>
</div>

<?php if ( $show_search && $search_pos === 'bottom' ) : ?>
    <div class="tmp-controls tmp-search-center" style="margin-top:0.75em;">
        <div class="tmp-search-wrap">
            <label class="screen-reader-text" for="<?php echo esc_attr( $table_uid ); ?>-search"><?php esc_html_e( 'Zoeken', TMP_TEXT_DOMAIN ); ?></label>
            <input type="search" id="<?php echo esc_attr( $table_uid ); ?>-search" class="tmp-search" placeholder="<?php esc_attr_e( 'Zoeken…', TMP_TEXT_DOMAIN ); ?>">
        </div>
    </div>
<?php endif; ?>

<?php
$export_enabled = ! empty( $settings['enable_export'] );
if ( $export_enabled ) :
?>
<div class="tmp-export-bar">
    <button class="button tmp-export-btn" data-format="csv"><?php esc_html_e( 'CSV exporteren', TMP_TEXT_DOMAIN ); ?></button>
    <button class="button tmp-export-btn" data-format="print"><?php esc_html_e( 'Afdrukken', TMP_TEXT_DOMAIN ); ?></button>
</div>
<?php endif; ?>
