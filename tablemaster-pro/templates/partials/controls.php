<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<?php if ( $show_search && $search_pos === 'top' ) : ?>
    <div class="tmp-controls tmp-controls-top tmp-search-center">
        <div class="tmp-search-wrap">
            <label class="screen-reader-text" for="<?php echo esc_attr( $table_uid ); ?>-search"><?php esc_html_e( 'Zoeken', TMP_TEXT_DOMAIN ); ?></label>
            <input type="search" id="<?php echo esc_attr( $table_uid ); ?>-search" class="tmp-search" placeholder="<?php esc_attr_e( 'Zoeken…', TMP_TEXT_DOMAIN ); ?>">
        </div>
    </div>
<?php endif; ?>

<div class="tmp-controls tmp-controls-top">
    <?php if ( $show_search && $search_pos === 'left' ) : ?>
        <div class="tmp-search-wrap">
            <label class="screen-reader-text" for="<?php echo esc_attr( $table_uid ); ?>-search"><?php esc_html_e( 'Zoeken', TMP_TEXT_DOMAIN ); ?></label>
            <input type="search" id="<?php echo esc_attr( $table_uid ); ?>-search" class="tmp-search" placeholder="<?php esc_attr_e( 'Zoeken…', TMP_TEXT_DOMAIN ); ?>">
        </div>
    <?php endif; ?>

    <div class="tmp-controls-right">
        <?php if ( $show_search && $search_pos === 'right' ) : ?>
            <div class="tmp-search-wrap">
                <label class="screen-reader-text" for="<?php echo esc_attr( $table_uid ); ?>-search"><?php esc_html_e( 'Zoeken', TMP_TEXT_DOMAIN ); ?></label>
                <input type="search" id="<?php echo esc_attr( $table_uid ); ?>-search" class="tmp-search" placeholder="<?php esc_attr_e( 'Zoeken…', TMP_TEXT_DOMAIN ); ?>">
            </div>
        <?php endif; ?>
        <?php if ( $show_pagination && $show_pp_selector ) : ?>
            <div class="tmp-per-page-wrap">
                <label for="<?php echo esc_attr( $table_uid ); ?>-per-page"><?php esc_html_e( 'Per pagina:', TMP_TEXT_DOMAIN ); ?></label>
                <select id="<?php echo esc_attr( $table_uid ); ?>-per-page" class="tmp-per-page-select">
                    <?php foreach ( array( 5, 10, 25, 50, 100, -1 ) as $opt ) : ?>
                        <option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $opt, $per_page ); ?>>
                            <?php echo $opt === -1 ? esc_html__( 'Alle', TMP_TEXT_DOMAIN ) : esc_html( $opt ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ( $show_col_filters && ! empty( $columns ) ) : ?>
<div class="tmp-col-filters">
    <?php foreach ( $columns as $col ) : ?>
        <div class="tmp-col-filter-item">
            <label><?php echo esc_html( wp_strip_all_tags( $col->label ) ); ?></label>
            <select class="tmp-col-filter" data-col-id="<?php echo esc_attr( $col->id ); ?>">
                <option value=""><?php esc_html_e( '— Alle —', TMP_TEXT_DOMAIN ); ?></option>
            </select>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
