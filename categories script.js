
        // Modal Functions
        function openCategoryModal() {
            document.getElementById('categoryModal').style.display = 'block';
        }

        function closeCategoryModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }

        function openEditModal(id, name, description, color) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            
            // Set the color radio button
            const colorRadios = document.querySelectorAll('#editCategoryModal input[name="color"]');
            colorRadios.forEach(radio => {
                if (radio.value === color) {
                    radio.checked = true;
                }
            });
            
            document.getElementById('editCategoryModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editCategoryModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Auto-hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
