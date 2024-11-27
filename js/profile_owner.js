function editFullName() {
    const fullName = document.getElementById('full_name_display').textContent.trim();
    const [firstName = '', middleName = '', lastName = ''] = fullName.split(' ');

    Swal.fire({
        title: 'Edit Full Name',
        html: `        
            <label for="first_name">First Name</label>
            <input id="first_name" class="swal2-input" value="${firstName}">
            <label for="middle_name">Middle Name</label>
            <input id="middle_name" class="swal2-input" value="${middleName}">
            <label for="last_name">Last Name</label>
            <input id="last_name" class="swal2-input" value="${lastName}">
        `,
        showCancelButton: true,
        confirmButtonText: 'Save',
        preConfirm: () => {
            const firstName = document.getElementById('first_name').value.trim();
            const middleName = document.getElementById('middle_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();

            if (!firstName || !lastName) {
                Swal.showValidationMessage('First and Last Name are required!');
            }

            return { firstName, middleName, lastName };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { firstName, middleName, lastName } = result.value;
            const updatedName = `${firstName} ${middleName} ${lastName}`.trim();
            document.getElementById('full_name_display').textContent = updatedName;

            Swal.fire('Saved!', 'Your name has been updated.', 'success');
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

            if (!email) {
                Swal.showValidationMessage('Email is required!');
            }

            return { email };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { email } = result.value;
            document.getElementById('email_display').textContent = email;

            Swal.fire('Saved!', 'Your email has been updated.', 'success');
        }
    });
}

    function editPassword() {
        
    Swal.fire({
        title: 'Edit Password',
        html: `
            <label for="new_password">New Password</label>
            <input id="new_password" class="swal2-input" type="password" placeholder="Enter new password">
            <label for="confirm_password">Confirm Password</label>
            <input id="confirm_password" class="swal2-input" type="password" placeholder="Confirm password">
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

            document.getElementById('password_display').textContent = '**********'; 

            Swal.fire('Saved!', 'Your password has been updated.', 'success');
        }
    });
}

function editPhone() {
    const phone = document.getElementById('phone_display').textContent.trim();

    Swal.fire({
        title: 'Edit Phone Number',
        html: `<input id="phone" class="swal2-input" value="${phone}">`,
        showCancelButton: true,
        confirmButtonText: 'Save',
        preConfirm: () => {
            const phone = document.getElementById('phone').value.trim();

            if (!phone) {
                Swal.showValidationMessage('Phone number is required!');
            }

            return { phone };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { phone } = result.value;
            document.getElementById('phone_display').textContent = phone;

            Swal.fire('Saved!', 'Your phone number has been updated.', 'success');
        }
    });
}

function editAddress() {
    const address = document.getElementById('address_display').textContent.trim();

    Swal.fire({
        title: 'Edit Address',
        html: `<input id="address" class="swal2-input" value="${address}">`,
        showCancelButton: true,
        confirmButtonText: 'Save',
        preConfirm: () => {
            const address = document.getElementById('address').value.trim();

            if (!address) {
                Swal.showValidationMessage('Address is required!');
            }

            return { address };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { address } = result.value;
            document.getElementById('address_display').textContent = address;

            Swal.fire('Saved!', 'Your address has been updated.', 'success');
        }
    });
}

function editGender() {
    const gender = document.getElementById('gender_display').textContent.trim();

    Swal.fire({
        title: 'Edit Gender',
        html: `<select id="gender" class="swal2-input">
                <option value="Male" ${gender === 'Male' ? 'selected' : ''}>Male</option>
                <option value="Female" ${gender === 'Female' ? 'selected' : ''}>Female</option>
                <option value="Other" ${gender === 'Other' ? 'selected' : ''}>Other</option>
            </select>`,
        showCancelButton: true,
        confirmButtonText: 'Save',
        preConfirm: () => {
            const gender = document.getElementById('gender').value;

            if (!gender) {
                Swal.showValidationMessage('Gender is required!');
            }

            return { gender };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { gender } = result.value;
            document.getElementById('gender_display').textContent = gender;

            Swal.fire('Saved!', 'Your gender has been updated.', 'success');
        }
    });
}

function editAge() {
    const age = document.getElementById('age_display').textContent.trim();

    Swal.fire({
        title: 'Edit Age',
        html: `<input id="age" class="swal2-input" type="number" value="${age}">`,
        showCancelButton: true,
        confirmButtonText: 'Save',
        preConfirm: () => {
            const age = document.getElementById('age').value.trim();

            if (!age) {
                Swal.showValidationMessage('Age is required!');
            }

            return { age };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { age } = result.value;
            document.getElementById('age_display').textContent = age;

            Swal.fire('Saved!', 'Your age has been updated.', 'success');
        }
    });
}

function editBirthday() {
    const birthday = document.getElementById('birthday_display').textContent.trim();

    Swal.fire({
        title: 'Edit Birthday',
        html: `<input id="birthday" class="swal2-input" type="date" value="${birthday}">`,
        showCancelButton: true,
        confirmButtonText: 'Save',
        preConfirm: () => {
            const birthday = document.getElementById('birthday').value.trim();

            if (!birthday) {
                Swal.showValidationMessage('Birthday is required!');
            }

            return { birthday };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { birthday } = result.value;
            document.getElementById('birthday_display').textContent = birthday;

            Swal.fire('Saved!', 'Your birthday has been updated.', 'success');
        }
    });
}


function editProfilePicture() {
    Swal.fire({
        title: 'Change Profile Picture',
        html: `
            <input type="file" id="profile_pic_input" class="swal2-input" accept="image/*">
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

            // Create a temporary URL to preview the uploaded image
            const reader = new FileReader();
            reader.onload = (e) => {
                document.getElementById('profile_picture_display').src = e.target.result;
            };
            reader.readAsDataURL(file);

            return { file };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { file } = result.value;
            
            Swal.fire('Uploaded!', 'Your profile picture has been updated.', 'success');
        }
    });
}
