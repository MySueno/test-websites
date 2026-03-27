/**
 * TableMaster Pro - Gutenberg Block
 */
(function (blocks, element, editor, components, serverSideRender) {
    var el               = element.createElement;
    var InspectorControls= editor.InspectorControls;
    var PanelBody        = components.PanelBody;
    var SelectControl    = components.SelectControl;
    var Button           = components.Button;
    var ServerSideRender = serverSideRender;

    var tableMasterBlock = window.tableMasterBlock || {};
    var tableOptions     = (tableMasterBlock.tables || []).map(function (t) {
        return { value: t.value, label: t.label };
    });
    tableOptions.unshift({ value: 0, label: '— Selecteer een tabel —' });

    blocks.registerBlockType('tablemaster/table', {
        title:       'TableMaster Tabel',
        icon:        'editor-table',
        category:    'text',
        description: 'Voeg een interactieve TableMaster tabel in.',
        attributes: {
            tableId: { type: 'number', default: 0 }
        },
        edit: function (props) {
            var attrs   = props.attributes;
            var setAttr = props.setAttributes;

            return [
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, { title: 'Tabel selecteren', initialOpen: true },
                        el(SelectControl, {
                            label:    'Kies een tabel',
                            value:    attrs.tableId,
                            options:  tableOptions,
                            onChange: function (val) { setAttr({ tableId: parseInt(val, 10) }); }
                        }),
                        attrs.tableId
                            ? el(Button, {
                                isLink: true,
                                href:   tableMasterBlock.editorUrl + attrs.tableId,
                                target: '_blank',
                                style:  { display: 'block', marginTop: 8 }
                              }, 'Tabel bewerken in TableMaster')
                            : null
                    )
                ),
                attrs.tableId
                    ? el(ServerSideRender, {
                        key:   'preview',
                        block: 'tablemaster/table',
                        attributes: { tableId: attrs.tableId }
                      })
                    : el('div', {
                        key:   'placeholder',
                        style: { padding: 24, textAlign: 'center', background: '#f0f4f8', border: '2px dashed #ccc', borderRadius: 4 }
                      }, 'Selecteer een tabel in het blokpaneel aan de rechterkant.')
            ];
        },
        save: function () {
            return null; // Server-side rendered
        }
    });

})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor || window.wp.editor,
    window.wp.components,
    window.wp.serverSideRender
);
