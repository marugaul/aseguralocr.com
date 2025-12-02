    </main>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Admin Scripts -->
    <script>
        // Auto-hide alerts after 5 seconds
        document.querySelectorAll('.alert-dismissible').forEach(alert => {
            setTimeout(() => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            }, 5000);
        });

        // Tooltip initialization
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));

        // Confirm delete actions
        document.querySelectorAll('[data-confirm]').forEach(el => {
            el.addEventListener('click', function(e) {
                if (!confirm(this.dataset.confirm || '¿Está seguro?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
