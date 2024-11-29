// Add the click event listener for the "Filter Date" button
document.querySelector(".btn-success").addEventListener("click", function () {
  Swal.fire({
    title: "Select a Date",
    html: `
            <input type="date" id="filterDate" class="swal2-input" />
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
  let rowsFound = false;

  tableRows.forEach((row) => {
    const dateColumn = row.children[0];
    if (dateColumn) {
      const rowDate = dateColumn.textContent.trim();
      if (rowDate === date) {
        row.style.display = "";
        rowsFound = true;
      } else {
        row.style.display = "none";
      }
    }
  });

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
  tableRows.forEach((row) => {
    row.style.display = ""; // Show all rows
  });
});
