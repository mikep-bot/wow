    
        // Tab Navigation
        document.addEventListener('DOMContentLoaded', function() {
            const navItems = document.querySelectorAll('.nav-item');
            const tabContents = document.querySelectorAll('.tab-content');
            
            navItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all items and contents
                    navItems.forEach(nav => nav.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to clicked item
                    this.classList.add('active');
                    
                    // Show corresponding tab content
                    const tabId = this.getAttribute('data-tab') + '-tab';
                    document.getElementById(tabId).classList.add('active');
                    
                    // Update URL hash
                    window.location.hash = this.getAttribute('href');
                });
            });
            
            // Check URL hash on page load
            if (window.location.hash) {
                const hash = window.location.hash.substring(1);
                const targetTab = document.querySelector(`.nav-item[href="#${hash}"]`);
                if (targetTab) {
                    targetTab.click();
                }
            }
            
            // Avatar Preview
            const avatarInput = document.getElementById('avatar');
            const avatarPreview = document.getElementById('avatarPreview');
            
            if (avatarInput && avatarPreview) {
                avatarInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        if (file.size > 5 * 1024 * 1024) {
                            alert('File size must be less than 5MB');
                            this.value = '';
                            return;
                        }
                        
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            avatarPreview.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
            
            // Threshold Range Slider
            function updateThresholdValue(value) {
                document.getElementById('thresholdValue').textContent = value;
            }
            
            // Password Strength Checker
            const passwordInput = document.getElementById('new_password');
            const strengthBar = document.querySelector('.strength-bar');
            const strengthText = document.querySelector('.strength-text');
            
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    let strength = 0;
                    
                    // Check length
                    if (password.length >= 8) {
                        strength++;
                        document.getElementById('req-length').style.color = '#48bb78';
                    } else {
                        document.getElementById('req-length').style.color = '#e53e3e';
                    }
                    
                    // Check uppercase
                    if (/[A-Z]/.test(password)) {
                        strength++;
                        document.getElementById('req-uppercase').style.color = '#48bb78';
                    } else {
                        document.getElementById('req-uppercase').style.color = '#e53e3e';
                    }
                    
                    // Check lowercase
                    if (/[a-z]/.test(password)) {
                        strength++;
                        document.getElementById('req-lowercase').style.color = '#48bb78';
                    } else {
                        document.getElementById('req-lowercase').style.color = '#e53e3e';
                    }
                    
                    // Check number
                    if (/[0-9]/.test(password)) {
                        strength++;
                        document.getElementById('req-number').style.color = '#48bb78';
                    } else {
                        document.getElementById('req-number').style.color = '#e53e3e';
                    }
                    
                    // Update strength bar
                    const width = (strength / 4) * 100;
                    strengthBar.style.width = width + '%';
                    
                    // Update text
                    if (password.length === 0) {
                        strengthText.textContent = 'Password strength';
                        strengthBar.style.background = '#e2e8f0';
                    } else if (strength <= 1) {
                        strengthText.textContent = 'Very Weak';
                        strengthBar.style.background = '#e53e3e';
                    } else if (strength === 2) {
                        strengthText.textContent = 'Weak';
                        strengthBar.style.background = '#ed8936';
                    } else if (strength === 3) {
                        strengthText.textContent = 'Good';
                        strengthBar.style.background = '#4299e1';
                    } else {
                        strengthText.textContent = 'Strong';
                        strengthBar.style.background = '#48bb78';
                    }
                });
            }
            
            // Password Match Checker
            const confirmInput = document.getElementById('confirm_password');
            const matchText = document.getElementById('match-text');
            
            if (confirmInput) {
                confirmInput.addEventListener('input', function() {
                    const password = document.getElementById('new_password').value;
                    const confirm = this.value;
                    
                    if (confirm.length === 0) {
                        matchText.textContent = '';
                        matchText.style.color = '';
                    } else if (password === confirm) {
                        matchText.textContent = '✓ Passwords match';
                        matchText.style.color = '#48bb78';
                    } else {
                        matchText.textContent = '✗ Passwords do not match';
                        matchText.style.color = '#e53e3e';
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
        });
        
        // Modal Functions
        function showDeleteModal() {
            document.getElementById('deleteAccountModal').style.display = 'block';
        }
        
        function closeDeleteAccountModal() {
            document.getElementById('deleteAccountModal').style.display = 'none';
        }
        
        function setup2FA() {
            alert('Two-Factor Authentication setup would be implemented here. This is a demo feature.');
        }
        
        function confirmAccountDeletion() {
            const confirmation = document.getElementById('delete_confirmation').value;
            if (confirmation !== 'DELETE') {
                alert('Please type DELETE to confirm account deletion');
                return false;
            }
            return confirm('Are you absolutely sure? This action cannot be undone.');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        };
    