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
                <div class="mt-2 text-center">
                    <a href="#" id="forgotPasswordLink" style="font-size: 0.9rem;">Forgot Password?</a>
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
            fetch('../endpoints/sign.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=login&email=${encodeURIComponent(result.value.email)}&password=${encodeURIComponent(result.value.password)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.href = './admin/index.php'; // Redirect to the admin page
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error', 'An error occurred. Please try again later.', 'error');
                });
        }
    });

    document.getElementById('forgotPasswordLink').addEventListener('click', (e) => {
        e.preventDefault();
        showForgotPasswordModal();
    });
}

function showForgotPasswordModal() {
    Swal.fire({
        title: 'Forgot Password',
        html: `
            <form id="forgotPasswordForm">
                <div class="mb-3">
                    <label for="resetEmail" class="form-label">Email</label>
                    <input type="email" class="form-control" id="resetEmail" placeholder="Enter your registered email" required>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Reset Password',
        preConfirm: () => {
            const resetEmail = document.getElementById('resetEmail').value.trim();

            if (!resetEmail) {
                Swal.showValidationMessage('Please enter your email');
                return false;
            }

            return { resetEmail };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            console.log("Sending reset email for:", result.value.resetEmail); // Log for debugging

            // Show a loading spinner
            Swal.fire({
                title: 'Sending Email...',
                html: 'Please wait while we process your request.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('../endpoints/forgot-password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=forgot_password&email=${encodeURIComponent(result.value.resetEmail)}`
            })
                .then(response => {
                    console.log(response); // Log the response
                    return response.json();
                })
                .then(data => {
                    console.log(data); // Log the parsed data
                    if (data.status === 'success') {
                        Swal.fire('Success', data.message, 'success').then(() => {
                            // Reload the page after success confirmation
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error); // Log network errors
                    Swal.fire('Error', 'An error occurred while processing your request. Please try again.', 'error');
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
            fetch('../endpoints/sign.php', {
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

     
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const resetToken = urlParams.get('reset_token');

    if (!resetToken) {
        console.log('No reset token found in the URL. Reset modal will not be displayed.');
        return;
    }

    Swal.fire({
        title: 'Reset Password',
        input: 'password',
        inputLabel: 'Enter your new password',
        inputAttributes: {
            maxlength: 50,
            autocapitalize: 'off',
            autocorrect: 'off',
        },
        showCancelButton: true,
        confirmButtonText: 'Reset Password',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
            if (!value) {
                return 'Password cannot be empty!';
            }
            if (value.length < 6) {
                return 'Password must be at least 6 characters long.';
            }
        },
    }).then((result) => {
        if (result.isConfirmed) {
            const newPassword = result.value;

            fetch('../endpoints/reset-password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: resetToken,
                    new_password: newPassword,
                }),
            })
                .then((response) => response.json().then((data) => ({ status: response.status, body: data })))
                .then(({ status, body }) => {
                    if (status === 200 && body.status === 'success') {
                        Swal.fire('Success', body.message, 'success').then(() => {
                            window.location.href = 'http://localhost/smartstoremanager/';
                        });
                    } else {
                        Swal.fire('Error', body.message || 'Something went wrong.', 'error');
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Something went wrong. Please try again later.', 'error');
                });
        }
    });
});

