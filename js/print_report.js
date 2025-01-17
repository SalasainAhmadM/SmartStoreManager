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
