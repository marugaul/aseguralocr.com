// /assets/js/cr-geo.js
(function (window, document) {
  const CRGeo = {
    defaults: {
      geoUrl: '/assets/data/cr_geo.json',
      provinciaSelector: '[name="provincia"]',
      cantonName: 'canton',
      distritoName: 'distrito',
      fallbackSuffix: '_fallback',
      placeholderCanton: 'Seleccione Cantón...',
      placeholderDistrito: 'Seleccione Distrito...',
      provinceValueToNameMap: {}
    },

    init: async function (opts = {}) {
      this.opts = Object.assign({}, this.defaults, opts);
      this.form = document.querySelector('form#insurance-form') || document.querySelector('form');
      if (!this.form) return console.warn('CRGeo: no se encontró formulario');

      this.provinciaEl = document.querySelector(this.opts.provinciaSelector);
      if (!this.provinciaEl) return console.warn('CRGeo: no se encontró select de provincia');

      // prepare canton/distrito fields (create select + preserve original input as fallback)
      this.prepareField(this.opts.cantonName);
      this.prepareField(this.opts.distritoName);

      this.cantonSelect = this.form.querySelector(`[name="${this.opts.cantonName}"]`);
      this.distritoSelect = this.form.querySelector(`[name="${this.opts.distritoName}"]`);
      this.cantonFallback = this.form.querySelector(`[name="${this.opts.cantonName + this.opts.fallbackSuffix}"]`);
      this.distritoFallback = this.form.querySelector(`[name="${this.opts.distritoName + this.opts.fallbackSuffix}"]`);

      // Load JSON
      try {
        const res = await fetch(this.opts.geoUrl, {cache: 'no-cache'});
        if (!res.ok) throw new Error('No se pudo cargar geo JSON: ' + res.status);
        this.geo = await res.json();
      } catch (e) {
        console.error('CRGeo:', e);
        this.geo = null;
      }

      // events
      this.provinciaEl.addEventListener('change', () => this.onProvinciaChange());
      if (this.cantonSelect) this.cantonSelect.addEventListener('change', () => this.onCantonChange());

      // initialize state in case of prefilled values
      this.initialiseValues();
    },

    prepareField: function (fieldName) {
      const existing = this.form.querySelector(`[name="${fieldName}"]`);
      if (!existing) {
        const select = document.createElement('select');
        select.name = fieldName;
        select.className = 'input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none';
        const opt = document.createElement('option');
        opt.value = '';
        opt.text = fieldName === this.defaults.cantonName ? this.defaults.placeholderCanton : this.defaults.placeholderDistrito;
        select.appendChild(opt);
        // append near provincia element
        const ref = this.form.querySelector(this.defaults.provinciaSelector);
        if (ref && fieldName === this.defaults.cantonName) ref.parentNode.insertAdjacentElement('afterend', select);
        else this.form.appendChild(select);
        return;
      }

      // if existing is not select -> rename it to fallback and insert select after it
      if (existing.tagName.toLowerCase() !== 'select') {
        existing.name = fieldName + this.defaults.fallbackSuffix;
        existing.classList.add('cr-geo-fallback');
        existing.style.display = 'none';
        const sel = document.createElement('select');
        sel.name = fieldName;
        sel.className = existing.className || 'input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg';
        const option = document.createElement('option');
        option.value = '';
        option.text = fieldName === this.defaults.cantonName ? this.defaults.placeholderCanton : this.defaults.placeholderDistrito;
        sel.appendChild(option);
        existing.insertAdjacentElement('afterend', sel);
      } else {
        // ensure empty option exists
        if (!existing.querySelector('option[value=""]')) {
          const opt = document.createElement('option');
          opt.value = '';
          opt.text = fieldName === this.defaults.cantonName ? this.defaults.placeholderCanton : this.defaults.placeholderDistrito;
          existing.insertBefore(opt, existing.firstChild);
        }
      }
    },

    initialiseValues: function () {
      const provVal = this.provinciaEl.value;
      if (provVal) this.onProvinciaChange();
      const prevC = (this.cantonSelect && this.cantonSelect.value) || (this.cantonFallback && this.cantonFallback.value) || '';
      const prevD = (this.distritoSelect && this.distritoSelect.value) || (this.distritoFallback && this.distritoFallback.value) || '';
      if (prevC && this.cantonSelect) {
        const opt = Array.from(this.cantonSelect.options).find(o => o.value === prevC);
        if (opt) { this.cantonSelect.value = prevC; this.onCantonChange(); }
        else this.showFallback(this.cantonSelect, this.cantonFallback, prevC);
      }
      if (prevD && this.distritoSelect) {
        const opt = Array.from(this.distritoSelect.options).find(o => o.value === prevD);
        if (opt) this.distritoSelect.value = prevD;
        else this.showFallback(this.distritoSelect, this.distritoFallback, prevD);
      }
    },

    onProvinciaChange: function () {
      const provValue = this.provinciaEl.value;
      const provName = (this.opts.provinceValueToNameMap && this.opts.provinceValueToNameMap[provValue]) ? this.opts.provinceValueToNameMap[provValue] : provValue;
      this.clearSelect(this.cantonSelect);
      this.clearSelect(this.distritoSelect);

      if (!this.geo || !provName || !this.geo[provName]) {
        this.showFallback(this.cantonSelect, this.cantonFallback);
        this.showFallback(this.distritoSelect, this.distritoFallback);
        return;
      }

      const cantonesObj = this.geo[provName];
      const cantonNames = Object.keys(cantonesObj).sort((a,b) => a.localeCompare(b, 'es'));
      this.populateSelect(this.cantonSelect, cantonNames, this.opts.placeholderCanton);
      this.hideFallback(this.cantonSelect, this.cantonFallback);
      this.cantonSelect.dispatchEvent(new Event('change'));
    },

    onCantonChange: function () {
      const provValue = this.provinciaEl.value;
      const provName = (this.opts.provinceValueToNameMap && this.opts.provinceValueToNameMap[provValue]) ? this.opts.provinceValueToNameMap[provValue] : provValue;
      const canton = this.cantonSelect.value;
      this.clearSelect(this.distritoSelect);

      if (!this.geo || !provName || !canton || !this.geo[provName] || !this.geo[provName][canton]) {
        this.showFallback(this.distritoSelect, this.distritoFallback);
        return;
      }

      const distritos = this.geo[provName][canton].slice().sort((a,b) => a.localeCompare(b, 'es'));
      this.populateSelect(this.distritoSelect, distritos, this.opts.placeholderDistrito);
      this.hideFallback(this.distritoSelect, this.distritoFallback);
    },

    populateSelect: function (sel, items, placeholder) {
      if (!sel) return;
      sel.innerHTML = '';
      const empty = document.createElement('option');
      empty.value = '';
      empty.text = placeholder || '';
      sel.appendChild(empty);
      items.forEach(i => {
        const opt = document.createElement('option');
        opt.value = i;
        opt.text = i;
        sel.appendChild(opt);
      });
    },

    clearSelect: function (sel) {
      if (!sel) return;
      sel.innerHTML = '';
      const empty = document.createElement('option');
      empty.value = '';
      empty.text = '';
      sel.appendChild(empty);
    },

    showFallback: function (selectEl, fallbackEl, fallbackValue) {
      if (selectEl) selectEl.style.display = 'none';
      if (fallbackEl) { fallbackEl.style.display = ''; if (typeof fallbackValue !== 'undefined') fallbackEl.value = fallbackValue; }
    },

    hideFallback: function (selectEl, fallbackEl) {
      if (selectEl) selectEl.style.display = '';
      if (fallbackEl) fallbackEl.style.display = 'none';
    }
  };

  window.CRGeo = CRGeo;
})(window, document);