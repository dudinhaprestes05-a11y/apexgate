        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });

        function copyToClipboard(text, button) {
            navigator.clipboard.writeText(text).then(() => {
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i>';
                button.classList.add('text-green-400');
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.classList.remove('text-green-400');
                }, 2000);
            });
        }

        function formatCurrency(value) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(value);
        }

        function formatDocument(doc) {
            doc = doc.replace(/\D/g, '');
            if (doc.length === 11) {
                return doc.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            } else if (doc.length === 14) {
                return doc.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
            }
            return doc;
        }
    </script>
</body>
</html>
