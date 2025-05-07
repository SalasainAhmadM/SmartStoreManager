
// Convert date from YYYY-MM-DD to MM/DD/YYYY format
function formatDateToMMDDYYYY(date) {
  const dateObj = new Date(date);
  const month = String(dateObj.getMonth() + 1).padStart(2, "0"); // Month is zero-indexed
  const day = String(dateObj.getDate()).padStart(2, "0");
  const year = dateObj.getFullYear();
  return `${month}/${day}/${year}`;
}

let selectedDate = null;

// Event listener for the filter date button
document.getElementById("filterDateButton").addEventListener("click", function () {
  // Get today's date in Asia/Manila timezone
  const today = new Date().toLocaleDateString("en-CA", { timeZone: "Asia/Manila" });

  Swal.fire({
    title: "Select a Date",
    html: `
      <div class="mb-3">
        <label for="saleDate" class="form-label">Date</label>
        <input type="date" id="saleDate" class="form-control" value="${today}">
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: "Filter",
    cancelButtonText: "Cancel",
    preConfirm: () => {
      const selectedDate = document.getElementById("saleDate").value;
      if (!selectedDate) {
        Swal.showValidationMessage("Please select a valid date.");
        return null;
      }
      return selectedDate;
    },
  }).then((result) => {
    if (result.isConfirmed && result.value) {
      selectedDate = result.value;
      document.getElementById('periodFilter').value = 'day'; // Set period to 'day'
      applyFilters();
    }
  });
});

// Reset filter function
function resetFilter() {
  document.getElementById('businessFilter').value = 'all';
  document.getElementById('periodFilter').value = 'all';
  selectedDate = getCurrentDateInManila(); // Reset to today's date
  applyFilters();
}

// Function to fetch and display sales data by date
// function fetchSalesByDate(date) {
//   fetch("../endpoints/sales/filter_sales.php", {
//     method: "POST",
//     headers: {
//       "Content-Type": "application/json",
//     },
//     body: JSON.stringify({ date }),
//   })
//     .then((response) => response.json())
//     .then((data) => {
//       const tableBody = document.getElementById("salesLogTable").getElementsByTagName("tbody")[0];
//       tableBody.innerHTML = "";

//       // Formatter for PHP currency with desired format
//       const currencyFormatter = new Intl.NumberFormat("en-PH", {
//         style: "currency",
//         currency: "PHP",
//         minimumFractionDigits: 2,
//       });

//       // Fallback if no sales data exists
//       if (!data.sales || data.sales.length === 0) {
//         tableBody.innerHTML = `
//           <tr>
//             <td colspan="5" class="text-center">No Sales for ${formatDate(date)}</td>
//           </tr>
//         `;
//         return;
//       }

//       // Populate the table with sales data
//       data.sales.forEach((sale) => {
//         tableBody.innerHTML += `
//           <tr>
//             <td>${sale.product_name}</td>
//             <td>${sale.business_or_branch_name}</td>
//             <td>${sale.quantity}</td>
//             <td>${currencyFormatter.format(sale.total_sales)}</td>
//             <td>${formatDateToMMDDYYYY(sale.date)}</td>
//           </tr>
//         `;
//       });
//     })
//     .catch((error) => {
//       console.error("Error fetching sales data:", error);
//       Swal.fire("Error", "Failed to fetch sales data. Please try again later.", "error");
//     });
// }

// Utility to get the current date in Manila timezone
function getCurrentDateInManila() {
  const now = new Date();
  const manilaOffset = 8 * 60 * 60 * 1000; // Manila is UTC+8
  const manilaTime = new Date(now.getTime() + manilaOffset - now.getTimezoneOffset() * 60 * 1000);
  return manilaTime.toISOString().split("T")[0]; // Format as YYYY-MM-DD
}

// Format date to MM/DD/YYYY
function formatDate(date) {
  const dateObj = new Date(date);
  const month = String(dateObj.getMonth() + 1).padStart(2, "0");
  const day = String(dateObj.getDate()).padStart(2, "0");
  const year = dateObj.getFullYear();
  return `${month}/${day}/${year}`;
}

// On page load, fetch sales for today's date
document.addEventListener("DOMContentLoaded", () => {
  const today = getCurrentDateInManila(); // Get today's date in Manila timezone
  fetchSalesByDate(today);
  selectedDate = getCurrentDateInManila();
  populateBusinessFilter();
  applyFilters();
});

function populateBusinessFilter() {
  fetch('../endpoints/sales/get_businesses_and_branches.php')
    .then(response => response.json())
    .then(data => {
      const businessFilter = document.getElementById('businessFilter');
      businessFilter.innerHTML = '<option value="all">All Businesses</option>'; 

      data.forEach(business => {
        const { business_id, business_name, branches } = business;

        // Add the main business
        const businessOption = document.createElement('option');
        businessOption.value = `business_${business_id}`;
        businessOption.textContent = business_name;
        businessFilter.appendChild(businessOption);

        // Add its branches if any
        if (branches.length > 0) {
          branches.forEach(branch => {
            const branchOption = document.createElement('option');
            branchOption.value = `branch_${branch.branch_id}`;
            branchOption.textContent = `${business_name} - ${branch.branch_location}`;
            businessFilter.appendChild(branchOption);
          });
        }
      });
    })
    .catch(error => console.error('Error fetching businesses:', error));
}


document.getElementById('businessFilter').addEventListener('change', applyFilters);
document.getElementById('periodFilter').addEventListener('change', applyFilters);

let currentPage = 1;
const rowsPerPage = 20;
let salesData = []; // store all sales temporarily

function renderTablePage(page) {
  const tableBody = document.getElementById("salesLogTable").getElementsByTagName("tbody")[0];
  tableBody.innerHTML = '';

  const start = (page - 1) * rowsPerPage;
  const end = start + rowsPerPage;
  const pageSales = salesData.slice(start, end);

  if (pageSales.length === 0) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="5" class="text-center">No sales data available</td>
      </tr>
    `;
  } else {
    pageSales.forEach((sale) => {
      const showConfirmIcon = sale.unregistered === 1;
      const confirmIconHTML = showConfirmIcon
        ? `<i class="fas fa-check-circle text-success cursor-pointer ms-2" title="Confirm" onclick="confirmSale(${sale.sales_id})"></i>`
        : '';

      tableBody.innerHTML += `
        <tr>
          <td>${sale.product_name}</td>
          <td>${sale.business_or_branch_name}</td>
          <td>${sale.quantity}</td>
          <td>${new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2 }).format(sale.total_sales)}</td>
          <td>
            <div class="d-flex align-items-center">
              <span>${formatDateToMMDDYYYY(sale.date)}</span>${confirmIconHTML}
            </div>
          </td>
        </tr>
      `;
    });
  }

  // Update pagination buttons
  document.getElementById("prevPage").disabled = (currentPage === 1);
  document.getElementById("nextPage").disabled = (end >= salesData.length);

  // Update page info
  document.getElementById("pageInfo").innerText = `Page ${currentPage} of ${Math.ceil(salesData.length / rowsPerPage)}`;
}


// Modify fetch functions to use pagination
function fetchSalesByDate(date) {
  fetch("../endpoints/sales/filter_sales.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ date }),
  })
    .then((response) => response.json())
    .then((data) => {
      salesData = data.sales || [];
      currentPage = 1;
      renderTablePage(currentPage);
    })
    .catch((error) => {
      console.error("Error fetching sales data:", error);
      Swal.fire("Error", "Failed to fetch sales data. Please try again later.", "error");
    });
}

function applyFilters() {
  const business = document.getElementById('businessFilter').value;
  const period = document.getElementById('periodFilter').value;
  const date = selectedDate;

  const requestData = { business, period };
  if (period === 'day' && date) {
    requestData.date = date;
  }

  fetch('../endpoints/sales/filter_sales.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(requestData),
  })
  .then(response => response.json())
  .then(data => {
    salesData = data.sales || [];
    currentPage = 1;
    renderTablePage(currentPage);
  })
  .catch(error => {
    console.error("Error applying filters:", error);
  });
}

// Pagination Button Events
document.getElementById("prevPage").addEventListener("click", () => {
  if (currentPage > 1) {
    currentPage--;
    renderTablePage(currentPage);
  }
});

document.getElementById("nextPage").addEventListener("click", () => {
  if ((currentPage * rowsPerPage) < salesData.length) {
    currentPage++;
    renderTablePage(currentPage);
  }
});

// Initial load
applyFilters();


function confirmSale(salesId) {
  Swal.fire({
    title: 'Confirm Sale',
    text: 'Are you sure you want to mark this sale as registered?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, confirm it',
  }).then((result) => {
    if (result.isConfirmed) {
      fetch('../endpoints/sales/confirm_sale.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sales_id: salesId }),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            Swal.fire('Confirmed!', 'Sale has been updated.', 'success');
            fetchSalesByDate(); // Refresh the table
          } else {
            Swal.fire('Error', data.message || 'Failed to confirm sale.', 'error');
          }
        })
        .catch((err) => {
          console.error(err);
          Swal.fire('Error', 'Something went wrong.', 'error');
        });
    }
  });
}
