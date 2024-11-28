document.addEventListener('DOMContentLoaded', function () {
    const sortIcons = document.querySelectorAll('.table th button');

    sortIcons.forEach(icon => {
        icon.addEventListener('click', function () {
            const columnIndex = Array.from(icon.parentElement.parentElement.children).indexOf(icon.parentElement);
            const table = icon.closest('table');
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const isAscending = icon.classList.contains('asc');

            // Toggle sort order
            icon.classList.toggle('asc', !isAscending);
            icon.classList.toggle('desc', isAscending);

            // Sort the rows
            rows.sort((rowA, rowB) => {
                const cellA = rowA.cells[columnIndex].textContent.trim();
                const cellB = rowB.cells[columnIndex].textContent.trim();

                // Try to parse as date-time if the cell contains a date-time format
                const dateA = new Date(cellA);
                const dateB = new Date(cellB);

                // Check if the values are valid dates
                const isDate = !isNaN(dateA) && !isNaN(dateB);

                if (isDate) {
                    // Compare the dates
                    return isAscending ? dateA - dateB : dateB - dateA;
                }

                // Try to parse as numbers if possible (for non-date cells)
                const numA = parseFloat(cellA.replace(/[^0-9.-]+/g, ''));
                const numB = parseFloat(cellB.replace(/[^0-9.-]+/g, ''));

                const isNumeric = !isNaN(numA) && !isNaN(numB);

                if (isNumeric) {
                    return isAscending ? numA - numB : numB - numA;
                }

                // Handle string values (fallback)
                return isAscending
                    ? cellA.localeCompare(cellB)
                    : cellB.localeCompare(cellA);
            });

            // Append sorted rows back to the table
            rows.forEach(row => table.querySelector('tbody').appendChild(row));
        });
    });
});
