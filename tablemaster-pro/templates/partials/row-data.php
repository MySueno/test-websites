<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<?php if ( $collapsible ) : ?>
    <td class="tmp-toggle-cell">&nbsp;</td>
<?php endif; ?>

<?php
$data_skip = 0;
$data_col_total = count( $columns );
foreach ( $columns as $data_col_idx => $col ) :
    if ( $data_skip > 0 ) { $data_skip--; continue; }
    $cs       = json_decode( $col->settings, true );
    $col_align = in_array( $cs['align'] ?? 'left', $valid_aligns, true ) ? $cs['align'] : 'left';
    $cell_align_val = isset( $row->cell_aligns[ $col->id ] ) && in_array( $row->cell_aligns[ $col->id ], $valid_aligns, true ) ? $row->cell_aligns[ $col->id ] : '';
    $align    = $cell_align_val !== '' ? $cell_align_val : $col_align;
    $col_type = sanitize_text_field( $col->type );
    $td_class = 'tmp-td';
    $remaining = $data_col_total - $data_col_idx;
    $cell_colspan = isset( $row->cell_merges[ $col->id ] ) ? min( max( 1, intval( $row->cell_merges[ $col->id ] ) ), $remaining ) : 1;
    if ( $cell_colspan > 1 ) $data_skip = $cell_colspan - 1;

    $raw_content = $row->cells[$col->id] ?? '';
?>
    <td class="<?php echo esc_attr( $td_class ); ?>"
        style="text-align:<?php echo esc_attr( $align ); ?>;"
        data-col-id="<?php echo esc_attr( $col->id ); ?>"
        data-col-type="<?php echo esc_attr( $col_type ); ?>"
        data-label="<?php echo esc_attr( wp_strip_all_tags( $col->label ) ); ?>"
        <?php if ( $cell_colspan > 1 ) : ?>colspan="<?php echo intval( $cell_colspan ); ?>"<?php endif; ?>>
        <?php if ( $col_type === 'link' ) : ?>
            <?php if ( filter_var( $raw_content, FILTER_VALIDATE_URL ) ) : ?>
                <a href="<?php echo esc_url( $raw_content ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $raw_content ); ?></a>
            <?php else : ?>
                <?php echo wp_kses_post( $raw_content ); ?>
            <?php endif; ?>
        <?php elseif ( $col_type === 'image' ) : ?>
            <?php if ( $raw_content ) : ?>
                <img src="<?php echo esc_url( $raw_content ); ?>" alt="" class="tmp-cell-image" loading="lazy">
            <?php endif; ?>
        <?php elseif ( $col_type === 'html' && $inline_html ) : ?>
            <?php echo wp_kses( $raw_content, array(
                'strong' => array(), 'b' => array(), 'em' => array(), 'i' => array(),
                'a' => array( 'href' => array(), 'target' => array(), 'rel' => array(), 'title' => array() ),
                'br' => array(), 'span' => array( 'style' => array(), 'class' => array() ),
                'p' => array( 'style' => array(), 'class' => array() ),
                'ul' => array(), 'ol' => array(), 'li' => array(),
                'sub' => array(), 'sup' => array(), 'small' => array(),
                'table' => array(), 'thead' => array(), 'tbody' => array(),
                'tr' => array(), 'th' => array(), 'td' => array( 'colspan' => array(), 'rowspan' => array() ),
            ) ); ?>
        <?php else : ?>
            <?php echo wp_kses_post( $raw_content ); ?>
        <?php endif; ?>
    </td>
<?php endforeach; ?>
