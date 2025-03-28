function editFullName() {
    const fullName = document.getElementById('full_name_display').textContent.trim();
    const nameParts = fullName.split(' ');
    
    const firstName = nameParts[0] || '';
    const middleName = nameParts.length > 2 ? nameParts.slice(1, -1).join(' ') : '';
    const lastName = nameParts.length > 1 ? nameParts[nameParts.length - 1] : '';

    Swal.fire({
        title: 'Edit Full Name',
        html: `
            <div>
                <input type="text" id="first_name" class="form-control mb-2" placeholder="First Name" value="${firstName}">
                <input type="text" id="middle_name" class="form-control mb-2" placeholder="Middle Name (Optional)" value="${middleName}">
                <input type="text" id="last_name" class="form-control mb-2" placeholder="Last Name" value="${lastName}">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Save',
        preConfirm: () => {
            const firstName = document.getElementById('first_name').value.trim();
            const middleName = document.getElementById('middle_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();

            if (!firstName || !lastName) {
                Swal.showValidationMessage('First and Last Name are required!');
                return false;
            }

            return { firstName, middleName, lastName };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { firstName, middleName, lastName } = result.value;

            // Update fields separately
            Promise.all([
                updateProfileField('first_name', firstName),
                updateProfileField('middle_name', middleName),
                updateProfileField('last_name', lastName),
            ]).then(() => {
                document.getElementById('full_name_display').textContent = `${firstName} ${middleName} ${lastName}`.trim();
                Swal.fire('Saved!', 'Your name has been updated.', 'success');
            }).catch(() => {
                Swal.fire('Error!', 'Failed to update your name.', 'error');
            });
        }
    });
}


// Reusable function to update a profile field
function updateProfileField(field, value) {
    return fetch('../endpoints/profile/update_profile_admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ field, value }),
    }).then((response) => response.json())
        .then((data) => {
            if (data.status !== 'success') {
                throw new Error(data.message);
            }
        });
}



function editEmail() {
    const email = document.getElementById('email_display').textContent.trim();

    Swal.fire({
        title: 'Edit Email',
        html: `<input id="email" class="swal2-input" value="${email}">`,
        showCancelButton: true,
        confirmButtonText: 'Save',
        preConfirm: () => {
            const email = document.getElementById('email').value.trim();

            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                Swal.showValidationMessage('Please enter a valid email address!');
            }

            return { email };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { email } = result.value;

            // Update on the server
            fetch('../endpoints/profile/update_profile_admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    field: 'email',
                    value: email,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.status === 'success') {
                        document.getElementById('email_display').textContent = email;
                        Swal.fire('Saved!', 'Your email has been updated.', 'success');
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                })
                .catch(() => {
                    Swal.fire('Error!', 'Failed to update your email.', 'error');
                });
        }
    });
}

function editPassword() {
    Swal.fire({
        title: 'Edit Password',
        html: `
            <div style="display: flex; flex-direction: column; align-items: center; gap: 10px;">
                <label for="new_password" style="font-size: 16px; margin-bottom: 5px;">New Password</label>
                <input id="new_password" class="swal2-input" type="password" placeholder="Enter new password" style="width: 80%;">
                <label for="confirm_password" style="font-size: 16px; margin-bottom: 5px;">Confirm Password</label>
                <input id="confirm_password" class="swal2-input" type="password" placeholder="Confirm password" style="width: 80%;">
            </div>
        `,
        showCancelButton: true,
        width: '600px',
        padding: '30px',
        confirmButtonText: 'Save',
        preConfirm: () => {
            const newPassword = document.getElementById('new_password').value.trim();
            const confirmPassword = document.getElementById('confirm_password').value.trim();

            if (!newPassword || !confirmPassword) {
                Swal.showValidationMessage('Both password fields are required!');
                return false;
            }

            if (newPassword !== confirmPassword) {
                Swal.showValidationMessage('Passwords do not match!');
                return false;
            }

            return { newPassword };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { newPassword } = result.value;

            fetch('../endpoints/profile/update_profile_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ field: 'password', value: newPassword }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.status === 'success') {
                        Swal.fire('Saved!', 'Your password has been updated.', 'success');
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                })
                .catch(() => {
                    Swal.fire('Error!', 'Failed to update your password.', 'error');
                });
        }
    });
}



function editProfilePicture() {
    Swal.fire({
        title: 'Change Profile Picture',
        html: `
            <input type="file" id="profile_pic_input" class="swal2-input form-control" accept="image/*">
        `,
        showCancelButton: true,
        confirmButtonText: 'Upload',
        preConfirm: () => {
            const fileInput = document.getElementById('profile_pic_input');
            const file = fileInput.files[0];

            if (!file) {
                Swal.showValidationMessage('Please select an image to upload!');
                return false;
            }

            return { file };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { file } = result.value;

            // Create a form data object to send the file
            const formData = new FormData();
            formData.append('field', 'profile_picture');
            formData.append('file', file);

            // Send the file to the server
            fetch('../endpoints/profile/update_profile_admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Preview the uploaded image
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        document.getElementById('profile_picture_display').src = e.target.result;
                    };
                    reader.readAsDataURL(file);

                    Swal.fire('Uploaded!', 'Your profile picture has been updated.', 'success');
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error!', 'Failed to upload profile picture. Please try again later.', 'error');
            });
        }
    });
}

