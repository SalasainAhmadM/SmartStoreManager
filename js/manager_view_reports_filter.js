// Function to get today's date in Asia/Manila timezone
function getManilaDate() {
  const now = new Date();
  const manilaTime = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
  const year = manilaTime.getFullYear();
  const month = String(manilaTime.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
  const day = String(manilaTime.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`; // Format: YYYY-MM-DD
}

// Add the click event listener for the "Filter Date" button
document.querySelector(".btn-success").addEventListener("click", function () {
  const today = getManilaDate(); // Get today's date in Asia/Manila timezone

  Swal.fire({
    title: "Select a Date",
    html: `
      <input type="date" id="filterDate" class="swal2-input" value="${today}" />
    `,
    confirmButtonText: "Filter",
    showCancelButton: true,
    cancelButtonText: "Cancel",
    preConfirm: () => {
      const selectedDate = document.getElementById("filterDate").value;
      if (!selectedDate) {
        Swal.showValidationMessage("Please select a date");
        return false;
      }
      return selectedDate;
    },
  }).then((result) => {
    if (result.isConfirmed) {
      const selectedDate = result.value;
      filterTableByDate(selectedDate);
    }
  });
});
// Function to filter the table by the selected date
function filterTableByDate(date) {
  const tableRows = document.querySelectorAll("#salesReportBody tr");
  let totalSales = 0;
  let rowsFound = false;

  tableRows.forEach((row) => {
    const dateColumn = row.querySelector("td:nth-child(4)"); // Date column is the 4th column
    if (dateColumn) {
      const rowDate = dateColumn.textContent.trim();
      if (rowDate === date) {
        row.style.display = ""; // Show the row
        rowsFound = true;

        // Calculate total sales for the filtered date
        const amountSoldColumn = row.querySelector("td:nth-child(2)"); // Amount Sold column is the 2nd column
        const revenueColumn = row.querySelector("td:nth-child(3)"); // Revenue column is the 3rd column
        if (amountSoldColumn && revenueColumn) {
          const amountSold = parseFloat(amountSoldColumn.textContent);
          const revenue = parseFloat(revenueColumn.textContent.replace('$', ''));
          totalSales += revenue; // Add to total sales
        }
      } else {
        row.style.display = "none"; // Hide the row
      }
    }
  });

  // Update the total sales for the filtered date
  document.getElementById("totalSalesCell").textContent = `$${totalSales.toFixed(2)}`;

  if (!rowsFound) {
    Swal.fire({
      icon: "warning",
      title: "No Sales Found",
      text: `No sales found for the selected date: ${date}`,
      confirmButtonText: "OK",
    });
  }
}

// Add the click event listener for the "Reset" button
document.getElementById("resetButton").addEventListener("click", function () {
  document.getElementById("productSearchInput").value = "";

  const tableRows = document.querySelectorAll("#salesReportBody tr");
  let totalSales = 0;

  tableRows.forEach((row) => {
    row.style.display = ""; // Show all rows

    // Calculate total sales for all rows
    const amountSoldColumn = row.querySelector("td:nth-child(2)"); // Amount Sold column is the 2nd column
    const revenueColumn = row.querySelector("td:nth-child(3)"); // Revenue column is the 3rd column
    if (amountSoldColumn && revenueColumn) {
      const amountSold = parseFloat(amountSoldColumn.textContent);
      const revenue = parseFloat(revenueColumn.textContent.replace('$', ''));
      totalSales += revenue; // Add to total sales
    }
  });

  // Update the total sales for all rows
  document.getElementById("totalSalesCell").textContent = `$${totalSales.toFixed(2)}`;
});