<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<style>
#<?php echo esc_attr( $table_uid ); ?> {
    --tmp-header-bg:    <?php echo esc_attr( $header_bg ); ?>;
    --tmp-header-text:  <?php echo esc_attr( $header_text ); ?>;
    --tmp-group1-bg:    <?php echo esc_attr( $group1_bg ); ?>;
    --tmp-group1-text:  <?php echo esc_attr( $group1_text ); ?>;
    --tmp-group2-bg:    <?php echo esc_attr( $group2_bg ); ?>;
    --tmp-group2-text:  <?php echo esc_attr( $group2_text ); ?>;
    --tmp-group3-bg:    <?php echo esc_attr( $group3_bg ); ?>;
    --tmp-group3-text:  <?php echo esc_attr( $group3_text ); ?>;
    --tmp-footer-bg:    <?php echo esc_attr( $footer_bg ); ?>;
    --tmp-footer-text:  <?php echo esc_attr( $footer_text ); ?>;
    --tmp-odd-bg:       <?php echo esc_attr( $odd_bg ); ?>;
    --tmp-even-bg:      <?php echo esc_attr( $even_bg ); ?>;
    --tmp-hover-bg:     <?php echo esc_attr( $hover_bg ); ?>;
    --tmp-border:       <?php echo esc_attr( $border_color ); ?>;
    --tmp-accent:       <?php echo esc_attr( $accent_color ); ?>;
    --tmp-radius:       <?php echo intval( $border_radius ); ?>px;
<?php if ( $first_col_bg !== '' ) : ?>
    --tmp-first-col-bg:   <?php echo esc_attr( $first_col_bg ); ?>;
<?php endif; ?>
<?php if ( $first_col_text !== '' ) : ?>
    --tmp-first-col-text: <?php echo esc_attr( $first_col_text ); ?>;
<?php endif; ?>
}
<?php
$font_css_map = array(
    'header'  => '.tmp-th',
    'group_1' => '.tmp-type-group_1 td',
    'group_2' => '.tmp-type-group_2 td',
    'group_3' => '.tmp-type-group_3 td',
    'footer'  => '.tmp-type-footer td',
    'data'    => '.tmp-type-data td',
);
foreach ( $font_css_map as $fk => $selector ) :
    $f = $fonts[ $fk ] ?? array();
    $rules = array();
    $allowed_font_sizes = array( '10px', '11px', '12px', '13px', '14px', '16px', '18px', '20px', '22px', '24px' );
    if ( ! empty( $f['size'] ) && in_array( $f['size'], $allowed_font_sizes, true ) ) {
        $rules[] = 'font-size:' . $f['size'];
    }
    if ( ! empty( $f['bold'] ) )   $rules[] = 'font-weight:bold';
    if ( ! empty( $f['italic'] ) ) $rules[] = 'font-style:italic';
    if ( ! empty( $rules ) ) :
?>
#<?php echo esc_attr( $table_uid ); ?> <?php echo $selector; ?> { <?php echo implode( ';', $rules ); ?>; }
<?php endif; endforeach; ?>
<?php if ( $max_width !== '' ) : ?>
#<?php echo esc_attr( $table_uid ); ?> { max-width: <?php echo esc_attr( $max_width ); ?>; }
<?php endif; ?>
</style>
