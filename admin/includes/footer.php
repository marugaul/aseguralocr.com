        </div><!-- /.content-wrapper -->
    </div><!-- /.main-content -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Common Scripts -->
    <script>
        // Toast notifications
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer') || createToastContainer();
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            toastContainer.appendChild(toast);
            new bootstrap.Toast(toast).show();
            setTimeout(() => toast.remove(), 5000);
        }

        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
            return container;
        }

        // Format currency
        function formatCurrency(amount, currency = 'CRC') {
            const formatter = new Intl.NumberFormat('es-CR', {
                style: 'currency',
                currency: currency === 'dolares' ? 'USD' : 'CRC'
            });
            return formatter.format(amount);
        }

        // Confirm delete
        function confirmDelete(message, form) {
            if (confirm(message || '¿Está seguro de eliminar este registro?')) {
                form.submit();
            }
        }
    </script>

    <?php if (isset($_SESSION['flash_message'])): ?>
    <script>
        showToast('<?= addslashes($_SESSION['flash_message']) ?>', '<?= $_SESSION['flash_type'] ?? 'success' ?>');
    </script>
    <?php
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    endif; ?>
</body>
</html>
