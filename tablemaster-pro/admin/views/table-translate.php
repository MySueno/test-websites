<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Geen toegang.', TMP_TEXT_DOMAIN ) );

$table_id    = intval( $_GET['id'] ?? 0 );
$target_lang = sanitize_text_field( $_GET['lang'] ?? '' );

if ( ! $table_id ) wp_die( esc_html__( 'Geen tabel opgegeven.', TMP_TEXT_DOMAIN ) );

$table = TableMaster_DB::get_table( $table_id );
if ( ! $table ) wp_die( esc_html__( 'Tabel niet gevonden.', TMP_TEXT_DOMAIN ) );

$data     = TableMaster_DB::get_table_data( $table_id, '' );
$columns  = $data['columns'] ?? array();
$rows     = $data['rows'] ?? array();
$settings = json_decode( $table->settings, true );
$context  = TableMaster_WPML::get_context( $table_id );

$all_langs = apply_filters( 'wpml_active_languages', array(), 'skip_missing=0' );
$active_langs = is_array( $all_langs ) ? $all_langs : array();

$default_lang = TableMaster_WPML::get_default_language();

if ( ! $target_lang && count( $active_langs ) > 0 ) {
    foreach ( $active_langs as $code => $l ) {
        if ( $code !== $default_lang ) {
            $target_lang = $code;
            break;
        }
    }
}

if ( ! TableMaster_WPML::is_active() || ! TableMaster_WPML::is_string_translation_active() ) {
    echo '<div class="wrap tmp-wrap"><h1>' . esc_html__( 'Vertaling', TMP_TEXT_DOMAIN ) . '</h1>';
    echo '<div class="notice notice-warning"><p>' . esc_html__( 'WPML en WPML String Translation moeten actief zijn om tabellen te vertalen.', TMP_TEXT_DOMAIN ) . '</p></div>';
    echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=tablemaster-edit&id=' . $table_id ) ) . '" class="button">&larr; ' . esc_html__( 'Terug naar bewerken', TMP_TEXT_DOMAIN ) . '</a></p>';
    echo '</div>';
    return;
}

$non_default_langs = array_filter( $active_langs, function( $l ) use ( $default_lang ) {
    return $l['code'] !== $default_lang;
} );

if ( empty( $non_default_langs ) ) {
    echo '<div class="wrap tmp-wrap"><h1>' . esc_html__( 'Vertaling', TMP_TEXT_DOMAIN ) . '</h1>';
    echo '<div class="notice notice-info"><p>' . esc_html__( 'Er is momenteel slechts één taal ingesteld in WPML. Voeg eerst een extra taal toe in WPML voordat je tabellen kunt vertalen.', TMP_TEXT_DOMAIN ) . '</p></div>';
    echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=tablemaster-edit&id=' . $table_id ) ) . '" class="button">&larr; ' . esc_html__( 'Terug naar bewerken', TMP_TEXT_DOMAIN ) . '</a></p>';
    echo '</div>';
    return;
}

$valid_target_langs = array_keys( $active_langs );
if ( $target_lang && ! in_array( $target_lang, $valid_target_langs, true ) ) {
    wp_die( esc_html__( 'Ongeldige doeltaal.', TMP_TEXT_DOMAIN ) );
}

$source_name = isset( $active_langs[ $default_lang ] ) ? $active_langs[ $default_lang ]['native_name'] : $default_lang;
$target_name = isset( $active_langs[ $target_lang ] ) ? $active_langs[ $target_lang ]['native_name'] : $target_lang;
$source_flag = isset( $active_langs[ $default_lang ]['country_flag_url'] ) ? $active_langs[ $default_lang ]['country_flag_url'] : '';
$target_flag = isset( $active_langs[ $target_lang ]['country_flag_url'] ) ? $active_langs[ $target_lang ]['country_flag_url'] : '';

