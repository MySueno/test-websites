<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$has_groups = false;
$col_meta   = array();
foreach ( $columns as $col ) {
    $cs = json_decode( $col->settings, true );
    if ( ! is_array( $cs ) ) $cs = array();
    $g1_raw = $cs['header_group1'] ?? '';
    $g2_raw = $cs['header_group2'] ?? '';
    $g1 = trim( wp_strip_all_tags( html_entity_decode( str_replace( array( '&nbsp;', "\xC2\xA0" ), ' ', $g1_raw ), ENT_QUOTES, 'UTF-8' ) ) ) !== '' ? trim( $g1_raw ) : '';
    $g2 = trim( wp_strip_all_tags( html_entity_decode( str_replace( array( '&nbsp;', "\xC2\xA0" ), ' ', $g2_raw ), ENT_QUOTES, 'UTF-8' ) ) ) !== '' ? trim( $g2_raw ) : '';
    if ( $g2 !== '' && $g1 === '' ) $g1 = $g2;
    if ( $g1 !== '' ) $has_groups = true;
    $col_meta[] = array(
        'col'   => $col,
        'cs'    => $cs,
        'g1'    => $g1,
        'g2'    => $g2,
    );
}
$max_depth = 1;
if ( $has_groups ) {
    $max_depth = 2;
    foreach ( $col_meta as $cm ) {
        if ( $cm['g2'] !== '' ) { $max_depth = 3; break; }
    }
}

$default_col_w   = $settings['default_col_width'] ?? '150px';
if ( $default_col_w !== '' && ! preg_match( '/^\d{1,4}(px|em|rem|%)$/', $default_col_w ) ) $default_col_w = '150px';
$first_col_w     = $settings['first_col_width'] ?? '';
if ( $first_col_w !== '' && ! preg_match( '/^\d{1,4}(px|em|rem|%)$/', $first_col_w ) ) $first_col_w = '';
?>
<colgroup>
    <?php if ( $collapsible ) : ?>
        <col style="width:36px;">
    <?php endif; ?>
    <?php $col_idx = 0; foreach ( $col_meta as $cg_cm ) :
        $cg_w = ( $col_idx === 0 && $first_col_w !== '' ) ? $first_col_w : $default_col_w;
        $col_idx++;
    ?>
        <col<?php if ( $cg_w !== 'auto' ) : ?> style="width:<?php echo esc_attr( $cg_w ); ?>;"<?php endif; ?>>
    <?php endforeach; ?>
</colgroup>
<thead>
<?php if ( $max_depth === 1 ) : ?>
    <tr class="tmp-header-row">
        <?php if ( $collapsible ) : ?>
            <th class="tmp-toggle-col" aria-hidden="true"></th>
        <?php endif; ?>
        <?php
        foreach ( $col_meta as $cm ) :
            $cs     = $cm['cs'];
            $col    = $cm['col'];
            $sort   = ! empty( $table_sortable );
            $th_class = 'tmp-th';
            if ( $sort )   $th_class .= ' tmp-sortable';
            $col_align = isset( $cs['align'] ) && in_array( $cs['align'], $valid_aligns, true ) ? $cs['align'] : '';
            $col_align_style = $col_align !== '' && $col_align !== 'left' ? ' style="text-align:' . esc_attr( $col_align ) . ';"' : '';
        ?>
            <th class="<?php echo esc_attr( $th_class ); ?>"
                data-col-id="<?php echo esc_attr( $col->id ); ?>"
                data-col-type="<?php echo esc_attr( $col->type ); ?>"
                <?php echo $sort ? 'role="columnheader" aria-sort="none" tabindex="0"' : ''; ?>
                <?php echo $col_align_style; ?>>
                <?php echo wp_kses_post( $col->label ); ?>
                <?php if ( $sort ) : ?>
                    <span class="tmp-sort-icon" aria-hidden="true"></span>
                <?php endif; ?>
            </th>
        <?php endforeach; ?>
    </tr>
