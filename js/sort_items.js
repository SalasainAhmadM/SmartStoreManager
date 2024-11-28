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

                // Try to parse as numbers if possible
                const numA = parseFloat(cellA.replace(/[^0-9.-]+/g, ''));
                const numB = parseFloat(cellB.replace(/[^0-9.-]+/g, ''));

                // Handle numeric values
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
