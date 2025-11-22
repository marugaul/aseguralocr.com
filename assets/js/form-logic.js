/* ==== Paso y validaciones robustas (sin errores) ==== */

// Config básica
const STORAGE_KEY = 'hogar_form';
let currentStep = 1;
let totalSteps = 7;    // se sincroniza al cargar
const formData = {};
let lastSubmit = 0;

// Helpers UI
function qs(sel, ctx=document){return ctx.querySelector(sel)}
function qsa(sel, ctx=document){return [...ctx.querySelectorAll(sel)]}

// Construye indicadores de pasos dinámicamente
function buildStepIndicators(){
  const container = qs('#step-indicators');
  container.innerHTML = '';
  for(let i=1;i<=totalSteps;i++){
    const wrap = document.createElement('div');
    wrap.className = 'flex-1 flex items-center';

    const circle = document.createElement('div');
    // usar currentStep para marcar activo si ya hay progreso guardado
    circle.className = `step-indicator w-10 h-10 rounded-full flex items-center justify-center text-white font-bold ${i===currentStep?'active':'bg-gray-300'}`;
    circle.dataset.step = i;
    circle.textContent = i;
    wrap.appendChild(circle);

    if(i<totalSteps){
      const line = document.createElement('div');
      line.className = 'flex-1 h-1 bg-gray-300 mx-2';
      wrap.appendChild(line);
    }
    container.appendChild(wrap);
  }
}

// Actualiza progreso
function updateProgress(){
  const pct = ((currentStep-1)/(totalSteps-1))*100;
  qs('#progress-bar').style.width = `${pct}%`;
  qs('#current-step-text').textContent = `Paso ${currentStep} de ${totalSteps}`;
  qsa('.step-indicator').forEach((el, idx)=>{
    const step = idx+1;
    el.classList.remove('active','completed','bg-gray-300');
    if(step<currentStep) el.classList.add('completed');
    else if(step===currentStep) el.classList.add('active');
    else el.classList.add('bg-gray-300');
  });
  qs('#btn-prev').style.display = (currentStep===1)?'none':'inline-flex';
  qs('#btn-next').style.display = (currentStep===totalSteps)?'none':'inline-flex';
  qs('#btn-submit').style.display = (currentStep===totalSteps)?'inline-flex':'none';
}

// Muestra sección
function showStep(n){
  qsa('.form-section').forEach(s=>s.classList.remove('active'));
  const cur = qs(`.form-section[data-step="${n}"]`);
  if(cur){ cur.classList.add('active'); window.scrollTo({top:0,behavior:'smooth'}); }
  updateProgress();
}

// Valida campos requeridos del paso actual
function validateCurrent(){
  const cur = qs(`.form-section[data-step="${currentStep}"]`);
  const required = qsa('[required]', cur);
  let ok = true;

  required.forEach(f=>{
    if(f.type==='radio'){
      const group = qsa(`input[name="${f.name}"]`, cur);
      const checked = group.some(r=>r.checked);
      if(!checked){ ok=false; group.forEach(r=>r.closest('.grid, .flex')?.classList.add('border-2','border-red-400','rounded')); }
      else group.forEach(r=>r.closest('.grid, .flex')?.classList.remove('border-red-400'));
    }else if(!f.checkValidity()){
      ok=false; f.classList.add('border-red-400'); f.classList.remove('border-gray-200');
    }else{
      f.classList.remove('border-red-400'); f.classList.add('border-gray-200');
    }
  });
  return ok;
}

// Guarda datos del paso (robusto)
function saveStep(){
  const cur = qs(`.form-section[data-step="${currentStep}"]`);
  qsa('input,select,textarea', cur).forEach(i=>{
    const name = i.name;
    if(!name) return; // ignorar campos sin name

    if(i.type === 'checkbox'){
      // Asegurar que formData[name] sea un array
      if(!Array.isArray(formData[name])){
        if(formData[name] != null && formData[name] !== '') {
          // convertir valor escalar previo en array
          formData[name] = Array.isArray(formData[name]) ? formData[name] : [String(formData[name])];
        } else {
          formData[name] = [];
        }
      }

      if(i.checked){
        if(!formData[name].includes(i.value)) formData[name].push(i.value);
      } else {
        if(Array.isArray(formData[name])){
          formData[name] = formData[name].filter(v => v !== i.value);
          // opcional: mantener la clave con array vacío o eliminarla
          if(formData[name].length === 0) delete formData[name];
        }
      }

    } else if(i.type === 'radio'){
      if(i.checked) formData[name] = i.value;
    } else {
      formData[name] = i.value;
    }
  });

  localStorage.setItem(STORAGE_KEY, JSON.stringify({step: currentStep, data: formData}));
}

