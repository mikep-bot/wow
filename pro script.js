
        // Modal Functions
        function openProductModal() {
            document.getElementById('productModal').style.display = 'block';
        }

        function closeProductModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal) {
                closeProductModal();
            }
        }

        // Live search functionality
        document.getElementById('search').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

                // Avatar Preview
            const avatarInput = document.getElementById('avatar');
            const avatarPreview = document.getElementById('avatarPreview');
            
            if (avatarInput && avatarPreview) {
                avatarInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            avatarPreview.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
            
        
            
            rows.forEach(row => {
                const productName = row.cells[1].textContent.toLowerCase();
                if (productName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });