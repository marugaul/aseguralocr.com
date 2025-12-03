<?php
// Client Navigation Bar
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<nav class="bg-white shadow-md sticky top-0 z-50">
    <div class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
            <!-- Logo -->
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 gradient-bg rounded-lg flex items-center justify-center">
                    <i class="fas fa-shield-alt text-white text-lg"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-800">AseguraloCR</h1>
                    <p class="text-xs text-gray-500">Portal de Clientes</p>
                </div>
            </div>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-6">
                <a href="/client/dashboard.php"
                   class="<?= $currentPage === 'dashboard' ? 'text-purple-600 font-semibold' : 'text-gray-600 hover:text-purple-600' ?> transition">
                    <i class="fas fa-th-large mr-1"></i>Dashboard
                </a>
                <a href="/client/policies.php"
                   class="<?= $currentPage === 'policies' ? 'text-purple-600 font-semibold' : 'text-gray-600 hover:text-purple-600' ?> transition">
                    <i class="fas fa-shield-alt mr-1"></i>Mis Pólizas
                </a>
                <a href="/client/quotes.php"
                   class="<?= $currentPage === 'quotes' ? 'text-purple-600 font-semibold' : 'text-gray-600 hover:text-purple-600' ?> transition">
                    <i class="fas fa-file-invoice mr-1"></i>Cotizaciones
                </a>
                <a href="/client/payments.php"
                   class="<?= $currentPage === 'payments' ? 'text-purple-600 font-semibold' : 'text-gray-600 hover:text-purple-600' ?> transition">
                    <i class="fas fa-credit-card mr-1"></i>Pagos
                </a>
                <a href="/client/documents.php"
                   class="<?= $currentPage === 'documents' ? 'text-purple-600 font-semibold' : 'text-gray-600 hover:text-purple-600' ?> transition">
                    <i class="fas fa-folder-open mr-1"></i>Documentos
                </a>

                <!-- User Menu -->
                <div class="relative group">
                    <?php
                    $navAvatar = $_SESSION['client_avatar'] ?? '';
                    $navInitial = strtoupper(substr($_SESSION['client_name'] ?? 'C', 0, 1));
                    ?>
                    <button class="flex items-center space-x-2 text-gray-700 hover:text-purple-600 transition">
                        <?php if (!empty($navAvatar)): ?>
                            <img src="<?= htmlspecialchars($navAvatar) ?>"
                                 alt=""
                                 class="w-8 h-8 rounded-full object-cover"
                                 referrerpolicy="no-referrer"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-8 h-8 bg-purple-100 rounded-full items-center justify-center text-purple-600 font-bold text-sm" style="display: none;">
                                <?= $navInitial ?>
                            </div>
                        <?php else: ?>
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 font-bold text-sm">
                                <?= $navInitial ?>
                            </div>
                        <?php endif; ?>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>

                    <!-- Dropdown -->
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                        <div class="py-2">
                            <a href="/client/profile.php"
                               class="block px-4 py-2 text-gray-700 hover:bg-purple-50 transition">
                                <i class="fas fa-user-circle mr-2"></i>Mi Perfil
                            </a>
                            <a href="/client/notifications.php"
                               class="block px-4 py-2 text-gray-700 hover:bg-purple-50 transition">
                                <i class="fas fa-bell mr-2"></i>Notificaciones
                            </a>
                            <hr class="my-2">
                            <a href="/client/logout.php"
                               class="block px-4 py-2 text-red-600 hover:bg-red-50 transition">
                                <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu Button -->
            <button id="mobile-menu-btn" class="md:hidden text-gray-600">
                <i class="fas fa-bars text-2xl"></i>
            </button>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden mt-4 pb-4">
            <div class="space-y-2">
                <a href="/client/dashboard.php"
                   class="block px-4 py-2 <?= $currentPage === 'dashboard' ? 'bg-purple-100 text-purple-600' : 'text-gray-600' ?> rounded hover:bg-gray-100 transition">
                    <i class="fas fa-th-large mr-2"></i>Dashboard
                </a>
                <a href="/client/policies.php"
                   class="block px-4 py-2 <?= $currentPage === 'policies' ? 'bg-purple-100 text-purple-600' : 'text-gray-600' ?> rounded hover:bg-gray-100 transition">
                    <i class="fas fa-shield-alt mr-2"></i>Mis Pólizas
                </a>
                <a href="/client/quotes.php"
                   class="block px-4 py-2 <?= $currentPage === 'quotes' ? 'bg-purple-100 text-purple-600' : 'text-gray-600' ?> rounded hover:bg-gray-100 transition">
                    <i class="fas fa-file-invoice mr-2"></i>Cotizaciones
                </a>
                <a href="/client/payments.php"
                   class="block px-4 py-2 <?= $currentPage === 'payments' ? 'bg-purple-100 text-purple-600' : 'text-gray-600' ?> rounded hover:bg-gray-100 transition">
                    <i class="fas fa-credit-card mr-2"></i>Pagos
                </a>
                <a href="/client/documents.php"
                   class="block px-4 py-2 <?= $currentPage === 'documents' ? 'bg-purple-100 text-purple-600' : 'text-gray-600' ?> rounded hover:bg-gray-100 transition">
                    <i class="fas fa-folder-open mr-2"></i>Documentos
                </a>
                <hr class="my-2">
                <a href="/client/profile.php"
                   class="block px-4 py-2 text-gray-600 rounded hover:bg-gray-100 transition">
                    <i class="fas fa-user-circle mr-2"></i>Mi Perfil
                </a>
                <a href="/client/logout.php"
                   class="block px-4 py-2 text-red-600 rounded hover:bg-red-50 transition">
                    <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
// Mobile menu toggle
document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
    const menu = document.getElementById('mobile-menu');
    menu.classList.toggle('hidden');
});
</script>
