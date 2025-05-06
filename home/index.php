<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Store Manager</title>
  <link rel="icon" href="../assets/logo.png">

  <?php include '../components/head_cdn.php' ?>

</head>

<body>

  <div id="particles-js"></div>

  <?php include '../components/homepage_nav.php' ?>

  <div class="homepage">

    <div class="title text-center my-4 px-3">
      <p class="h6">WELCOME TO</p>
      <h1 class="display-4"><b>SMART STORE MANAGER</b></h1>
    </div>
    <p class="welcome-content text-center mx-auto px-3" style="max-width: 800px;">
      SmartStoreManager is your go-to solution for managing your business efficiently.
      Our platform offers a range of features designed to streamline operations and enhance productivity.
    </p>



    <div class="container-fluid page-body">
      <div class="row">
        <div class="col-md-12">
          <div class="row">
            <div class="col-md-6">
              <div class="row">
                <div class="col-md-12 card">
                  <h3 class="card-header"><i class="fa-solid fa-list-check"></i> Expense Management
                  </h3>
                  <p class="card-body">
                    Track and manage your business expenses with ease. Our system provides tools to
                    categorize and analyze spending, helping you stay on top of your finances.
                  </p>
                </div>
              </div>
              <div class="row">
                <div class="col-md-12 card">
                  <h3 class="card-header"><i class="fa-solid fa-chart-line"></i> Sales Tracking</h3>
                  <p class="card-body">
                    Monitor your sales performance with detailed reports and analytics. Understand
                    trends,
                    track sales metrics, and make data-driven decisions to boost your revenue.
                  </p>
                </div>
              </div>
              <div class="row">
                <div class="col-md-12 card">
                  <h3 class="card-header"><i class="fa-solid fa-chart-simple"></i> Valuable Insights
                  </h3>
                  <p class="card-body">
                    Gain insights into your business operations with our advanced reporting tools.
                    Get actionable insights to improve efficiency and drive growth.
                  </p>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <img src="../assets/ww.png" class="homepage_img">
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <?php include '../components/footer.php' ?>
  <script>
    // Check for verification status in URL
    document.addEventListener('DOMContentLoaded', function () {
      const urlParams = new URLSearchParams(window.location.search);
      const verificationStatus = urlParams.get('verification');
      const ownerId = urlParams.get('id');

      if (verificationStatus === 'success' && ownerId) {
        // Store owner ID in sessionStorage
        sessionStorage.setItem('ownerId', ownerId);

        Swal.fire({
          title: 'Success!',
          text: 'Your email has been verified. Please complete your profile.',
          icon: 'success',
          confirmButtonText: 'Continue',
          allowOutsideClick: false
        }).then((result) => {
          if (result.isConfirmed) {
            showOwnerProfileForm();
          }
        });
      } else if (verificationStatus === 'invalid') {
        Swal.fire({
          title: 'Error!',
          text: 'Invalid or expired verification link.',
          icon: 'error',
          confirmButtonText: 'OK'
        });
      }
    });

    function showOwnerProfileForm() {
      const ownerId = sessionStorage.getItem('ownerId');

      if (!ownerId) {
        Swal.fire({
          title: 'Error!',
          text: 'Session expired. Please log in again.',
          icon: 'error',
          confirmButtonText: 'OK'
        }).then(() => {
          window.location.href = 'index.php';
        });
        return;
      }

      Swal.fire({
        title: 'Complete Your Profile',
        html: `
    <form id="profileForm">
    <div class="mb-3 text-center">
        <label for="first-name" class="form-label">First Name <span style="color:red">*</span></label>
        <input type="text" id="first-name" placeholder="First Name" class="form-control" required>
    </div>
    <div class="mb-3 text-center">
        <label for="middle-name" class="form-label">Middle Name <span style="color:red">*</span></label>
        <input type="text" id="middle-name" placeholder="Middle Name" class="form-control" required>
    </div>
    <div class="mb-3 text-center">
        <label for="last-name" class="form-label">Last Name <span style="color:red">*</span></label>
        <input type="text" id="last-name" placeholder="Last Name" class="form-control" required>
    </div>
    <div class="mb-3 text-center">
        <label for="gender" class="form-label">Gender <span style="color:red">*</span></label>
        <select id="gender" class="form-control" required>
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
        </select>
    </div>
    <div class="mb-3 text-center">
        <label for="age" class="form-label">Age <span style="color:red">*</span></label>
        <input type="number" id="age" placeholder="Age" class="form-control" min="18" max="100" required>
    </div>
    <div class="mb-3 text-center">
        <label for="birthday" class="form-label">Birthday <span style="color:red">*</span></label>
        <input type="date" id="birthday" class="form-control" required>
    </div>
    <div class="mb-3 text-center">
        <label for="contact-number" class="form-label">Contact Number <span style="color:red">*</span></label>
        <input type="tel" id="contact-number" placeholder="Contact Number" class="form-control" required>
    </div>

    <!-- Dynamic Address Fields -->
    <div class="mb-3 text-center">
  <label for="country" class="form-label">Country <span style="color:red">*</span></label>
  <input type="text" id="country" value="Philippines" class="form-control" required>
</div>

     <div class="mb-2">
    <label>Region <span style="color:red">*</span></label>
    <select id="region" class="form-control">
        <option value="">Select Region</option>
    </select>
</div>

<div class="mb-2">
    <label>Province <span style="color:red">*</span></label>
    <select id="province" class="form-control">
        <option value="">Select Province</option>
    </select>
</div>

<div class="mb-2">
    <label>City / Municipality <span style="color:red">*</span></label>
    <select id="city" class="form-control">
        <option value="">Select City/Municipality</option>
    </select>
</div>

<div class="mb-2">
    <label>Barangay <span style="color:red">*</span></label>
    <select id="barangay" class="form-control">
        <option value="">Select Barangay</option>
    </select>
</div>


    <div class="mb-3 text-center">
        <label for="profile-image" class="form-label">Profile Image (Optional)</label>
        <input type="file" id="profile-image" class="form-control" accept="image/*">
    </div>

    <div class="mb-3 text-center">
        <label for="valid-id" class="form-label">Valid ID <span style="color:red">*</span></label>
        <input type="file" id="valid-id" class="form-control" accept="image/*" required>
        <small class="text-muted">Upload a clear photo of your government-issued ID</small>
    </div>

</form>

`
        ,
        focusConfirm: false,
        confirmButtonText: 'Save Profile',
        showCancelButton: true,
        cancelButtonText: 'Cancel',
        didOpen: () => {
          // Fetch and populate region dropdown
          fetch('../json/refregion.json')
            .then(res => res.json())
            .then(data => {
              const regionSelect = document.getElementById('region');
              data.RECORDS.forEach(region => {
                const opt = document.createElement('option');
                opt.value = region.regCode;
                opt.textContent = region.regDesc;
                regionSelect.appendChild(opt);
              });
            });

          // Handle region → province
          document.getElementById('region').addEventListener('change', function () {
            const regCode = this.value;
            const provinceSelect = document.getElementById('province');
            provinceSelect.innerHTML = '<option value="">Select Province</option>';
            document.getElementById('city').innerHTML = '<option value="">Select City/Municipality</option>';
            document.getElementById('barangay').innerHTML = '<option value="">Select Barangay</option>';

            fetch('../json/refprovince.json')
              .then(res => res.json())
              .then(data => {
                data.RECORDS.filter(p => p.regCode === regCode).forEach(province => {
                  const opt = document.createElement('option');
                  opt.value = province.provCode;
                  opt.textContent = province.provDesc;
                  provinceSelect.appendChild(opt);
                });
              });
          });

          // Handle province → city
          document.getElementById('province').addEventListener('change', function () {
            const provCode = this.value;
            const citySelect = document.getElementById('city');
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
            document.getElementById('barangay').innerHTML = '<option value="">Select Barangay</option>';

            fetch('../json/refcitymun.json')
              .then(res => res.json())
              .then(data => {
                data.RECORDS.filter(c => c.provCode === provCode).forEach(city => {
                  const opt = document.createElement('option');
                  opt.value = city.citymunCode;
                  opt.textContent = city.citymunDesc;
                  citySelect.appendChild(opt);
                });
              });
          });

          // Handle city → barangay
          document.getElementById('city').addEventListener('change', function () {
            const cityCode = this.value;
            const brgySelect = document.getElementById('barangay');
            brgySelect.innerHTML = '<option value="">Select Barangay</option>';

            fetch('../json/refbrgy.json')
              .then(res => res.json())
              .then(data => {
                data.RECORDS.filter(b => b.citymunCode === cityCode).forEach(brgy => {
                  const opt = document.createElement('option');
                  opt.value = brgy.brgyDesc;
                  opt.textContent = brgy.brgyDesc;
                  brgySelect.appendChild(opt);
                });
              });
          });
        },
        preConfirm: () => {
          const form = document.getElementById('profileForm');
          if (!form.checkValidity()) {
            Swal.showValidationMessage('Please fill all required fields correctly');
            return false;
          }

          return {
            firstName: document.getElementById('first-name').value.trim(),
            middleName: document.getElementById('middle-name').value.trim(),
            lastName: document.getElementById('last-name').value.trim(),
            gender: document.getElementById('gender').value,
            age: document.getElementById('age').value,
            birthday: document.getElementById('birthday').value,
            contactNumber: document.getElementById('contact-number').value.trim(),
            barangay: document.getElementById('barangay').value.trim(),
            city: document.getElementById('city').options[document.getElementById('city').selectedIndex].text,
            province: document.getElementById('province').options[document.getElementById('province').selectedIndex].text,
            region: document.getElementById('region').options[document.getElementById('region').selectedIndex].text,
            country: document.getElementById('country').value.trim(),
            profileImage: document.getElementById('profile-image').files[0],
            validId: document.getElementById('valid-id').files[0]
          };
        }
      }).then((result) => {
        if (result.isConfirmed && result.value) {
          const formData = result.value;

          Swal.fire({
            title: 'Saving Profile...',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          const submitData = new FormData();
          submitData.append('action', 'complete_profile');
          submitData.append('owner_id', ownerId);
          submitData.append('first_name', formData.firstName);
          submitData.append('middle_name', formData.middleName);
          submitData.append('last_name', formData.lastName);
          submitData.append('gender', formData.gender);
          submitData.append('age', formData.age);
          submitData.append('birthday', formData.birthday);
          submitData.append('contact_number', formData.contactNumber);
          submitData.append('barangay', formData.barangay);
          submitData.append('city', formData.city);
          submitData.append('province', formData.province);
          submitData.append('region', formData.region);
          submitData.append('country', formData.country);
          if (formData.profileImage) submitData.append('profile_image', formData.profileImage);
          submitData.append('valid_id', formData.validId);

          fetch('./profile_details.php', {
            method: 'POST',
            body: submitData
          })
            .then(response => {
              if (!response.ok) {
                return response.text().then(text => {
                  try {
                    const json = JSON.parse(text);
                    throw new Error(json.message || 'Server error');
                  } catch {
                    throw new Error(text || 'Network response was not ok');
                  }
                });
              }
              return response.json();
            })
            .then(data => {
              Swal.close();
              if (data.status === 'success') {
                sessionStorage.removeItem('ownerId');
                Swal.fire({
                  title: 'Success!',
                  text: data.message || 'Profile completed successfully! Please log in to continue.',
                  icon: 'success',
                  confirmButtonText: 'Login Now'
                }).then(() => {
                  const cleanUrl = window.location.origin + window.location.pathname;
                  window.history.replaceState({}, document.title, cleanUrl);
                  if (typeof showLoginModal === 'function') {
                    showLoginModal();
                  } else {
                    window.location.href = 'index.php';
                  }
                });
              } else {
                throw new Error(data.message || 'Failed to save profile');
              }
            })
            .catch(error => {
              Swal.close();
              console.error('Error:', error);
              Swal.fire({
                title: 'Error!',
                text: error.message || 'An error occurred while saving your profile',
                icon: 'error',
                confirmButtonText: 'OK'
              });
            });
        }
      });
    }
  </script>
  <script src="../js/particle.js"></script>
</body>

</html>