<?php elseif ( $max_depth === 2 ) : ?>
    <tr class="tmp-header-row">
        <?php if ( $collapsible ) : ?>
            <th class="tmp-toggle-col" aria-hidden="true"></th>
        <?php endif; ?>
        <?php
        $prev_g1 = null;
        $col_count_total = count( $col_meta );
        foreach ( $col_meta as $idx => $cm ) :
            if ( $cm['g1'] === '' ) :
                $ug_sort = ! empty( $table_sortable );
                $ug_class = 'tmp-th';
                if ( $ug_sort ) $ug_class .= ' tmp-sortable';
                $ug_align = isset( $cm['cs']['align'] ) && in_array( $cm['cs']['align'], $valid_aligns, true ) ? $cm['cs']['align'] : '';
                $ug_align_style = $ug_align !== '' && $ug_align !== 'left' ? ' style="text-align:' . esc_attr( $ug_align ) . ';"' : '';
            ?>
                <th class="<?php echo esc_attr( $ug_class ); ?>"
                    data-col-id="<?php echo esc_attr( $cm['col']->id ); ?>"
                    data-col-type="<?php echo esc_attr( $cm['col']->type ); ?>"
                    <?php echo $ug_sort ? 'role="columnheader" aria-sort="none" tabindex="0"' : ''; ?>
                    <?php echo $ug_align_style; ?>>
                    <?php echo wp_kses_post( $cm['col']->label ); ?>
                    <?php if ( $ug_sort ) : ?><span class="tmp-sort-icon" aria-hidden="true"></span><?php endif; ?>
                </th>
            <?php elseif ( $cm['g1'] !== $prev_g1 ) :
                $g1_colspan = 1;
                for ( $j = $idx + 1; $j < $col_count_total; $j++ ) {
                    if ( $col_meta[ $j ]['g1'] === $cm['g1'] ) $g1_colspan++;
                    else break;
                }
                $g1_sort = ! empty( $table_sortable );
                $g1_class = 'tmp-th';
                if ( $g1_sort ) $g1_class .= ' tmp-sortable';
                $g1_align = isset( $cm['cs']['align'] ) && in_array( $cm['cs']['align'], $valid_aligns, true ) ? $cm['cs']['align'] : '';
                $g1_align_style = $g1_align !== '' && $g1_align !== 'left' ? ' style="text-align:' . esc_attr( $g1_align ) . ';"' : '';
            ?>
                <th class="<?php echo esc_attr( $g1_class ); ?>" colspan="<?php echo intval( $g1_colspan ); ?>"
                    data-col-id="<?php echo esc_attr( $cm['col']->id ); ?>"
                    data-col-type="<?php echo esc_attr( $cm['col']->type ); ?>"
                    <?php echo $g1_sort ? 'role="columnheader" aria-sort="none" tabindex="0"' : ''; ?>
                    <?php echo $g1_align_style; ?>>
                    <?php echo wp_kses_post( $cm['g1'] ); ?>
                    <?php if ( $g1_sort ) : ?><span class="tmp-sort-icon" aria-hidden="true"></span><?php endif; ?>
                </th>
            <?php endif;
            $prev_g1 = $cm['g1'];
        endforeach; ?>
    </tr>
