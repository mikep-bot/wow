
        // JS for tab switching
        const loginTab = document.getElementById('login-tab');
        const signupTab = document.getElementById('signup-tab');
        const loginContent = document.getElementById('login-content');
        const signupContent = document.getElementById('signup-content');

        loginTab.addEventListener('click', () => {
            loginTab.classList.add('active');
            signupTab.classList.remove('active');
            loginContent.classList.add('active');
            signupContent.classList.remove('active');
        });

        signupTab.addEventListener('click', () => {
            signupTab.classList.add('active');
            loginTab.classList.remove('active');
            signupContent.classList.add('active');
            loginContent.classList.remove('active');
        });
    