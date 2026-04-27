/* ============================================================
   gacov-ui.js — Módulos UI: Ajustes, Filtro de tabla, Sort
   GACOV Inventarios · AMR Tech
   Standalone IIFE — sin dependencias externas
   ============================================================ */
(function () {
  'use strict';

  /* ══════════════════════════════════════════════════════════
     MÓDULO A — Ajustes de usuario (tema, fuente, acento, densidad)
  ══════════════════════════════════════════════════════════ */

  var STORAGE_KEY = 'gacov_ui_prefs';

  var DEFAULTS = {
    theme:   'auto',
    font:    'md',
    accent:  '#00D4FF',
    density: 'normal'
  };

  var FONT_SIZES = { sm: 13, md: 15, lg: 17 };

  var ACCENT_PRESETS = [
    { label: 'Cian',    color: '#00D4FF' },
    { label: 'Violeta', color: '#7C3AED' },
    { label: 'Verde',   color: '#10B981' },
    { label: 'Ámbar',   color: '#F59E0B' },
    { label: 'Rojo',    color: '#EF4444' },
    { label: 'Azul',    color: '#3B82F6' },
    { label: 'Rosa',    color: '#EC4899' },
    { label: 'Naranja', color: '#F97316' }
  ];

  function loadPrefs() {
    try {
      var raw = localStorage.getItem(STORAGE_KEY);
      var parsed = raw ? JSON.parse(raw) : {};
      return Object.assign({}, DEFAULTS, parsed);
    } catch (e) {
      return Object.assign({}, DEFAULTS);
    }
  }

  function savePrefs(prefs) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
    } catch (e) {
      // silencia errores de cuota
    }
  }

  function resolveTheme(theme) {
    if (theme === 'auto') {
      return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    return theme;
  }

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', resolveTheme(theme));
  }

  function applyFont(font) {
    document.documentElement.setAttribute('data-font', font);
    var size = FONT_SIZES[font] || 15;
    document.documentElement.style.setProperty('--ui-font-base', size + 'px');
  }

  function applyAccent(color) {
    var root = document.documentElement.style;
    root.setProperty('--gacov-primary', color);
    root.setProperty('--gacov-border-focus', color);
    root.setProperty('--gacov-gradient', 'linear-gradient(135deg,' + color + ' 0%,#7C3AED 100%)');
  }

  function applyDensity(density) {
    document.documentElement.setAttribute('data-density', density);
  }

  function applyAll(prefs) {
    applyTheme(prefs.theme);
    applyFont(prefs.font);
    applyAccent(prefs.accent);
    applyDensity(prefs.density);
  }

  /* ─── SVG icons ─── */
  var SVG_MOON = '<svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>';
  var SVG_SUN  = '<svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13"><path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/></svg>';
  var SVG_AUTO = '<svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13"><path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"/></svg>';
  var SVG_GEAR = '<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>';
  var SVG_CLOSE = '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>';

  function buildPanelHTML(prefs) {
    /* Segmented theme */
    function themeBtn(value, icon, label) {
      return '<button class="ui-seg-btn' + (prefs.theme === value ? ' active' : '') + '" data-theme-val="' + value + '">' + icon + label + '</button>';
    }

    /* Segmented density */
    function densBtn(value, label) {
      return '<button class="ui-seg-btn' + (prefs.density === value ? ' active' : '') + '" data-density-val="' + value + '">' + label + '</button>';
    }

    /* Color swatches */
    var swatches = ACCENT_PRESETS.map(function (p) {
      var isActive = p.color.toLowerCase() === prefs.accent.toLowerCase();
      return '<button class="ui-color-swatch' + (isActive ? ' active' : '') + '" data-color="' + p.color + '" title="' + p.label + '" style="background:' + p.color + ';"></button>';
    }).join('');

    var currentFontSize = FONT_SIZES[prefs.font] || 15;

    return [
      '<div class="ui-settings-overlay" id="ui-settings-overlay"></div>',
      '<div class="ui-settings-panel" id="ui-settings-panel">',
      '  <div class="ui-settings-header">',
      '    <h3>Preferencias de interfaz</h3>',
      '    <button class="ui-settings-close" id="ui-settings-close" aria-label="Cerrar">' + SVG_CLOSE + '</button>',
      '  </div>',
      '  <div class="ui-settings-body">',

      /* Tema */
      '    <div class="ui-settings-section">',
      '      <span class="ui-settings-label">Tema</span>',
      '      <div class="ui-seg" id="ui-seg-theme">',
      themeBtn('light', SVG_SUN, 'Claro'),
      themeBtn('dark',  SVG_MOON, 'Oscuro'),
      themeBtn('auto',  SVG_AUTO, 'Auto'),
      '      </div>',
      '    </div>',

      /* Tamaño de fuente */
      '    <div class="ui-settings-section">',
      '      <span class="ui-settings-label">Tamaño de texto</span>',
      '      <div class="ui-font-control">',
      '        <button class="ui-font-btn ui-font-btn--dec" id="ui-font-dec" aria-label="Reducir fuente">A−</button>',
      '        <span class="ui-font-sample" id="ui-font-sample">' + currentFontSize + 'px</span>',
      '        <button class="ui-font-btn ui-font-btn--inc" id="ui-font-inc" aria-label="Aumentar fuente">A+</button>',
      '      </div>',
      '    </div>',

      /* Densidad */
      '    <div class="ui-settings-section">',
      '      <span class="ui-settings-label">Densidad</span>',
      '      <div class="ui-seg" id="ui-seg-density">',
      densBtn('compact', 'Compacto'),
      densBtn('normal', 'Normal'),
      densBtn('comfortable', 'Amplio'),
      '      </div>',
      '    </div>',

      /* Acento */
      '    <div class="ui-settings-section">',
      '      <span class="ui-settings-label">Color de acento</span>',
      '      <div class="ui-colors" id="ui-colors">' + swatches + '</div>',
      '      <div class="ui-color-custom">',
      '        <label for="ui-color-input">Personalizado:</label>',
      '        <input type="color" id="ui-color-input" value="' + prefs.accent + '">',
      '      </div>',
      '    </div>',

      '  </div>',
      '  <div class="ui-settings-footer">',
      '    <button class="ui-settings-reset" id="ui-settings-reset">Restablecer valores predeterminados</button>',
      '  </div>',
      '</div>'
    ].join('\n');
  }

  function initSettings() {
    var prefs = loadPrefs();
    applyAll(prefs);

    /* Inyectar HTML del panel */
    var panelContainer = document.createElement('div');
    panelContainer.innerHTML = buildPanelHTML(prefs);
    document.body.appendChild(panelContainer);

    /* Crear botón de configuración en topbar */
    var actionsEl = document.querySelector('.topbar-actions');
    if (actionsEl) {
      var gearBtn = document.createElement('button');
      gearBtn.className = 'topbar-settings-btn';
      gearBtn.id = 'ui-settings-open';
      gearBtn.title = 'Preferencias de interfaz';
      gearBtn.setAttribute('aria-label', 'Abrir preferencias');
      gearBtn.innerHTML = SVG_GEAR;
      actionsEl.prepend(gearBtn);
    }

    /* Referencias a elementos */
    function getPanel()   { return document.getElementById('ui-settings-panel'); }
    function getOverlay() { return document.getElementById('ui-settings-overlay'); }

    function openPanel() {
      var p = getPanel(); var o = getOverlay();
      if (p) p.classList.add('open');
      if (o) o.classList.add('open');
    }

    function closePanel() {
      var p = getPanel(); var o = getOverlay();
      if (p) p.classList.remove('open');
      if (o) o.classList.remove('open');
    }

    /* Abrir */
    document.addEventListener('click', function (e) {
      if (e.target.closest('#ui-settings-open')) openPanel();
    });

    /* Cerrar */
    document.addEventListener('click', function (e) {
      if (e.target.closest('#ui-settings-close')) closePanel();
      if (e.target.id === 'ui-settings-overlay') closePanel();
    });

    /* Cambio de tema */
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-theme-val]');
      if (!btn) return;
      var val = btn.getAttribute('data-theme-val');
      prefs.theme = val;
      savePrefs(prefs);
      applyTheme(val);
      document.querySelectorAll('[data-theme-val]').forEach(function (b) {
        b.classList.toggle('active', b.getAttribute('data-theme-val') === val);
      });
    });

    /* Cambio de densidad */
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-density-val]');
      if (!btn) return;
      var val = btn.getAttribute('data-density-val');
      prefs.density = val;
      savePrefs(prefs);
      applyDensity(val);
      document.querySelectorAll('[data-density-val]').forEach(function (b) {
        b.classList.toggle('active', b.getAttribute('data-density-val') === val);
      });
    });

    /* Fuente — escalas disponibles */
    var FONT_STEPS = ['sm', 'md', 'lg'];

    function updateFontSample(font) {
      var sampleEl = document.getElementById('ui-font-sample');
      if (sampleEl) sampleEl.textContent = (FONT_SIZES[font] || 15) + 'px';
    }

    document.addEventListener('click', function (e) {
      var dec = e.target.closest('#ui-font-dec');
      var inc = e.target.closest('#ui-font-inc');
      if (!dec && !inc) return;
      var idx = FONT_STEPS.indexOf(prefs.font);
      if (dec) idx = Math.max(0, idx - 1);
      if (inc) idx = Math.min(FONT_STEPS.length - 1, idx + 1);
      prefs.font = FONT_STEPS[idx];
      savePrefs(prefs);
      applyFont(prefs.font);
      updateFontSample(prefs.font);
    });

    /* Swatches de color */
    var customColorTimer = null;

    function applyAndSaveAccent(color) {
      prefs.accent = color;
      savePrefs(prefs);
      applyAccent(color);
      /* Actualizar estado activo en swatches */
      document.querySelectorAll('.ui-color-swatch').forEach(function (sw) {
        sw.classList.toggle('active', sw.getAttribute('data-color').toLowerCase() === color.toLowerCase());
      });
    }

    document.addEventListener('click', function (e) {
      var swatch = e.target.closest('.ui-color-swatch');
      if (!swatch) return;
      var color = swatch.getAttribute('data-color');
      var customInput = document.getElementById('ui-color-input');
      if (customInput) customInput.value = color;
      applyAndSaveAccent(color);
    });

    /* Color personalizado (debounced 100ms) */
    document.addEventListener('input', function (e) {
      if (e.target.id !== 'ui-color-input') return;
      clearTimeout(customColorTimer);
      var color = e.target.value;
      customColorTimer = setTimeout(function () {
        applyAndSaveAccent(color);
      }, 100);
    });

    /* Listener para cambio de preferencia del SO en modo auto */
    var mq = window.matchMedia('(prefers-color-scheme: dark)');
    mq.addEventListener('change', function () {
      if (prefs.theme === 'auto') applyTheme('auto');
    });

    /* Reset */
    document.addEventListener('click', function (e) {
      if (!e.target.closest('#ui-settings-reset')) return;
      savePrefs(Object.assign({}, DEFAULTS));
      location.reload();
    });
  }

  /* ══════════════════════════════════════════════════════════
     MÓDULO B — Filtro de tabla en vivo
  ══════════════════════════════════════════════════════════ */

  function debounce(fn, delay) {
    var timer;
    return function () {
      var args = arguments;
      var ctx  = this;
      clearTimeout(timer);
      timer = setTimeout(function () { fn.apply(ctx, args); }, delay);
    };
  }

  function getOrCreateEmptyRow(tbody, colCount) {
    var row = tbody.querySelector('.filter-empty-row');
    if (!row) {
      row = document.createElement('tr');
      row.className = 'filter-empty-row';
      var td = document.createElement('td');
      td.colSpan = colCount || 1;
      td.textContent = 'No hay resultados para los filtros aplicados.';
      row.appendChild(td);
    }
    return row;
  }

  function initTableFilter() {
    var tables = document.querySelectorAll('table.data-table');
    if (!tables.length) return;

    var forms = Array.from(document.querySelectorAll('form[method="get"], form[method="GET"]'));

    tables.forEach(function (table) {
      /* Buscar el formulario más cercano */
      var form = null;
      var panel = table.closest('.panel');

      if (panel) {
        /* Buscar en el padre del panel */
        var parentEl = panel.parentElement;
        if (parentEl) {
          form = parentEl.querySelector('form[method="get"], form[method="GET"]');
        }
      }

      if (!form) {
        /* Buscar en .gacov-content */
        var contentEl = table.closest('.gacov-content');
        if (contentEl) {
          form = contentEl.querySelector('form[method="get"], form[method="GET"]');
        }
      }

      if (!form && forms.length) form = forms[0];
      if (!form) return;

      var tbody = table.querySelector('tbody');
      if (!tbody) return;

      var colCount = table.querySelectorAll('thead th').length || 1;
      var emptyRow = getOrCreateEmptyRow(tbody, colCount);

      /* Obtener badge de conteo */
      var firstTextInput = form.querySelector('input[type="text"], input[type="search"], input:not([type])');
      var badge = null;
      if (firstTextInput) {
        badge = firstTextInput.parentElement.querySelector('.filter-count-badge');
        if (!badge) {
          badge = document.createElement('span');
          badge.className = 'filter-count-badge';
          badge.style.display = 'none';
          firstTextInput.parentElement.appendChild(badge);
        }
      }

      /* Eliminar botón "Filtrar" exacto */
      form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
        if (btn.textContent.trim().toLowerCase() === 'filtrar') {
          btn.remove();
        }
      });

      function runFilter() {
        /* Solo filtramos por text inputs — los selects usan valor de ID,
           no de texto visible, así que los dejamos hacer submit al servidor */
        var textInputs = Array.from(form.querySelectorAll('input[type="text"], input[type="search"], input:not([type]):not([type="hidden"]):not([type="date"]):not([type="number"])'));

        var terms = textInputs
          .map(function (i) { return i.value.trim().toLowerCase(); })
          .filter(function (v) { return v !== ''; });

        var allRows = Array.from(tbody.querySelectorAll('tr:not(.filter-empty-row)'));
        var visibleCount = 0;

        allRows.forEach(function (row) {
          var text = row.textContent.toLowerCase();
          var matches = terms.every(function (term) { return text.indexOf(term) !== -1; });
          if (matches) {
            row.classList.remove('filter-hidden');
            visibleCount++;
          } else {
            row.classList.add('filter-hidden');
          }
        });

        /* Empty state */
        if (visibleCount === 0 && terms.length > 0) {
          tbody.appendChild(emptyRow);
          emptyRow.style.display = '';
        } else {
          emptyRow.style.display = 'none';
        }

        /* Badge */
        if (badge) {
          if (terms.length > 0) {
            badge.textContent = visibleCount + ' de ' + allRows.length;
            badge.style.display = '';
          } else {
            badge.style.display = 'none';
          }
        }
      }

      var debouncedFilter = debounce(runFilter, 150);

      /* Inputs de texto → filtro en tiempo real */
      form.querySelectorAll('input[type="text"], input[type="search"], input:not([type]):not([type="hidden"]):not([type="date"]):not([type="number"])').forEach(function (input) {
        input.addEventListener('input', debouncedFilter);
        input.addEventListener('keydown', function (e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            runFilter();
          }
        });
      });

      /* Selects → submit al servidor (comportamiento nativo, no interceptamos) */

      /* Reset */
      form.addEventListener('reset', function () {
        setTimeout(runFilter, 50);
      });
    });
  }

  /* ══════════════════════════════════════════════════════════
     MÓDULO C — Sort de tabla
  ══════════════════════════════════════════════════════════ */

  function getCellValue(row, idx) {
    var cell = row.cells[idx];
    if (!cell) return '';
    return cell.dataset.sort || cell.textContent.trim() || '';
  }

  function parseDate(str) {
    /* Soporta dd/mm/yyyy */
    var m = str.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (m) return new Date(+m[3], +m[2] - 1, +m[1]);
    return null;
  }

  function compareValues(a, b) {
    /* Numérico */
    var na = parseFloat(a.replace(/[^\d.-]/g, ''));
    var nb = parseFloat(b.replace(/[^\d.-]/g, ''));
    if (!isNaN(na) && !isNaN(nb)) return na - nb;

    /* Fecha dd/mm/yyyy */
    var da = parseDate(a);
    var db = parseDate(b);
    if (da && db) return da - db;

    /* Cadena */
    return a.localeCompare(b, 'es', { sensitivity: 'base' });
  }

  function sortTableRows(table, colIdx, dir) {
    var tbody = table.querySelector('tbody');
    if (!tbody) return;

    var rows = Array.from(tbody.querySelectorAll('tr:not(.filter-empty-row)'));
    var emptyRow = tbody.querySelector('.filter-empty-row');

    rows.sort(function (a, b) {
      var va = getCellValue(a, colIdx);
      var vb = getCellValue(b, colIdx);
      var cmp = compareValues(va, vb);
      return dir === 'desc' ? -cmp : cmp;
    });

    rows.forEach(function (r) { tbody.appendChild(r); });
    if (emptyRow) tbody.appendChild(emptyRow);
  }

  var SORT_ICON_HTML = [
    '<span class="th-sort-icon">',
    '<svg class="sort-asc-arrow"  viewBox="0 0 8 5"><path d="M4 0L8 5H0z"/></svg>',
    '<svg class="sort-desc-arrow" viewBox="0 0 8 5"><path d="M4 5L0 0h8z"/></svg>',
    '</span>'
  ].join('');

  function initTableSort() {
    document.querySelectorAll('table.data-table').forEach(function (table) {
      var state = { col: -1, dir: 'asc' };
      var headers = Array.from(table.querySelectorAll('thead th'));

      headers.forEach(function (th, idx) {
        if (th.hasAttribute('data-no-sort')) return;

        th.classList.add('sortable');
        th.insertAdjacentHTML('beforeend', SORT_ICON_HTML);

        th.addEventListener('click', function () {
          if (state.col === idx) {
            state.dir = state.dir === 'asc' ? 'desc' : 'asc';
          } else {
            state.col = idx;
            state.dir = 'asc';
          }

          /* Limpiar clases en todos los headers */
          headers.forEach(function (h) {
            h.classList.remove('sort-asc', 'sort-desc');
          });

          th.classList.add('sort-' + state.dir);
          sortTableRows(table, idx, state.dir);
        });
      });
    });
  }

  /* ══════════════════════════════════════════════════════════
     INIT
  ══════════════════════════════════════════════════════════ */

  function init() {
    initSettings();
    initTableFilter();
    initTableSort();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