// Restaura si hay storage (ligeramente más tolerante)
// Restaura si hay storage (ligeramente más tolerante)
// Restaura si hay storage (forzando provincia en blanco)
function restore(){
  const raw = localStorage.getItem(STORAGE_KEY);
  if(!raw) return;
  try{
    const st = JSON.parse(raw);

    // Evitar restaurar provincia(s) aunque existan en cache:
    if(st.data){
      // Borra las claves de provincia para forzar selección manual
      delete st.data.provincia;
      delete st.data.provinciaProp;
    }

    Object.assign(formData, st.data || {});

    qsa('input,select,textarea').forEach(i=>{
      const val = formData[i.name];
      // Si es select/provincia forzado a vacío o no existe valor restaurado, déjalo en blanco
      if(i.name === 'provincia' || i.name === 'provinciaProp'){
        i.value = ''; // siempre forzamos a vacío
        return;
      }
      if(val == null) return;
      if(i.type === 'checkbox'){
        if(Array.isArray(val)) i.checked = val.includes(i.value);
      } else if(i.type === 'radio'){
        i.checked = (i.value === val);
      } else {
        i.value = val;
      }
    });

    // Además limpiar selects de cantón/distrito para evitar valores huérfanos
    const safeClear = id => {
      const el = document.getElementById(id);
      if(el){
        el.value = '';
        try { delete el.dataset.restoreValue; } catch(e){}
        // también vaciar opciones si procede (si prefieres mantener opciones, coméntalo)
        // el.innerHTML = '<option value="">Seleccione...</option>';
      }
    };
    safeClear('canton');
    safeClear('distrito');
    safeClear('cantonProp');
    safeClear('distritoProp');

    currentStep = Number(st.step) || 1;
  } catch(err){
    console.warn('restore: error parsing storage', err);
  }
}

// Resumen simple (puedes expandirlo)
function generateSummary(){
  const cont = qs('#resumen-contenido');
  if(!cont) return;
  const rows = Object.entries(formData).map(([k,v])=>{
    const val = Array.isArray(v)? v.join(', '): (v??'');
    return `<div class="flex justify-between py-2 border-b border-gray-200"><span class="text-gray-600">${k}</span><span class="font-medium">${val||'-'}</span></div>`;
  }).join('');
  cont.innerHTML = `<div class="bg-white border-2 border-gray-200 rounded-lg p-4">${rows||'<em>Sin datos</em>'}</div>`;
}

// Eventos condicionales (actividad/tapias/propiedad sola)
function setupConditionals(){
  qsa('select[name="actividad"]').forEach(sel=>{
    sel.addEventListener('change', ()=>{
      const box = qs('#porcentajes-actividad');
      box.style.display = (sel.value==='casa-oficina'||sel.value==='casa-comercio'||sel.value==='otro')?'block':'none';
    });
  });
  qsa('input[name="tapias"]').forEach(r=>{
    r.addEventListener('change', ()=>{ qs('#detalles-tapias').style.display = (r.value==='si'&&r.checked)?'block':'none'; });
  });
  qsa('input[name="propiedadSola"]').forEach(r=>{
    r.addEventListener('change', ()=>{ qs('#horas-sola').style.display = (r.value==='si'&&r.checked)?'block':'none'; });
  });
}

// Navegación
function nextStep(){
  if(!validateCurrent()) return;
  saveStep();
  if(currentStep<totalSteps){ currentStep++; showStep(currentStep); if(currentStep===totalSteps) generateSummary(); }
}
function prevStep(){
  if(currentStep>1){ currentStep--; showStep(currentStep); }
}

// Envío con pequeñas salvaguardas
function onSubmit(e){
  e.preventDefault();
  const now = Date.now();
  if(now-lastSubmit<2500) return; // rate limit simple
  lastSubmit = now;

  if(!validateCurrent()) return;
  saveStep();

  // deshabilitar botón
  const btn = qs('#btn-submit');
  btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enviando...';

  // Envío real del form
  e.target.submit();
}

document.addEventListener('DOMContentLoaded', ()=>{
  // Contar pasos reales del DOM por si cambia el total
  totalSteps = qsa('.form-section').length || 7;

  buildStepIndicators();
  restore();
  showStep(currentStep);
  setupConditionals();

  qs('#btn-next')?.addEventListener('click', nextStep);
  qs('#btn-prev')?.addEventListener('click', prevStep);
  qs('#insurance-form')?.addEventListener('submit', onSubmit);

  // Autoguardado suave
  let t;
  qs('#insurance-form')?.addEventListener('input', ()=>{ clearTimeout(t); t=setTimeout(saveStep, 400); });
  qs('#insurance-form')?.addEventListener('change', saveStep);
});