if ( ! function_exists( 'tmp_get_translation' ) ) {
    function tmp_get_translation( $context, $name, $lang ) {
        global $wpdb;
        if ( ! defined( 'WPML_ST_VERSION' ) ) return '';

        $strings_table      = $wpdb->prefix . 'icl_strings';
        $translations_table = $wpdb->prefix . 'icl_string_translations';

        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $strings_table ) ) !== $strings_table ) return '';
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $translations_table ) ) !== $translations_table ) return '';

        $string_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$strings_table} WHERE context = %s AND name = %s",
            $context, $name
        ) );
        if ( ! $string_id ) return '';
        $translation = $wpdb->get_var( $wpdb->prepare(
            "SELECT value FROM {$translations_table} WHERE string_id = %d AND language = %s AND status = 10",
            $string_id, $lang
        ) );
        return $translation !== null ? $translation : '';
    }
}

$caption = ! empty( $settings['caption'] ) ? $settings['caption'] : '';

$total_fields      = 0;
$translated_fields = 0;

$translate_rows = array();

$translate_rows[] = array(
    'section' => 'Tabel',
    'label'   => __( 'Naam', TMP_TEXT_DOMAIN ),
    'name'    => 'table_name',
    'original'=> $table->name,
    'type'    => 'input',
);
$total_fields++;
$tn = tmp_get_translation( $context, 'table_name', $target_lang );
if ( $tn !== '' ) $translated_fields++;

if ( $caption !== '' ) {
    $translate_rows[] = array(
        'section' => '',
        'label'   => __( 'Onderschrift', TMP_TEXT_DOMAIN ),
        'name'    => 'caption',
        'original'=> $caption,
        'type'    => 'input',
    );
    $total_fields++;
    $tc = tmp_get_translation( $context, 'caption', $target_lang );
    if ( $tc !== '' ) $translated_fields++;
}

$translate_rows[] = array( 'section' => __( 'Kolomnamen', TMP_TEXT_DOMAIN ) );

foreach ( $columns as $col ) {
    $string_name = 'col_' . $col->id . '_label';
    $translated  = tmp_get_translation( $context, $string_name, $target_lang );
    $translate_rows[] = array(
        'section' => '',
        'label'   => $col->label,
        'name'    => $string_name,
        'original'=> $col->label,
        'type'    => 'input',
    );
    $total_fields++;
    if ( $translated !== '' ) $translated_fields++;
}

$registered_hg = array();
$has_hg = false;
foreach ( $columns as $col ) {
    $cs = json_decode( $col->settings, true );
    $g1 = trim( $cs['header_group1'] ?? '' );
    $g2 = trim( $cs['header_group2'] ?? '' );
    if ( $g1 !== '' && ! isset( $registered_hg[ 'g1_' . $g1 ] ) ) {
        if ( ! $has_hg ) {
            $translate_rows[] = array( 'section' => __( 'Kolomgroepen', TMP_TEXT_DOMAIN ) );
            $has_hg = true;
        }
        $gname = 'header_group1_' . md5( $g1 );
        $gt    = tmp_get_translation( $context, $gname, $target_lang );
        $translate_rows[] = array(
            'section' => '',
            'label'   => $g1 . ' (niveau 1)',
            'name'    => $gname,
            'original'=> $g1,
            'type'    => 'input',
        );
        $total_fields++;
        if ( $gt !== '' ) $translated_fields++;
        $registered_hg[ 'g1_' . $g1 ] = true;
    }
    if ( $g2 !== '' && ! isset( $registered_hg[ 'g2_' . $g2 ] ) ) {
        if ( ! $has_hg ) {
            $translate_rows[] = array( 'section' => __( 'Kolomgroepen', TMP_TEXT_DOMAIN ) );
            $has_hg = true;
        }
        $gname = 'header_group2_' . md5( $g2 );
        $gt    = tmp_get_translation( $context, $gname, $target_lang );
        $translate_rows[] = array(
            'section' => '',
            'label'   => $g2 . ' (niveau 2)',
            'name'    => $gname,
            'original'=> $g2,
            'type'    => 'input',
        );
        $total_fields++;
        if ( $gt !== '' ) $translated_fields++;
        $registered_hg[ 'g2_' . $g2 ] = true;
    }
}

$known_translations = array();

