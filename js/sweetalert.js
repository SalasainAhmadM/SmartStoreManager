// sweetalert.js

function showLoginModal() {
    Swal.fire({
        title: 'Login',
        html: `
            <form id="loginForm">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" placeholder="Enter your email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" placeholder="Enter your password" required>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Login',
        focusConfirm: false,
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
            // Handle login logic here
            console.log('Login data:', result.value);
            Swal.fire('Success', 'You are now logged in!', 'success');
        }
    });
}

function showRegisterModal() {
    Swal.fire({
        title: 'Register',
        html: `
            <form id="registerForm">
                <div class="mb-3">
                    <label for="regName" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="regName" placeholder="Enter your name" required>
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
        focusConfirm: false,
        preConfirm: () => {
            const name = document.getElementById('regName').value;
            const email = document.getElementById('regEmail').value;
            const password = document.getElementById('regPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (!name || !email || !password || !confirmPassword) {
                Swal.showValidationMessage('Please fill out all fields');
            } else if (password !== confirmPassword) {
                Swal.showValidationMessage('Passwords do not match');
            }

            return { name, email, password };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Handle registration logic here
            console.log('Registration data:', result.value);
            Swal.fire('Success', 'You have successfully registered!', 'success');
        }
    });
}
