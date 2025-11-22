// download-pdf.js - intercepta submit del form y muestra modal de agradecimiento
document.addEventListener('DOMContentLoaded', function() {
  const FORM_SELECTOR = '#mainForm';
  const BTN_SELECTOR  = '#btnDownload';

  const form = document.querySelector(FORM_SELECTOR);
  const btn  = document.querySelector(BTN_SELECTOR);

  if (!form) {
    console.warn('download-pdf.js: form no encontrado:', FORM_SELECTOR);
    return;
  }

  // Helper para crear modal de agradecimiento
  function showThankYouModal({referencia_submission, referencia_cotizacion, pdf_url, email_enviado}) {
    const overlay = document.createElement('div');
    overlay.style.cssText = `
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.6);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 99999;
      animation: fadeIn 0.3s ease-in-out;
    `;

    const modal = document.createElement('div');
    modal.style.cssText = `
      width: min(500px, 92%);
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      padding: 32px 28px;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
      color: #1a1a1a;
      animation: slideIn 0.3s ease-out;
    `;

    // Icono de éxito
    const iconCheck = document.createElement('div');
    iconCheck.style.cssText = `
      width: 64px;
      height: 64px;
      background: #10b981;
      border-radius: 50%;
      margin: 0 auto 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 36px;
      color: white;
    `;
    iconCheck.innerHTML = '✓';

    const header = document.createElement('h2');
    header.textContent = '¡Solicitud Recibida!';
    header.style.cssText = `
      margin: 0 0 12px 0;
      font-size: 24px;
      font-weight: 700;
      text-align: center;
      color: #111827;
    `;

    const message = document.createElement('p');
    message.innerHTML = 'Su solicitud ha sido procesada exitosamente. Nuestro equipo revisará su información y se pondrá en contacto con usted pronto.';
    message.style.cssText = `
      margin: 0 0 24px 0;
      font-size: 15px;
      line-height: 1.6;
      text-align: center;
      color: #4b5563;
    `;

    const infoBox = document.createElement('div');
    infoBox.style.cssText = `
      background: #f3f4f6;
      border-radius: 8px;
      padding: 16px;
      margin-bottom: 24px;
      border-left: 4px solid #3b82f6;
    `;

    let infoHTML = '';
    if (referencia_submission) {
      infoHTML += `
        <div style="margin-bottom: 12px;">
          <div style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
            Referencia de Envío
          </div>
          <div style="font-size: 15px; font-weight: 600; color: #1f2937; font-family: monospace;">
            ${escapeHtml(referencia_submission)}
          </div>
        </div>
      `;
    }
    if (referencia_cotizacion) {
      infoHTML += `
        <div>
          <div style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
            Código de Cotización
          </div>
          <div style="font-size: 15px; font-weight: 600; color: #1f2937; font-family: monospace;">
            ${escapeHtml(referencia_cotizacion)}
          </div>
        </div>
      `;
    }
    infoBox.innerHTML = infoHTML;

    const emailNote = document.createElement('p');
    emailNote.style.cssText = `
      font-size: 14px;
      color: ${email_enviado ? '#059669' : '#dc2626'};
      text-align: center;
      margin: 0 0 20px 0;
      padding: 10px;
      background: ${email_enviado ? '#d1fae5' : '#fee2e2'};
      border-radius: 6px;
      font-weight: 500;
    `;
    emailNote.textContent = email_enviado 
      ? '✓ Se ha enviado un correo de confirmación'
      : '⚠ No se pudo enviar el correo de confirmación en este momento';

    const actions = document.createElement('div');
    actions.style.cssText = `
      display: flex;
      gap: 12px;
      justify-content: center;
      flex-wrap: wrap;
    `;

    if (pdf_url) {
      const dlBtn = document.createElement('a');
      dlBtn.href = pdf_url;
      dlBtn.target = '_blank';
      dlBtn.rel = 'noopener noreferrer';
      dlBtn.innerHTML = '📄 Ver PDF';
      dlBtn.style.cssText = `
        display: inline-block;
        padding: 12px 24px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 15px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
      `;
      dlBtn.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-2px)';
        this.style.boxShadow = '0 4px 12px rgba(59, 130, 246, 0.4)';
      });
      dlBtn.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = '0 2px 8px rgba(59, 130, 246, 0.3)';
      });
      actions.appendChild(dlBtn);
    }

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.textContent = 'Cerrar';
    closeBtn.style.cssText = `
      padding: 12px 24px;
      background: #f3f4f6;
      color: #374151;
      border-radius: 8px;
      border: 2px solid #e5e7eb;
      cursor: pointer;
      font-weight: 600;
      font-size: 15px;
      transition: all 0.2s;
    `;
    closeBtn.addEventListener('mouseenter', function() {
      this.style.background = '#e5e7eb';
      this.style.borderColor = '#d1d5db';
    });
    closeBtn.addEventListener('mouseleave', function() {
      this.style.background = '#f3f4f6';
      this.style.borderColor = '#e5e7eb';
    });
    closeBtn.addEventListener('click', () => {
      overlay.style.animation = 'fadeOut 0.2s ease-in-out';
      setTimeout(() => {
        if (document.body.contains(overlay)) document.body.removeChild(overlay);
      }, 200);
    });

    actions.appendChild(closeBtn);

    modal.appendChild(iconCheck);
    modal.appendChild(header);
    modal.appendChild(message);
    modal.appendChild(infoBox);
    modal.appendChild(emailNote);
    modal.appendChild(actions);
    overlay.appendChild(modal);

    // Agregar animaciones CSS
    const style = document.createElement('style');
    style.textContent = `
      @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
      }
      @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
      }
      @keyframes slideIn {
        from { 
          opacity: 0;
          transform: translateY(-30px) scale(0.95);
        }
        to { 
          opacity: 1;
          transform: translateY(0) scale(1);
        }
      }
    `;
    document.head.appendChild(style);
    document.body.appendChild(overlay);

    // Cerrar con tecla Escape
    function onKey(e) {
      if (e.key === 'Escape') {
        overlay.style.animation = 'fadeOut 0.2s ease-in-out';
        setTimeout(() => {
          if (document.body.contains(overlay)) document.body.removeChild(overlay);
        }, 200);
        document.removeEventListener('keydown', onKey);
      }
    }
    document.addEventListener('keydown', onKey);

    // Cerrar al hacer clic en el fondo oscuro
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) {
        overlay.style.animation = 'fadeOut 0.2s ease-in-out';
        setTimeout(() => {
          if (document.body.contains(overlay)) document.body.removeChild(overlay);
        }, 200);
      }
    });
  }

  function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  // Función que hace el envío AJAX
  async function submitFormViaAjax(eventSource) {
    let submitBtn = btn || document.querySelector('button[type="submit"]');

    if (submitBtn) {
      submitBtn.disabled = true;
      var originalText = submitBtn.textContent || 'Enviando...';
      submitBtn.textContent = '⏳ Procesando...';
      submitBtn.style.opacity = '0.7';
      submitBtn.style.cursor = 'wait';
    }

    try {
      const formData = new FormData(form);
      const res = await fetch('/enviarformularios/hogar_procesar.php', {
        method: 'POST',
        body: formData
      });

      let json;
      try { 
        json = await res.json(); 
      } catch (err) {
        throw new Error('Respuesta inválida del servidor');
      }

      if (!json || !json.success) {
        const msg = json?.message || 'Error al procesar la solicitud. Por favor intente de nuevo.';
        alert('❌ ' + msg);
        console.error('Error en hogar_procesar:', json);
        return;
      }

      // Mostrar modal de agradecimiento con animación
      showThankYouModal({
        referencia_submission: json.referencia_submission || null,
        referencia_cotizacion: json.referencia_cotizacion || null,
        pdf_url: json.pdf_url || null,
        email_enviado: !!json.email_enviado
      });

      // Opcional: resetear el formulario después de mostrar el modal
      setTimeout(() => {
        form.reset();
      }, 500);

    } catch (err) {
      console.error('Error en submitFormViaAjax:', err);
      alert('❌ Error al procesar la solicitud. Por favor intente nuevamente o contacte a soporte.');
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
      }
    }
  }

  // Interceptar submit del form
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();
    submitFormViaAjax(e);
  });

  // Si hay botón específico, agregar listener
  if (btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      submitFormViaAjax(e);
    });
  }
});