foreach ( $translate_rows as $tr ) {
    if ( ! isset( $tr['name'] ) || ! isset( $tr['original'] ) ) continue;
    $tv = tmp_get_translation( $context, $tr['name'], $target_lang );
    if ( $tv !== '' ) {
        $known_translations[ $tr['original'] ] = $tv;
    }
}

if ( ! function_exists( 'tmp_get_global_translations' ) ) {
    function tmp_get_global_translations( $originals, $target_lang ) {
        global $wpdb;
        if ( ! defined( 'WPML_ST_VERSION' ) || empty( $originals ) ) return array();

        $strings_table      = $wpdb->prefix . 'icl_strings';
        $translations_table = $wpdb->prefix . 'icl_string_translations';

        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $strings_table ) ) !== $strings_table ) return array();

        $placeholders = implode( ',', array_fill( 0, count( $originals ), '%s' ) );
        $query_args   = array_merge( $originals, array( $target_lang ) );

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT s.value AS original, t.value AS translation
             FROM {$strings_table} s
             INNER JOIN {$translations_table} t ON t.string_id = s.id
             WHERE s.context LIKE 'tablemaster-pro - Table %'
               AND s.value IN ({$placeholders})
               AND t.language = %s
               AND t.status = 10
               AND t.value != ''
             GROUP BY s.value",
            $query_args
        ) );

        $map = array();
        if ( $results ) {
            foreach ( $results as $r ) {
                $map[ $r->original ] = $r->translation;
            }
        }
        return $map;
    }
}

$all_originals = array();
foreach ( $translate_rows as $tr ) {
    if ( isset( $tr['original'] ) && $tr['original'] !== '' && ! isset( $known_translations[ $tr['original'] ] ) ) {
        $all_originals[] = $tr['original'];
    }
}

$all_originals_cells = array();
foreach ( $rows as $row ) {
    foreach ( $columns as $col ) {
        $content = $row->cells[ $col->id ] ?? '';
        if ( trim( $content ) !== '' && ! isset( $known_translations[ $content ] ) ) {
            $all_originals_cells[] = $content;
        }
    }
}
$all_originals = array_unique( array_merge( $all_originals, $all_originals_cells ) );

if ( ! empty( $all_originals ) ) {
    $global_translations = tmp_get_global_translations( $all_originals, $target_lang );
    foreach ( $global_translations as $orig => $trans ) {
        if ( ! isset( $known_translations[ $orig ] ) ) {
            $known_translations[ $orig ] = $trans;
        }
    }
}

$has_cell_rows = false;
$cell_rows     = array();
foreach ( $rows as $row ) {
    $row_label = ucfirst( str_replace( '_', ' ', $row->row_type ) );
    foreach ( $columns as $col ) {
        $content = $row->cells[ $col->id ] ?? '';
        if ( trim( $content ) === '' ) continue;
        $has_cell_rows   = true;
        $string_name     = 'row_' . $row->id . '_col_' . $col->id;
        $translated      = tmp_get_translation( $context, $string_name, $target_lang );
        $is_multiline    = ( strpos( $content, "\n" ) !== false || mb_strlen( $content ) > 60 );
        $prefilled       = false;

        if ( $translated === '' && isset( $known_translations[ $content ] ) ) {
            $translated = $known_translations[ $content ];
            $prefilled  = true;
        }

        if ( $translated !== '' && ! isset( $known_translations[ $content ] ) ) {
            $known_translations[ $content ] = $translated;
        }

        $cell_rows[] = array(
            'section'    => '',
            'label'      => $col->label,
            'badge'      => $row_label,
            'name'       => $string_name,
            'original'   => $content,
            'type'       => $is_multiline ? 'textarea' : 'input',
            'prefilled'  => $prefilled,
        );
        $total_fields++;
        if ( $translated !== '' && ! $prefilled ) $translated_fields++;
    }
}

