function filterProductsByMonth(selectedMonth) {
    const rows = document.querySelectorAll('#business-table-body tr'); // Get all table rows
    const year = new Date().getFullYear(); // Get the current year (or you can allow the user to select a year)

    rows.forEach(row => {
        const dateCell = row.querySelector('td:nth-child(5)'); // Get the "Created At" cell (5th column)
        const dateString = dateCell.textContent.trim(); // Get the date string (e.g., "2024-11-25 23:07:18")
        const date = new Date(dateString); // Convert to a Date object
        const rowMonth = date.getMonth() + 1; // Get the month (1-12)

        if (selectedMonth == 0 || rowMonth == selectedMonth) {
            // Show the row if "All Time" is selected or the row's month matches the selected month
            row.style.display = '';
        } else {
            // Hide the row if the month doesn't match
            row.style.display = 'none';
        }
    });
}

function populateYearDropdown() {
    const yearDropdown = document.getElementById('yearFilter');
    const rows = document.querySelectorAll('#business-table-body tr');
    const years = new Set(); // Use a Set to store unique years

    // Loop through each row and extract the year from the "created_at" column
    rows.forEach(row => {
        const dateCell = row.querySelector('td:nth-child(5)'); // 5th column is "created_at"
        const dateString = dateCell.textContent.trim(); // Get the date string (e.g., "2024-11-25 23:07:18")
        const year = new Date(dateString).getFullYear(); // Extract the year
        years.add(year); // Add the year to the Set
    });

    // Convert the Set to an array and sort it
    const sortedYears = Array.from(years).sort((a, b) => b - a); // Sort in descending order

    // Clear existing options
    yearDropdown.innerHTML = '<option value="0">All Years</option>';

    // Add the years to the dropdown
    sortedYears.forEach(year => {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        yearDropdown.appendChild(option);
    });
}

    // Call the function to populate the year dropdown when the page loads
    document.addEventListener('DOMContentLoaded', populateYearDropdown);

    function filterProductsByMonthAndYear() {
    const selectedMonth = document.getElementById('monthFilter').value;
    const selectedYear = document.getElementById('yearFilter').value;
    const rows = document.querySelectorAll('#business-table-body tr');

    rows.forEach(row => {
        const dateCell = row.querySelector('td:nth-child(5)'); // 5th column is "created_at"
        const dateString = dateCell.textContent.trim(); // Get the date string (e.g., "2024-11-25 23:07:18")
        const date = new Date(dateString);
        const rowMonth = date.getMonth() + 1; // Get the month (1-12)
        const rowYear = date.getFullYear(); // Get the year

        const monthMatch = selectedMonth == 0 || rowMonth == selectedMonth;
        const yearMatch = selectedYear == 0 || rowYear == selectedYear;

        if (monthMatch && yearMatch) {
            row.style.display = ''; // Show the row
        } else {
            row.style.display = 'none'; // Hide the row
        }
    });
}