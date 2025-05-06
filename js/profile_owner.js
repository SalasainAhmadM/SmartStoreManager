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
    return fetch('../endpoints/profile/update_profile.php', {
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
            fetch('../endpoints/profile/update_profile.php', {
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

            fetch('../endpoints/profile/update_profile.php', {
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

function editPhone() {
    const phone = document.getElementById('phone_display').textContent.trim();

    Swal.fire({
        title: 'Edit Phone Number',
        html: `<input id="phone" class="swal2-input" value="${phone}" placeholder="Enter phone number">`,
        showCancelButton: true,
        confirmButtonText: 'Save',
        preConfirm: () => {
            const phone = document.getElementById('phone').value.trim();

            if (!phone || !/^\d+$/.test(phone)) {
                Swal.showValidationMessage('Please enter a valid phone number!');
            }

            return { phone };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { phone } = result.value;

            fetch('../endpoints/profile/update_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ field: 'contact_number', value: phone }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.status === 'success') {
                        document.getElementById('phone_display').textContent = phone;
                        Swal.fire('Saved!', 'Your phone number has been updated.', 'success');
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                })
                .catch(() => {
                    Swal.fire('Error!', 'Failed to update your phone number.', 'error');
                });
        }
    });
}

function editAddress() {
    Swal.fire({
        title: 'Edit Address',
        html: `
            <div class="mb-2 text-start">
                <label>Region <span style="color:red">*</span></label>
                <select id="region" class="form-control">
                    <option value="">Select Region</option>
                </select>
            </div>

            <div class="mb-2 text-start">
                <label>Province <span style="color:red">*</span></label>
                <select id="province" class="form-control">
                    <option value="">Select Province</option>
                </select>
            </div>

            <div class="mb-2 text-start">
                <label>City / Municipality <span style="color:red">*</span></label>
                <select id="city" class="form-control">
                    <option value="">Select City/Municipality</option>
                </select>
            </div>

            <div class="mb-2 text-start">
                <label>Barangay <span style="color:red">*</span></label>
                <select id="barangay" class="form-control">
                    <option value="">Select Barangay</option>
                </select>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Save',
        willOpen: () => {
            const regionSelect = document.getElementById('region');
            const provinceSelect = document.getElementById('province');
            const citySelect = document.getElementById('city');
            const barangaySelect = document.getElementById('barangay');

            // Load regions
            fetch('../json/refregion.json')
                .then(res => res.json())
                .then(data => {
                    data.RECORDS.forEach(region => {
                        const opt = document.createElement('option');
                        opt.value = region.regCode;
                        opt.textContent = region.regDesc;
                        regionSelect.appendChild(opt);
                    });
                });

            // Province
            regionSelect.addEventListener('change', () => {
                provinceSelect.innerHTML = '<option value="">Select Province</option>';
                citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

                fetch('../json/refprovince.json')
                    .then(res => res.json())
                    .then(data => {
                        data.RECORDS.filter(p => p.regCode === regionSelect.value)
                            .forEach(province => {
                                const opt = document.createElement('option');
                                opt.value = province.provCode;
                                opt.textContent = province.provDesc;
                                provinceSelect.appendChild(opt);
                            });
                    });
            });

            // City/Municipality
            provinceSelect.addEventListener('change', () => {
                citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

                fetch('../json/refcitymun.json')
                    .then(res => res.json())
                    .then(data => {
                        data.RECORDS.filter(c => c.provCode === provinceSelect.value)
                            .forEach(city => {
                                const opt = document.createElement('option');
                                opt.value = city.citymunCode;
                                opt.textContent = city.citymunDesc;
                                citySelect.appendChild(opt);
                            });
                    });
            });

            // Barangay
            citySelect.addEventListener('change', () => {
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

                fetch('../json/refbrgy.json')
                    .then(res => res.json())
                    .then(data => {
                        data.RECORDS.filter(b => b.citymunCode === citySelect.value)
                            .forEach(brgy => {
                                const opt = document.createElement('option');
                                opt.value = brgy.brgyDesc;
                                opt.textContent = brgy.brgyDesc;
                                barangaySelect.appendChild(opt);
                            });
                    });
            });
        },
        preConfirm: () => {
            const region = document.getElementById('region');
            const province = document.getElementById('province');
            const city = document.getElementById('city');
            const barangay = document.getElementById('barangay');

            if (!region.value || !province.value || !city.value || !barangay.value) {
                Swal.showValidationMessage('Please complete all address fields!');
                return false;
            }

            return {
                regionText: region.options[region.selectedIndex].text,
                provinceText: province.options[province.selectedIndex].text,
                cityText: city.options[city.selectedIndex].text,
                barangayText: barangay.options[barangay.selectedIndex].text,
            };
        }
    }).then(result => {
        if (result.isConfirmed && result.value) {
            const { barangayText, cityText, regionText } = result.value;

            // Update backend
            Promise.all([
                updateProfileField('barangay', barangayText),
                updateProfileField('city', cityText),
                updateProfileField('region', regionText),
            ]).then(() => {
                document.getElementById('address_display').innerHTML = `<b>${barangayText}, ${cityText}, ${regionText}, Philippines</b>`;
                Swal.fire('Saved!', 'Your address has been updated.', 'success');
            }).catch(() => {
                Swal.fire('Error!', 'Failed to update your address.', 'error');
            });
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

            fetch('../endpoints/profile/update_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ field: 'gender', value: gender }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.status === 'success') {
                        document.getElementById('gender_display').textContent = gender;
                        Swal.fire('Saved!', 'Your gender has been updated.', 'success');
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                })
                .catch(() => {
                    Swal.fire('Error!', 'Failed to update your gender.', 'error');
                });
        }
    });
}


function editAge() {
    const age = document.getElementById('age_display').textContent.trim();

    Swal.fire({
        title: 'Edit Age',
        html: `<input id="age" class="swal2-input" type="number" value="${age}" min="1">`,
        showCancelButton: true,
        confirmButtonText: 'Save',
        preConfirm: () => {
            const age = document.getElementById('age').value.trim();

            if (!age || age <= 0) {
                Swal.showValidationMessage('Please enter a valid age!');
            }

            return { age };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { age } = result.value;

            // Send the updated age to the server
            fetch('../endpoints/profile/update_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ field: 'age', value: age })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('age_display').textContent = age;
                    Swal.fire('Saved!', 'Your age has been updated.', 'success');
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error!', 'Failed to update age. Please try again later.', 'error');
            });
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

            // Send the updated birthday to the server
            fetch('../endpoints/profile/update_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ field: 'birthday', value: birthday })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('birthday_display').textContent = birthday;
                    Swal.fire('Saved!', 'Your birthday has been updated.', 'success');
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error!', 'Failed to update birthday. Please try again later.', 'error');
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
            fetch('../endpoints/profile/update_profile.php', {
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

