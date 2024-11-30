// Reset Filter function
function resetFilter() {
  const rows = document.querySelectorAll("#salesLogTable tbody tr");
  rows.forEach((row) => {
    row.style.display = "";
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

// Filter by Date function
document.getElementById("filterDateButton").addEventListener("click", function () {
  const today = new Date();
  const todayDay = String(today.getDate()).padStart(2, "0");
  const todayMonth = String(today.getMonth() + 1).padStart(2, "0");
  const todayYear = today.getFullYear();
  const todayFormatted = `${todayYear}-${todayMonth}-${todayDay}`;

  // Show SweetAlert with the date input field
  Swal.fire({
    title: "Select a Date",
    html: `
      <div class="mb-3">
        <label for="saleDate" class="form-label">Date</label>
        <input type="date" id="saleDate" class="form-control" value="${todayFormatted}">
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: "Filter",
    cancelButtonText: "Cancel",
    preConfirm: () => {
      const selectedDate = document.getElementById("saleDate").value;
      return selectedDate;
    },
  }).then((result) => {
    if (result.isConfirmed && result.value) {
      const selectedDate = result.value; // This is in YYYY-MM-DD format
      const formattedSelectedDate = formatDateToMMDDYYYY(selectedDate);
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
          text: "No sales found for the selected date.",
        });
      }
    }
  });
});
