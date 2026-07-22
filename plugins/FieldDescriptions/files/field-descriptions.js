(function() {
    var el = document.getElementById('fd-config');
    if (!el) return;

    var cfg;
    try { cfg = JSON.parse(el.textContent); } catch(e) { return; }

    var isFormPage    = cfg.isFormPage;
    var isListPage    = cfg.isListPage;
    var viewSelectors = cfg.viewSelectors;
    var listSelectors = cfg.listSelectors;
    var defaultLabels = cfg.defaultLabels;
    var customFields  = cfg.customFields;

    function merge(base, override) {
        var result = {};
        Object.keys(base).forEach(function(k) { result[k] = base[k]; });
        Object.keys(override).forEach(function(k) { result[k] = override[k]; });
        return result;
    }

    var labels       = merge(cfg.global.labels,       cfg.project.labels);
    var descriptions = merge(cfg.global.descriptions, cfg.project.descriptions);
    var placeholders = merge(cfg.global.placeholders, cfg.project.placeholders);

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
                if (placeholders[name]) el.placeholder = placeholders[name];
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
                if (!sel) return;
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

    function applyCustomFields() {
        customFields.forEach(function(cf) {
            if (!cf.label && !cf.desc && !cf.ph) return;

            if (isFormPage) {
                var el = document.querySelector('[name="custom_field_' + cf.id + '"], [name="custom_field_' + cf.id + '[]"]');
                if (!el) return;
                var labelEl = document.querySelector('label[for="custom_field_' + cf.id + '"]');
                if (cf.ph) el.placeholder = cf.ph;
                if (cf.desc) {
                    var hintParent = labelEl ? labelEl.parentNode : el.parentNode;
                    var hintAfter  = labelEl ? labelEl.nextSibling  : el.nextSibling;
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
            } else {
                if (!cf.label) return;
                document.querySelectorAll('th.bug-custom-field.category').forEach(function(th) {
                    if (th.textContent.trim() === cf.name) th.textContent = cf.label;
                });
            }
        });
    }

    function run() { applyEnhancements(); applyCustomFields(); }
    if (document.readyState === 'complete') { run(); } else { window.addEventListener('load', run); }
})();
