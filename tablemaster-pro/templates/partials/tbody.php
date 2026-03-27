<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<tbody class="tmp-tbody">
    <?php
    $data_row_index = 0;
    foreach ( $rows as $row ) :
        $row_class   = 'tmp-row tmp-type-' . esc_attr( $row->row_type );
        $is_group    = in_array( $row->row_type, array( 'group_1', 'group_2', 'group_3' ), true );
        $is_footer   = $row->row_type === 'footer';
        $indent_lvl  = 0;
        switch ( $row->row_type ) {
            case 'group_2': $indent_lvl = 1; break;
            case 'group_3': $indent_lvl = 2; break;
            case 'data':    $indent_lvl = 0; break;
        }

        if ( $row->row_type === 'data' ) {
            $row_class .= $data_row_index % 2 === 0 ? ' tmp-odd' : ' tmp-even';
            $data_row_index++;
        }
    ?>
        <tr class="<?php echo esc_attr( $row_class ); ?>"
            data-row-id="<?php echo esc_attr( $row->id ); ?>"
            data-row-type="<?php echo esc_attr( $row->row_type ); ?>"
            data-parent-id="<?php echo esc_attr( $row->parent_id ?? '' ); ?>"
            <?php echo $row->is_collapsed ? 'data-collapsed="1"' : ''; ?>>

            <?php if ( $is_footer ) :
                include __DIR__ . '/row-footer.php';
            elseif ( $is_group ) :
                include __DIR__ . '/row-group.php';
            else :
                include __DIR__ . '/row-data.php';
            endif; ?>
        </tr>
    <?php endforeach; ?>
</tbody>
