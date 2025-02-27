function printContent(tabId, title) {
  // Get the table from the specific tab section (business, branch, or product)
  var table = document.getElementById(tabId).getElementsByTagName('table')[0];
  
  // Check if any <th> element contains the word "Action"
  var headers = table.getElementsByTagName('th');
  var shouldDeleteLastColumn = false;
  for (var i = 0; i < headers.length; i++) {
      if (headers[i].textContent.includes('Action')) {
          shouldDeleteLastColumn = true;
          break;
      }
  }

  // If the condition is met, delete the last column
  if (shouldDeleteLastColumn) {
      var rows = table.rows;
      for (var i = 0; i < rows.length; i++) {
          rows[i].deleteCell(rows[i].cells.length - 1);
      }
  }

  // Get the modified table's outerHTML
  var content = table.outerHTML;

  // Create a new window to show the printable content
  var printWindow = window.open('', '', 'height=600,width=800');
  printWindow.document.write('<html><head><title>' + title + '</title></head><body>');
  printWindow.document.write('<style>');
  printWindow.document.write('body { font-family: Arial, sans-serif; margin: 20px; }');
  printWindow.document.write('table { width: 100%; border-collapse: collapse; margin: 20px 0; }');
  printWindow.document.write('th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }');
  printWindow.document.write('th {background-color: #333;color: #fff; font-weight: bold; }');
  printWindow.document.write('td { background-color: #fff; }');
  printWindow.document.write('h1 { text-align: center; }');
  printWindow.document.write('button, .btn, .fas.fa-sort {display: none;}');
  printWindow.document.write('ul {list-style-type: none; padding: 0}');
  printWindow.document.write('@media print {');
  printWindow.document.write('  body { width: 100%; padding: 0; }');
  printWindow.document.write('  th, td { font-size: 12px; }');
  printWindow.document.write('}');
  printWindow.document.write('</style>');
  printWindow.document.write('<h1>' + title + '</h1>');
  printWindow.document.write(content);
  printWindow.document.write('</body></html>');

  // Print the content after it's fully loaded
  printWindow.document.close();
  printWindow.print();

  // Refresh the page
  location.reload();
}






function printSingleLine(id, type) {
    // Find the row to print
    const element = document.querySelector(`tr[data-id="${id}"][data-type="${type}"]`);
    if (element) {
        // Open a new window for printing
        const printWindow = window.open('', '', 'height=500,width=800');
        printWindow.document.write('<html><head><title>Print</title>');
        printWindow.document.write('<style>');
        printWindow.document.write(`
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            table, th, td {
                border: 1px solid black;
            }
            th, td {
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
            }
            .text-center {
                text-align: center;
            }
            .print-button {
                display: none; /* Hide print buttons in the printed output */
            }
            h1 {
                text-align: center;
                margin-bottom: 20px;
            }
            button {
                display: none;
            }
        `);
        printWindow.document.write('</style>');
        printWindow.document.write('</head><body>');

        // Add a header to indicate the type of data (business or branch)
        const headerTitle = type === 'business' ? 'Business Data' : 'Branch Data';
        printWindow.document.write(`<h1>${headerTitle}</h1>`);

        // Create a table for printing
        printWindow.document.write('<table>');
        printWindow.document.write('<thead>');

        // Get the header row and remove <i> tags
        const headerRow = element.closest('table').querySelector('thead').outerHTML;
        const cleanedHeaderRow = headerRow.replace(/<i[^>]*>.*?<\/i>/gi, ''); // Remove <i> tags
        printWindow.document.write(cleanedHeaderRow.replace(/<th[^>]*>Action<\/th>/i, '')); // Remove "Action" header
        printWindow.document.write('</thead>');
        printWindow.document.write('<tbody>');

        // Clone the row and remove the last cell (Action column)
        const rowClone = element.cloneNode(true);
        rowClone.deleteCell(rowClone.cells.length - 1); // Remove the last cell
        printWindow.document.write(rowClone.outerHTML); // Add the modified row

        printWindow.document.write('</tbody>');
        printWindow.document.write('</table>');

        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    } else {
        alert('Element not found');
    }
}

// Add event listeners to all print buttons
document.addEventListener('DOMContentLoaded', function () {
    const printButtons = document.querySelectorAll('.print-button');

    printButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const type = this.getAttribute('data-type'); // Get the type (business or branch)
            printSingleLine(id, type);
        });
    });
});