if ( $has_cell_rows ) {
    $translate_rows[] = array( 'section' => __( 'Celinhoud', TMP_TEXT_DOMAIN ) );
    $translate_rows   = array_merge( $translate_rows, $cell_rows );
}
?>
<div class="wrap tmp-wrap">
    <h1>
        <?php printf( esc_html__( 'Vertaling: %s', TMP_TEXT_DOMAIN ), esc_html( $table->name ) ); ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=tablemaster-edit&id=' . $table_id ) ); ?>" class="page-title-action">
            &larr; <?php esc_html_e( 'Terug naar bewerken', TMP_TEXT_DOMAIN ); ?>
        </a>
    </h1>

    <div class="tmp-translate-header">
        <div class="tmp-translate-lang tmp-translate-source">
            <?php if ( $source_flag ) : ?><img src="<?php echo esc_url( $source_flag ); ?>" alt=""><?php endif; ?>
            <strong><?php esc_html_e( 'Origineel:', TMP_TEXT_DOMAIN ); ?></strong> <?php echo esc_html( $source_name ); ?>
        </div>
        <div class="tmp-translate-lang-arrow">&#10132;</div>
        <div class="tmp-translate-lang tmp-translate-target">
            <?php if ( count( $active_langs ) > 2 ) : ?>
                <strong><?php esc_html_e( 'Vertaling naar het:', TMP_TEXT_DOMAIN ); ?></strong>
                <select id="tmp-translate-lang-select">
                    <?php foreach ( $active_langs as $code => $l ) :
                        if ( $code === $default_lang ) continue;
                    ?>
                        <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, $target_lang ); ?>>
                            <?php echo esc_html( $l['native_name'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else : ?>
                <?php if ( $target_flag ) : ?><img src="<?php echo esc_url( $target_flag ); ?>" alt=""><?php endif; ?>
                <strong><?php esc_html_e( 'Vertaling naar het:', TMP_TEXT_DOMAIN ); ?></strong> <?php echo esc_html( $target_name ); ?>
            <?php endif; ?>
        </div>
        <div class="tmp-translate-progress">
            <span class="tmp-translate-progress-count" id="tmp-progress-count"><?php echo intval( $translated_fields ); ?></span>
            / <?php echo intval( $total_fields ); ?>
            <span class="tmp-translate-progress-label"><?php esc_html_e( 'vertaald', TMP_TEXT_DOMAIN ); ?></span>
        </div>
        <div class="tmp-translate-export">
            <a id="tmp-export-translated-csv" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?tablemaster_export_translated_csv=' . $table_id . '&lang=' . $target_lang ), 'tablemaster_export_translated_csv' ) ); ?>" class="button button-secondary">
                <span class="dashicons dashicons-download" style="vertical-align:middle;margin-right:4px;"></span>
                <?php esc_html_e( 'CSV exporteren (vertaald)', TMP_TEXT_DOMAIN ); ?>
            </a>
        </div>
    </div>

    <div id="tmp-translate-incomplete-notice" class="notice notice-warning inline" style="margin:10px 0;<?php echo $translated_fields >= $total_fields ? 'display:none;' : ''; ?>">
        <p><strong><?php esc_html_e( 'Let op:', TMP_TEXT_DOMAIN ); ?></strong>
        <?php esc_html_e( 'De vertaling is nog niet compleet. Zolang niet alle velden zijn vertaald, zal de tabel op de frontend in de standaardtaal worden getoond. Pas als 100% is vertaald, wordt de vertaalde versie getoond aan bezoekers.', TMP_TEXT_DOMAIN ); ?></p>
    </div>

    <div id="tmp-translate-complete-notice" class="notice notice-success inline" style="margin:10px 0;<?php echo $translated_fields >= $total_fields ? '' : 'display:none;'; ?>">
        <p><strong>&#10003; <?php esc_html_e( 'Vertaling compleet!', TMP_TEXT_DOMAIN ); ?></strong>
        <?php esc_html_e( 'Alle velden zijn vertaald. De vertaalde tabel wordt getoond aan bezoekers die de site in deze taal bekijken.', TMP_TEXT_DOMAIN ); ?></p>
    </div>

    <div class="tmp-translate-table-wrap">
        <table class="tmp-translate-table">
            <thead>
                <tr>
                    <th class="tmp-translate-context"><?php esc_html_e( 'Veld', TMP_TEXT_DOMAIN ); ?></th>
                    <th class="tmp-translate-original"><?php echo esc_html( $source_name ); ?></th>
                    <th class="tmp-translate-translated"><?php echo esc_html( $target_name ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $current_section = '';
                foreach ( $translate_rows as $tr_row ) :
                    if ( isset( $tr_row['section'] ) && $tr_row['section'] !== '' && ! isset( $tr_row['name'] ) ) :
                ?>
                    <tr class="tmp-translate-section-header">
                        <td colspan="3"><strong><?php echo esc_html( $tr_row['section'] ); ?></strong></td>
                    </tr>
                    <?php
                        continue;
                    endif;

                    if ( ! isset( $tr_row['name'] ) ) continue;

                    $translated  = tmp_get_translation( $context, $tr_row['name'], $target_lang );
                    $is_prefilled = false;

                    if ( $translated === '' && isset( $known_translations[ $tr_row['original'] ] ) ) {
                        $translated = $known_translations[ $tr_row['original'] ];
                    }

                    $has_value  = ( $translated !== '' );
                    $row_class  = 'tmp-translate-row';
                    if ( $has_value && ! $is_prefilled ) $row_class .= ' tmp-translate-done';
                    if ( $is_prefilled && $has_value ) $row_class .= ' tmp-translate-prefilled';
                ?>
                <tr class="<?php echo esc_attr( $row_class ); ?>">
                    <td class="tmp-translate-context">
                        <?php if ( ! empty( $tr_row['badge'] ) ) : ?>
                            <span class="tmp-translate-row-type"><?php echo esc_html( $tr_row['badge'] ); ?></span>
                        <?php endif; ?>
                        <?php echo esc_html( $tr_row['label'] ); ?>
                    </td>
                    <td class="tmp-translate-original">
                        <div class="tmp-translate-original-text"><?php echo esc_html( $tr_row['original'] ); ?></div>
                    </td>
                    <td class="tmp-translate-translated">
                        <div class="tmp-translate-field-wrap">
                            <?php if ( $tr_row['type'] === 'textarea' ) : ?>
                                <textarea class="tmp-translate-input tmp-translate-textarea" data-string-name="<?php echo esc_attr( $tr_row['name'] ); ?>"
                                          data-original="<?php echo esc_attr( $tr_row['original'] ); ?>"
                                          placeholder="<?php echo esc_attr( $tr_row['original'] ); ?>"><?php echo esc_textarea( $translated ); ?></textarea>
                            <?php else : ?>
                                <input type="text" class="tmp-translate-input" data-string-name="<?php echo esc_attr( $tr_row['name'] ); ?>"
                                       data-original="<?php echo esc_attr( $tr_row['original'] ); ?>"
                                       value="<?php echo esc_attr( $translated ); ?>"
                                       placeholder="<?php echo esc_attr( $tr_row['original'] ); ?>">
                            <?php endif; ?>
                            <button type="button" class="tmp-translate-copy-btn" title="<?php esc_attr_e( 'Kopieer origineel', TMP_TEXT_DOMAIN ); ?>" data-original="<?php echo esc_attr( $tr_row['original'] ); ?>">
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="tmp-translate-save-bar">
        <button id="tmp-translate-save" class="button button-primary button-large">
            <?php esc_html_e( 'Vertalingen opslaan', TMP_TEXT_DOMAIN ); ?>
        </button>
        <span id="tmp-translate-status" class="tmp-save-status"></span>
    </div>
</div>

<script>
(function($) {
    var ajaxurl  = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
    var nonce    = '<?php echo esc_js( wp_create_nonce( 'tablemaster_admin' ) ); ?>';
    var tableId  = <?php echo intval( $table_id ); ?>;
    var lang     = '<?php echo esc_js( $target_lang ); ?>';
    var totalFields = <?php echo intval( $total_fields ); ?>;
    var isDirty  = false;

    function updateProgress() {
        var count = 0;
        $('.tmp-translate-row').each(function() {
            var $row   = $(this);
            var $input = $row.find('.tmp-translate-input');
            if (!$input.length) return;
            var filled = $input.val().trim() !== '';
            if (filled) {
                if (!$row.hasClass('tmp-translate-prefilled')) {
                    $row.addClass('tmp-translate-done');
                }
                count++;
            } else {
                $row.removeClass('tmp-translate-done tmp-translate-prefilled');
            }
        });
        $('#tmp-progress-count').text(count);
        var isComplete = (count >= totalFields);
        $('#tmp-translate-incomplete-notice').toggle(!isComplete);
        $('#tmp-translate-complete-notice').toggle(isComplete);
    }

    function propagateTranslation($source) {
        var original = $source.data('original');
        var val      = $source.val().trim();
        if (!original || val === '') return;

        $('.tmp-translate-input').not($source).each(function() {
            var $field = $(this);
            if ($field.data('original') === original && $field.val().trim() === '') {
                $field.val(val);
                $field.closest('.tmp-translate-row')
                      .addClass('tmp-translate-done')
                      .removeClass('tmp-translate-prefilled');
            }
        });
    }

    $('.tmp-translate-input').on('input', function() {
        isDirty = true;
        var $row = $(this).closest('.tmp-translate-row');
        $row.removeClass('tmp-translate-prefilled');
        propagateTranslation($(this));
        updateProgress();
    });

    $('.tmp-translate-input').on('change', function() {
        isDirty = true;
        propagateTranslation($(this));
        updateProgress();
    });

    $(window).on('beforeunload', function() {
        if (isDirty) {
            return '<?php echo esc_js( __( 'Je hebt niet-opgeslagen vertalingen. Weet je zeker dat je wilt vertrekken?', TMP_TEXT_DOMAIN ) ); ?>';
        }
    });

    $('.tmp-translate-copy-btn').on('click', function() {
        var original = $(this).data('original');
        var $field   = $(this).closest('.tmp-translate-field-wrap').find('.tmp-translate-input');
        $field.val(original).trigger('input').focus();
        syncLinkFieldsFromInput($field.closest('.tmp-translate-row'));
    });

    function parseLinks(html) {
        var links = [];
        if (typeof html !== 'string') return links;
        var re = /<a\s([^>]*)>([\s\S]*?)<\/a>/gi;
        var m;
        while ((m = re.exec(html)) !== null) {
            var attrs = m[1];
            var text  = m[2].replace(/<[^>]+>/g, '');
            var hm    = attrs.match(/href="([^"]*)"/);
            var tm    = attrs.match(/target="([^"]*)"/);
            links.push({
                full:   m[0],
                href:   hm ? hm[1] : '',
                target: tm ? tm[1] : '',
                text:   text,
            });
        }
        return links;
    }

    function escHtml(str) {
        return $('<span>').text(str).html();
    }

    function syncLinkFieldsFromInput($row) {
        var $input = $row.find('.tmp-translate-input');
        var val    = $input.val() || '';
        var links  = parseLinks(val);
        $row.find('.tmp-translate-link-url').each(function() {
            var idx = parseInt($(this).data('link-idx'), 10);
            $(this).val(links[idx] ? links[idx].href : '');
        });
    }

    function syncInputFromLinkFields($row) {
        var $input = $row.find('.tmp-translate-input');
        var val    = $input.val() || '';
        if (!val) return;
        var idx = 0;
        val = val.replace(/href="([^"]*)"/g, function(full, oldUrl) {
            var $urlField = $row.find('.tmp-translate-link-url[data-link-idx="' + idx + '"]');
            idx++;
            if ($urlField.length && $urlField.val().trim()) {
                return 'href="' + $urlField.val().trim() + '"';
            }
            return full;
        });
        $input.val(val).trigger('input');
    }

    function enhanceLinkTranslations() {
        $('.tmp-translate-row').each(function() {
            var $row   = $(this);
            var $input = $row.find('.tmp-translate-input');
            if (!$input.length) return;

            var rawOriginal = $input.data('original');
            if (typeof rawOriginal !== 'string') rawOriginal = rawOriginal + '';
            var links = parseLinks(rawOriginal);
            if (!links.length) return;

            $row.addClass('tmp-translate-has-links');

            var $origText = $row.find('.tmp-translate-original-text');
            var parts = rawOriginal;
            var displayParts = [];
            var remaining = rawOriginal;
            links.forEach(function(link) {
                var pos = remaining.indexOf(link.full);
                if (pos > 0) {
                    displayParts.push(escHtml(remaining.substring(0, pos)));
                }
                displayParts.push(
                    '<span class="tmp-orig-link-inline">' + escHtml(link.text) + '</span>'
                );
                remaining = remaining.substring(pos + link.full.length);
            });
            if (remaining) displayParts.push(escHtml(remaining));

            var linkInfoHtml = links.map(function(link) {
                return '<div class="tmp-orig-link-info">' +
                    '<span class="dashicons dashicons-admin-links"></span> ' +
                    '<span class="tmp-orig-link-url-display">' + escHtml(link.href) + '</span>' +
                    (link.target === '_blank' ? ' <span class="tmp-orig-link-target">(nieuw tabblad)</span>' : ' <span class="tmp-orig-link-target">(zelfde tab)</span>') +
                '</div>';
            }).join('');

            $origText.html(
                '<div class="tmp-orig-text-display">' + displayParts.join('') + '</div>' +
                linkInfoHtml
            );

            var $fieldWrap = $input.closest('.tmp-translate-field-wrap');
            var curTranslation = $input.val() || '';
            var curLinks = parseLinks(curTranslation);

            var linkFieldsHtml = '';
            links.forEach(function(link, idx) {
                var curHref = curLinks[idx] ? curLinks[idx].href : '';
                linkFieldsHtml +=
                    '<div class="tmp-translate-link-field">' +
                        '<label><span class="dashicons dashicons-admin-links"></span> Link URL' +
                        (links.length > 1 ? ' ' + (idx + 1) : '') + '</label>' +
                        '<input type="text" class="tmp-translate-link-url" ' +
                            'data-link-idx="' + idx + '" ' +
                            'placeholder="' + escHtml(link.href) + '" ' +
                            'value="' + escHtml(curHref) + '">' +
                    '</div>';
            });
            $fieldWrap.after(linkFieldsHtml);

            $row.find('.tmp-translate-link-url').on('input', function() {
                isDirty = true;
                syncInputFromLinkFields($row);
            });

            $input.on('input.linkSync', function() {
                syncLinkFieldsFromInput($row);
            });
        });
    }

    enhanceLinkTranslations();

    $('#tmp-translate-lang-select').on('change', function() {
        if (isDirty && !confirm('<?php echo esc_js( __( 'Je hebt niet-opgeslagen vertalingen. Toch van taal wisselen?', TMP_TEXT_DOMAIN ) ); ?>')) {
            return;
        }
        isDirty = false;
        var newLang = $(this).val();
        window.location.href = '<?php echo esc_js( admin_url( 'admin.php?page=tablemaster-translate&id=' . $table_id . '&lang=' ) ); ?>' + newLang;
    });

    $('#tmp-translate-save').on('click', function() {
        var $btn    = $(this);
        var $status = $('#tmp-translate-status');

        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).addClass('updating-message');

        var translations = {};
        $('.tmp-translate-input').each(function() {
            var name = $(this).data('string-name');
            var val  = $(this).val();
            translations[name] = val;
        });

        $.post(ajaxurl, {
            action:       'tablemaster_save_translations',
            nonce:        nonce,
            table_id:     tableId,
            lang:         lang,
            translations: JSON.stringify(translations),
        }, function(res) {
            $btn.prop('disabled', false).removeClass('updating-message');
            if (res.success) {
                isDirty = false;
                $('.tmp-translate-prefilled').each(function() {
                    var $row = $(this);
                    if ($row.find('.tmp-translate-input').val().trim() !== '') {
                        $row.removeClass('tmp-translate-prefilled').addClass('tmp-translate-done');
                    }
                });
                updateProgress();
                $status.removeClass('error').addClass('success').text('Vertalingen opgeslagen!');
                setTimeout(function() { $status.text('').removeClass('success'); }, 3000);
            } else {
                $status.removeClass('success').addClass('error').text(res.data && res.data.message ? res.data.message : 'Fout bij opslaan.');
            }
        }).fail(function() {
            $btn.prop('disabled', false).removeClass('updating-message');
            $status.removeClass('success').addClass('error').text('Fout bij opslaan.');
        });
    });
})(jQuery);
</script>
