'use client'

import { useState, useEffect } from 'react'
import Link from 'next/link'

export default function Home() {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false)
  const [stats, setStats] = useState({
    homes: 50000,
    satisfaction: 98,
  })
  const [loading, setLoading] = useState(true)

  // Fetch datos desde Express API
  useEffect(() => {
    const fetchStats = async () => {
      try {
        const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:3001'
        const response = await fetch(`${API_URL}/api/stats`)
        const result = await response.json()

        if (result.success) {
          setStats(result.data)
        }
      } catch (error) {
        console.error('Error fetching stats:', error)
        // Keep default values on error
      } finally {
        setLoading(false)
      }
    }

    fetchStats()
  }, [])

  return (
    <div className="bg-gray-50">
      {/* Navigation */}
      <nav className="bg-white shadow-md fixed w-full top-0 z-50">
        <div className="container mx-auto px-6 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-3">
              <div>
                <h1 className="text-2xl font-bold text-gray-800">
                  Agente Autorizado 110886
                </h1>
                <img
                  src="/imagenes/INSJADE.png"
                  alt="Agente 110886 Autorizado INS"
                  className="h-6 object-contain"
                />
              </div>
            </div>

            {/* Desktop Menu */}
            <div className="hidden md:flex space-x-8">
              <a href="#inicio" className="text-gray-600 hover:text-purple-600 transition">
                Inicio
              </a>
              <a href="#seguros" className="text-gray-600 hover:text-purple-600 transition">
                Seguros
              </a>
              <a href="#beneficios" className="text-gray-600 hover:text-purple-600 transition">
                Beneficios
              </a>
              <a href="#contacto" className="text-gray-600 hover:text-purple-600 transition">
                Contacto
              </a>
              <a href="/admin/login.php" className="text-gray-600 hover:text-red-600 transition font-semibold">
                Administrador
              </a>
            </div>

            {/* Mobile Menu Button */}
            <button
              className="md:hidden text-gray-600"
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            >
              <i className="fas fa-bars text-2xl"></i>
            </button>
          </div>
        </div>
      </nav>

      {/* Hero Section */}
      <section id="inicio" className="relative min-h-screen flex items-center justify-center overflow-hidden mt-16">
        <div className="absolute inset-0 hero-overlay z-0"></div>

        <div className="container mx-auto px-6 relative z-10 text-center text-white">
          <div className="animate-float">
            <i className="fas fa-home text-8xl mb-6 opacity-90"></i>
          </div>

          <h1 className="text-5xl md:text-7xl font-bold mb-6 leading-tight">
            Protege tu <span className="text-yellow-300">Hogar</span>
          </h1>

          <p className="text-xl md:text-2xl mb-8 max-w-3xl mx-auto opacity-90">
            Seguros flexibles y modernos para tu casa, contenido y familia.
            Cotiza en minutos y obtén protección inmediata.
          </p>

          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              href="/hogar-comprensivo.php"
              className="bg-white text-purple-600 px-8 py-4 rounded-full font-bold text-lg hover:bg-yellow-300 hover:text-purple-700 transition transform hover:scale-105 shadow-2xl"
            >
              <i className="fas fa-rocket mr-2"></i>
              Cotizar Ahora
            </Link>

            <a
              href="#beneficios"
              className="bg-transparent border-2 border-white text-white px-8 py-4 rounded-full font-bold text-lg hover:bg-white hover:text-purple-600 transition"
            >
              <i className="fas fa-info-circle mr-2"></i>
              Conocer Más
            </a>
          </div>

          {/* Stats */}
          <div className="grid grid-cols-3 gap-8 mt-16 max-w-3xl mx-auto">
            <div className="pulse-slow">
              <div className="text-4xl font-bold text-yellow-300">
                {stats.homes.toLocaleString()}+
              </div>
              <div className="text-sm opacity-80">Hogares Protegidos</div>
            </div>

            <div className="pulse-slow" style={{ animationDelay: '0.5s' }}>
              <div className="text-4xl font-bold text-yellow-300">
                {stats.satisfaction}%
              </div>
              <div className="text-sm opacity-80">Satisfacción</div>
            </div>

            <div className="pulse-slow" style={{ animationDelay: '1s' }}>
              <div className="text-4xl font-bold text-yellow-300">24/7</div>
              <div className="text-sm opacity-80">Atención</div>
            </div>
          </div>
        </div>

        <div className="absolute bottom-8 left-1/2 -translate-x-1/2 z-10 animate-bounce">
          <i className="fas fa-chevron-down text-white text-2xl opacity-70"></i>
        </div>
      </section>

      {/* Tipos de Seguros */}
      <section id="seguros" className="py-20 bg-white">
        <div className="container mx-auto px-6">
          <div className="text-center mb-16">
            <h2 className="text-4xl md:text-5xl font-bold text-gray-800 mb-4">
              Nuestros <span className="text-purple-600">Seguros</span>
            </h2>
            <p className="text-gray-600 text-lg max-w-2xl mx-auto">
              Soluciones de protección diseñadas para cada necesidad
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            {/* Hogar */}
            <div className="card-hover bg-white border-2 border-gray-100 rounded-2xl p-8 text-center">
              <div className="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i className="fas fa-home text-purple-600 text-3xl"></i>
              </div>
              <h3 className="text-2xl font-bold text-gray-800 mb-4">Seguro de Hogar</h3>
              <p className="text-gray-600 mb-6">
                Protege tu casa y todo lo que hay en ella contra incendios,
                robos, terremotos y más.
              </p>
              <Link
                href="/hogar-comprensivo.php"
                className="inline-block bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-700 transition"
              >
                Cotizar <i className="fas fa-arrow-right ml-2"></i>
              </Link>

              <div className="mt-6 pt-6 border-t border-gray-100">
                <div className="flex items-center justify-center space-x-2 text-sm text-gray-500">
                  <i className="fas fa-check text-green-500"></i>
                  <span>Cobertura amplia</span>
                </div>
                <div className="flex items-center justify-center space-x-2 text-sm text-gray-500 mt-2">
                  <i className="fas fa-check text-green-500"></i>
                  <span>Primas accesibles</span>
                </div>
              </div>
            </div>

            {/* Auto */}
            <div className="card-hover bg-white border-2 border-gray-100 rounded-2xl p-8 text-center">
              <div className="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i className="fas fa-car text-blue-600 text-3xl"></i>
              </div>
              <h3 className="text-2xl font-bold text-gray-800 mb-4">Seguro de Auto</h3>
              <p className="text-gray-600 mb-6">
                Circula tranquilo con la mejor cobertura para tu vehículo.
                Incluye responsabilidad civil.
              </p>
              <button className="inline-block bg-gray-200 text-gray-500 px-6 py-3 rounded-lg font-semibold cursor-not-allowed">
                Próximamente
              </button>

              <div className="mt-6 pt-6 border-t border-gray-100">
                <div className="flex items-center justify-center space-x-2 text-sm text-gray-500">
                  <i className="fas fa-check text-green-500"></i>
                  <span>Daños propios y terceros</span>
                </div>
                <div className="flex items-center justify-center space-x-2 text-sm text-gray-500 mt-2">
                  <i className="fas fa-check text-green-500"></i>
                  <span>Asistencia vial 24/7</span>
                </div>
              </div>
            </div>

            {/* Vida */}
            <div className="card-hover bg-white border-2 border-gray-100 rounded-2xl p-8 text-center">
              <div className="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i className="fas fa-heart text-green-600 text-3xl"></i>
              </div>
              <h3 className="text-2xl font-bold text-gray-800 mb-4">Seguro de Vida</h3>
              <p className="text-gray-600 mb-6">
                Asegura el futuro de tu familia con nuestros planes de vida
                flexibles y completos.
              </p>
              <button className="inline-block bg-gray-200 text-gray-500 px-6 py-3 rounded-lg font-semibold cursor-not-allowed">
                Próximamente
              </button>

              <div className="mt-6 pt-6 border-t border-gray-100">
                <div className="flex items-center justify-center space-x-2 text-sm text-gray-500">
                  <i className="fas fa-check text-green-500"></i>
                  <span>Protección familiar</span>
                </div>
                <div className="flex items-center justify-center space-x-2 text-sm text-gray-500 mt-2">
                  <i className="fas fa-check text-green-500"></i>
                  <span>Planes personalizados</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-20 gradient-bg text-white">
        <div className="container mx-auto px-6 text-center">
          <h2 className="text-4xl font-bold mb-6">
            ¿Listo para Proteger tu Hogar?
          </h2>
          <p className="text-xl mb-8 max-w-2xl mx-auto opacity-90">
            Obtén una cotización personalizada en menos de 5 minutos
          </p>
          <Link
            href="/hogar-comprensivo.php"
            className="inline-block bg-white text-purple-600 px-10 py-4 rounded-full font-bold text-lg hover:bg-yellow-300 hover:text-purple-700 transition transform hover:scale-105 shadow-2xl"
          >
            <i className="fas fa-calculator mr-2"></i>
            Cotizar Gratis Ahora
          </Link>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-gray-900 text-white py-12">
        <div className="container mx-auto px-6">
          <div className="grid md:grid-cols-3 gap-8 mb-8">
            <div>
              <h3 className="text-xl font-bold mb-4">AseguraloCR</h3>
              <p className="text-gray-400">
                Agente Autorizado INS 110886
              </p>
            </div>

            <div>
              <h4 className="font-semibold mb-4">Enlaces</h4>
              <ul className="space-y-2 text-gray-400">
                <li><a href="#seguros" className="hover:text-white transition">Seguros</a></li>
                <li><a href="/client/login.php" className="hover:text-white transition">Portal Clientes</a></li>
                <li><a href="/admin/login.php" className="hover:text-white transition">Admin</a></li>
              </ul>
            </div>

            <div>
              <h4 className="font-semibold mb-4">Contacto</h4>
              <p className="text-gray-400">info@aseguralocr.com</p>
            </div>
          </div>

          <div className="border-t border-gray-800 pt-8 text-center text-gray-400">
            <p>&copy; 2024 AseguraloCR. Todos los derechos reservados.</p>
            <p className="mt-2 text-sm">
              <span className="text-yellow-400">⚡ Prototipo Next.js + React</span> -
              Comparar con <a href="/" className="text-purple-400 hover:underline">versión PHP</a>
            </p>
          </div>
        </div>
      </footer>

      {/* Font Awesome */}
      <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
      />
    </div>
  )
}
