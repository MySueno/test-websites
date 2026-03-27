<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$filled_count   = 0;
$first_filled   = '';
$group_align    = '';
$filled_cells   = array();
foreach ( $columns as $gcol ) {
    $gc_raw = $row->cells[ $gcol->id ] ?? '';
    $gc_text = trim( strip_tags( str_replace( array( '&nbsp;', "\xC2\xA0" ), ' ', $gc_raw ) ) );
    if ( $gc_text !== '' ) {
        $filled_count++;
        $filled_cells[ $gcol->id ] = $gc_raw;
        if ( $first_filled === '' ) {
            $first_filled = $gc_raw;
            if ( isset( $row->cell_aligns[ $gcol->id ] ) && in_array( $row->cell_aligns[ $gcol->id ], $valid_aligns, true ) ) {
                $group_align = $row->cell_aligns[ $gcol->id ];
            }
        }
    }
}
$g1_has_multi_content = false;
if ( $row->row_type === 'group_1' && $filled_count > 1 ) {
    $g1_has_multi_content = true;
}
$use_colspan = ( ( $row->row_type === 'group_1' && ! $g1_has_multi_content ) || $filled_count <= 1 );
$group_align_style = $group_align !== '' ? ' style="text-align:' . esc_attr( $group_align ) . ';"' : '';

if ( $use_colspan ) :
    $total_cols = count( $columns );
    if ( $collapsible ) $total_cols++;
?>
    <?php if ( $sticky_first_col && $total_cols > 1 ) : ?>
        <td class="tmp-td tmp-group-cell tmp-sticky-label"<?php echo $group_align_style; ?>>
            <div class="tmp-group-cell-inner">
                <?php if ( $collapsible ) : ?>
                    <button class="tmp-toggle-btn" aria-expanded="<?php echo $row->is_collapsed ? 'false' : 'true'; ?>" aria-label="<?php esc_attr_e( 'In-/uitklappen', TMP_TEXT_DOMAIN ); ?>">
                        <span class="tmp-toggle-icon"><?php echo $row->is_collapsed ? '▶' : '▼'; ?></span>
                    </button>
                <?php endif; ?>
                <span class="tmp-group-label"><?php echo wp_kses_post( $first_filled ); ?></span>
            </div>
        </td>
        <td class="tmp-td tmp-group-cell" colspan="<?php echo esc_attr( $total_cols - 1 ); ?>"></td>
    <?php else : ?>
        <td class="tmp-td tmp-group-cell" colspan="<?php echo esc_attr( $total_cols ); ?>"<?php echo $group_align_style; ?>>
            <div class="tmp-group-cell-inner">
                <?php if ( $collapsible ) : ?>
                    <button class="tmp-toggle-btn" aria-expanded="<?php echo $row->is_collapsed ? 'false' : 'true'; ?>" aria-label="<?php esc_attr_e( 'In-/uitklappen', TMP_TEXT_DOMAIN ); ?>">
                        <span class="tmp-toggle-icon"><?php echo $row->is_collapsed ? '▶' : '▼'; ?></span>
                    </button>
                <?php endif; ?>
                <span class="tmp-group-label"><?php echo wp_kses_post( $first_filled ); ?></span>
            </div>
        </td>
    <?php endif; ?>
<?php else :
    if ( $collapsible ) : ?>
        <td class="tmp-toggle-cell">
            <button class="tmp-toggle-btn" aria-expanded="<?php echo $row->is_collapsed ? 'false' : 'true'; ?>" aria-label="<?php esc_attr_e( 'In-/uitklappen', TMP_TEXT_DOMAIN ); ?>">
                <span class="tmp-toggle-icon"><?php echo $row->is_collapsed ? '▶' : '▼'; ?></span>
            </button>
        </td>
    <?php endif;
    $has_explicit_merges = false;
    if ( ! empty( $row->cell_merges ) ) {
        foreach ( $row->cell_merges as $_cm_v ) {
            if ( intval( $_cm_v ) > 1 ) { $has_explicit_merges = true; break; }
        }
    }
    if ( $has_explicit_merges ) :
        $grp_skip = 0;
        $grp_col_total = count( $columns );
        foreach ( $columns as $grp_col_idx => $grp_col ) :
            if ( $grp_skip > 0 ) { $grp_skip--; continue; }
            $grp_remaining = $grp_col_total - $grp_col_idx;
            $grp_cspan = isset( $row->cell_merges[ $grp_col->id ] ) ? min( max( 1, intval( $row->cell_merges[ $grp_col->id ] ) ), $grp_remaining ) : 1;
            if ( $grp_cspan > 1 ) $grp_skip = $grp_cspan - 1;
            $grp_content = $row->cells[ $grp_col->id ] ?? '';
            $grp_cell_align = isset( $row->cell_aligns[ $grp_col->id ] ) && in_array( $row->cell_aligns[ $grp_col->id ], $valid_aligns, true ) ? $row->cell_aligns[ $grp_col->id ] : '';
            $grp_cs = json_decode( $grp_col->settings, true );
            if ( ! is_array( $grp_cs ) ) $grp_cs = array();
            $grp_col_align = in_array( $grp_cs['align'] ?? 'left', $valid_aligns, true ) ? $grp_cs['align'] : 'left';
            $grp_align = $grp_cell_align !== '' ? $grp_cell_align : $grp_col_align;
        ?>
            <td class="tmp-td tmp-group-cell"
                style="text-align:<?php echo esc_attr( $grp_align ); ?>;"
                <?php if ( $grp_cspan > 1 ) : ?>colspan="<?php echo intval( $grp_cspan ); ?>"<?php endif; ?>>
                <?php echo wp_kses_post( $grp_content ); ?>
            </td>
        <?php endforeach;
    else :
        $auto_cells = array();
        foreach ( $columns as $ac_col ) {
            $ac_raw = $row->cells[ $ac_col->id ] ?? '';
            $ac_has_text = trim( strip_tags( str_replace( array( '&nbsp;', "\xC2\xA0" ), ' ', $ac_raw ) ) ) !== '';
            $ac_cell_align = isset( $row->cell_aligns[ $ac_col->id ] ) && in_array( $row->cell_aligns[ $ac_col->id ], $valid_aligns, true ) ? $row->cell_aligns[ $ac_col->id ] : '';
            $ac_cs = json_decode( $ac_col->settings, true );
            if ( ! is_array( $ac_cs ) ) $ac_cs = array();
            $ac_col_align = in_array( $ac_cs['align'] ?? 'left', $valid_aligns, true ) ? $ac_cs['align'] : 'left';
            if ( $ac_has_text ) {
                $auto_cells[] = array(
                    'content' => $ac_raw,
                    'cols'    => 1,
                    'align'   => $ac_cell_align !== '' ? $ac_cell_align : $ac_col_align,
                );
            } elseif ( ! empty( $auto_cells ) ) {
                $auto_cells[ count( $auto_cells ) - 1 ]['cols']++;
            } else {
                $auto_cells[] = array(
                    'content' => '',
                    'cols'    => 1,
                    'align'   => $ac_col_align,
                );
            }
        }
        foreach ( $auto_cells as $ac ) : ?>
            <td class="tmp-td tmp-group-cell"
                style="text-align:<?php echo esc_attr( $ac['align'] ); ?>;"
                <?php if ( $ac['cols'] > 1 ) : ?>colspan="<?php echo intval( $ac['cols'] ); ?>"<?php endif; ?>>
                <?php echo wp_kses_post( $ac['content'] ); ?>
            </td>
        <?php endforeach;
    endif;
endif; ?>
