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
            <div class="form-group mb-3">
                <input type="text" id="first-name" class="form-control" placeholder="First Name" required>
            </div>
            <div class="form-group mb-3">
                <input type="text" id="middle-name" class="form-control" placeholder="Middle Name" required>
            </div>
            <div class="form-group mb-3">
                <input type="text" id="last-name" class="form-control" placeholder="Last Name" required>
            </div>
            <div class="form-group mb-3">
                <select id="gender" class="form-control" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group mb-3">
                <input type="number" id="age" class="form-control" placeholder="Age" min="18" max="100" required>
            </div>
            <div class="form-group mb-3">
                <input type="date" id="birthday" placeholder="Birthday"  class="form-control" required>
            </div>
            <div class="form-group mb-3">
                <input type="tel" id="contact-number" class="form-control" placeholder="Contact Number" required>
            </div>
            <div class="form-group mb-3">
                <input type="text" id="barangay" class="form-control" placeholder="Barangay" required>
            </div>
            <div class="form-group mb-3">
                <input type="text" id="city" class="form-control" placeholder="City" required>
            </div>
            <div class="form-group mb-3">
                <input type="text" id="region" class="form-control" placeholder="Region" required>
            </div>
            <div class="form-group mb-3">
                <input type="text" id="country" class="form-control" placeholder="Country" required>
            </div>
            <div class="form-group mb-3">
                <label for="profile-image">Profile Image (Optional)</label>
                <input type="file" id="profile-image" class="form-control" accept="image/*">
            </div>
            <div class="form-group mb-3">
                <label for="valid-id">Valid ID (Required)</label>
                <input type="file" id="valid-id" class="form-control" accept="image/*" required>
                <small class="text-muted">Upload a clear photo of your government-issued ID</small>
            </div>
        </form>
    `,
        focusConfirm: false,
        confirmButtonText: 'Save Profile',
        showCancelButton: true,
        cancelButtonText: 'Cancel',
        preConfirm: () => {
          // Validate form before submission
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
            city: document.getElementById('city').value.trim(),
            region: document.getElementById('region').value.trim(),
            country: document.getElementById('country').value.trim(),
            profileImage: document.getElementById('profile-image').files[0],
            validId: document.getElementById('valid-id').files[0]
          };
        }
      }).then((result) => {
        if (result.isConfirmed && result.value) {
          const formData = result.value;

          // Show loading indicator
          Swal.fire({
            title: 'Saving Profile...',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          // Prepare FormData for submission
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
          submitData.append('region', formData.region);
          submitData.append('country', formData.country);

          if (formData.profileImage) {
            submitData.append('profile_image', formData.profileImage);
          }
          submitData.append('valid_id', formData.validId);

          // Update your fetch request with better error handling
          fetch('./profile_details.php', {
            method: 'POST',
            body: submitData
          })
            .then(response => {
              if (!response.ok) {
                // If response is not OK, try to get error details
                return response.text().then(text => {
                  try {
                    // Try to parse as JSON first
                    const json = JSON.parse(text);
                    throw new Error(json.message || 'Server error');
                  } catch (e) {
                    // If not JSON, use raw text
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
                  // Clear URL parameters
                  const cleanUrl = window.location.origin + window.location.pathname;
                  window.history.replaceState({}, document.title, cleanUrl);

                  // Trigger login modal instead of redirecting
                  if (typeof showLoginModal === 'function') {
                    showLoginModal();
                  } else {
                    console.error('showLoginModal function is not defined');
                    // Fallback to redirect if login modal function doesn't exist
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