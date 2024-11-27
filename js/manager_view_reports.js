document.getElementById('businessSelect').addEventListener('change', function() {
    var selectedBusiness = this.value;
    var salesReportPanel = document.getElementById('salesReportPanel');
    var reportTitle = document.getElementById('reportTitle');
    var salesReportBody = document.getElementById('salesReportBody');
    var totalSalesCell = document.getElementById('totalSalesCell');

    salesReportBody.innerHTML = '';
    totalSalesCell.textContent = '';

    if (selectedBusiness === 'A') {
        reportTitle.textContent = 'Sales Report for Business A';

        const salesData = [
            { date: '2024-11-01', product: 'Product 1', quantity: 10, price: 150, sales: 10 * 150 },
            { date: '2024-11-01', product: 'Product 2', quantity: 5, price: 100, sales: 5 * 100 },
            { date: '2024-11-02', product: 'Product 1', quantity: 8, price: 150, sales: 8 * 150 },
            { date: '2024-11-02', product: 'Product 2', quantity: 3, price: 100, sales: 3 * 100 }
        ];

        let totalSales = 0;
        salesData.forEach(sale => {
            salesReportBody.innerHTML += `
                <tr>
                    <td>${sale.date}</td>
                    <td>${sale.product} (Quantity: ${sale.quantity}, Price: ₱${sale.price.toLocaleString()})</td>
                    <td>₱${sale.sales.toLocaleString()}</td>
                </tr>
            `;
            totalSales += sale.sales;
        });

        totalSalesCell.textContent = `₱${totalSales.toLocaleString()}`;
    } else if (selectedBusiness === 'B') {
        reportTitle.textContent = 'Sales Report for Business B';
        
        const salesData = [
            { date: '2024-11-01', product: 'Product 3', quantity: 15, price: 200, sales: 15 * 200 },
            { date: '2024-11-01', product: 'Product 4', quantity: 7, price: 120, sales: 7 * 120 },
            { date: '2024-11-02', product: 'Product 3', quantity: 12, price: 200, sales: 12 * 200 },
            { date: '2024-11-02', product: 'Product 4', quantity: 5, price: 120, sales: 5 * 120 }
        ];

        let totalSales = 0;
        salesData.forEach(sale => {
            salesReportBody.innerHTML += `
                <tr>
                    <td>${sale.date}</td>
                    <td>${sale.product} (Quantity: ${sale.quantity}, Price: ₱${sale.price.toLocaleString()})</td>
                    <td>₱${sale.sales.toLocaleString()}</td>
                </tr>
            `;
            totalSales += sale.sales;
        });

        totalSalesCell.textContent = `₱${totalSales.toLocaleString()}`;
    }

    if (selectedBusiness) {
        salesReportPanel.classList.add('show');
    } else {
        salesReportPanel.classList.remove('show');
    }
});

// Print report functionality
document.getElementById('printReportBtn').addEventListener('click', function () {
    const reportTitle = document.getElementById('reportTitle').outerHTML;
    const salesTable = document.querySelector('#salesReportPanel table.table').outerHTML;

    const printWindow = window.open('', '', 'width=800,height=600');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Print Sales Report</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                }
                h4 {
                    text-align: center;
                    margin-bottom: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                table, th, td {
                    border: 1px solid black;
                }
                th, td {
                    padding: 10px;
                    text-align: left;
                }
                thead {
                    background-color: #333;
                    color: #fff;
                }
                tfoot {
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            ${reportTitle}
            ${salesTable}
        </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.print();
    printWindow.close();
});