<?php else : ?>
    <?php
    $g1_has_g2 = array();
    foreach ( $col_meta as $cm ) {
        if ( $cm['g1'] !== '' && $cm['g2'] !== '' ) {
            $g1_has_g2[ $cm['g1'] ] = true;
        }
    }
    $rows_below = ( $max_depth - 1 );
    ?>
    <tr class="tmp-header-row tmp-header-row-1">
        <?php if ( $collapsible ) : ?>
            <th class="tmp-toggle-col" rowspan="<?php echo $max_depth; ?>" aria-hidden="true"></th>
        <?php endif; ?>
        <?php
        $prev_g1 = null;
        $col_count_total = count( $col_meta );
        foreach ( $col_meta as $idx => $cm ) :
            if ( $cm['g1'] === '' ) :
                $ug_sort = ! empty( $table_sortable );
                $ug_class = 'tmp-th tmp-th-grouped';
                if ( $ug_sort ) $ug_class .= ' tmp-sortable';
                $ug_align = isset( $cm['cs']['align'] ) && in_array( $cm['cs']['align'], $valid_aligns, true ) ? $cm['cs']['align'] : '';
                $ug_align_style = $ug_align !== '' && $ug_align !== 'left' ? ' style="text-align:' . esc_attr( $ug_align ) . ';"' : '';
            ?>
                <th class="<?php echo esc_attr( $ug_class ); ?>" rowspan="<?php echo $max_depth; ?>"
                    data-col-id="<?php echo esc_attr( $cm['col']->id ); ?>"
                    data-col-type="<?php echo esc_attr( $cm['col']->type ); ?>"
                    <?php echo $ug_sort ? 'role="columnheader" aria-sort="none" tabindex="0"' : ''; ?>
                    <?php echo $ug_align_style; ?>>
                    <?php echo wp_kses_post( $cm['col']->label ); ?>
                    <?php if ( $ug_sort ) : ?><span class="tmp-sort-icon" aria-hidden="true"></span><?php endif; ?>
                </th>
            <?php elseif ( $cm['g1'] !== $prev_g1 ) :
                $g1_colspan = 1;
                for ( $j = $idx + 1; $j < $col_count_total; $j++ ) {
                    if ( $col_meta[ $j ]['g1'] === $cm['g1'] ) $g1_colspan++;
                    else break;
                }
                $g1_no_sub = ! isset( $g1_has_g2[ $cm['g1'] ] );
                $g1_sort   = $g1_no_sub && ! empty( $table_sortable );
                $g1_class  = 'tmp-th tmp-th-grouped';
                if ( $g1_sort ) $g1_class .= ' tmp-sortable';
                $g1_align = isset( $cm['cs']['align'] ) && in_array( $cm['cs']['align'], $valid_aligns, true ) ? $cm['cs']['align'] : '';
                $g1_align_style = $g1_align !== '' && $g1_align !== 'left' ? ' style="text-align:' . esc_attr( $g1_align ) . ';"' : '';
            ?>
                <th class="<?php echo esc_attr( $g1_class ); ?>"
                    colspan="<?php echo intval( $g1_colspan ); ?>"
                    <?php if ( $g1_no_sub ) : ?>rowspan="<?php echo intval( $rows_below + 1 ); ?>"<?php endif; ?>
                    <?php if ( $g1_no_sub ) : ?>data-col-id="<?php echo esc_attr( $cm['col']->id ); ?>"<?php endif; ?>
                    <?php if ( $g1_no_sub ) : ?>data-col-type="<?php echo esc_attr( $cm['col']->type ); ?>"<?php endif; ?>
                    <?php echo $g1_sort ? 'role="columnheader" aria-sort="none" tabindex="0"' : ''; ?>
                    <?php echo $g1_align_style; ?>>
                    <?php echo wp_kses_post( $cm['g1'] ); ?>
                    <?php if ( $g1_sort ) : ?><span class="tmp-sort-icon" aria-hidden="true"></span><?php endif; ?>
                </th>
            <?php endif;
            $prev_g1 = $cm['g1'];
        endforeach; ?>
    </tr>
    <tr class="tmp-header-row tmp-header-row-2">
        <?php
        $prev_g2_key = null;
        foreach ( $col_meta as $idx => $cm ) :
            if ( $cm['g1'] === '' ) continue;
            if ( ! isset( $g1_has_g2[ $cm['g1'] ] ) ) continue;
            $sort_r2 = ! empty( $table_sortable );
            if ( $cm['g2'] === '' ) :
                $th2_class = 'tmp-th tmp-th-grouped';
                if ( $sort_r2 ) $th2_class .= ' tmp-sortable';
                $r2_align = isset( $cm['cs']['align'] ) && in_array( $cm['cs']['align'], $valid_aligns, true ) ? $cm['cs']['align'] : '';
                $r2_align_style = $r2_align !== '' && $r2_align !== 'left' ? ' style="text-align:' . esc_attr( $r2_align ) . ';"' : '';
            ?>
                <th class="<?php echo esc_attr( $th2_class ); ?>" rowspan="2"
                    data-col-id="<?php echo esc_attr( $cm['col']->id ); ?>"
                    data-col-type="<?php echo esc_attr( $cm['col']->type ); ?>"
                    <?php echo $sort_r2 ? 'role="columnheader" aria-sort="none" tabindex="0"' : ''; ?>
                    <?php echo $r2_align_style; ?>>
                    <?php echo wp_kses_post( $cm['col']->label ); ?>
                    <?php if ( $sort_r2 ) : ?><span class="tmp-sort-icon" aria-hidden="true"></span><?php endif; ?>
                </th>
            <?php else :
                $g2_key = $cm['g1'] . '|||' . $cm['g2'];
                if ( $g2_key !== $prev_g2_key ) :
                    $g2_colspan = 1;
                    for ( $j = $idx + 1; $j < $col_count_total; $j++ ) {
                        $j_g2_key = $col_meta[ $j ]['g1'] . '|||' . $col_meta[ $j ]['g2'];
                        if ( $j_g2_key === $g2_key ) $g2_colspan++;
                        else break;
                    }
            ?>
                <th class="tmp-th tmp-th-grouped" colspan="<?php echo intval( $g2_colspan ); ?>"><?php echo wp_kses_post( $cm['g2'] ); ?></th>
            <?php endif;
                $prev_g2_key = $g2_key;
            endif;
        endforeach; ?>
    </tr>
    <tr class="tmp-header-row tmp-header-row-3">
        <?php foreach ( $col_meta as $idx => $cm ) :
            if ( $cm['g1'] === '' ) continue;
            if ( ! isset( $g1_has_g2[ $cm['g1'] ] ) ) continue;
            if ( $cm['g2'] === '' ) continue;
            $sort   = ! empty( $table_sortable );
            $th_class = 'tmp-th tmp-th-grouped';
            if ( $sort )   $th_class .= ' tmp-sortable';
            $leaf_align = isset( $cm['cs']['align'] ) && in_array( $cm['cs']['align'], $valid_aligns, true ) ? $cm['cs']['align'] : '';
            $leaf_align_style = $leaf_align !== '' && $leaf_align !== 'left' ? ' style="text-align:' . esc_attr( $leaf_align ) . ';"' : '';
        ?>
            <th class="<?php echo esc_attr( $th_class ); ?>"
                data-col-id="<?php echo esc_attr( $cm['col']->id ); ?>"
                data-col-type="<?php echo esc_attr( $cm['col']->type ); ?>"
                <?php echo $sort ? 'role="columnheader" aria-sort="none" tabindex="0"' : ''; ?>
                <?php echo $leaf_align_style; ?>>
                <?php echo wp_kses_post( $cm['col']->label ); ?>
                <?php if ( $sort ) : ?>
                    <span class="tmp-sort-icon" aria-hidden="true"></span>
                <?php endif; ?>
            </th>
        <?php endforeach; ?>
    </tr>
<?php endif; ?>
</thead>
