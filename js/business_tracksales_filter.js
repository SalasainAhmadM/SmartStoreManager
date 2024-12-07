// Reset Filter function
function resetFilter() {
  const rows = document.querySelectorAll("#salesLogTable tbody tr");
  rows.forEach((row) => {
    row.style.display = ""; // Show all rows
  });
  document.getElementById("saleSearchBar").value = ""; // Optional: reset search bar
}

// Convert date from YYYY-MM-DD to MM/DD/YYYY format
function formatDateToMMDDYYYY(date) {
  const dateObj = new Date(date);
  const month = String(dateObj.getMonth() + 1).padStart(2, "0"); // Month is zero-indexed
  const day = String(dateObj.getDate()).padStart(2, "0");
  const year = dateObj.getFullYear();
  return `${month}/${day}/${year}`;
}

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
      const selectedDate = result.value;
  
      // Convert selectedDate to Asia/Manila time and format it to MM/DD/YYYY
      const formattedSelectedDate = new Date(selectedDate + "T00:00:00")
        .toLocaleDateString("en-US", { timeZone: "Asia/Manila" });
  
      const rows = document.querySelectorAll("#salesLogTable tbody tr");
      let found = false;
  
      rows.forEach((row) => {
        const dateCell = row.cells[4].innerText.trim(); 
        if (dateCell === formattedSelectedDate) {
          row.style.display = ""; 
          found = true;
        } else {
          row.style.display = "none"; 
        }
      });
  
      if (!found) {
        Swal.fire({
          icon: "warning",
          title: "No Sales Found",
          text: `No sales found for ${formattedSelectedDate}.`,
        });
      }
    }
  });
  
});


document.addEventListener("DOMContentLoaded", () => {
  const today = getCurrentDateInManila(); // Get today's date in Manila timezone
  fetchSalesByDate(today);
});

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
      const currencyFormatter = new Intl.NumberFormat('en-PH', {
          style: 'currency',
          currency: 'PHP',
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
                  <td>${sale.date}</td>
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
  const manilaOffset = 8 * 60 * 60 * 1000;
  const manilaTime = new Date(now.getTime() + manilaOffset - now.getTimezoneOffset() * 60 * 1000);
  // Format the date as YYYY-MM-DD
  return manilaTime.toISOString().split("T")[0];
}

function formatDate(date) {
  const options = { year: "numeric", month: "2-digit", day: "2-digit" };
  return new Date(date).toLocaleDateString("en-US", options);
}
