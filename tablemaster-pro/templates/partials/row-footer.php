<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$total_cols_footer = count( $columns );
if ( $collapsible ) $total_cols_footer++;
$footer_content_parts = array();
$footer_align = '';
foreach ( $columns as $fcol ) {
    $fc_raw = $row->cells[ $fcol->id ] ?? '';
    $fc = trim( strip_tags( str_replace( array( '&nbsp;', "\xC2\xA0" ), ' ', $fc_raw ) ) );
    if ( $fc !== '' ) {
        $footer_content_parts[] = $fc_raw;
        if ( $footer_align === '' && isset( $row->cell_aligns[ $fcol->id ] ) && in_array( $row->cell_aligns[ $fcol->id ], $valid_aligns, true ) ) {
            $footer_align = $row->cell_aligns[ $fcol->id ];
        }
    }
}
$footer_label = implode( ' ', $footer_content_parts );
$footer_align_style = $footer_align !== '' ? 'text-align:' . esc_attr( $footer_align ) . ';' : '';
?>

<?php if ( $sticky_first_col && $total_cols_footer > 1 ) : ?>
    <td class="tmp-td tmp-footer-cell tmp-sticky-label"<?php echo $footer_align_style ? ' style="' . esc_attr( $footer_align_style ) . '"' : ''; ?>>
        <?php echo wp_kses_post( $footer_label ); ?>
    </td>
    <td class="tmp-td tmp-footer-cell" colspan="<?php echo esc_attr( $total_cols_footer - 1 ); ?>"></td>
<?php else : ?>
    <td class="tmp-td tmp-footer-cell" colspan="<?php echo esc_attr( $total_cols_footer ); ?>"<?php echo $footer_align_style ? ' style="' . esc_attr( $footer_align_style ) . '"' : ''; ?>>
        <?php echo wp_kses_post( $footer_label ); ?>
    </td>
<?php endif; ?>
