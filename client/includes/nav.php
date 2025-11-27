<?php
// Client Navigation Bar
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$clientName = $_SESSION['client_name'] ?? 'Usuario';
$clientAvatar = $_SESSION['client_avatar'] ?? '';
?>
<nav class="bg-white shadow-md sticky top-0 z-50">
    <div class="container mx-auto px-4 py-3">
        <div class="flex items-center justify-between">
            <!-- Logo -->
            <a href="/client/dashboard.php" class="flex items-center space-x-3">
                <div class="w-10 h-10 gradient-bg rounded-lg flex items-center justify-center">
                    <i class="fas fa-shield-alt text-white text-lg"></i>
                </div>
                <div class="hidden sm:block">
                    <h1 class="text-lg font-bold text-gray-800">AseguraloCR</h1>
                    <p class="text-xs text-gray-500">Portal de Clientes</p>
                </div>
            </a>

            <!-- Desktop Menu -->
            <div class="hidden lg:flex items-center space-x-4">
                <a href="/client/dashboard.php"
                   class="px-3 py-2 rounded-lg <?= $currentPage === 'dashboard' ? 'bg-purple-100 text-purple-600 font-semibold' : 'text-gray-600 hover:bg-gray-100' ?> transition">
                    Dashboard
                </a>
                <a href="/client/policies.php"
                   class="px-3 py-2 rounded-lg <?= $currentPage === 'policies' ? 'bg-purple-100 text-purple-600 font-semibold' : 'text-gray-600 hover:bg-gray-100' ?> transition">
                    Mis Pólizas
                </a>
                <a href="/client/quotes.php"
                   class="px-3 py-2 rounded-lg <?= $currentPage === 'quotes' ? 'bg-purple-100 text-purple-600 font-semibold' : 'text-gray-600 hover:bg-gray-100' ?> transition">
                    Cotizaciones
                </a>
                <a href="/client/payments.php"
                   class="px-3 py-2 rounded-lg <?= $currentPage === 'payments' ? 'bg-purple-100 text-purple-600 font-semibold' : 'text-gray-600 hover:bg-gray-100' ?> transition">
                    Pagos
                </a>
                <a href="/client/documents.php"
                   class="px-3 py-2 rounded-lg <?= $currentPage === 'documents' ? 'bg-purple-100 text-purple-600 font-semibold' : 'text-gray-600 hover:bg-gray-100' ?> transition">
                    Documentos
                </a>
                <a href="/client/claims.php"
                   class="px-3 py-2 rounded-lg <?= $currentPage === 'claims' ? 'bg-purple-100 text-purple-600 font-semibold' : 'text-gray-600 hover:bg-gray-100' ?> transition">
                    Asistencias
                </a>
            </div>

            <!-- User Menu (Desktop) + Mobile Button -->
            <div class="flex items-center space-x-3">
                <!-- User Dropdown (Desktop) -->
                <div class="relative hidden lg:block" id="user-menu-desktop">
                    <button onclick="toggleUserMenu()" class="flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-gray-100 transition">
                        <div class="w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                            <?= strtoupper(substr($clientName, 0, 1)) ?>
                        </div>
                        <span class="text-gray-700 font-medium max-w-[120px] truncate"><?= htmlspecialchars($clientName) ?></span>
                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                    </button>

                    <!-- Dropdown -->
                    <div id="user-dropdown" class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl border border-gray-100 hidden z-50">
                        <div class="p-4 border-b border-gray-100">
                            <p class="font-semibold text-gray-800"><?= htmlspecialchars($clientName) ?></p>
                            <p class="text-sm text-gray-500 truncate"><?= htmlspecialchars($_SESSION['client_email'] ?? '') ?></p>
                        </div>
                        <div class="py-2">
                            <a href="/client/profile.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-purple-50 transition">
                                <i class="fas fa-user-circle w-5 mr-3 text-gray-400"></i>Mi Perfil
                            </a>
                            <a href="/client/notifications.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-purple-50 transition">
                                <i class="fas fa-bell w-5 mr-3 text-gray-400"></i>Notificaciones
                            </a>
                        </div>
                        <div class="border-t border-gray-100 py-2">
                            <a href="/client/logout.php" class="flex items-center px-4 py-2 text-red-600 hover:bg-red-50 transition">
                                <i class="fas fa-sign-out-alt w-5 mr-3"></i>Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Mobile Menu Button -->
                <button id="mobile-menu-btn" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 text-gray-600">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden lg:hidden mt-4 pb-4 border-t border-gray-100 pt-4">
            <!-- User Info -->
            <div class="flex items-center space-x-3 px-4 py-3 bg-purple-50 rounded-lg mb-4">
                <div class="w-10 h-10 bg-purple-600 rounded-full flex items-center justify-center text-white font-semibold">
                    <?= strtoupper(substr($clientName, 0, 1)) ?>
                </div>
                <div>
                    <p class="font-semibold text-gray-800"><?= htmlspecialchars($clientName) ?></p>
                    <p class="text-xs text-gray-500"><?= htmlspecialchars($_SESSION['client_email'] ?? '') ?></p>
                </div>
            </div>

            <!-- Navigation Links -->
            <div class="space-y-1">
                <a href="/client/dashboard.php"
                   class="flex items-center px-4 py-3 <?= $currentPage === 'dashboard' ? 'bg-purple-100 text-purple-600 font-semibold' : 'text-gray-600' ?> rounded-lg hover:bg-gray-100 transition">
                    <i class="fas fa-th-large w-6 mr-3"></i>Dashboard
                </a>
                <a href="/client/policies.php"
                   class="flex items-center px-4 py-3 <?= $currentPage === 'policies' ? 'bg-purple-100 text-purple-600 font-semibold' : 'text-gray-600' ?> rounded-lg hover:bg-gray-100 transition">
                    <i class="fas fa-shield-alt w-6 mr-3"></i>Mis Pólizas
                </a>
                <a href="/client/quotes.php"
                   class="flex items-center px-4 py-3 <?= $currentPage === 'quotes' ? 'bg-purple-100 text-purple-600 font-semibold' : 'text-gray-600' ?> rounded-lg hover:bg-gray-100 transition">
                    <i class="fas fa-file-invoice w-6 mr-3"></i>Cotizaciones
                </a>
                <a href="/client/payments.php"
                   class="flex items-center px-4 py-3 <?= $currentPage === 'payments' ? 'bg-purple-100 text-purple-600 font-semibold' : 'text-gray-600' ?> rounded-lg hover:bg-gray-100 transition">
                    <i class="fas fa-credit-card w-6 mr-3"></i>Pagos
                </a>
                <a href="/client/documents.php"
                   class="flex items-center px-4 py-3 <?= $currentPage === 'documents' ? 'bg-purple-100 text-purple-600 font-semibold' : 'text-gray-600' ?> rounded-lg hover:bg-gray-100 transition">
                    <i class="fas fa-folder-open w-6 mr-3"></i>Documentos
                </a>
                <a href="/client/claims.php"
                   class="flex items-center px-4 py-3 <?= $currentPage === 'claims' ? 'bg-purple-100 text-purple-600 font-semibold' : 'text-gray-600' ?> rounded-lg hover:bg-gray-100 transition">
                    <i class="fas fa-hands-helping w-6 mr-3"></i>Asistencias
                </a>
            </div>

            <!-- User Actions -->
            <div class="mt-4 pt-4 border-t border-gray-200 space-y-1">
                <a href="/client/profile.php"
                   class="flex items-center px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-100 transition">
                    <i class="fas fa-user-circle w-6 mr-3"></i>Mi Perfil
                </a>
                <a href="/client/logout.php"
                   class="flex items-center px-4 py-3 text-red-600 rounded-lg hover:bg-red-50 transition">
                    <i class="fas fa-sign-out-alt w-6 mr-3"></i>Cerrar Sesión
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
// Mobile menu toggle
document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
    document.getElementById('mobile-menu').classList.toggle('hidden');
});

// User dropdown toggle (desktop)
function toggleUserMenu() {
    document.getElementById('user-dropdown').classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const userMenu = document.getElementById('user-menu-desktop');
    const dropdown = document.getElementById('user-dropdown');
    if (userMenu && dropdown && !userMenu.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});
</script>
