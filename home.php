<?php // home.php — portada completa 

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>INS - Seguros para el Hogar</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    *{font-family:Inter,system-ui,Arial,sans-serif}
    .gradient-bg{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%)}
    .hero-overlay{background:linear-gradient(135deg,rgba(102,126,234,.9) 0%,rgba(118,75,162,.9) 100%)}
    .card-hover{transition:all .3s ease}
    .card-hover:hover{transform:translateY(-10px);box-shadow:0 20px 40px rgba(0,0,0,.1)}
    .animate-float{animation:float 3s ease-in-out infinite}
    @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-20px)}}
    .pulse-slow{animation:pulse 3s cubic-bezier(.4,0,.6,1) infinite}
  </style>
</head>
<body class="bg-gray-50">
  <!-- Nav -->
  <nav class="bg-white shadow-md fixed w-full top-0 z-50">
    <div class="container mx-auto px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
         
          <div>
            <h1 class="text-2xl font-bold text-gray-800">Agente Autorizado 110886</h1>
            <img src="/imagenes/INSJADE.png" alt="Agente 110886 Autorizado Instituto Nacional de Seguros" class="h-6 object-contain">
          </div>
        </div>
        <div class="hidden md:flex space-x-8">
          <a href="#inicio" class="text-gray-600 hover:text-purple-600 transition">Inicio</a>
          <a href="#seguros" class="text-gray-600 hover:text-purple-600 transition">Seguros</a>
          <a href="#beneficios" class="text-gray-600 hover:text-purple-600 transition">Beneficios</a>
          <a href="#contacto" class="text-gray-600 hover:text-purple-600 transition">Contacto</a>
          <a href="/client/login.php" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition font-semibold">
            <i class="fas fa-user mr-2"></i>Mi Cuenta
          </a>
          <a href="/admin/login.php" class="text-gray-600 hover:text-red-600 transition font-semibold">Administrador</a>
        </div>
        <button id="mobile-menu-btn" class="md:hidden text-gray-600">
          <i class="fas fa-bars text-2xl"></i>
        </button>
      </div>

      <!-- Mobile Menu -->
      <div id="mobile-menu" class="hidden md:hidden mt-4 pb-4 border-t border-gray-200">
        <div class="space-y-2 pt-4">
          <a href="#inicio" class="block px-4 py-2 text-gray-600 rounded hover:bg-purple-50 transition">
            <i class="fas fa-home mr-2"></i>Inicio
          </a>
          <a href="#seguros" class="block px-4 py-2 text-gray-600 rounded hover:bg-purple-50 transition">
            <i class="fas fa-shield-alt mr-2"></i>Seguros
          </a>
          <a href="#beneficios" class="block px-4 py-2 text-gray-600 rounded hover:bg-purple-50 transition">
            <i class="fas fa-star mr-2"></i>Beneficios
          </a>
          <a href="#contacto" class="block px-4 py-2 text-gray-600 rounded hover:bg-purple-50 transition">
            <i class="fas fa-envelope mr-2"></i>Contacto
          </a>
          <hr class="my-2">
          <a href="/client/login.php" class="block px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition text-center font-semibold">
            <i class="fas fa-user mr-2"></i>Mi Cuenta
          </a>
          <a href="/admin/login.php" class="block px-4 py-2 text-gray-600 rounded hover:bg-gray-100 transition text-center">
            Administrador
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Hero -->
  <section id="inicio" class="relative min-h-screen flex items-center justify-center overflow-hidden mt-16">
    <div class="absolute inset-0 hero-overlay z-0"></div>
    <div class="container mx-auto px-6 relative z-10 text-center text-white">
      <div class="animate-float"><i class="fas fa-home text-8xl mb-6 opacity-90"></i></div>
      <h1 class="text-5xl md:text-7xl font-bold mb-6 leading-tight">
        Protege tu <span class="text-yellow-300">Hogar</span>
      </h1>
      <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto opacity-90">
        Seguros flexibles y modernos para tu casa, contenido y familia. Cotiza en minutos y obtén protección inmediata.
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="/hogar-comprensivo.php" class="bg-white text-purple-600 px-8 py-4 rounded-full font-bold text-lg hover:bg-yellow-300 hover:text-purple-700 transition transform hover:scale-105 shadow-2xl">
          <i class="fas fa-rocket mr-2"></i>Cotizar Ahora
        </a>
        <a href="#beneficios" class="bg-transparent border-2 border-white text-white px-8 py-4 rounded-full font-bold text-lg hover:bg-white hover:text-purple-600 transition">
          <i class="fas fa-info-circle mr-2"></i>Conocer Más
        </a>
      </div>

      <div class="grid grid-cols-3 gap-8 mt-16 max-w-3xl mx-auto">
        <div class="pulse-slow"><div class="text-4xl font-bold text-yellow-300">50K+</div><div class="text-sm opacity-80">Hogares Protegidos</div></div>
        <div class="pulse-slow" style="animation-delay:.5s"><div class="text-4xl font-bold text-yellow-300">98%</div><div class="text-sm opacity-80">Satisfacción</div></div>
        <div class="pulse-slow" style="animation-delay:1s"><div class="text-4xl font-bold text-yellow-300">24/7</div><div class="text-sm opacity-80">Atención</div></div>
      </div>
    </div>
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-10 animate-bounce"><i class="fas fa-chevron-down text-white text-2xl opacity-70"></i></div>
  </section>

  <!-- Tipos de Seguros -->
  <section id="seguros" class="py-20 bg-white">
    <div class="container mx-auto px-6">
      <div class="text-center mb-16">
        <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">
          Nuestros <span class="text-purple-600">Seguros</span>
        </h2>
        <p class="text-gray-600 text-lg max-w-2xl mx-auto">
          Soluciones de protección diseñadas para cada necesidad
        </p>
      </div>

      <div class="grid md:grid-cols-3 gap-8">
        <!-- Hogar -->
        <div class="card-hover bg-white border-2 border-gray-100 rounded-2xl p-8 text-center">
          <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-home text-purple-600 text-3xl"></i>
          </div>
          <h3 class="text-2xl font-bold text-gray-800 mb-4">Seguros para el Hogar</h3>
          <p class="text-gray-600 mb-6">
            Protege tu vivienda, contenido y familia contra incendios, robo, desastres naturales y más.
          </p>
          <ul class="text-left space-y-3 mb-8">
            <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i><span class="text-gray-700">Hogar Comprensivo</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i><span class="text-gray-700">Protección de Contenidos</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i><span class="text-gray-700">Responsabilidad Civil</span></li>
          </ul>
          <a href="/hogar-comprensivo.php" class="bg-purple-600 text-white px-6 py-3 rounded-full font-semibold hover:bg-purple-700 transition inline-block">
            Cotizar Ahora <i class="fas fa-arrow-right ml-2"></i>
          </a>
        </div>

        <!-- Autos -->
        <div class="card-hover bg-gray-50 border-2 border-gray-200 rounded-2xl p-8 text-center opacity-60">
          <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-car text-blue-600 text-3xl"></i>
          </div>
          <h3 class="text-2xl font-bold text-gray-800 mb-4">Seguros de Autos</h3>
          <p class="text-gray-600 mb-6">Cobertura completa para tu vehículo con asistencia vial 24/7.</p>
          <div class="bg-yellow-100 border border-yellow-300 rounded-lg p-3 mb-4">
            <p class="text-yellow-800 font-semibold text-sm"><i class="fas fa-clock mr-2"></i>Próximamente</p>
          </div>
        </div>

        <!-- Riesgos del Trabajo -->
        <div class="card-hover bg-gray-50 border-2 border-gray-200 rounded-2xl p-8 text-center opacity-60">
          <div class="w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-hard-hat text-orange-600 text-3xl"></i>
          </div>
          <h3 class="text-2xl font-bold text-gray-800 mb-4">Riesgos del Trabajo</h3>
          <p class="text-gray-600 mb-6">Protección para empleados domésticos y trabajadores del hogar.</p>
          <div class="bg-yellow-100 border border-yellow-300 rounded-lg p-3 mb-4">
            <p class="text-yellow-800 font-semibold text-sm"><i class="fas fa-clock mr-2"></i>Próximamente</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Beneficios -->
  <section id="beneficios" class="py-20 bg-gradient-to-br from-purple-50 to-blue-50">
    <div class="container mx-auto px-6">
      <div class="text-center mb-16">
        <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">
          ¿Por qué elegir <span class="text-purple-600">INS</span>?
        </h2>
        <p class="text-gray-600 text-lg max-w-2xl mx-auto">La confianza de miles de costarricenses nos respalda</p>
      </div>

      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
        <div class="bg-white rounded-xl p-6 shadow-lg text-center">
          <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fas fa-bolt text-green-600 text-2xl"></i></div>
          <h3 class="text-xl font-bold text-gray-800 mb-2">Cotización Rápida</h3>
          <p class="text-gray-600 text-sm">En menos de 5 minutos obtienes tu cotización personalizada</p>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-lg text-center">
          <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fas fa-mobile-alt text-blue-600 text-2xl"></i></div>
          <h3 class="text-xl font-bold text-gray-800 mb-2">100% Digital</h3>
          <p class="text-gray-600 text-sm">Proceso completamente en línea, sin papeleos ni esperas</p>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-lg text-center">
          <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fas fa-headset text-purple-600 text-2xl"></i></div>
          <h3 class="text-xl font-bold text-gray-800 mb-2">Soporte 24/7</h3>
          <p class="text-gray-600 text-sm">Asistencia disponible en cualquier momento que lo necesites</p>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-lg text-center">
          <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fas fa-shield-alt text-yellow-600 text-2xl"></i></div>
          <h3 class="text-xl font-bold text-gray-800 mb-2">Respaldo INS</h3>
          <p class="text-gray-600 text-sm">La confianza y experiencia de más de 100 años</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Final -->
  <section class="py-20 gradient-bg text-white">
    <div class="container mx-auto px-6 text-center">
      <h2 class="text-4xl md:text-5xl font-bold mb-6">¿Listo para proteger tu hogar?</h2>
      <p class="text-xl mb-8 max-w-2xl mx-auto opacity-90">
        Completa nuestro formulario y recibe tu cotización personalizada en minutos
      </p>
      <a href="/hogar-comprensivo.php" class="bg-white text-purple-600 px-10 py-5 rounded-full font-bold text-xl hover:bg-yellow-300 hover:text-purple-700 transition transform hover:scale-105 shadow-2xl inline-block">
        <i class="fas fa-file-alt mr-2"></i>Iniciar Solicitud
      </a>
    </div>
  </section>

  <!-- Footer -->
  <footer id="contacto" class="bg-gray-900 text-white py-12">
    <div class="container mx-auto px-6">
      <div class="grid md:grid-cols-4 gap-8 mb-8">
        <div>
          <h3 class="text-xl font-bold mb-4">INS</h3>
          <p class="text-gray-400 text-sm">Protegiendo a las familias costarricenses desde hace más de 100 años.</p>
        </div>
        <div>
          <h4 class="font-bold mb-4">Seguros</h4>
          <ul class="space-y-2 text-gray-400 text-sm">
            <li><a href="/hogar-comprensivo.php" class="hover:text-white">Hogar</a></li>
            <li><span class="opacity-70 cursor-not-allowed">Autos (Próximamente)</span></li>
            <li><span class="opacity-70 cursor-not-allowed">Riesgos de Trabajo (Próximamente)</span></li>
          </ul>
        </div>
        <div>
          <h4 class="font-bold mb-4">Contacto</h4>
          <ul class="space-y-2 text-gray-400 text-sm">
            <li><i class="fas fa-phone mr-2"></i>8890-2814</li>
            <li><i class="fas fa-envelope mr-2"></i>info@aseguralocr.com</li>
          </ul>
        </div>
        <div>
          <h4 class="font-bold mb-4">Síguenos</h4>
          <div class="flex space-x-4">
            <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-purple-600 transition" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-purple-600 transition" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-purple-600 transition" aria-label="X (Twitter)"><i class="fab fa-twitter"></i></a>
          </div>
        </div>
      </div>
      <div class="border-t border-gray-800 pt-8 text-center text-gray-400 text-sm">
        <p>&copy; 2025 - AGENTE INS AUTORIZADO: 110886. Todos los derechos reservados.</p>
        <p class="mt-2">www.aseguralocr.com</p>
      </div>
    </div>
  </footer>

  <script>
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(function(a){
      a.addEventListener('click', function(e){
        e.preventDefault();
        var t = document.querySelector(this.getAttribute('href'));
        if (t) t.scrollIntoView({behavior:'smooth', block:'start'});
        // Close mobile menu after clicking a link
        document.getElementById('mobile-menu')?.classList.add('hidden');
      });
    });

    // Mobile menu toggle
    document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
      const menu = document.getElementById('mobile-menu');
      menu.classList.toggle('hidden');
    });
  </script>
</body>
</html>