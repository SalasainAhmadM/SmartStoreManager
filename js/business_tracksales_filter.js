
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
function fetchSalesByDate(date) {
  fetch("../endpoints/sales/filter_sales.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ date }),
  })
    .then((response) => response.json())
    .then((data) => {
      const tableBody = document.getElementById("salesLogTable").getElementsByTagName("tbody")[0];
      tableBody.innerHTML = "";

      // Formatter for PHP currency with desired format
      const currencyFormatter = new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 2,
      });

      // Fallback if no sales data exists
      if (!data.sales || data.sales.length === 0) {
        tableBody.innerHTML = `
          <tr>
            <td colspan="5" class="text-center">No Sales for ${formatDate(date)}</td>
          </tr>
        `;
        return;
      }

      // Populate the table with sales data
      data.sales.forEach((sale) => {
        tableBody.innerHTML += `
          <tr>
            <td>${sale.product_name}</td>
            <td>${sale.business_or_branch_name}</td>
            <td>${sale.quantity}</td>
            <td>${currencyFormatter.format(sale.total_sales)}</td>
            <td>${formatDateToMMDDYYYY(sale.date)}</td>
          </tr>
        `;
      });
    })
    .catch((error) => {
      console.error("Error fetching sales data:", error);
      Swal.fire("Error", "Failed to fetch sales data. Please try again later.", "error");
    });
}

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
      data.forEach(item => {
        if (item.branch_id) {
          const option = document.createElement('option');
          option.value = `branch_${item.branch_id}`;
          option.textContent = `${item.business_name} - ${item.branch_location}`;
          businessFilter.appendChild(option);
        } else {
          const option = document.createElement('option');
          option.value = `business_${item.business_id}`;
          option.textContent = item.business_name;
          businessFilter.appendChild(option);
        }
      });
    })
    .catch(error => console.error('Error:', error));
}

// Call on page load
document.addEventListener('DOMContentLoaded', populateBusinessFilter);

document.getElementById('businessFilter').addEventListener('change', applyFilters);
document.getElementById('periodFilter').addEventListener('change', applyFilters);

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
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(requestData),
  })
  .then(response => response.json())
  .then(data => {
    const tableBody = document.getElementById("salesLogTable").getElementsByTagName("tbody")[0];
    tableBody.innerHTML = '';

    // Formatter for currency
    const currencyFormatter = new Intl.NumberFormat('en-PH', {
      style: 'currency',
      currency: 'PHP',
      minimumFractionDigits: 2,
    });

    if (!data.sales || data.sales.length === 0) {
      tableBody.innerHTML = `
        <tr>
          <td colspan="5" class="text-center">
            ${date ? `No Sales for ${formatDate(date)}` : 'No sales found'}
          </td>
        </tr>
      `;
      return;
    }

    data.sales.forEach(sale => {
      tableBody.innerHTML += `
        <tr>
          <td>${sale.product_name}</td>
          <td>${sale.business_or_branch_name}</td>
          <td>${sale.quantity}</td>
          <td>${currencyFormatter.format(sale.total_sales)}</td>
          <td>${formatDate(sale.date)}</td>
        </tr>
      `;
    });
  })
  .catch(error => {
  });
}

// Initial load
applyFilters();
