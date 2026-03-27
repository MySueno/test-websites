<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$colors   = TableMaster_Settings::sanitize_colors( $settings['colors'] ?? array() );
$table_uid = 'tmp-' . intval( $table->id ) . '-' . wp_rand( 1000, 9999 );
$global_settings = TableMaster_Settings::get();
$border_radius   = min( 50, max( 0, intval( $global_settings['border_radius'] ?? 4 ) ) );

$header_bg    = $colors['header_bg'];
$header_text  = $colors['header_text'];
$group1_bg    = $colors['group1_bg'];
$group1_text  = $colors['group1_text'];
$group2_bg    = $colors['group2_bg'];
$group2_text  = $colors['group2_text'];
$group3_bg    = $colors['group3_bg'];
$group3_text  = $colors['group3_text'];
$footer_bg    = $colors['footer_bg'];
$footer_text  = $colors['footer_text'];
$odd_bg       = $colors['odd_bg'];
$even_bg      = $colors['even_bg'];
$hover_bg      = $colors['hover_bg'];
$border_color  = $colors['border_color'];
$accent_color  = $colors['accent_color'];
$first_col_bg   = $colors['first_col_bg'] ?? '';
$first_col_text = $colors['first_col_text'] ?? '';

$allowed_search_pos = array( 'left', 'right', 'top', 'bottom' );
$allowed_mobile     = array( 'scroll' );
$allowed_sort_dir   = array( 'asc', 'desc' );

$show_search       = ! empty( $settings['search'] );
$search_pos        = in_array( $settings['search_position'] ?? '', $allowed_search_pos, true ) ? $settings['search_position'] : 'right';
$show_pagination   = ! empty( $settings['pagination'] );
$per_page          = $show_pagination ? min( 500, max( -1, intval( $settings['per_page'] ?? 10 ) ) ) : -1;
$show_pp_selector  = ! empty( $settings['per_page_selector'] );
$collapsible       = ! empty( $settings['collapsible_groups'] );
$mobile_mode       = 'scroll';
$show_col_filters  = ! empty( $settings['column_filters'] );
$caption           = sanitize_text_field( $settings['caption'] ?? '' );
$default_sort_col  = sanitize_text_field( $settings['default_sort_col'] ?? '' );
$default_sort_dir  = in_array( $settings['default_sort_dir'] ?? '', $allowed_sort_dir, true ) ? $settings['default_sort_dir'] : 'asc';
$inline_html       = ! empty( $settings['inline_html'] );
$sticky_first_col  = ! empty( $settings['sticky_first_col'] );
$sticky_header     = ! empty( $settings['sticky_header'] );
$table_sortable    = $settings['sortable'] ?? true;
$fonts             = $settings['fonts'] ?? array();
$max_width         = sanitize_text_field( $settings['max_width'] ?? '' );
if ( $max_width !== '' && ! preg_match( '/^\d{1,4}(px|em|rem|%|vw)$/', $max_width ) ) $max_width = '';
$max_height        = sanitize_text_field( $settings['max_height'] ?? '' );
if ( $max_height !== '' && ! preg_match( '/^\d{1,4}(px|em|rem|%|vh)$/', $max_height ) ) $max_height = '';

$columns = $data['columns'];
$rows    = $data['rows'];
$valid_aligns = array( 'left', 'center', 'right' );

include __DIR__ . '/partials/style.php';
?>

<div id="<?php echo esc_attr( $table_uid ); ?>"
     class="tmp-wrapper tmp-mobile-<?php echo esc_attr( $mobile_mode ); ?><?php echo $sticky_first_col ? ' tmp-sticky-first' : ''; ?><?php echo $sticky_header ? ' tmp-sticky-header' : ''; ?><?php echo ( $first_col_bg !== '' || $first_col_text !== '' ) ? ' tmp-first-col-colored' : ''; ?>"
     data-table-id="<?php echo esc_attr( $table->id ); ?>"
     data-per-page="<?php echo esc_attr( $per_page ); ?>"
     data-collapsible="<?php echo $collapsible ? '1' : '0'; ?>"
     data-default-sort-col="<?php echo esc_attr( $default_sort_col ); ?>"
     data-default-sort-dir="<?php echo esc_attr( $default_sort_dir ); ?>"
     data-mobile-mode="<?php echo esc_attr( $mobile_mode ); ?>">

    <?php include __DIR__ . '/partials/controls.php'; ?>

    <?php
    $col_count     = count( $columns ) + ( $collapsible ? 1 : 0 );
    $min_col_width = 120;
    $table_min_w   = max( 400, $col_count * $min_col_width );
    $table_style   = $mobile_mode === 'scroll' ? 'min-width:' . intval( $table_min_w ) . 'px;' : '';
    ?>
    <div class="tmp-table-scroll-wrapper"<?php if ( $max_height !== '' ) : ?> style="max-height:<?php echo esc_attr( $max_height ); ?>;overflow-y:auto;"<?php endif; ?>>
        <table class="tmp-table" role="grid" aria-label="<?php echo esc_attr( $caption ?: $table->name ); ?>"
            <?php if ( $table_style ) : ?> style="<?php echo esc_attr( $table_style ); ?>"<?php endif; ?>>
            <?php include __DIR__ . '/partials/thead.php'; ?>
            <?php include __DIR__ . '/partials/tbody.php'; ?>
        </table></div>

    <?php include __DIR__ . '/partials/footer-controls.php'; ?>

</div>
