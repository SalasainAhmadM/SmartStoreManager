function showLoginModal() {
    Swal.fire({
        title: 'Login',
        html: `
            <form id="loginForm">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="text" class="form-control" id="email" placeholder="Enter your email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" placeholder="Enter your password" required>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Login',
        preConfirm: () => {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            if (!email || !password) {
                Swal.showValidationMessage('Please fill out all fields');
            }

            return { email, password };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('./endpoints/sign.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=login&email=${result.value.email}&password=${result.value.password}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('Success', data.message, 'success');
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
        }
    });
}

function showRegisterModal() {
    Swal.fire({
        title: 'Register',
        html: `
            <form id="registerForm">
                <div class="mb-3">
                    <label for="userName" class="form-label">Username</label>
                    <input type="text" class="form-control" id="userName" placeholder="Enter your username" required>
                </div>
                <div class="mb-3">
                    <label for="regEmail" class="form-label">Email</label>
                    <input type="email" class="form-control" id="regEmail" placeholder="Enter your email" required>
                </div>
                <div class="mb-3">
                    <label for="regPassword" class="form-label">Password</label>
                    <input type="password" class="form-control" id="regPassword" placeholder="Create a password" required>
                </div>
                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirmPassword" placeholder="Confirm your password" required>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Register',
        preConfirm: () => {
            const userName = document.getElementById('userName').value;
            const email = document.getElementById('regEmail').value;
            const password = document.getElementById('regPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (!userName || !email || !password || !confirmPassword) {
                Swal.showValidationMessage('Please fill out all fields');
            } else if (password !== confirmPassword) {
                Swal.showValidationMessage('Passwords do not match');
            }

            return { userName, email, password };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('./endpoints/sign.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=register&userName=${result.value.userName}&email=${result.value.email}&password=${result.value.password}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('Success', data.message, 'success');
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
        }
    });
}
