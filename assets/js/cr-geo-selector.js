/**
 * Costa Rica Geographic Selector
 * Cascading dropdowns for Provincia -> Canton -> Distrito
 */
(function() {
    let geoData = null;

    // Load the JSON data
    async function loadGeoData() {
        if (geoData) return geoData;
        try {
            const response = await fetch('/assets/data/cr_geo.json');
            geoData = await response.json();
            return geoData;
        } catch (error) {
            console.error('Error loading geo data:', error);
            return null;
        }
    }

    // Initialize a group of selects (provincia, canton, distrito)
    function initGeoGroup(provinciaSelect, cantonSelect, distritoSelect) {
        if (!provinciaSelect || !cantonSelect || !distritoSelect) return;

        // Populate provincias
        populateProvincias(provinciaSelect);

        // Event listeners
        provinciaSelect.addEventListener('change', function() {
            populateCantones(cantonSelect, this.value);
            distritoSelect.innerHTML = '<option value="">-- Seleccione Distrito --</option>';
        });

        cantonSelect.addEventListener('change', function() {
            populateDistritos(distritoSelect, provinciaSelect.value, this.value);
        });
    }

    // Populate provincias dropdown
    function populateProvincias(select) {
        if (!geoData || !geoData.provincias) return;

        select.innerHTML = '<option value="">-- Seleccione Provincia --</option>';

        for (const [codigo, provincia] of Object.entries(geoData.provincias)) {
            const option = document.createElement('option');
            option.value = provincia.nombre;
            option.textContent = provincia.nombre;
            option.dataset.codigo = codigo;
            select.appendChild(option);
        }
    }

    // Populate cantones dropdown based on selected provincia
    function populateCantones(select, provinciaNombre) {
        select.innerHTML = '<option value="">-- Seleccione Cantón --</option>';

        if (!provinciaNombre || !geoData || !geoData.provincias) return;

        // Find provincia by name
        const provincia = Object.values(geoData.provincias).find(p => p.nombre === provinciaNombre);
        if (!provincia || !provincia.cantones) return;

        for (const [codigo, canton] of Object.entries(provincia.cantones)) {
            const option = document.createElement('option');
            option.value = canton.nombre;
            option.textContent = canton.nombre;
            option.dataset.codigo = codigo;
            select.appendChild(option);
        }
    }

    // Populate distritos dropdown based on selected provincia and canton
    function populateDistritos(select, provinciaNombre, cantonNombre) {
        select.innerHTML = '<option value="">-- Seleccione Distrito --</option>';

        if (!provinciaNombre || !cantonNombre || !geoData || !geoData.provincias) return;

        // Find provincia by name
        const provincia = Object.values(geoData.provincias).find(p => p.nombre === provinciaNombre);
        if (!provincia || !provincia.cantones) return;

        // Find canton by name
        const canton = Object.values(provincia.cantones).find(c => c.nombre === cantonNombre);
        if (!canton || !canton.distritos) return;

        for (const [codigo, distritoNombre] of Object.entries(canton.distritos)) {
            const option = document.createElement('option');
            option.value = distritoNombre;
            option.textContent = distritoNombre;
            option.dataset.codigo = codigo;
            select.appendChild(option);
        }
    }

    // Auto-initialize all geo groups on page load
    async function initAllGeoGroups() {
        await loadGeoData();

        // Find all geo groups by data attribute
        document.querySelectorAll('[data-geo-group]').forEach(provinciaSelect => {
            const group = provinciaSelect.dataset.geoGroup;
            const cantonSelect = document.querySelector(`[data-geo-canton="${group}"]`);
            const distritoSelect = document.querySelector(`[data-geo-distrito="${group}"]`);

            if (cantonSelect && distritoSelect) {
                initGeoGroup(provinciaSelect, cantonSelect, distritoSelect);

                // Restore values if they exist (e.g., from session)
                const savedProvincia = provinciaSelect.dataset.savedValue;
                const savedCanton = cantonSelect.dataset.savedValue;
                const savedDistrito = distritoSelect.dataset.savedValue;

                if (savedProvincia) {
                    provinciaSelect.value = savedProvincia;
                    provinciaSelect.dispatchEvent(new Event('change'));

                    if (savedCanton) {
                        setTimeout(() => {
                            cantonSelect.value = savedCanton;
                            cantonSelect.dispatchEvent(new Event('change'));

                            if (savedDistrito) {
                                setTimeout(() => {
                                    distritoSelect.value = savedDistrito;
                                }, 50);
                            }
                        }, 50);
                    }
                }
            }
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllGeoGroups);
    } else {
        initAllGeoGroups();
    }

    // Export for manual initialization if needed
    window.CRGeoSelector = {
        init: initAllGeoGroups,
        initGroup: initGeoGroup,
        loadData: loadGeoData
    };
})();
