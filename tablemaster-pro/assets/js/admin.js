/**
 * TableMaster Pro - Admin JavaScript
 * Requires: jQuery, jQuery UI Sortable, wp-color-picker
 */
(function ($) {
    'use strict';

    var cfg        = window.tableMasterAdmin || {};
    var ajaxurl    = cfg.ajaxurl   || '';
    var nonce      = cfg.nonce     || '';
    var tableId    = cfg.table_id  || 0;
    var settings   = cfg.settings  || {};
    var presets    = cfg.presets   || {};
    var listUrl    = cfg.list_url  || '';
    var lang       = cfg.lang      || '';
    var i18n       = cfg.i18n      || {};

    var columns    = [];   // [{id, label, type, settings:{align,sortable,filterable}}, ...]
    var rows       = [];   // [{id, temp_id, row_type, parent_id, parent_temp_id, cells:{col_key: content}, is_collapsed}, ...]
    var colTempIdx    = 0;
    var rowTempIdx    = 0;
    var isDirty       = false;
    var justDraggedKey = null;
    var activeCell    = null;

    var undoStack     = [];
    var redoStack     = [];
    var undoMax       = 50;

    function cloneState() {
        return JSON.parse(JSON.stringify({ columns: columns, rows: rows }));
    }

    function pushUndo() {
        undoStack.push(cloneState());
        if (undoStack.length > undoMax) undoStack.shift();
        redoStack = [];
        isDirty = true;
    }

    function doUndo() {
        if (!undoStack.length) return;
        redoStack.push(cloneState());
        var prev = undoStack.pop();
        columns = prev.columns;
        rows = prev.rows;
        isDirty = true;
        activeCell = null;
        hideCellMergeToolbar();
        rebuildRowTable();
    }

    function doRedo() {
        if (!redoStack.length) return;
        undoStack.push(cloneState());
        var next = redoStack.pop();
        columns = next.columns;
        rows = next.rows;
        isDirty = true;
        activeCell = null;
        hideCellMergeToolbar();
        rebuildRowTable();
    }

    /* ===== BOOT ===== */
    $(document).ready(function () {
        initTabs();
        initColorPickers();
        initPresetButtons();
        initColumnSortable();

        if (tableId) {
            loadStructure();
        } else {
            initDefaultTable();
        }

        bindEvents();
    });

    /* ===== TABS ===== */
    function initTabs() {
        $('.tmp-tab').on('click', function () {
            var tab = $(this).data('tab');
            $('.tmp-tab').removeClass('active');
            $('.tmp-tab-content').removeClass('active');
            $(this).addClass('active');
            $('#tmp-tab-' + tab).addClass('active');
        });
    }

    /* ===== COLOR PICKERS ===== */
    function initColorPickers() {
        var colors = (settings.colors) || {};

        $('.tmp-color-picker').each(function () {
            var $input = $(this);
            var key    = $input.data('color-key');
            if (colors[key]) {
                $input.val(colors[key]);
                $input.attr('data-default-color', colors[key]);
            }

            $input.wpColorPicker({
                change: function (event, ui) {
                    var newColor = ui.color.toString();
                    $input.val(newColor);
                    updatePreview();
                    isDirty = true;
                },
                clear: function () {
                    updatePreview();
                    isDirty = true;
                }
            });
        });

        $('.tmp-panel-right .wp-picker-input-wrap').css('display', 'inline-flex');

        $('.wp-picker-container').each(function () {
            var $container = $(this);
            var $hexInput = $container.find('input.wp-color-picker');
            var $holder = $container.find('.wp-picker-holder');
            $hexInput.off('focus.wpColorPicker focus.iris');
            $hexInput.on('focus.tmpHex', function (e) {
                e.stopImmediatePropagation();
            });
            $hexInput.on('input.tmpHex change.tmpHex', function () {
                var val = $(this).val();
                if (/^#[0-9a-fA-F]{6}$/.test(val)) {
                    $(this).iris('color', val);
                    updatePreview();
                    isDirty = true;
                }
            });
        });

        $(document).on('click.tmpColorClose', function (e) {
            var $target = $(e.target);
            var $currentContainer = $target.closest('.wp-picker-container');
            $('.wp-picker-container').each(function () {
                var $container = $(this);
                if ($container[0] === $currentContainer[0]) return;
                var $holder = $container.find('.wp-picker-holder');
                if ($holder.is(':visible')) {
                    $holder.hide();
                    $container.find('button.wp-color-result').removeClass('wp-picker-open');
                }
            });
            if ($currentContainer.length === 0) {
                $('.wp-picker-holder:visible').hide();
                $('button.wp-color-result.wp-picker-open').removeClass('wp-picker-open');
            }
        });

        updatePreview();
    }

    function getColorValues() {
        var colors = {};
        var optionalKeys = { first_col_bg: true, first_col_text: true };
        $('.tmp-color-picker').each(function () {
            var key = $(this).data('color-key');
            var val = $(this).val();
            if (optionalKeys[key]) {
                colors[key] = val || '';
            } else {
                colors[key] = val || $(this).attr('data-default-color') || '#ffffff';
            }
        });
        return colors;
    }

    /* ===== LIVE PREVIEW (applies colors to admin row table) ===== */
    function updatePreview() {
        var c = getColorValues();

        var $adminTable = $('.tmp-admin-table');
        if ($adminTable.length === 0) return;

        $adminTable.find('thead th').css({ background: c.header_bg, color: c.header_text, borderColor: 'rgba(255,255,255,0.15)' });

        $adminTable.find('.tmp-admin-row-group_1').css({ background: c.group1_bg, color: c.group1_text });
        $adminTable.find('.tmp-admin-row-group_2').css({ background: c.group2_bg, color: c.group2_text });
        $adminTable.find('.tmp-admin-row-group_3').css({ background: c.group3_bg, color: c.group3_text });
        $adminTable.find('.tmp-admin-row-footer').css({ background: c.footer_bg, color: c.footer_text });

        var dataIdx = 0;
        $adminTable.find('.tmp-admin-row-data').each(function () {
            var bg = dataIdx % 2 === 0 ? c.odd_bg : c.even_bg;
            $(this).css({ background: bg });
            var $firstCell = $(this).find('td').eq(2);
            if ((c.first_col_bg || c.first_col_text) && $firstCell.length) {
                $firstCell.css({ background: c.first_col_bg || '', color: c.first_col_text || '', fontWeight: c.first_col_bg ? '600' : '' });
            } else if ($firstCell.length) {
                $firstCell.css({ background: '', color: '', fontWeight: '' });
            }
            dataIdx++;
        });

        $adminTable.find('td').css({ borderColor: c.border_color || '#e0e0e0' });

        var fontMap = {
            header:  '.tmp-admin-header-row',
            group_1: '.tmp-admin-row-group_1',
            group_2: '.tmp-admin-row-group_2',
            group_3: '.tmp-admin-row-group_3',
            footer:  '.tmp-admin-row-footer',
            data:    '.tmp-admin-row-data'
        };
        $.each(fontMap, function (fk, sel) {
            var sizeVal   = $('.tmp-font-size[data-font-key="' + fk + '"]').val() || '';
            var boldVal   = $('.tmp-font-bold[data-font-key="' + fk + '"]').is(':checked');
            var italicVal = $('.tmp-font-italic[data-font-key="' + fk + '"]').is(':checked');
            var $targets = fk === 'header'
                ? $adminTable.find(sel).find('.tmp-col-header-label')
                : $adminTable.find(sel).find('.tmp-cell-input');
            $targets.css({
                fontSize:   sizeVal || '',
                fontWeight: boldVal ? 'bold' : '',
                fontStyle:  italicVal ? 'italic' : ''
            });
        });
    }

    /* ===== PRESETS (removed — colors are now fully custom) ===== */
    function initPresetButtons() {
    }

    /* ===== COLUMNS ===== */
    function initColumnSortable() {
        // Column sorting is no longer needed — columns are managed via table headers
    }

    function addColumn(colData) {
        var tempKey = 'new_' + (++colTempIdx);
        var col = $.extend({
            id:       0,
            temp_key: tempKey,
            label:    (i18n.add_column || 'Kolom') + ' ' + (columns.length + 1),
            type:     'text',
            settings: { align: 'left', sortable: true, filterable: true, header_group1: '', header_group2: '' }
        }, colData || {});
        if (!col.settings.header_group1) col.settings.header_group1 = '';
        if (!col.settings.header_group2) col.settings.header_group2 = '';

        pushUndo();
        columns.push(col);
        rebuildRowTable();
    }

    function renderColumnItem(col) {
        // No-op: columns are now managed inline in the row table headers
    }

    function syncColumnsFromDOM() {
        // Columns are managed inline — no DOM scan needed
    }

    /* ===== ROWS ===== */
    function addRow(rowType, parentTempId) {
        if (columns.length === 0) {
            alert(i18n.no_columns);
            return;
        }
        var tempId = 'new_row_' + (++rowTempIdx);
        var cells  = {};
        columns.forEach(function (col) {
            cells[col.temp_key || col.id] = '';
        });

        var row = {
            id:             0,
            temp_id:        tempId,
            row_type:       rowType || 'data',
            parent_id:      0,
            parent_temp_id: parentTempId || '',
            is_collapsed:   false,
            cells:          cells,
            cell_aligns:    {},
            cell_merges:    {},
        };
        pushUndo();
        rows.push(row);
        rebuildRowTable();
    }

    function rebuildRowTable() {
        activeCell = null;
        $('#tmp-cell-toolbar').addClass('tmp-toolbar-disabled');
        $('#tmp-tb-cell-ref').text('');
        hideMergeToolbar();
        hideCellMergeToolbar();
        var $wrapper = $('#tmp-rows-wrapper');
        $wrapper.find('.tmp-rows-empty').hide();
        $wrapper.find('.tmp-admin-table-wrap').remove();

        if (columns.length === 0) {
            $wrapper.find('.tmp-rows-empty').text(i18n.no_columns || 'Klik op "+ Kolom" om te beginnen.').show();
            return;
        }

        if (rows.length === 0) {
            $wrapper.find('.tmp-rows-empty').text('Nog geen rijen. Voeg rijen toe met de knoppen hierboven.').show();
        }

        var headerCols = '';
        var ci = 0;
        while (ci < columns.length) {
            var col = columns[ci];
            var key = (col.temp_key || col.id) + '';
            var g1 = col.settings.header_group1 || '';
            if (g1) {
                var groupKeys = [key];
                var groupEndIdx = ci;
                while (groupEndIdx + 1 < columns.length) {
                    var nextG1 = columns[groupEndIdx + 1].settings.header_group1 || '';
                    if (nextG1 === g1) {
                        groupEndIdx++;
                        groupKeys.push((columns[groupEndIdx].temp_key || columns[groupEndIdx].id) + '');
                    } else break;
                }
                var colspan = groupKeys.length;
                var grpAlign = (col.settings && col.settings.align) || '';
                var grpAlignStyle = grpAlign && grpAlign !== 'left' ? ' style="text-align:' + escAttr(grpAlign) + ';"' : '';
                headerCols += '<th class="tmp-col-header-cell tmp-col-merged-group" ' +
                    'data-col-key="' + escAttr(groupKeys[0]) + '" ' +
                    'data-col-idx="' + ci + '" ' +
                    'data-group-end-idx="' + groupEndIdx + '" ' +
                    'data-group-keys="' + escAttr(groupKeys.join(',')) + '" ' +
                    'data-group-name="' + escAttr(g1) + '" ' +
                    'draggable="true" ' +
                    (colspan > 1 ? 'colspan="' + colspan + '" ' : '') + grpAlignStyle + '>' +
                    '<span class="tmp-col-header-label">' + g1 + '</span>' +
                    '<span class="tmp-col-unmerge-btn dashicons dashicons-editor-unlink" title="Groep opheffen"></span>' +
                '</th>';
                ci = groupEndIdx + 1;
            } else {
                var colAlign = (col.settings && col.settings.align) || '';
                var colAlignStyle = colAlign && colAlign !== 'left' ? ' style="text-align:' + escAttr(colAlign) + ';"' : '';
                headerCols += '<th class="tmp-col-header-cell" data-col-key="' + escAttr(key) + '" data-col-idx="' + ci + '" draggable="true"' + colAlignStyle + '>' +
                    '<span class="tmp-col-header-label">' + (col.label || 'Kolom') + '</span>' +
                    '<span class="tmp-col-delete-btn dashicons dashicons-no-alt" title="Kolom verwijderen"></span>' +
                '</th>';
                ci++;
            }
        }

        var colGroupHtml = '<colgroup><col style="width:28px;"><col style="width:80px;">';
        var dataColWidth = columns.length > 0 ? 'calc((100% - 144px) / ' + columns.length + ')' : 'auto';
        for (var cgi = 0; cgi < columns.length; cgi++) {
            colGroupHtml += '<col style="width:' + dataColWidth + ';">';
        }
        colGroupHtml += '<col style="width:36px;"></colgroup>';

        var $table = $('<table class="tmp-admin-table"></table>');
        $table.append(colGroupHtml);
        var $thead = $('<thead><tr class="tmp-admin-header-row"><th style="width:28px;"></th><th style="width:80px;">Type</th>' + headerCols + '<th style="width:36px;"></th></tr></thead>');
        var $tbody = $('<tbody class="tmp-rows-sortable"></tbody>');

        $table.append($thead).append($tbody);

        rows.forEach(function (row) {
            var $tr = buildRowTr(row);
            $tbody.append($tr);
        });

        var $wrap = $('<div class="tmp-admin-table-wrap"></div>').append($table);
        $wrapper.append($wrap);

        $thead.find('.tmp-col-delete-btn').on('click', function (e) {
            e.stopPropagation();
            var $th = $(this).closest('.tmp-col-header-cell');
            var colKey = $th.data('col-key') + '';
            if (confirm('Kolom verwijderen?')) {
                pushUndo();
                columns = columns.filter(function(c) { return (c.temp_key || c.id) + '' !== colKey; });
                rebuildRowTable();
            }
        });

        $thead.find('.tmp-col-unmerge-btn').on('click', function (e) {
            e.stopPropagation();
            var $th = $(this).closest('.tmp-col-merged-group');
            var groupKeys = ($th.data('group-keys') + '').split(',');
            pushUndo();
            groupKeys.forEach(function (k) {
                var col = columns.find(function(c) { return (c.temp_key || c.id) + '' === k; });
                if (col) {
                    col.settings.header_group1 = '';
                    col.settings.header_group2 = '';
                }
            });
            hideMergeToolbar();
            rebuildRowTable();
        });

        $thead.find('.tmp-col-header-cell').on('click', function (e) {
            e.stopPropagation();
            if ($(e.target).hasClass('tmp-col-delete-btn') || $(e.target).hasClass('tmp-col-unmerge-btn')) return;
            if (justDraggedKey) {
                justDraggedKey = null;
                return;
            }
            var $th = $(this);

            if ($th.find('.tmp-col-inline-edit').length) return;

            if (e.ctrlKey || e.metaKey) {
                $th.toggleClass('tmp-col-selected');
                updateMergeToolbar();
                return;
            }

            var wasSelected = $th.hasClass('tmp-col-selected');
            var otherSelected = $('.tmp-col-header-cell.tmp-col-selected').not($th).length;

            if (otherSelected > 0) {
                $th.toggleClass('tmp-col-selected');
                updateMergeToolbar();
                return;
            }

            if (!wasSelected) {
                $th.addClass('tmp-col-selected');
                updateMergeToolbar();
                return;
            }

            $th.removeClass('tmp-col-selected');
            hideMergeToolbar();
        });

        $thead.find('.tmp-col-header-cell').on('dblclick', function (e) {
            e.stopPropagation();
            if ($(e.target).hasClass('tmp-col-delete-btn') || $(e.target).hasClass('tmp-col-unmerge-btn')) return;
            var $th = $(this);

            $('.tmp-col-header-cell').removeClass('tmp-col-selected');
            hideMergeToolbar();
            if ($th.find('.tmp-col-inline-edit').length) return;

            if ($th.hasClass('tmp-col-merged-group')) {
                var groupKeys = ($th.data('group-keys') + '').split(',');
                var groupName = $th.data('group-name') + '';
                var origGroupName = groupName;
                var $label = $th.find('.tmp-col-header-label');
                var $unlinkBtn = $th.find('.tmp-col-unmerge-btn');
                var frozenW = $th.outerWidth();
                $th.css('min-width', frozenW + 'px');
                $label.hide();
                $unlinkBtn.css('opacity', '0.85');
                var $editDiv = $('<div class="tmp-col-inline-edit tmp-cell-input" contenteditable="true"></div>');
                $editDiv.html(groupName);
                $unlinkBtn.before($editDiv);
                $editDiv.focus();
                var rng = document.createRange();
                rng.selectNodeContents($editDiv[0]);
                var sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(rng);
                activeCell = { $area: $editDiv, el: $editDiv[0], $tr: null, colKey: '__group__' + groupKeys.join(','), tempId: '__header__' };
                formatUndoPushed = false;
                $('#tmp-cell-toolbar').removeClass('tmp-toolbar-disabled');
                $('#tmp-tb-cell-ref').text(groupName);
                var firstGrpCol = columns.find(function(c) { return (c.temp_key || c.id) + '' === groupKeys[0]; });
                var curGrpAlign = (firstGrpCol && firstGrpCol.settings && firstGrpCol.settings.align) || 'left';
                updateAlignButtons(curGrpAlign);
                function finishGroupNameEdit() {
                    var newVal = cleanCellHtml($editDiv.html()).trim();
                    if (activeCell && activeCell.el === $editDiv[0]) {
                        activeCell = null;
                        $('#tmp-cell-toolbar').addClass('tmp-toolbar-disabled');
                        $('#tmp-tb-cell-ref').text('');
                    }
                    var firstCol = columns.find(function(c) { return (c.temp_key || c.id) + '' === groupKeys[0]; });
                    var savedAlign = (firstCol && firstCol.settings && firstCol.settings.align) || '';
                    if (newVal && newVal !== origGroupName) {
                        pushUndo();
                        groupKeys.forEach(function (k) {
                            var col = columns.find(function(c) { return (c.temp_key || c.id) + '' === k; });
                            if (col) col.settings.header_group1 = newVal;
                        });
                        $editDiv.remove();
                        $th.css('min-width', '');
                        rebuildRowTable();
                    } else {
                        $th.css('text-align', savedAlign && savedAlign !== 'left' ? savedAlign : '');
                        $label.show();
                        $unlinkBtn.css('opacity', '');
                        $editDiv.remove();
                        $th.css('min-width', '');
                    }
                }
                $editDiv.on('blur', function () { setTimeout(finishGroupNameEdit, 150); });
                $editDiv.on('keydown', function (ev) {
                    if (ev.key === 'Enter') { ev.preventDefault(); finishGroupNameEdit(); }
                    if (ev.key === 'Escape') { if (activeCell && activeCell.el === $editDiv[0]) { activeCell = null; $('#tmp-cell-toolbar').addClass('tmp-toolbar-disabled'); } var fc = columns.find(function(c) { return (c.temp_key || c.id) + '' === groupKeys[0]; }); var sa = (fc && fc.settings && fc.settings.align) || ''; $th.css('text-align', sa && sa !== 'left' ? sa : ''); $label.show(); $unlinkBtn.css('opacity', ''); $editDiv.remove(); $th.css('min-width', ''); }
                });
            } else {
                var colKey = $th.data('col-key') + '';
                var col = columns.find(function(c) { return (c.temp_key || c.id) + '' === colKey; });
                if (!col) return;
                var origLabel = col.label;
                var $label = $th.find('.tmp-col-header-label');
                var $delBtn = $th.find('.tmp-col-delete-btn');
                var frozenW = $th.outerWidth();
                $th.css('min-width', frozenW + 'px');
                $label.hide();
                $delBtn.hide();
                var $editDiv = $('<div class="tmp-col-inline-edit tmp-cell-input" contenteditable="true"></div>');
                $editDiv.html(col.label || '');
                $th.append($editDiv);
                $editDiv.focus();
                var rng = document.createRange();
                rng.selectNodeContents($editDiv[0]);
                var sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(rng);
                activeCell = { $area: $editDiv, el: $editDiv[0], $tr: null, colKey: colKey, tempId: '__header__' };
                formatUndoPushed = false;
                $('#tmp-cell-toolbar').removeClass('tmp-toolbar-disabled');
                $('#tmp-tb-cell-ref').text(col.label || 'Kolom');
                var curColAlign = (col.settings && col.settings.align) || 'left';
                updateAlignButtons(curColAlign);
                function finishEdit() {
                    var newLabel = cleanCellHtml($editDiv.html()).trim() || origLabel;
                    if (newLabel !== origLabel) {
                        pushUndo();
                        col.label = newLabel;
                    }
                    if (activeCell && activeCell.el === $editDiv[0]) {
                        activeCell = null;
                        $('#tmp-cell-toolbar').addClass('tmp-toolbar-disabled');
                        $('#tmp-tb-cell-ref').text('');
                    }
                    var savedAlign = (col.settings && col.settings.align) || '';
                    $th.css('text-align', savedAlign && savedAlign !== 'left' ? savedAlign : '');
                    $label.html(col.label || 'Kolom').show();
                    $delBtn.show();
                    $editDiv.remove();
                    $th.css('min-width', '');
                }
                var editCancelled = false;
                function cancelEdit() {
                    editCancelled = true;
                    col.label = origLabel;
                    if (activeCell && activeCell.el === $editDiv[0]) {
                        activeCell = null;
                        $('#tmp-cell-toolbar').addClass('tmp-toolbar-disabled');
                        $('#tmp-tb-cell-ref').text('');
                    }
                    var savedAlign = (col.settings && col.settings.align) || '';
                    $th.css('text-align', savedAlign && savedAlign !== 'left' ? savedAlign : '');
                    $label.html(origLabel || 'Kolom').show();
                    $delBtn.show();
                    $editDiv.remove();
                    $th.css('min-width', '');
                }
                $editDiv.on('blur', function () { setTimeout(function() { if (!editCancelled) finishEdit(); }, 150); });
                $editDiv.on('keydown', function (ev) {
                    if (ev.key === 'Enter') { ev.preventDefault(); finishEdit(); }
                    if (ev.key === 'Escape') { ev.preventDefault(); cancelEdit(); }
                });
            }
        });

        $(document).off('click.colselect').on('click.colselect', function (e) {
            if (!$(e.target).closest('.tmp-col-header-cell, .tmp-col-merge-toolbar').length) {
                $('.tmp-col-header-cell').removeClass('tmp-col-selected');
                hideMergeToolbar();
            }
        });

        (function initColDrag() {
            var dragSrcIdx = null;
            var dragSrcEndIdx = null;

            $thead.find('.tmp-col-header-cell').on('dragstart', function (e) {
                dragSrcIdx = parseInt($(this).data('col-idx'), 10);
                dragSrcEndIdx = $(this).data('group-end-idx') !== undefined ? parseInt($(this).data('group-end-idx'), 10) : dragSrcIdx;
                e.originalEvent.dataTransfer.effectAllowed = 'move';
                e.originalEvent.dataTransfer.setData('text/plain', dragSrcIdx);
                $(this).addClass('tmp-col-dragging');
            });

            $thead.find('.tmp-col-header-cell').on('dragover', function (e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'move';
                var $th = $(this);
                $thead.find('.tmp-col-header-cell').removeClass('tmp-col-drag-over-left tmp-col-drag-over-right');
                var targetIdx = parseInt($th.data('col-idx'), 10);
                if (targetIdx < dragSrcIdx) {
                    $th.addClass('tmp-col-drag-over-left');
                } else if (targetIdx > dragSrcEndIdx) {
                    $th.addClass('tmp-col-drag-over-right');
                }
            });

            $thead.find('.tmp-col-header-cell').on('dragleave', function () {
                $(this).removeClass('tmp-col-drag-over-left tmp-col-drag-over-right');
            });

            $thead.find('.tmp-col-header-cell').on('drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $thead.find('.tmp-col-header-cell').removeClass('tmp-col-drag-over-left tmp-col-drag-over-right tmp-col-dragging');
                var targetIdx = parseInt($(this).data('col-idx'), 10);
                var targetEndIdx = $(this).data('group-end-idx') !== undefined ? parseInt($(this).data('group-end-idx'), 10) : targetIdx;
                if (dragSrcIdx === null) return;
                if (targetIdx >= dragSrcIdx && targetIdx <= dragSrcEndIdx) return;
                var count = dragSrcEndIdx - dragSrcIdx + 1;
                pushUndo();
                var moved = columns.splice(dragSrcIdx, count);
                var insertAt;
                if (targetIdx < dragSrcIdx) {
                    insertAt = targetIdx;
                } else {
                    insertAt = targetEndIdx - count + 1;
                }
                for (var i = 0; i < moved.length; i++) {
                    columns.splice(insertAt + i, 0, moved[i]);
                }
                justDraggedKey = moved[0] ? (moved[0].temp_key || moved[0].id) + '' : null;
                rebuildRowTable();
            });

            $thead.find('.tmp-col-header-cell').on('dragend', function () {
                $thead.find('.tmp-col-header-cell').removeClass('tmp-col-dragging tmp-col-drag-over-left tmp-col-drag-over-right');
                dragSrcIdx = null;
                dragSrcEndIdx = null;
            });
        })();

        $tbody.sortable({
            handle: '.tmp-drag-handle',
            tolerance: 'pointer',
            update: function () {
                pushUndo();
                syncRowsFromDOM();
            }
        });

        updatePreview();
    }

    function getSelectedColKeys() {
        var keys = [];
        $('.tmp-col-header-cell.tmp-col-selected').each(function () {
            var groupKeys = $(this).data('group-keys');
            if (groupKeys) {
                (groupKeys + '').split(',').forEach(function(k) { keys.push(k); });
            } else {
                keys.push($(this).data('col-key') + '');
            }
        });
        return keys;
    }

    function updateMergeToolbar() {
        var keys = getSelectedColKeys();
        var selectedHeaderCount = $('.tmp-col-header-cell.tmp-col-selected').length;
        hideMergeToolbar();
        if (selectedHeaderCount < 2 || keys.length < 2) return;

        var $sel = $('.tmp-col-header-cell.tmp-col-selected').first();
        var $last = $('.tmp-col-header-cell.tmp-col-selected').last();
        var topOff = $sel.offset();
        var leftOff = topOff.left;
        var rightOff = $last.offset().left + $last.outerWidth();
        var centerX = leftOff + (rightOff - leftOff) / 2;

        var anyHasGroup = keys.some(function (k) {
            var col = columns.find(function(c) { return (c.temp_key || c.id) + '' === k; });
            return col && (col.settings.header_group1 || col.settings.header_group2);
        });

        var $bar = $('<div class="tmp-col-merge-toolbar">' +
            '<span class="tmp-merge-label">' + keys.length + ' kolommen</span>' +
            '<button type="button" class="button button-primary tmp-merge-g1-btn">Samenvoegen</button>' +
            (anyHasGroup ? '<button type="button" class="button tmp-unmerge-btn" style="margin-left:4px;">' +
                '<span class="dashicons dashicons-editor-unlink" style="vertical-align:middle;font-size:14px;width:14px;height:14px;margin-right:2px;"></span>Opheffen' +
            '</button>' : '') +
        '</div>');

        $('body').append($bar);
        var barW = $bar.outerWidth();
        var barLeft = centerX - barW / 2;
        if (barLeft < 8) barLeft = 8;
        $bar.css({
            top: topOff.top + $sel.outerHeight() + 6,
            left: barLeft,
        });

        function areKeysContiguous(selectedKeys) {
            var indices = [];
            selectedKeys.forEach(function(k) {
                columns.forEach(function(col, idx) {
                    if ((col.temp_key || col.id) + '' === k) indices.push(idx);
                });
            });
            indices.sort(function(a,b) { return a - b; });
            for (var i = 1; i < indices.length; i++) {
                if (indices[i] !== indices[i-1] + 1) return false;
            }
            return true;
        }

        function doMerge(field) {
            var selCount = $('.tmp-col-header-cell.tmp-col-selected').length;
            if (selCount < 2) {
                hideMergeToolbar();
                return;
            }
            if (!areKeysContiguous(keys)) {
                alert('Selecteer aaneengesloten kolommen om samen te voegen.');
                return;
            }
            var firstCol = columns.find(function(c) { return (c.temp_key || c.id) + '' === keys[0]; });
            var rawLabel = (firstCol && firstCol.label) ? firstCol.label : '';
            var textOnly = rawLabel.replace(/<[^>]*>/g, '').replace(/&nbsp;/gi, ' ').replace(/\s+/g, ' ').trim();
            var groupName = textOnly !== '' ? rawLabel : 'Groep';
            pushUndo();
            keys.forEach(function (k) {
                var col = columns.find(function(c) { return (c.temp_key || c.id) + '' === k; });
                if (col) {
                    col.settings[field] = groupName;
                }
            });
            hideMergeToolbar();
            rebuildRowTable();
        }

        $bar.find('.tmp-merge-g1-btn').on('click', function () { doMerge('header_group1'); });

        $bar.find('.tmp-unmerge-btn').on('click', function () {
            pushUndo();
            keys.forEach(function (k) {
                var col = columns.find(function(c) { return (c.temp_key || c.id) + '' === k; });
                if (col) {
                    col.settings.header_group1 = '';
                    col.settings.header_group2 = '';
                }
            });
            hideMergeToolbar();
            rebuildRowTable();
        });
    }

    function hideMergeToolbar() {
        $('.tmp-col-merge-toolbar').remove();
    }

    function updateCellMergeToolbar($tr) {
        hideCellMergeToolbar();
        var $selected = $tr.find('td.tmp-cell-selected');
        if ($selected.length < 1) return;
        var colKeys = [];
        $selected.each(function () {
            var $inp = $(this).find('.tmp-cell-input');
            if ($inp.length) colKeys.push($inp.data('col-key') + '');
        });
        if (colKeys.length < 1) return;
        var tempId = $tr.data('temp-id') + '';
        var rowObj = rows.find(function (r) { return r.temp_id + '' === tempId; });
        var anyMerged = colKeys.some(function (k) { return rowObj && rowObj.cell_merges && rowObj.cell_merges[k] && parseInt(rowObj.cell_merges[k]) > 1; });
        var colIndices = colKeys.map(function (k) {
            for (var i = 0; i < columns.length; i++) {
                if ((columns[i].temp_key || columns[i].id) + '' === k) return i;
            }
            return -1;
        }).sort(function (a, b) { return a - b; });
        var contiguous = true;
        for (var ci = 1; ci < colIndices.length; ci++) {
            if (colIndices[ci] !== colIndices[ci - 1] + 1) { contiguous = false; break; }
        }
        var canMerge = colKeys.length >= 2 && contiguous;
        if (!canMerge && !anyMerged) return;
        var $first = $selected.first();
        var $last = $selected.last();
        var topOff = $first.offset();
        var bottomY = topOff.top + $first.outerHeight();
        var leftOff = topOff.left;
        var rightOff = $last.offset().left + $last.outerWidth();
        var centerX = leftOff + (rightOff - leftOff) / 2;
        var $bar = $('<div class="tmp-cell-merge-toolbar">' +
            '<span class="tmp-merge-label">' + colKeys.length + ' cel' + (colKeys.length > 1 ? 'len' : '') + '</span>' +
            (canMerge ? '<button type="button" class="button button-primary tmp-cell-merge-btn">Samenvoegen</button>' : '') +
            (anyMerged ? '<button type="button" class="button tmp-cell-unmerge-btn" style="margin-left:4px;">Opheffen</button>' : '') +
        '</div>');
        $('body').append($bar);
        var barW = $bar.outerWidth();
        var barLeft = centerX - barW / 2;
        if (barLeft < 8) barLeft = 8;
        $bar.css({ top: bottomY + 6, left: barLeft });
        $bar.find('.tmp-cell-merge-btn').on('click', function () {
            if (!rowObj) return;
            pushUndo();
            if (!rowObj.cell_merges) rowObj.cell_merges = {};
            var sortedKeys = [];
            for (var si = colIndices[0]; si <= colIndices[colIndices.length - 1]; si++) {
                sortedKeys.push((columns[si].temp_key || columns[si].id) + '');
            }
            rowObj.cell_merges[sortedKeys[0]] = sortedKeys.length;
            for (var mi = 1; mi < sortedKeys.length; mi++) {
                delete rowObj.cell_merges[sortedKeys[mi]];
            }
            hideCellMergeToolbar();
            $('td.tmp-cell-selected').removeClass('tmp-cell-selected');
            rebuildRowTable();
        });
        $bar.find('.tmp-cell-unmerge-btn').on('click', function () {
            if (!rowObj || !rowObj.cell_merges) return;
            pushUndo();
            colKeys.forEach(function (k) { delete rowObj.cell_merges[k]; });
            hideCellMergeToolbar();
            $('td.tmp-cell-selected').removeClass('tmp-cell-selected');
            rebuildRowTable();
        });
    }

    function hideCellMergeToolbar() {
        $('.tmp-cell-merge-toolbar').remove();
    }

    var rowTypeOrder = ['data', 'group_1', 'group_2', 'group_3', 'footer'];
    var rowTypeLabels = { data: 'Data', group_1: 'G1', group_2: 'G2', group_3: 'G3', footer: 'Afsluit' };

    function buildRowTr(row) {
        var typeClass = 'tmp-admin-row tmp-admin-row-' + (row.row_type || 'data');
        var badgeClass= 'tmp-row-type-badge tmp-type-badge-' + (row.row_type || 'data');
        var badgeText = rowTypeLabels[row.row_type] || 'Data';
        var isGroup   = row.row_type && row.row_type !== 'data' && row.row_type !== 'footer';

        var cellMerges = row.cell_merges || {};
        var cellInputs = '';
        var skipCols = 0;
        for (var ci = 0; ci < columns.length; ci++) {
            if (skipCols > 0) { skipCols--; continue; }
            var col = columns[ci];
            var key = col.temp_key || col.id;
            var content = row.cells[key] !== undefined ? row.cells[key] : '';
            var cellAlign = (row.cell_aligns && row.cell_aligns[key]) || '';
            var effectiveAlign = cellAlign || (col.settings && col.settings.align) || 'left';
            var alignStyle = effectiveAlign && effectiveAlign !== 'left' ? 'text-align:' + escAttr(effectiveAlign) + ';' : '';
            var placeholder = '';
            var cspan = cellMerges[key] ? parseInt(cellMerges[key]) : 1;
            if (cspan > 1) skipCols = cspan - 1;
            cellInputs += '<td' + (cspan > 1 ? ' colspan="' + cspan + '"' : '') + '>' +
                '<div class="tmp-cell-input" contenteditable="true" data-col-key="' + escAttr(key + '') + '"' +
                (placeholder ? ' data-placeholder="' + escAttr(placeholder) + '"' : '') +
                (alignStyle ? ' style="' + alignStyle + '"' : '') + '>' +
                content + '</div></td>';
        }

        var $tr = $('<tr class="' + escAttr(typeClass) + '" data-temp-id="' + escAttr(row.temp_id) + '">' +
            '<td><span class="tmp-drag-handle dashicons dashicons-menu"></span></td>' +
            '<td><span class="' + escAttr(badgeClass) + '" title="Klik om rijtype te wijzigen">' + escHtml(badgeText) + '</span></td>' +
            cellInputs +
            '<td class="tmp-row-actions">' +
                '<button type="button" class="tmp-row-duplicate dashicons dashicons-admin-page" title="Rij dupliceren"></button>' +
                '<button type="button" class="tmp-row-delete dashicons dashicons-trash" title="' + escAttr(i18n.delete_row) + '"></button>' +
            '</td>' +
        '</tr>');

        $tr.find('.tmp-row-type-badge').on('click', function () {
            var tempId = $tr.data('temp-id') + '';
            var rowObj = rows.find(function (r) { return r.temp_id + '' === tempId; });
            if (!rowObj) return;
            var curIdx = rowTypeOrder.indexOf(rowObj.row_type);
            var newIdx = (curIdx + 1) % rowTypeOrder.length;
            pushUndo();
            rowObj.row_type = rowTypeOrder[newIdx];
            rebuildRowTable();
        });

        $tr.find('.tmp-cell-input').on('input', function () {
            var $div  = $(this);
            var colKey = $div.data('col-key') + '';
            var tempId = $tr.data('temp-id') + '';
            var rowObj = rows.find(function (r) { return r.temp_id + '' === tempId; });
            if (rowObj) {
                if (!$div.data('undo-pushed')) {
                    pushUndo();
                    $div.data('undo-pushed', true);
                } else {
                    isDirty = true;
                }
                rowObj.cells[colKey] = cleanCellHtml($div.html());
            }
        });

        $tr.find('.tmp-cell-input').on('mousedown', function (e) {
            var $td = $(this).closest('td');
            var isMergedCell = $td.attr('colspan') && parseInt($td.attr('colspan')) > 1;
            if (e.ctrlKey || e.metaKey) {
                e.preventDefault();
                e.stopPropagation();
                $tr.siblings().find('td.tmp-cell-selected').removeClass('tmp-cell-selected');
                var $focused = $tr.find('.tmp-cell-input:focus');
                if ($focused.length && $focused[0] !== this && !$focused.closest('td').hasClass('tmp-cell-selected')) {
                    $focused.closest('td').addClass('tmp-cell-selected');
                    $focused.blur();
                }
                $td.toggleClass('tmp-cell-selected');
                updateCellMergeToolbar($tr);
                return false;
            } else if (isGroup) {
                e.preventDefault();
                e.stopPropagation();
                $tr.siblings().find('td.tmp-cell-selected').removeClass('tmp-cell-selected');
                $td.toggleClass('tmp-cell-selected');
                updateCellMergeToolbar($tr);
                return false;
            } else if (isMergedCell) {
                $('td.tmp-cell-selected').removeClass('tmp-cell-selected');
                hideCellMergeToolbar();
                $td.addClass('tmp-cell-selected');
                updateCellMergeToolbar($tr);
            } else {
                $('td.tmp-cell-selected').removeClass('tmp-cell-selected');
                hideCellMergeToolbar();
            }
        });

        if (isGroup) {
            $tr.find('.tmp-cell-input').on('dblclick', function (e) {
                e.stopPropagation();
                $('td.tmp-cell-selected').removeClass('tmp-cell-selected');
                hideCellMergeToolbar();
                $(this).focus();
            });
        }

        $tr.find('.tmp-cell-input').on('focus', function () {
            var $div = $(this);
            $div.data('undo-pushed', false);
            formatUndoPushed = false;
            var colKey = $div.data('col-key') + '';
            var tempId = $tr.data('temp-id') + '';
            var col = columns.find(function(c) { return (c.temp_key || c.id) + '' === colKey; });
            var colLabel = col ? col.label : '';
            var rowObj = rows.find(function (r) { return r.temp_id + '' === tempId; });
            var rowIdx = rowObj ? rows.indexOf(rowObj) + 1 : '';
            activeCell = { $area: $div, el: $div[0], $tr: $tr, colKey: colKey, tempId: tempId };
            $('#tmp-cell-toolbar').removeClass('tmp-toolbar-disabled');
            $('#tmp-tb-cell-ref').text(colLabel + ' · rij ' + rowIdx);
            $('.tmp-cell-input').closest('td').removeClass('tmp-cell-active');
            $div.closest('td').addClass('tmp-cell-active');
            var curCellAlign = (rowObj && rowObj.cell_aligns && rowObj.cell_aligns[colKey]) || '';
            var colObj = columns.find(function(c) { return (c.temp_key || c.id) + '' === colKey; });
            var curColAlign = (colObj && colObj.settings && colObj.settings.align) || 'left';
            var curAlign = curCellAlign || curColAlign;
            updateAlignButtons(curAlign);
        });

        $tr.find('.tmp-cell-input').on('blur', function () {
            var $div = $(this);
            var colKey = $div.data('col-key') + '';
            var tempId = $tr.data('temp-id') + '';
            var rowObj = rows.find(function (r) { return r.temp_id + '' === tempId; });
            if (rowObj) {
                var html = cleanCellHtml($div.html());
                if (html === '') $div.html('');
                rowObj.cells[colKey] = html;
            }
        });

        $tr.find('.tmp-row-duplicate').on('click', function () {
            var tempId = $tr.data('temp-id') + '';
            var rowObj = rows.find(function (r) { return r.temp_id + '' === tempId; });
            if (!rowObj) return;
            var newTempId = 'new_row_' + (++rowTempIdx);
            var newCells = {};
            for (var k in rowObj.cells) {
                newCells[k] = rowObj.cells[k];
            }
            var newAligns = {};
            if (rowObj.cell_aligns) {
                for (var ak in rowObj.cell_aligns) {
                    newAligns[ak] = rowObj.cell_aligns[ak];
                }
            }
            var newMerges = {};
            if (rowObj.cell_merges) {
                for (var mk in rowObj.cell_merges) {
                    newMerges[mk] = rowObj.cell_merges[mk];
                }
            }
            var newRow = {
                temp_id:      newTempId,
                row_type:     rowObj.row_type,
                cells:        newCells,
                cell_aligns:  newAligns,
                cell_merges:  newMerges,
                sort_order:   0,
                is_collapsed: false,
                parent_id:    rowObj.parent_id || null,
                parent_temp_id: rowObj.parent_temp_id || null,
            };
            var idx = rows.indexOf(rowObj);
            pushUndo();
            rows.splice(idx + 1, 0, newRow);
            rebuildRowTable();
        });

        $tr.find('.tmp-row-delete').on('click', function () {
            var tempId = $tr.data('temp-id') + '';
            pushUndo();
            rows = rows.filter(function (r) { return r.temp_id + '' !== tempId; });
            $tr.remove();
        });

        $tr.find('.tmp-cell-input').on('paste', function (e) {
            e.preventDefault();
            var clip = (e.originalEvent || e).clipboardData;
            if (!clip) return;
            var html = clip.getData('text/html');
            var text = clip.getData('text/plain');
            var clean = '';
            if (html) {
                var $tmp = $('<div>').html(html);
                $tmp.find('style, script, meta, link, head, title, xml').remove();
                $tmp.find('[style]').removeAttr('style');
                $tmp.find('[class]').removeAttr('class');
                $tmp.find('[id]').removeAttr('id');
                clean = $tmp.html()
                    .replace(/<!--[\s\S]*?-->/g, '')
                    .replace(/<\/?span[^>]*>/gi, '')
                    .replace(/<\/?div[^>]*>/gi, '')
                    .replace(/<\/?p[^>]*>/gi, '<br>')
                    .replace(/(<br\s*\/?>){3,}/gi, '<br><br>')
                    .trim();
            } else {
                clean = escHtml(text);
            }
            document.execCommand('insertHTML', false, clean);
        });

        $tr.find('.tmp-cell-input').on('keydown', function (e) {
            if (e.ctrlKey && e.key === 'b') {
                e.preventDefault();
                toolbarBold();
            }
            if (e.ctrlKey && e.key === 'i') {
                e.preventDefault();
                toolbarItalic();
            }
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.execCommand('insertLineBreak');
            }
        });

        return $tr;
    }

    function syncRowsFromDOM() {
        var newOrder = [];
        $('#tmp-rows-wrapper .tmp-rows-sortable tr').each(function () {
            var tempId = $(this).data('temp-id') + '';
            var existing = rows.find(function (r) { return r.temp_id + '' === tempId; });
            if (existing) newOrder.push(existing);
        });
        rows = newOrder;
    }

    /* ===== ALIGNMENT ===== */
    function updateAlignButtons(align) {
        $('.tmp-tb-align').removeClass('tmp-tb-align-active');
        $('#tmp-tb-align-' + (align || 'left')).addClass('tmp-tb-align-active');
    }

    function toolbarAlign(align) {
        if (!activeCell) return;
        if (activeCell.tempId === '__header__') {
            var colKey = activeCell.colKey;
            if (colKey.indexOf('__group__') === 0) {
                var gKeys = colKey.replace('__group__', '').split(',');
                pushUndo();
                gKeys.forEach(function(k) {
                    var gc = columns.find(function(c) { return (c.temp_key || c.id) + '' === k; });
                    if (gc) {
                        if (!gc.settings) gc.settings = {};
                        gc.settings.align = align;
                    }
                });
                activeCell.$area.css('text-align', align);
                activeCell.$area.closest('th').css('text-align', align);
            } else {
                var colObj = columns.find(function(c) { return (c.temp_key || c.id) + '' === colKey; });
                if (colObj) {
                    pushUndo();
                    if (!colObj.settings) colObj.settings = {};
                    colObj.settings.align = align;
                    activeCell.$area.css('text-align', align);
                    activeCell.$area.closest('th').css('text-align', align);
                }
            }
            updateAlignButtons(align);
            activeCell.$area.focus();
            return;
        }
        var rowObj = rows.find(function (r) { return r.temp_id + '' === activeCell.tempId; });
        if (!rowObj) return;
        pushUndo();
        if (!rowObj.cell_aligns) rowObj.cell_aligns = {};
        var colKey = activeCell.colKey;
        var colObj = columns.find(function(c) { return (c.temp_key || c.id) + '' === colKey; });
        var colDefault = (colObj && colObj.settings && colObj.settings.align) || 'left';
        rowObj.cell_aligns[colKey] = align === colDefault ? '' : align;
        var $td = activeCell.$area.closest('td');
        $td.css('text-align', align);
        activeCell.$area.css('text-align', align);
        updateAlignButtons(align);
        activeCell.$area.focus();
    }

    /* ===== TOOLBAR FUNCTIONS ===== */
    function ensureCellEditable() {
        if (!activeCell) return null;
        activeCell.$area.focus();
        return activeCell;
    }

    var formatUndoPushed = false;

    function syncCellData() {
        if (!activeCell) return;
        var colKey = activeCell.colKey;
        var tempId = activeCell.tempId;
        if (tempId === '__header__') {
            if (!formatUndoPushed) { pushUndo(); formatUndoPushed = true; } else { isDirty = true; }
            if (colKey.indexOf('__group__') === 0) {
                var gKeys = colKey.replace('__group__', '').split(',');
                var newVal = cleanCellHtml(activeCell.$area.html()).trim();
                gKeys.forEach(function (k) {
                    var col = columns.find(function(c) { return (c.temp_key || c.id) + '' === k; });
                    if (col) col.settings.header_group1 = newVal;
                });
            } else {
                var col = columns.find(function(c) { return (c.temp_key || c.id) + '' === colKey; });
                if (col) col.label = cleanCellHtml(activeCell.$area.html()).trim();
            }
            return;
        }
        var rowObj = rows.find(function (r) { return r.temp_id + '' === tempId; });
        if (rowObj) {
            if (!formatUndoPushed) { pushUndo(); formatUndoPushed = true; } else { isDirty = true; }
            rowObj.cells[colKey] = cleanCellHtml(activeCell.$area.html());
        }
    }

    function toolbarBold() {
        if (!ensureCellEditable()) return;
        document.execCommand('bold', false, null);
        syncCellData();
    }

    function toolbarItalic() {
        if (!ensureCellEditable()) return;
        document.execCommand('italic', false, null);
        syncCellData();
    }

    var savedRange = null;
    function saveSelection() {
        var sel = window.getSelection();
        if (sel.rangeCount > 0) savedRange = sel.getRangeAt(0).cloneRange();
    }
    function restoreSelection() {
        if (!savedRange) return;
        var sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(savedRange);
    }

    function toolbarLink() {
        if (!ensureCellEditable()) return;
        saveSelection();
        var sel = window.getSelection();
        var selectedText = sel.rangeCount ? sel.toString() : '';
        openLinkModal(activeCell.$area, activeCell.el, 0, 0, selectedText);
    }

    function toolbarBullet() {
        if (!ensureCellEditable()) return;
        document.execCommand('insertUnorderedList', false, null);
        syncCellData();
    }

    function toolbarDeleteRow() {
        if (!activeCell) return;
        var tempId = activeCell.tempId;
        if (tempId === '__header__') return;
        var rowObj = rows.find(function (r) { return r.temp_id + '' === tempId; });
        if (!rowObj) { activeCell = null; return; }
        pushUndo();
        rows = rows.filter(function (r) { return r.temp_id + '' !== tempId; });
        rebuildRowTable();
    }

    function toolbarDeleteCol() {
        if (!activeCell) return;
        var colKey = activeCell.colKey;
        if (colKey.indexOf('__group__') === 0) return;
        var col = columns.find(function(c) { return (c.temp_key || c.id) + '' === colKey; });
        if (!col) { activeCell = null; return; }
        if (!confirm('Kolom "' + (col.label || 'Kolom') + '" verwijderen?')) return;
        pushUndo();
        columns = columns.filter(function(c) { return (c.temp_key || c.id) + '' !== colKey; });
        rows.forEach(function (r) {
            if (!r.cell_merges) return;
            var newMerges = {};
            Object.keys(r.cell_merges).forEach(function (mk) {
                if (mk === colKey) return;
                var span = parseInt(r.cell_merges[mk]) || 1;
                if (span <= 1) { newMerges[mk] = 1; return; }
                var anchorIdx = -1, delIdx = -1;
                for (var ci = 0; ci < columns.length; ci++) {
                    var ck = (columns[ci].temp_key || columns[ci].id) + '';
                    if (ck === mk) anchorIdx = ci;
                }
                if (anchorIdx === -1) return;
                newMerges[mk] = Math.max(1, span - 1);
            });
            r.cell_merges = newMerges;
        });
        rebuildRowTable();
    }

    /* ===== LINK MODAL ===== */
    var siteDomains = cfg.site_domains || [];

    function isInternalUrl(url) {
        if (!url) return false;
        if (url.charAt(0) === '/' || url.charAt(0) === '#') return true;
        try {
            var parsed = new URL(url, window.location.origin);
            var host   = parsed.hostname.toLowerCase();
            for (var i = 0; i < siteDomains.length; i++) {
                if (host === siteDomains[i].toLowerCase()) return true;
            }
            return host === window.location.hostname.toLowerCase();
        } catch (e) {
            return false;
        }
    }

    var linkSearchTimer = null;

    function openLinkModal($area, el, start, end, selectedText) {
        closeLinkModal();

        var $overlay = $('<div class="tmp-link-overlay"></div>');
        var $modal = $(
            '<div class="tmp-link-modal">' +
                '<div class="tmp-link-modal-header">' +
                    '<span>Link invoegen</span>' +
                    '<button type="button" class="tmp-link-modal-close">&times;</button>' +
                '</div>' +
                '<div class="tmp-link-modal-tabs">' +
                    '<button type="button" class="tmp-link-tab active" data-tab="url">URL</button>' +
                    '<button type="button" class="tmp-link-tab" data-tab="search">Post / Pagina zoeken</button>' +
                '</div>' +
                '<div class="tmp-link-tab-content tmp-link-tab-url active">' +
                    '<div class="tmp-link-field">' +
                        '<label>URL</label>' +
                        '<input type="text" class="tmp-link-url" placeholder="https://example.com" value="">' +
                    '</div>' +
                '</div>' +
                '<div class="tmp-link-tab-content tmp-link-tab-search">' +
                    '<div class="tmp-link-field">' +
                        '<label>Zoek een post of pagina</label>' +
                        '<input type="text" class="tmp-link-search-input" placeholder="Typ om te zoeken...">' +
                    '</div>' +
                    '<div class="tmp-link-search-results"></div>' +
                '</div>' +
                '<div class="tmp-link-field">' +
                    '<label>Linktekst</label>' +
                    '<input type="text" class="tmp-link-text" placeholder="Weergavetekst" value="' + escAttr(selectedText) + '">' +
                '</div>' +
                '<div class="tmp-link-option">' +
                    '<label><input type="checkbox" class="tmp-link-newtab"> Open in nieuw tabblad</label>' +
                '</div>' +
                '<div class="tmp-link-modal-footer">' +
                    '<button type="button" class="button tmp-link-cancel">Annuleren</button>' +
                    '<button type="button" class="button button-primary tmp-link-insert">Link invoegen</button>' +
                '</div>' +
            '</div>'
        );

        $('body').append($overlay).append($modal);

        $modal.find('.tmp-link-tab').on('click', function () {
            var tab = $(this).data('tab');
            $modal.find('.tmp-link-tab').removeClass('active');
            $(this).addClass('active');
            $modal.find('.tmp-link-tab-content').removeClass('active');
            $modal.find('.tmp-link-tab-' + tab).addClass('active');
        });

        $modal.find('.tmp-link-url').on('input', function () {
            var url = $(this).val().trim();
            var internal = isInternalUrl(url);
            $modal.find('.tmp-link-newtab').prop('checked', !internal);
        });

        $modal.find('.tmp-link-search-input').on('input', function () {
            var q = $(this).val().trim();
            var $results = $modal.find('.tmp-link-search-results');
            if (linkSearchTimer) clearTimeout(linkSearchTimer);
            if (q.length < 2) {
                $results.empty();
                return;
            }
            linkSearchTimer = setTimeout(function () {
                $results.html('<div class="tmp-link-searching">Zoeken...</div>');
                $.post(ajaxurl, {
                    action: 'tablemaster_search_posts',
                    nonce:  nonce,
                    search: q,
                }, function (res) {
                    $results.empty();
                    if (!res.success || !res.data.results.length) {
                        $results.html('<div class="tmp-link-no-results">Geen resultaten gevonden.</div>');
                        return;
                    }
                    res.data.results.forEach(function (item) {
                        var typeLabel = item.type === 'page' ? 'Pagina' : 'Bericht';
                        var $item = $('<div class="tmp-link-result-item">' +
                            '<span class="tmp-link-result-title">' + escHtml(item.title) + '</span>' +
                            '<span class="tmp-link-result-type">' + escHtml(typeLabel) + '</span>' +
                        '</div>');
                        $item.on('click', function () {
                            $modal.find('.tmp-link-url').val(item.url);
                            if (!$modal.find('.tmp-link-text').val().trim()) {
                                $modal.find('.tmp-link-text').val(item.title);
                            }
                            $modal.find('.tmp-link-newtab').prop('checked', false);
                            $results.find('.tmp-link-result-item').removeClass('selected');
                            $item.addClass('selected');
                        });
                        $results.append($item);
                    });
                });
            }, 300);
        });

        function doInsert() {
            var url  = $modal.find('.tmp-link-url').val().trim();
            if (!url) return;
            if (!/^(https?:|mailto:|tel:|#|\/)/i.test(url)) url = 'https://' + url;
            var text    = $modal.find('.tmp-link-text').val().trim() || url;
            var newTab  = $modal.find('.tmp-link-newtab').is(':checked');
            var a = document.createElement('a');
            a.href = url;
            a.textContent = text;
            if (newTab) { a.target = '_blank'; a.rel = 'noopener'; }
            var tag = a.outerHTML;
            $area.focus();
            restoreSelection();
            document.execCommand('insertHTML', false, tag);
            savedRange = null;
            syncCellData();
            closeLinkModal();
        }

        $modal.find('.tmp-link-insert').on('click', doInsert);
        $modal.find('.tmp-link-cancel, .tmp-link-modal-close').on('click', closeLinkModal);
        $overlay.on('click', closeLinkModal);

        $modal.find('.tmp-link-url').focus();
    }

    function closeLinkModal() {
        $('.tmp-link-overlay, .tmp-link-modal').remove();
        if (linkSearchTimer) clearTimeout(linkSearchTimer);
    }

    /* ===== CSV IMPORT ===== */
    function parseCSVWithDelimiter(text, delim) {
        var lines = [];
        var row = [];
        var field = '';
        var inQuote = false;
        var i = 0;
        var len = text.length;

        while (i < len) {
            var ch = text[i];
            if (inQuote) {
                if (ch === '"') {
                    if (i + 1 < len && text[i + 1] === '"') {
                        field += '"';
                        i += 2;
                    } else {
                        inQuote = false;
                        i++;
                    }
                } else {
                    field += ch;
                    i++;
                }
            } else {
                if (ch === '"') {
                    inQuote = true;
                    i++;
                } else if (ch === delim) {
                    row.push(field);
                    field = '';
                    i++;
                } else if (ch === '\r') {
                    row.push(field);
                    field = '';
                    lines.push(row);
                    row = [];
                    i++;
                    if (i < len && text[i] === '\n') i++;
                } else if (ch === '\n') {
                    row.push(field);
                    field = '';
                    lines.push(row);
                    row = [];
                    i++;
                } else {
                    field += ch;
                    i++;
                }
            }
        }
        if (field !== '' || row.length > 0) {
            row.push(field);
            lines.push(row);
        }
        return lines;
    }

    function detectDelimiter(text) {
        var delimiters = [',', ';', '\t'];
        var best = ',';
        var bestScore = 0;

        for (var d = 0; d < delimiters.length; d++) {
            var delim = delimiters[d];
            var testRows = parseCSVWithDelimiter(text, delim);
            if (testRows.length < 2) continue;

            var headerLen = testRows[0].length;
            if (headerLen < 2) continue;

            var consistent = 0;
            var total = Math.min(testRows.length, 10);
            for (var r = 0; r < total; r++) {
                if (testRows[r].length === headerLen) consistent++;
            }

            var score = (consistent / total) * headerLen;
            if (score > bestScore) {
                bestScore = score;
                best = delim;
            }
        }
        return best;
    }

    function handleCSVImport(file) {
        var reader = new FileReader();
        reader.onload = function (e) {
            var text = e.target.result;
            if (!text || !text.trim()) {
                alert('Het CSV-bestand is leeg.');
                return;
            }

            if (text.charCodeAt(0) === 0xFEFF) text = text.substring(1);

            var delimiter = detectDelimiter(text);
            var parsed = parseCSVWithDelimiter(text, delimiter);

            parsed = parsed.filter(function (row) {
                return row.some(function (cell) { return cell.trim() !== ''; });
            });

            if (parsed.length < 2) {
                alert('Het CSV-bestand bevat te weinig data (minimaal een kopregel en één datarij).');
                return;
            }

            var headers = parsed[0];
            var dataRows = parsed.slice(1);

            var maxCols = headers.length;
            dataRows.forEach(function (r) {
                if (r.length > maxCols) maxCols = r.length;
            });
            while (headers.length < maxCols) {
                headers.push('Kolom ' + (headers.length + 1));
            }

            var colCount = headers.length;
            var rowCount = dataRows.length;

            var msg = 'CSV gelezen: ' + colCount + ' kolommen, ' + rowCount + ' rijen.\n\n';
            if (columns.length > 0 || rows.length > 0) {
                msg += 'Er zijn al gegevens in de tabel.\n';
                msg += 'Wilt u de bestaande data VERVANGEN?\n\n';
                msg += 'OK = Vervangen\nAnnuleren = Import annuleren';
                if (!confirm(msg)) return;
            }

            pushUndo();
            columns = [];
            rows = [];
            colTempIdx = 0;
            rowTempIdx = 0;

            headers.forEach(function (label, idx) {
                var tempKey = 'new_' + (++colTempIdx);
                columns.push({
                    id:       0,
                    temp_key: tempKey,
                    label:    label.trim() || ('Kolom ' + (idx + 1)),
                    type:     'text',
                    settings: { align: 'left', sortable: true, filterable: true, header_group1: '', header_group2: '' }
                });
            });

            dataRows.forEach(function (csvRow) {
                var tempId = 'new_row_' + (++rowTempIdx);
                var cells = {};
                columns.forEach(function (col, ci) {
                    cells[col.temp_key] = (csvRow[ci] !== undefined ? csvRow[ci] : '').trim();
                });
                rows.push({
                    id:             0,
                    temp_id:        tempId,
                    row_type:       'data',
                    parent_id:      0,
                    parent_temp_id: '',
                    is_collapsed:   false,
                    cells:          cells,
                    cell_aligns:    {},
                    cell_merges:    {},
                });
            });

            rebuildRowTable();
        };

        reader.onerror = function () {
            alert('Fout bij het lezen van het bestand.');
        };

        reader.readAsText(file, 'UTF-8');
    }

    /* ===== DEFAULT TABLE FOR NEW ===== */
    function initDefaultTable() {
        columns = [];
        rows = [];
        colTempIdx = 0;
        rowTempIdx = 0;

        for (var i = 1; i <= 4; i++) {
            var tempKey = 'new_' + (++colTempIdx);
            columns.push({
                id:       0,
                temp_key: tempKey,
                label:    (i18n.add_column || 'Kolom') + ' ' + i,
                type:     'text',
                settings: { align: 'left', sortable: true, filterable: true, header_group1: '', header_group2: '' }
            });
        }

        for (var r = 0; r < 3; r++) {
            var tempId = 'new_row_' + (++rowTempIdx);
            var cells = {};
            columns.forEach(function (col) {
                cells[col.temp_key] = '';
            });
            rows.push({
                id: 0, temp_id: tempId, row_type: 'data',
                parent_id: 0, parent_temp_id: '', is_collapsed: false,
                cells: cells, cell_aligns: {}, cell_merges: {}
            });
        }

        var closeTempId = 'new_row_' + (++rowTempIdx);
        var closeCells = {};
        columns.forEach(function (col) {
            closeCells[col.temp_key] = '';
        });
        rows.push({
            id: 0, temp_id: closeTempId, row_type: 'footer',
            parent_id: 0, parent_temp_id: '', is_collapsed: false,
            cells: closeCells, cell_aligns: {}, cell_merges: {}
        });

        rebuildRowTable();
    }

    /* ===== LOAD STRUCTURE ===== */
    function loadStructure() {
        $.post(ajaxurl, {
            action:   'tablemaster_get_structure',
            nonce:    nonce,
            table_id: tableId,
            lang:     lang,
        }, function (res) {
            if (!res.success) return;
            var d = res.data;
            columns = [];

            // Build column temp_key map
            var colTempMap = {};
            (d.columns || []).forEach(function (col) {
                var colSettings = {};
                try { colSettings = JSON.parse(col.settings || '{}'); } catch(e) {}
                var tempKey = 'db_' + col.id;
                colTempMap[col.id] = tempKey;
                columns.push({
                    id:       parseInt(col.id),
                    temp_key: tempKey,
                    label:    col.label,
                    type:     col.type,
                    settings: {
                        align:         colSettings.align         || 'left',
                        sortable:      colSettings.sortable      !== false,
                        filterable:    colSettings.filterable     !== false,
                        header_group1: colSettings.header_group1 || '',
                        header_group2: colSettings.header_group2 || '',
                    }
                });
            });

            // Columns are rendered inline in the row table headers

            rows = [];
            (d.rows || []).forEach(function (row) {
                var cells = {};
                var cellAligns = {};
                var cellMerges = {};
                var rowCells = row.cells || {};
                var rowCellAligns = row.cell_aligns || {};
                var rowCellMerges = row.cell_merges || {};
                columns.forEach(function (col) {
                    var dbColId = col.id + '';
                    cells[col.temp_key] = rowCells[dbColId] !== undefined ? rowCells[dbColId] : '';
                    if (rowCellAligns[dbColId]) {
                        cellAligns[col.temp_key] = rowCellAligns[dbColId];
                    }
                    if (rowCellMerges[dbColId] && parseInt(rowCellMerges[dbColId]) > 1) {
                        cellMerges[col.temp_key] = parseInt(rowCellMerges[dbColId]);
                    }
                });

                rows.push({
                    id:             parseInt(row.id),
                    temp_id:        'db_' + row.id,
                    row_type:       row.row_type,
                    parent_id:      row.parent_id ? parseInt(row.parent_id) : 0,
                    parent_temp_id: row.parent_id ? 'db_' + row.parent_id : '',
                    is_collapsed:   row.is_collapsed === '1' || row.is_collapsed === 1,
                    cells:          cells,
                    cell_aligns:    cellAligns,
                    cell_merges:    cellMerges,
                });
            });

            rebuildRowTable();
        });
    }

    /* ===== SAVE ALL ===== */
    function saveAll() {
        syncColumnsFromDOM();
        syncRowsFromDOM();

        var tableName = $('#tmp-table-name').val().trim();
        if (!tableName) {
            alert('Vul een tabelnaam in.');
            return;
        }

        var activeTheme = 'custom';
        var colors      = getColorValues();

        var tableSettings = {
            theme:              activeTheme,
            colors:             colors,
            caption:            ($('#tmp-caption').val() || '').trim(),
            search:             $('#tmp-search').is(':checked'),
            search_position:    $('#tmp-search-position').val(),
            pagination:         $('#tmp-pagination').is(':checked'),
            per_page:           parseInt($('#tmp-per-page').val(), 10),
            per_page_selector:  $('#tmp-per-page-selector').is(':checked'),
            collapsible_groups: $('#tmp-collapsible').is(':checked'),
            mobile_mode:        'scroll',
            sortable:           $('#tmp-sortable').is(':checked'),
            column_filters:     $('#tmp-column-filters').is(':checked'),
            enable_export:      $('#tmp-enable-export').is(':checked'),
            inline_html:        $('#tmp-inline-html').is(':checked'),
            sticky_first_col:   $('#tmp-sticky-first-col').is(':checked'),
            sticky_header:      $('#tmp-sticky-header').is(':checked'),
            default_sort_col:   $('#tmp-default-sort-col').val(),
            default_sort_dir:   $('#tmp-default-sort-dir').val(),
            default_col_width:  $('#tmp-default-col-width').val().trim(),
            first_col_width:    $('#tmp-first-col-width').val().trim(),
            max_width:          $('#tmp-max-width').val().trim(),
            max_height:         $('#tmp-max-height').val().trim(),
            fonts:              (function () {
                var f = {};
                $('.tmp-font-size').each(function () {
                    var k = $(this).data('font-key');
                    if (!f[k]) f[k] = {};
                    f[k].size = $(this).val();
                });
                $('.tmp-font-bold').each(function () {
                    var k = $(this).data('font-key');
                    if (!f[k]) f[k] = {};
                    f[k].bold = $(this).is(':checked');
                });
                $('.tmp-font-italic').each(function () {
                    var k = $(this).data('font-key');
                    if (!f[k]) f[k] = {};
                    f[k].italic = $(this).is(':checked');
                });
                return f;
            })(),
        };

        setStatus('loading');

        // Step 1: save table meta
        $.post(ajaxurl, {
            action:   'tablemaster_save_table',
            nonce:    nonce,
            id:       tableId,
            name:     tableName,
            settings: JSON.stringify(tableSettings),
        }, function (res) {
            if (!res.success) {
                setStatus('error', i18n.error);
                return;
            }

            var savedId = res.data.id;
            if (!tableId) {
                tableId = savedId;
            }

            // Step 2: save structure
            var colsPayload = columns.map(function (col, idx) {
                var cleanSettings = {};
                for (var sk in col.settings) {
                    cleanSettings[sk] = col.settings[sk];
                }
                if (cleanSettings.header_group1) {
                    cleanSettings.header_group1 = cleanCellHtml(cleanSettings.header_group1);
                }
                if (cleanSettings.header_group2) {
                    cleanSettings.header_group2 = cleanCellHtml(cleanSettings.header_group2);
                }
                return {
                    id:       col.id || 0,
                    temp_key: col.temp_key,
                    label:    col.label,
                    type:     col.type,
                    settings: cleanSettings,
                };
            });

            var rowsPayload = rows.map(function (row) {
                return {
                    id:             row.id || 0,
                    temp_id:        row.temp_id,
                    row_type:       row.row_type,
                    parent_id:      row.parent_id || 0,
                    parent_temp_id: row.parent_temp_id || '',
                    is_collapsed:   row.is_collapsed ? 1 : 0,
                    cells:          row.cells,
                    cell_aligns:    row.cell_aligns || {},
                    cell_merges:    row.cell_merges || {},
                };
            });

            $.post(ajaxurl, {
                action:   'tablemaster_save_structure',
                nonce:    nonce,
                table_id: savedId,
                columns:  JSON.stringify(colsPayload),
                rows:     JSON.stringify(rowsPayload),
                lang:     lang,
            }, function (res2) {
                if (res2.success) {
                    setStatus('success', i18n.saved);
                    isDirty = false;
                    if (!cfg.table_id && savedId) {
                        // Redirect to edit page
                        setTimeout(function () {
                            window.location.href = cfg.edit_url + savedId;
                        }, 800);
                    }
                } else {
                    setStatus('error', i18n.error);
                }
            }).fail(function () {
                setStatus('error', i18n.error);
            });

        }).fail(function () {
            setStatus('error', i18n.error);
        });
    }

    function setStatus(type, msg) {
        var $s = $('#tmp-save-status');
        $s.removeClass('success error').text('');
        if (type === 'loading') {
            $s.html('<span class="tmp-spinner"></span>');
        } else if (type === 'success') {
            $s.addClass('success').text(msg || '✓ Opgeslagen');
        } else if (type === 'error') {
            $s.addClass('error').text(msg || 'Fout');
        }
    }

    /* ===== EVENTS ===== */
    function bindEvents() {
        // Add column
        $('#tmp-add-column').on('click', function () {
            addColumn();
        });

        // CSV Import
        $('#tmp-import-csv').on('click', function () {
            $('#tmp-csv-file').val('').trigger('click');
        });
        $('#tmp-csv-file').on('change', function () {
            var file = this.files[0];
            if (file) handleCSVImport(file);
            $(this).val('');
        });

        // Toolbar buttons
        $('#tmp-tb-bold').on('mousedown', function (e) { e.preventDefault(); toolbarBold(); });
        $('#tmp-tb-italic').on('mousedown', function (e) { e.preventDefault(); toolbarItalic(); });
        $('#tmp-tb-link').on('mousedown', function (e) { e.preventDefault(); toolbarLink(); });
        $('#tmp-tb-bullet').on('mousedown', function (e) { e.preventDefault(); toolbarBullet(); });
        $('.tmp-tb-align').on('mousedown', function (e) { e.preventDefault(); toolbarAlign($(this).data('align')); });
        $('#tmp-tb-delete-row').on('mousedown', function (e) { e.preventDefault(); toolbarDeleteRow(); });
        $('#tmp-tb-delete-col').on('mousedown', function (e) { e.preventDefault(); toolbarDeleteCol(); });

        $(document).on('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && !e.altKey) {
                var k = (e.key || '').toLowerCase();
                if (k === 'z' && !e.shiftKey) {
                    e.preventDefault();
                    doUndo();
                } else if ((k === 'z' && e.shiftKey) || k === 'y') {
                    e.preventDefault();
                    doRedo();
                }
            }
        });

        $(document).on('mousedown', function (e) {
            if (!$(e.target).closest('.tmp-cell-input, .tmp-cell-toolbar, .tmp-link-modal, .tmp-link-overlay, .tmp-col-inline-edit, .tmp-cell-merge-toolbar').length) {
                setTimeout(function () {
                    if (!$('.tmp-cell-input:focus').length) {
                        activeCell = null;
                        $('#tmp-cell-toolbar').addClass('tmp-toolbar-disabled');
                        $('#tmp-tb-cell-ref').text('');
                        $('.tmp-cell-active').removeClass('tmp-cell-active');
                    }
                }, 100);
            }
        });

        // Add row / groups
        $('#tmp-add-row').on('click', function ()    { addRow('data'); });
        $('#tmp-add-group1').on('click', function () { addRow('group_1'); });
        $('#tmp-add-group2').on('click', function () { addRow('group_2'); });
        $('#tmp-add-group3').on('click', function () { addRow('group_3'); });
        $('#tmp-add-footer').on('click', function () { addRow('footer'); });

        // Save
        $('#tmp-save-all').on('click', function () {
            saveAll();
        });

        // Settings change -> dirty
        $('#tmp-caption, #tmp-search, #tmp-search-position, #tmp-pagination, #tmp-per-page, #tmp-per-page-selector, #tmp-collapsible, #tmp-sortable, #tmp-column-filters, #tmp-enable-export, #tmp-inline-html, #tmp-sticky-first-col, #tmp-sticky-header, #tmp-default-sort-col, #tmp-default-sort-dir, #tmp-default-col-width, #tmp-first-col-width, #tmp-max-width, #tmp-max-height, #tmp-table-name, .tmp-font-size, .tmp-font-bold, .tmp-font-italic').on('change input', function () {
            isDirty = true;
            updatePreview();
        });

        // Search/pagination toggle show/hide
        $('#tmp-search').on('change', function () {
            $('#tmp-search-position-group').toggle($(this).is(':checked'));
        });
        $('#tmp-search-position-group').toggle($('#tmp-search').is(':checked'));

        $('#tmp-pagination').on('change', function () {
            $('#tmp-per-page-group').toggle($(this).is(':checked'));
        });
        $('#tmp-per-page-group').toggle($('#tmp-pagination').is(':checked'));

        // Shortcode copy button
        $(document).on('click', '.tmp-copy-btn', function () {
            var shortcode = $(this).data('shortcode') || $('#tmp-shortcode-value').text();
            copyToClipboard(shortcode);
            var $btn = $(this);
            var origText = $btn.text();
            $btn.text('✓ ' + (i18n.copy_shortcode || 'Gekopieerd!'));
            setTimeout(function () { $btn.text(origText); }, 2000);
        });

        // Delete on list page
        $(document).on('click', '.tmp-delete-btn', function () {
            if (!confirm(i18n.confirm_delete)) return;
            var id = $(this).data('id');
            var $row = $(this).closest('tr');
            $.post(ajaxurl, {
                action: 'tablemaster_delete_table',
                nonce:  nonce,
                id:     id,
            }, function (res) {
                if (res.success) {
                    $row.fadeOut(300, function () { $row.remove(); });
                } else {
                    alert(i18n.error);
                }
            });
        });

        // Duplicate on list page
        $(document).on('click', '.tmp-duplicate-btn', function () {
            var id = $(this).data('id');
            $.post(ajaxurl, {
                action: 'tablemaster_duplicate_table',
                nonce:  nonce,
                id:     id,
            }, function (res) {
                if (res.success) {
                    window.location.href = cfg.edit_url + res.data.id;
                } else {
                    alert(i18n.error);
                }
            });
        });

        // Unsaved changes warning
        window.addEventListener('beforeunload', function (e) {
            if (isDirty) {
                e.returnValue = i18n.unsaved_changes;
                return i18n.unsaved_changes;
            }
        });
    }

    /* ===== UTILS ===== */
    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).catch(function () {
                fallbackCopy(text);
            });
        } else {
            fallbackCopy(text);
        }
    }

    function fallbackCopy(text) {
        var $ta = $('<textarea>').val(text).css({ position: 'fixed', top: -9999 }).appendTo('body');
        $ta[0].select();
        document.execCommand('copy');
        $ta.remove();
    }

    function cleanCellHtml(html) {
        var stripped = html.replace(/<[^>]*>/g, '').replace(/&nbsp;/gi, ' ').replace(/&#160;/g, ' ').replace(/\s+/g, '').trim();
        return stripped === '' ? '' : html;
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escAttr(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

})(jQuery);
