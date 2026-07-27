(function() {
    var el = document.getElementById('fd-config');
    if (!el) return;

    var cfg;
    try { cfg = JSON.parse(el.textContent); } catch(e) { return; }

    var isFormPage          = cfg.isFormPage;
    var isListPage          = cfg.isListPage;
    var isSummaryPage       = cfg.isSummaryPage;
    var summaryReplacements = cfg.summaryReplacements || [];
    var viewSelectors       = cfg.viewSelectors;
    var listSelectors       = cfg.listSelectors;
    var filterSelectors     = cfg.filterSelectors || {};
    var defaultLabels       = cfg.defaultLabels;
    var customFields        = cfg.customFields;

    function merge(base, override) {
        var result = {};
        Object.keys(base).forEach(function(k) { result[k] = base[k]; });
        Object.keys(override).forEach(function(k) { result[k] = override[k]; });
        return result;
    }

    var labels       = merge(cfg.global.labels,       cfg.project.labels);
    var descriptions = merge(cfg.global.descriptions, cfg.project.descriptions);
    var placeholders = merge(cfg.global.placeholders, cfg.project.placeholders);

    // --- TinyMCE placeholder support ---
    var pendingMce = {}; // editorId -> placeholder text

    function applyMcePlaceholder(editor, text) {
        var body = editor.getBody();
        body.setAttribute('data-fd-ph', text);
        editor.dom.addStyle(
            'body[data-fd-ph]::before{content:attr(data-fd-ph);color:#aaa;display:block;' +
            'position:absolute;pointer-events:none;}' +
            'body[data-fd-ph-active]::before{display:none;}'
        );
        function update() {
            var empty = editor.getContent({ format: 'text' }).trim() === '';
            if (empty) { body.removeAttribute('data-fd-ph-active'); }
            else { body.setAttribute('data-fd-ph-active', ''); }
        }
        editor.on('input keyup Change SetContent', update);
        update();
    }

    function setMcePlaceholder(editorId, text) {
        if (!window.tinymce) { pendingMce[editorId] = text; return; }
        var editor = tinymce.get(editorId);
        if (editor) {
            applyMcePlaceholder(editor, text);
        } else {
            pendingMce[editorId] = text;
        }
    }

    // Handle editors that initialize after our script runs
    if (window.tinymce) {
        tinymce.on('AddEditor', function(e) {
            var id = e.editor.id;
            if (pendingMce[id]) {
                applyMcePlaceholder(e.editor, pendingMce[id]);
                delete pendingMce[id];
            }
        });
    }
    // --- end TinyMCE support ---

    function applyEnhancements() {
        var allFields = Object.keys(labels).concat(Object.keys(descriptions)).concat(Object.keys(placeholders))
            .filter(function(v, i, a) { return a.indexOf(v) === i; });

        allFields.forEach(function(name) {
            if (isFormPage) {
                var aliases = {'additional_info': 'additional_information'};
                var altName = aliases[name] || null;
                var el = document.querySelector(
                    '[name="' + name + '"], [name="' + name + '[]"]' +
                    (altName ? ', [name="' + altName + '"], [name="' + altName + '[]"]' : '')
                );
                if (!el) {
                    if (labels[name] && defaultLabels[name]) {
                        var defaultText = defaultLabels[name];
                        var tds = document.querySelectorAll('td.category');
                        for (var i = 0; i < tds.length; i++) {
                            if (tds[i].children.length === 0 && tds[i].textContent.trim() === defaultText) {
                                tds[i].textContent = labels[name];
                                break;
                            }
                        }
                    }
                    return;
                }
                var labelEl = document.querySelector('label[for="' + el.id + '"]');
                if (!labelEl && altName) labelEl = document.querySelector('label[for="' + altName + '"]');
                if (placeholders[name]) {
                    el.placeholder = placeholders[name];
                    if (el.id) setMcePlaceholder(el.id, placeholders[name]);
                }
                if (descriptions[name]) {
                    var hintParent = labelEl ? labelEl.parentNode : el.parentNode;
                    var hintAfter  = labelEl ? labelEl.nextSibling  : el.nextSibling;
                    if (!hintParent.querySelector('.fd-hint')) {
                        var hint = document.createElement('p');
                        hint.className = 'fd-hint';
                        hint.style.cssText = 'color:#777;font-size:11px;margin:3px 0 0;line-height:1.4;';
                        hint.textContent = descriptions[name];
                        hintParent.insertBefore(hint, hintAfter);
                    }
                }
                if (labels[name] && labelEl) labelEl.textContent = labels[name];

            } else if (isListPage) {
                if (!labels[name]) return;
                var sel = listSelectors[name];
                if (sel) {
                    document.querySelectorAll(sel).forEach(function(thEl) {
                        var link = thEl.querySelector('a');
                        if (link) {
                            for (var i = 0; i < link.childNodes.length; i++) {
                                if (link.childNodes[i].nodeType === 3) {
                                    link.childNodes[i].textContent = labels[name];
                                    break;
                                }
                            }
                        } else {
                            thEl.textContent = labels[name];
                        }
                    });
                }
                var filterSel = filterSelectors[name];
                if (filterSel) {
                    var filterEl = document.querySelector(filterSel);
                    if (filterEl) filterEl.textContent = labels[name];
                }
            } else {
                if (!labels[name]) return;
                var sel = viewSelectors[name];
                if (!sel) return;
                document.querySelectorAll(sel).forEach(function(el) {
                    el.textContent = labels[name];
                });
            }
        });
    }

    function findRowLabelCell(el) {
        var row = el;
        while (row && row.tagName !== 'TR') row = row.parentNode;
        if (!row) return null;
        return row.querySelector('th') || row.querySelector('td.category');
    }

    function applyCustomFields() {
        customFields.forEach(function(cf) {
            if (!cf.label && !cf.desc && !cf.ph) return;

            if (isFormPage) {
                var el = document.querySelector('[name="custom_field_' + cf.id + '"], [name="custom_field_' + cf.id + '[]"]');
                if (!el) return;
                var labelEl = document.querySelector('label[for="custom_field_' + cf.id + '"]');
                if (cf.ph) {
                    el.placeholder = cf.ph;
                    if (el.id) setMcePlaceholder(el.id, cf.ph);
                }
                if (cf.desc) {
                    var thEl = labelEl ? labelEl.parentNode : findRowLabelCell(el);
                    var hintParent = thEl || el.parentNode;
                    var hintAfter  = thEl ? null : el.nextSibling;
                    if (!hintParent.querySelector('.fd-hint')) {
                        var hint = document.createElement('p');
                        hint.className = 'fd-hint';
                        hint.style.cssText = 'color:#777;font-size:11px;margin:3px 0 0;line-height:1.4;';
                        hint.textContent = cf.desc;
                        hintParent.insertBefore(hint, hintAfter);
                    }
                }
                if (cf.label && labelEl) labelEl.textContent = cf.label;

            } else if (isListPage) {
                if (!cf.label) return;
                var sel = 'th.column-custom-' + cf.cssName;
                document.querySelectorAll(sel).forEach(function(thEl) {
                    var link = thEl.querySelector('a');
                    if (link) {
                        for (var i = 0; i < link.childNodes.length; i++) {
                            if (link.childNodes[i].nodeType === 3) { link.childNodes[i].textContent = cf.label; break; }
                        }
                    } else { thEl.textContent = cf.label; }
                });
                var cfFilterEl = document.querySelector('#custom_field_' + cf.id + '_filter');
                if (cfFilterEl) cfFilterEl.textContent = cf.label;
            } else {
                if (!cf.label) return;
                document.querySelectorAll('th.bug-custom-field.category').forEach(function(th) {
                    if (th.textContent.trim() === cf.name) th.textContent = cf.label;
                });
            }
        });
    }

    function applySummaryHeadings() {
        if (!summaryReplacements.length) return;
        document.querySelectorAll('th').forEach(function(th) {
            var text = th.textContent.trim();
            summaryReplacements.forEach(function(r) {
                if (text === r.find) th.textContent = r.replace;
            });
        });
    }

    function run() { applyEnhancements(); applyCustomFields(); if (isSummaryPage) applySummaryHeadings(); }
    if (document.readyState === 'complete') { run(); } else { window.addEventListener('load', run); }
})();
