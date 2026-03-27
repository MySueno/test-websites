<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Geen toegang.', TMP_TEXT_DOMAIN ) );

$table_id  = intval( $_GET['id'] ?? 0 );
$is_new    = ! $table_id;
$table     = $table_id ? TableMaster_DB::get_table( $table_id ) : null;
$settings  = $table ? json_decode( $table->settings, true ) : array();
$table_name= $table ? $table->name : '';

$colors        = $settings['colors'] ?? array();
$presets       = TableMaster_Settings::get_color_presets();
$active_theme  = $settings['theme'] ?? 'red';
$default_colors= $presets['red'];

$c = array_merge( $default_colors, $colors );

$page_title = $is_new
    ? __( 'Nieuwe Tabel aanmaken', TMP_TEXT_DOMAIN )
    : __( 'Tabel bewerken', TMP_TEXT_DOMAIN );
?>
<div class="wrap tmp-wrap">
    <h1>
        <?php echo esc_html( $page_title ); ?>
        <?php if ( ! $is_new ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=tablemaster' ) ); ?>" class="page-title-action">
                &larr; <?php esc_html_e( 'Alle Tabellen', TMP_TEXT_DOMAIN ); ?>
            </a>
            <?php if ( TableMaster_WPML::is_active() ) : ?>
                <a href="<?php echo esc_url( TableMaster_WPML::get_translate_url( $table_id ) ); ?>" class="page-title-action">
                    <span class="dashicons dashicons-translation" style="margin-top:3px;margin-right:2px;font-size:16px;"></span>
                    <?php esc_html_e( 'Vertalen', TMP_TEXT_DOMAIN ); ?>
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </h1>

    <?php if ( ! $is_new ) : ?>
        <div class="tmp-shortcode-bar">
            <span><?php esc_html_e( 'Shortcode:', TMP_TEXT_DOMAIN ); ?></span>
            <code id="tmp-shortcode-value">[tablemaster id="<?php echo esc_attr( $table_id ); ?>"]</code>
            <button class="button button-small tmp-copy-btn" data-shortcode='[tablemaster id="<?php echo esc_attr( $table_id ); ?>"]'>
                <?php esc_html_e( 'Kopiëren', TMP_TEXT_DOMAIN ); ?>
            </button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=tablemaster-preview&id=' . $table_id ) ); ?>"
               class="button button-small" target="_blank" style="margin-left:8px;">
                <span class="dashicons dashicons-visibility" style="margin-top:3px;margin-right:3px;"></span>
                <?php esc_html_e( 'Bekijk op website', TMP_TEXT_DOMAIN ); ?>
            </a>
            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?tablemaster_export_csv=' . $table_id ), 'tablemaster_export_csv' ) ); ?>"
               class="button button-small" style="margin-left:8px;">
                <span class="dashicons dashicons-download" style="margin-top:3px;margin-right:3px;"></span>
                <?php esc_html_e( 'CSV exporteren', TMP_TEXT_DOMAIN ); ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="tmp-editor-layout">

        <!-- LEFT PANEL -->
        <div class="tmp-panel tmp-panel-left">
            <div class="tmp-panel-header">
                <h2><?php esc_html_e( 'Tabelstructuur', TMP_TEXT_DOMAIN ); ?></h2>
                <div class="tmp-table-name-field">
                    <label for="tmp-table-name"><?php esc_html_e( 'Tabelnaam (intern):', TMP_TEXT_DOMAIN ); ?></label>
                    <input type="text" id="tmp-table-name" value="<?php echo esc_attr( $table_name ); ?>" placeholder="<?php esc_attr_e( 'Bijv. Productenlijst', TMP_TEXT_DOMAIN ); ?>" class="regular-text">
                </div>
            </div>

            <!-- TABLE DATA -->
            <div class="tmp-section">
                <div class="tmp-section-header">
                    <h3><?php esc_html_e( 'Tabelgegevens', TMP_TEXT_DOMAIN ); ?></h3>
                    <div class="tmp-row-buttons">
                        <button id="tmp-add-column" class="button button-secondary button-small" title="<?php esc_attr_e( 'Kolom toevoegen', TMP_TEXT_DOMAIN ); ?>">+ <?php esc_html_e( 'Kolom', TMP_TEXT_DOMAIN ); ?></button>
                        <span class="tmp-btn-separator"></span>
                        <button id="tmp-add-row"    class="button button-secondary button-small" title="<?php esc_attr_e( 'Normale datarij toevoegen', TMP_TEXT_DOMAIN ); ?>">+ <?php esc_html_e( 'Rij', TMP_TEXT_DOMAIN ); ?></button>
                        <button id="tmp-add-group1" class="button button-secondary button-small tmp-group1-btn" title="<?php esc_attr_e( 'Groepsrij niveau 1 — lege cellen worden automatisch samengevoegd', TMP_TEXT_DOMAIN ); ?>">+ <?php esc_html_e( 'Groep 1', TMP_TEXT_DOMAIN ); ?></button>
                        <button id="tmp-add-group2" class="button button-secondary button-small tmp-group2-btn" title="<?php esc_attr_e( 'Groepsrij niveau 2 — sub-groep onder G1', TMP_TEXT_DOMAIN ); ?>">+ <?php esc_html_e( 'Groep 2', TMP_TEXT_DOMAIN ); ?></button>
                        <button id="tmp-add-group3" class="button button-secondary button-small tmp-group3-btn" title="<?php esc_attr_e( 'Groepsrij niveau 3 — sub-groep onder G2', TMP_TEXT_DOMAIN ); ?>">+ <?php esc_html_e( 'Groep 3', TMP_TEXT_DOMAIN ); ?></button>
                        <button id="tmp-add-footer" class="button button-secondary button-small tmp-footer-btn" title="<?php esc_attr_e( 'Afsluitende rij onderaan de tabel', TMP_TEXT_DOMAIN ); ?>">+ <?php esc_html_e( 'Afsluitrij', TMP_TEXT_DOMAIN ); ?></button>
                        <span class="tmp-btn-separator"></span>
                        <button id="tmp-import-csv" class="button button-secondary button-small" title="<?php esc_attr_e( 'Importeer data vanuit een CSV-bestand', TMP_TEXT_DOMAIN ); ?>">
                            <span class="dashicons dashicons-upload" style="margin-top:3px;margin-right:2px;font-size:14px;"></span>
                            <?php esc_html_e( 'CSV importeren', TMP_TEXT_DOMAIN ); ?>
                        </button>
                        <input type="file" id="tmp-csv-file" accept=".csv,.tsv,.txt" style="display:none;">
                    </div>
                </div>
                <div class="tmp-rows-hint tmp-hint"><?php esc_html_e( 'Klik op kolommen om ze te selecteren en samen te voegen. Dubbelklik op een kolomnaam om te bewerken. Sleep rijen om te herordenen.', TMP_TEXT_DOMAIN ); ?></div>
                <div id="tmp-cell-toolbar" class="tmp-cell-toolbar tmp-toolbar-disabled">
                    <div class="tmp-toolbar-group">
                        <button type="button" id="tmp-tb-bold" class="tmp-tb-btn" title="<?php esc_attr_e( 'Vet (Ctrl+B)', TMP_TEXT_DOMAIN ); ?>"><strong>B</strong></button>
                        <button type="button" id="tmp-tb-italic" class="tmp-tb-btn" title="<?php esc_attr_e( 'Cursief (Ctrl+I)', TMP_TEXT_DOMAIN ); ?>"><em>I</em></button>
                        <button type="button" id="tmp-tb-link" class="tmp-tb-btn" title="<?php esc_attr_e( 'Link invoegen', TMP_TEXT_DOMAIN ); ?>"><span class="dashicons dashicons-admin-links"></span></button>
                        <button type="button" id="tmp-tb-bullet" class="tmp-tb-btn" title="<?php esc_attr_e( 'Opsommingslijst', TMP_TEXT_DOMAIN ); ?>"><span class="dashicons dashicons-editor-ul"></span></button>
                    </div>
                    <span class="tmp-toolbar-sep"></span>
                    <div class="tmp-toolbar-group tmp-align-group">
                        <button type="button" id="tmp-tb-align-left" class="tmp-tb-btn tmp-tb-align tmp-tb-align-active" data-align="left" title="<?php esc_attr_e( 'Links uitlijnen', TMP_TEXT_DOMAIN ); ?>"><span class="dashicons dashicons-editor-alignleft"></span></button>
                        <button type="button" id="tmp-tb-align-center" class="tmp-tb-btn tmp-tb-align" data-align="center" title="<?php esc_attr_e( 'Centreren', TMP_TEXT_DOMAIN ); ?>"><span class="dashicons dashicons-editor-aligncenter"></span></button>
                        <button type="button" id="tmp-tb-align-right" class="tmp-tb-btn tmp-tb-align" data-align="right" title="<?php esc_attr_e( 'Rechts uitlijnen', TMP_TEXT_DOMAIN ); ?>"><span class="dashicons dashicons-editor-alignright"></span></button>
                    </div>
                    <span class="tmp-toolbar-sep"></span>
                    <div class="tmp-toolbar-group">
                        <button type="button" id="tmp-tb-delete-row" class="tmp-tb-btn tmp-tb-danger" title="<?php esc_attr_e( 'Rij verwijderen', TMP_TEXT_DOMAIN ); ?>"><span class="dashicons dashicons-table-row-delete"></span></button>
                        <button type="button" id="tmp-tb-delete-col" class="tmp-tb-btn tmp-tb-danger" title="<?php esc_attr_e( 'Kolom verwijderen', TMP_TEXT_DOMAIN ); ?>"><span class="dashicons dashicons-table-col-delete"></span></button>
                    </div>
                    <span class="tmp-toolbar-sep"></span>
                    <span id="tmp-tb-cell-ref" class="tmp-toolbar-ref"></span>
                </div>
                <div id="tmp-rows-wrapper" class="tmp-rows-wrapper">
                    <div class="tmp-rows-empty tmp-hint"><?php esc_html_e( 'Klik op "+ Kolom" om te beginnen.', TMP_TEXT_DOMAIN ); ?></div>
                </div>
            </div>

            <div id="tmp-columns-container" style="display:none;"></div>

            <div class="tmp-save-bar">
                <button id="tmp-save-all" class="button button-primary button-large">
                    <span class="dashicons dashicons-saved"></span>
                    <?php esc_html_e( 'Alles opslaan', TMP_TEXT_DOMAIN ); ?>
                </button>
                <span id="tmp-save-status" class="tmp-save-status"></span>
            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="tmp-panel tmp-panel-right">
            <div class="tmp-tabs">
                <button class="tmp-tab active" data-tab="colors"><?php esc_html_e( 'Kleuren', TMP_TEXT_DOMAIN ); ?></button>
                <button class="tmp-tab" data-tab="display"><?php esc_html_e( 'Weergave', TMP_TEXT_DOMAIN ); ?></button>
                <button class="tmp-tab" data-tab="font"><?php esc_html_e( 'Font', TMP_TEXT_DOMAIN ); ?></button>
                <button class="tmp-tab" data-tab="advanced"><?php esc_html_e( 'Geavanceerd', TMP_TEXT_DOMAIN ); ?></button>
            </div>

            <!-- COLORS TAB -->
            <div class="tmp-tab-content active" id="tmp-tab-colors">
                <?php
                $color_sections = array(
                    array(
                        'title' => __( 'Koptekst', TMP_TEXT_DOMAIN ),
                        'bg'    => array( 'key' => 'header_bg',   'label' => __( 'Achtergrond', TMP_TEXT_DOMAIN ) ),
                        'text'  => array( 'key' => 'header_text', 'label' => __( 'Tekst', TMP_TEXT_DOMAIN ) ),
                    ),
                    array(
                        'title' => __( 'Groep 1', TMP_TEXT_DOMAIN ),
                        'bg'    => array( 'key' => 'group1_bg',   'label' => __( 'Achtergrond', TMP_TEXT_DOMAIN ) ),
                        'text'  => array( 'key' => 'group1_text', 'label' => __( 'Tekst', TMP_TEXT_DOMAIN ) ),
                    ),
                    array(
                        'title' => __( 'Groep 2', TMP_TEXT_DOMAIN ),
                        'bg'    => array( 'key' => 'group2_bg',   'label' => __( 'Achtergrond', TMP_TEXT_DOMAIN ) ),
                        'text'  => array( 'key' => 'group2_text', 'label' => __( 'Tekst', TMP_TEXT_DOMAIN ) ),
                    ),
                    array(
                        'title' => __( 'Groep 3', TMP_TEXT_DOMAIN ),
                        'bg'    => array( 'key' => 'group3_bg',   'label' => __( 'Achtergrond', TMP_TEXT_DOMAIN ) ),
                        'text'  => array( 'key' => 'group3_text', 'label' => __( 'Tekst', TMP_TEXT_DOMAIN ) ),
                    ),
                    array(
                        'title' => __( 'Afsluitrij', TMP_TEXT_DOMAIN ),
                        'bg'    => array( 'key' => 'footer_bg',   'label' => __( 'Achtergrond', TMP_TEXT_DOMAIN ) ),
                        'text'  => array( 'key' => 'footer_text', 'label' => __( 'Tekst', TMP_TEXT_DOMAIN ) ),
                    ),
                    array(
                        'title' => __( 'Eerste kolom', TMP_TEXT_DOMAIN ),
                        'bg'    => array( 'key' => 'first_col_bg',   'label' => __( 'Achtergrond', TMP_TEXT_DOMAIN ) ),
                        'text'  => array( 'key' => 'first_col_text', 'label' => __( 'Tekst', TMP_TEXT_DOMAIN ) ),
                    ),
                    array(
                        'title' => __( 'Rijen', TMP_TEXT_DOMAIN ),
                        'bg'    => array( 'key' => 'odd_bg',  'label' => __( 'Oneven', TMP_TEXT_DOMAIN ) ),
                        'text'  => array( 'key' => 'even_bg', 'label' => __( 'Even', TMP_TEXT_DOMAIN ) ),
                    ),
                );
                $optional_color_keys = array( 'first_col_bg', 'first_col_text' );
                foreach ( $color_sections as $section ) :
                    $bg_val   = $c[ $section['bg']['key'] ] ?? '';
                    $text_val = $c[ $section['text']['key'] ] ?? '';
                    $bg_is_optional   = in_array( $section['bg']['key'], $optional_color_keys, true );
                    $text_is_optional = in_array( $section['text']['key'], $optional_color_keys, true );
                    $bg_default   = $bg_is_optional ? '' : ( $bg_val ?: '#ffffff' );
                    $text_default = $text_is_optional ? '' : ( $text_val ?: '#ffffff' );
                ?>
                    <div class="tmp-color-section">
                        <div class="tmp-color-section-title"><?php echo esc_html( $section['title'] ); ?></div>
                        <div class="tmp-color-row">
                            <div class="tmp-color-field">
                                <label><?php echo esc_html( $section['bg']['label'] ); ?></label>
                                <input type="text" class="tmp-color-picker"
                                       data-color-key="<?php echo esc_attr( $section['bg']['key'] ); ?>"
                                       value="<?php echo esc_attr( $bg_default ); ?>"
                                       data-default-color="<?php echo esc_attr( $bg_default ); ?>">
                            </div>
                            <div class="tmp-color-field">
                                <label><?php echo esc_html( $section['text']['label'] ); ?></label>
                                <input type="text" class="tmp-color-picker"
                                       data-color-key="<?php echo esc_attr( $section['text']['key'] ); ?>"
                                       value="<?php echo esc_attr( $text_default ); ?>"
                                       data-default-color="<?php echo esc_attr( $text_default ); ?>">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="tmp-color-section">
                    <div class="tmp-color-section-title"><?php esc_html_e( 'Tabelkleuren (hover, rand & accent)', TMP_TEXT_DOMAIN ); ?></div>
                    <div class="tmp-color-row">
                        <div class="tmp-color-field">
                            <label><?php esc_html_e( 'Hover', TMP_TEXT_DOMAIN ); ?></label>
                            <input type="text" class="tmp-color-picker"
                                   data-color-key="hover_bg"
                                   value="<?php echo esc_attr( $c['hover_bg'] ?? '#f0f0f0' ); ?>"
                                   data-default-color="<?php echo esc_attr( $c['hover_bg'] ?? '#f0f0f0' ); ?>">
                        </div>
                        <div class="tmp-color-field">
                            <label><?php esc_html_e( 'Rand', TMP_TEXT_DOMAIN ); ?></label>
                            <input type="text" class="tmp-color-picker"
                                   data-color-key="border_color"
                                   value="<?php echo esc_attr( $c['border_color'] ?? '#e8e8e8' ); ?>"
                                   data-default-color="<?php echo esc_attr( $c['border_color'] ?? '#e8e8e8' ); ?>">
                        </div>
                    </div>
                    <div class="tmp-color-row">
                        <div class="tmp-color-field">
                            <label><?php esc_html_e( 'Accent', TMP_TEXT_DOMAIN ); ?></label>
                            <input type="text" class="tmp-color-picker"
                                   data-color-key="accent_color"
                                   value="<?php echo esc_attr( $c['accent_color'] ?? '#D32637' ); ?>"
                                   data-default-color="<?php echo esc_attr( $c['accent_color'] ?? '#D32637' ); ?>">
                        </div>
                        <div class="tmp-color-field"></div>
                    </div>
                </div>

                <p class="description" style="margin-top:12px;font-style:italic;"><?php esc_html_e( 'Kleuren worden direct toegepast op de tabel links.', TMP_TEXT_DOMAIN ); ?></p>
            </div>

            <!-- DISPLAY TAB -->
            <div class="tmp-tab-content" id="tmp-tab-display">
                <div class="tmp-form-group">
                    <label>
                        <input type="checkbox" id="tmp-search" <?php checked( $settings['search'] ?? true ); ?>>
                        <?php esc_html_e( 'Zoekbalk tonen', TMP_TEXT_DOMAIN ); ?>
                    </label>
                </div>
                <div class="tmp-form-group tmp-indent" id="tmp-search-position-group">
                    <label for="tmp-search-position"><?php esc_html_e( 'Positie zoekbalk:', TMP_TEXT_DOMAIN ); ?></label>
                    <select id="tmp-search-position">
                        <?php
                        $sp = $settings['search_position'] ?? 'right';
                        foreach ( array( 'left' => __( 'Links', TMP_TEXT_DOMAIN ), 'right' => __( 'Rechts', TMP_TEXT_DOMAIN ), 'top' => __( 'Boven (gecentreerd)', TMP_TEXT_DOMAIN ), 'bottom' => __( 'Onder', TMP_TEXT_DOMAIN ) ) as $val => $lbl ) {
                            echo '<option value="' . esc_attr( $val ) . '"' . selected( $sp, $val, false ) . '>' . esc_html( $lbl ) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="tmp-form-group">
                    <label>
                        <input type="checkbox" id="tmp-pagination" <?php checked( $settings['pagination'] ?? true ); ?>>
                        <?php esc_html_e( 'Paginering inschakelen', TMP_TEXT_DOMAIN ); ?>
                    </label>
                </div>
                <div class="tmp-form-group tmp-indent" id="tmp-per-page-group">
                    <label for="tmp-per-page"><?php esc_html_e( 'Items per pagina:', TMP_TEXT_DOMAIN ); ?></label>
                    <select id="tmp-per-page">
                        <?php
                        $pp = intval( $settings['per_page'] ?? 10 );
                        foreach ( array( 5, 10, 25, 50, 100, -1 ) as $val ) {
                            $lbl = $val === -1 ? __( 'Alle', TMP_TEXT_DOMAIN ) : $val;
                            echo '<option value="' . esc_attr( $val ) . '"' . selected( $pp, $val, false ) . '>' . esc_html( $lbl ) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="tmp-form-group tmp-indent">
                    <label>
                        <input type="checkbox" id="tmp-per-page-selector" <?php checked( $settings['per_page_selector'] ?? true ); ?>>
                        <?php esc_html_e( 'Items-per-pagina selector tonen', TMP_TEXT_DOMAIN ); ?>
                    </label>
                </div>
                <div class="tmp-form-group">
                    <label>
                        <input type="checkbox" id="tmp-collapsible" <?php checked( $settings['collapsible_groups'] ?? false ); ?>>
                        <?php esc_html_e( 'Groepen in-/uitklapbaar maken', TMP_TEXT_DOMAIN ); ?>
                    </label>
                    <p class="description"><?php esc_html_e( 'Groepsrijen (G1/G2/G3) kunnen worden in- en uitgeklapt door bezoekers.', TMP_TEXT_DOMAIN ); ?></p>
                </div>
                <div class="tmp-form-group">
                    <label for="tmp-default-col-width"><?php esc_html_e( 'Standaard kolombreedte:', TMP_TEXT_DOMAIN ); ?></label>
                    <input type="text" id="tmp-default-col-width" value="<?php echo esc_attr( $settings['default_col_width'] ?? '150px' ); ?>" class="small-text" placeholder="150px" style="width:100px;">
                    <p class="description"><?php esc_html_e( 'Bijv. 150px, 10%, 8em. Geldt voor alle kolommen in de tabel.', TMP_TEXT_DOMAIN ); ?></p>
                </div>
                <div class="tmp-form-group">
                    <label for="tmp-first-col-width"><?php esc_html_e( 'Eerste kolom breedte:', TMP_TEXT_DOMAIN ); ?></label>
                    <input type="text" id="tmp-first-col-width" value="<?php echo esc_attr( $settings['first_col_width'] ?? '' ); ?>" class="small-text" placeholder="standaard" style="width:100px;">
                    <p class="description"><?php esc_html_e( 'Optioneel. Overschrijft de standaard breedte voor de eerste kolom. Bijv. 250px, 200px.', TMP_TEXT_DOMAIN ); ?></p>
                </div>
                <div class="tmp-form-group">
                    <label for="tmp-max-width"><?php esc_html_e( 'Maximale tabelbreedte:', TMP_TEXT_DOMAIN ); ?></label>
                    <input type="text" id="tmp-max-width" value="<?php echo esc_attr( $settings['max_width'] ?? '' ); ?>" class="small-text" placeholder="100%" style="width:100px;">
                    <p class="description"><?php esc_html_e( 'Bijv. 800px, 90%, 60vw. Laat leeg voor volledige containerbreedte. Wordt meegenomen bij vertalingen.', TMP_TEXT_DOMAIN ); ?></p>
                </div>
                <div class="tmp-form-group">
                    <label for="tmp-max-height"><?php esc_html_e( 'Maximale tabelhoogte:', TMP_TEXT_DOMAIN ); ?></label>
                    <input type="text" id="tmp-max-height" value="<?php echo esc_attr( $settings['max_height'] ?? '' ); ?>" class="small-text" placeholder="geen limiet" style="width:100px;">
                    <p class="description"><?php esc_html_e( 'Bijv. 400px, 50vh. Bij overschrijding verschijnt automatisch een verticale scrollbar.', TMP_TEXT_DOMAIN ); ?></p>
                </div>
            </div>

            <!-- FONT TAB -->
            <div class="tmp-tab-content" id="tmp-tab-font">
                <?php
                $fonts = $settings['fonts'] ?? array();
                $font_rows = array(
                    'header'     => __( 'Koptekst', TMP_TEXT_DOMAIN ),
                    'group_1'    => __( 'Groep 1 (G1)', TMP_TEXT_DOMAIN ),
                    'group_2'    => __( 'Groep 2 (G2)', TMP_TEXT_DOMAIN ),
                    'group_3'    => __( 'Groep 3 (G3)', TMP_TEXT_DOMAIN ),
                    'footer'     => __( 'Afsluitrij', TMP_TEXT_DOMAIN ),
                    'data'       => __( 'Datarijen', TMP_TEXT_DOMAIN ),
                );
                $font_sizes = array( '10', '11', '12', '13', '14', '16', '18', '20', '22', '24' );
                foreach ( $font_rows as $fkey => $flabel ) :
                    $f = $fonts[ $fkey ] ?? array();
                ?>
                <div class="tmp-form-group tmp-font-row" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:6px 0;border-bottom:1px solid #eee;">
                    <strong style="min-width:120px;"><?php echo esc_html( $flabel ); ?></strong>
                    <select class="tmp-font-size" data-font-key="<?php echo esc_attr( $fkey ); ?>" style="width:70px;">
                        <option value=""><?php esc_html_e( 'Auto', TMP_TEXT_DOMAIN ); ?></option>
                        <?php foreach ( $font_sizes as $fs ) : ?>
                            <option value="<?php echo esc_attr( $fs ); ?>px" <?php selected( $f['size'] ?? '', $fs . 'px' ); ?>><?php echo esc_html( $fs ); ?>px</option>
                        <?php endforeach; ?>
                    </select>
                    <label style="display:flex;align-items:center;gap:4px;">
                        <input type="checkbox" class="tmp-font-bold" data-font-key="<?php echo esc_attr( $fkey ); ?>" <?php checked( $f['bold'] ?? false ); ?>>
                        <strong>B</strong>
                    </label>
                    <label style="display:flex;align-items:center;gap:4px;">
                        <input type="checkbox" class="tmp-font-italic" data-font-key="<?php echo esc_attr( $fkey ); ?>" <?php checked( $f['italic'] ?? false ); ?>>
                        <em>I</em>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ADVANCED TAB -->
            <div class="tmp-tab-content" id="tmp-tab-advanced">
                <div class="tmp-form-group">
                    <label>
                        <input type="checkbox" id="tmp-sortable" <?php checked( $settings['sortable'] ?? true ); ?>>
                        <?php esc_html_e( 'Kolommen sorteerbaar', TMP_TEXT_DOMAIN ); ?>
                    </label>
                    <p class="description"><?php esc_html_e( 'Bezoekers kunnen op kolomkoppen klikken om te sorteren.', TMP_TEXT_DOMAIN ); ?></p>
                </div>
                <div class="tmp-form-group">
                    <label>
                        <input type="checkbox" id="tmp-column-filters" <?php checked( $settings['column_filters'] ?? false ); ?>>
                        <?php esc_html_e( 'Kolomfilters tonen (dropdown per kolom)', TMP_TEXT_DOMAIN ); ?>
                    </label>
                </div>
                <div class="tmp-form-group">
                    <label>
                        <input type="checkbox" id="tmp-enable-export" <?php checked( $settings['enable_export'] ?? false ); ?>>
                        <?php esc_html_e( 'Exportknoppen tonen op de frontend (CSV/Print)', TMP_TEXT_DOMAIN ); ?>
                    </label>
                </div>
                <hr style="margin:16px 0;">
                <div class="tmp-form-group">
                    <label>
                        <input type="checkbox" id="tmp-inline-html" <?php checked( $settings['inline_html'] ?? false ); ?>>
                        <?php esc_html_e( 'Inline HTML toestaan in cellen', TMP_TEXT_DOMAIN ); ?>
                    </label>
                    <p class="description"><?php esc_html_e( 'Laat HTML-opmaak toe in celinhoud. Gebruik alleen als u de inhoud vertrouwt.', TMP_TEXT_DOMAIN ); ?></p>
                </div>
                <div class="tmp-form-group">
                    <label>
                        <input type="checkbox" id="tmp-sticky-first-col" <?php checked( $settings['sticky_first_col'] ?? false ); ?>>
                        <?php esc_html_e( 'Eerste kolom vastzetten bij horizontaal scrollen', TMP_TEXT_DOMAIN ); ?>
                    </label>
                    <p class="description"><?php esc_html_e( 'De eerste kolom blijft zichtbaar als de tabel horizontaal gescrolld wordt (scroll-modus).', TMP_TEXT_DOMAIN ); ?></p>
                </div>
                <div class="tmp-form-group">
                    <label>
                        <input type="checkbox" id="tmp-sticky-header" <?php checked( $settings['sticky_header'] ?? false ); ?>>
                        <?php esc_html_e( 'Header vastzetten bij verticaal scrollen', TMP_TEXT_DOMAIN ); ?>
                    </label>
                    <p class="description"><?php esc_html_e( 'De kolomkoppen blijven zichtbaar als de bezoeker door de tabel scrollt, tot het einde van de tabel.', TMP_TEXT_DOMAIN ); ?></p>
                </div>
                <div class="tmp-form-group">
                    <label for="tmp-default-sort-col"><?php esc_html_e( 'Standaard sorteerkolom (index, 0 = eerste):', TMP_TEXT_DOMAIN ); ?></label>
                    <input type="number" id="tmp-default-sort-col" value="<?php echo esc_attr( $settings['default_sort_col'] ?? '' ); ?>" min="0" class="small-text">
                </div>
                <div class="tmp-form-group">
                    <label for="tmp-default-sort-dir"><?php esc_html_e( 'Standaard sorteerrichting:', TMP_TEXT_DOMAIN ); ?></label>
                    <select id="tmp-default-sort-dir">
                        <?php
                        $sd = $settings['default_sort_dir'] ?? 'asc';
                        echo '<option value="asc"'  . selected( $sd, 'asc', false )  . '>' . esc_html__( 'Oplopend', TMP_TEXT_DOMAIN ) . '</option>';
                        echo '<option value="desc"' . selected( $sd, 'desc', false ) . '>' . esc_html__( 'Aflopend', TMP_TEXT_DOMAIN ) . '</option>';
                        ?>
                    </select>
                </div>
                <?php if ( ! $is_new ) : ?>
                <div class="tmp-form-group">
                    <label><?php esc_html_e( 'Tabel ID (voor debuggen):', TMP_TEXT_DOMAIN ); ?></label>
                    <code><?php echo esc_html( $table_id ); ?></code>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- end right panel -->
    </div><!-- end editor layout -->
</div>
