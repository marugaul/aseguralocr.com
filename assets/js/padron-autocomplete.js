/**
 * Autocomplete de datos desde Padrón Electoral TSE
 *
 * Uso:
 * 1. Agregar atributo data-padron-cedula al input de cédula
 * 2. Agregar atributos data-padron-nombre, data-padron-apellido1, etc. a los campos destino
 *
 * Ejemplo:
 * <input type="text" name="tomador_num_id" data-padron-cedula="tomador">
 * <input type="text" name="tomador_nombre" data-padron-nombre="tomador">
 * <input type="text" name="tomador_apellido1" data-padron-apellido1="tomador">
 */
(function() {
    const API_URL = '/api/padron.php';
    const MIN_CEDULA_LENGTH = 9;
    const DEBOUNCE_MS = 500;

    // Debounce helper
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Buscar persona por cédula
    async function buscarCedula(cedula) {
        try {
            const response = await fetch(`${API_URL}?cedula=${encodeURIComponent(cedula)}`);
            if (!response.ok) throw new Error('Error en la consulta');
            return await response.json();
        } catch (error) {
            console.error('Error consultando padrón:', error);
            return null;
        }
    }

    // Autocompletar campos del grupo
    function autocompletarCampos(grupo, data) {
        if (!data || !data.encontrado) return;

        const mappings = {
            'nombre': data.nombre,
            'apellido1': data.apellido1,
            'apellido2': data.apellido2,
            'nombre-completo': data.nombre_completo,
            'provincia': data.provincia,
            'canton': data.canton,
            'distrito': data.distrito
        };

        for (const [campo, valor] of Object.entries(mappings)) {
            if (!valor) continue;

            // Buscar por data attribute
            const input = document.querySelector(`[data-padron-${campo}="${grupo}"]`);
            if (input) {
                if (input.tagName === 'SELECT') {
                    // Para selects, buscar la opción que coincida
                    const option = Array.from(input.options).find(opt =>
                        opt.value.toLowerCase() === valor.toLowerCase() ||
                        opt.textContent.toLowerCase() === valor.toLowerCase()
                    );
                    if (option) {
                        input.value = option.value;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                } else {
                    input.value = valor;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
        }

        // Mostrar indicador de éxito
        mostrarNotificacion(`Datos encontrados: ${data.nombre_completo}`, 'success');
    }

    // Mostrar notificación temporal
    function mostrarNotificacion(mensaje, tipo = 'info') {
        // Remover notificación anterior si existe
        const prev = document.getElementById('padron-notification');
        if (prev) prev.remove();

        const div = document.createElement('div');
        div.id = 'padron-notification';
        div.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 transition-opacity duration-300 ${
            tipo === 'success' ? 'bg-green-500 text-white' :
            tipo === 'error' ? 'bg-red-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        div.innerHTML = `<i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>${mensaje}`;

        document.body.appendChild(div);

        setTimeout(() => {
            div.style.opacity = '0';
            setTimeout(() => div.remove(), 300);
        }, 3000);
    }

    // Manejar input de cédula
    const handleCedulaInput = debounce(async function(e) {
        const input = e.target;
        const grupo = input.dataset.padronCedula;
        let cedula = input.value.replace(/[^0-9]/g, '');

        // Indicador de carga
        input.style.backgroundImage = 'url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%236366f1\' stroke-width=\'2\'%3E%3Ccircle cx=\'12\' cy=\'12\' r=\'10\' stroke-dasharray=\'60\' stroke-dashoffset=\'0\'%3E%3CanimateTransform attributeName=\'transform\' type=\'rotate\' from=\'0 12 12\' to=\'360 12 12\' dur=\'1s\' repeatCount=\'indefinite\'/%3E%3C/circle%3E%3C/svg%3E")';
        input.style.backgroundRepeat = 'no-repeat';
        input.style.backgroundPosition = 'right 10px center';
        input.style.backgroundSize = '20px';

        if (cedula.length >= MIN_CEDULA_LENGTH) {
            const data = await buscarCedula(cedula);

            // Remover indicador de carga
            input.style.backgroundImage = '';

            if (data && data.encontrado) {
                autocompletarCampos(grupo, data);
                input.classList.remove('border-red-400');
                input.classList.add('border-green-400');
            } else if (data && !data.encontrado) {
                input.classList.remove('border-green-400');
                mostrarNotificacion('Cédula no encontrada en el padrón', 'error');
            }
        } else {
            input.style.backgroundImage = '';
            input.classList.remove('border-green-400', 'border-red-400');
        }
    }, DEBOUNCE_MS);

    // Inicializar cuando el DOM esté listo
    function init() {
        // Buscar todos los inputs con data-padron-cedula
        document.querySelectorAll('[data-padron-cedula]').forEach(input => {
            input.addEventListener('input', handleCedulaInput);
            input.addEventListener('blur', handleCedulaInput);

            // Placeholder informativo
            if (!input.placeholder) {
                input.placeholder = 'Ingrese cédula para autocompletar';
            }
        });
    }

    // Inicializar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Exportar para uso manual
    window.PadronAutocomplete = {
        buscar: buscarCedula,
        autocompletar: autocompletarCampos,
        init: init
    };
})();
