function showBranchDetails(businessName, branches) {
    const totalSales = branches.reduce((sum, branch) => sum + branch.sales, 0);
    const totalExpenses = branches.reduce((sum, branch) => sum + branch.expenses, 0);

    let branchDetails = branches.map(branch => `
        <tr>
            <td>${branch.branch}</td>
            <td>₱${branch.sales.toLocaleString()}</td>
            <td>₱${branch.expenses.toLocaleString()}</td>
        </tr>
    `).join('');

    Swal.fire({
        title: businessName,
        html: `
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Sales (₱)</th>
                        <th>Expenses (₱)</th>
                    </tr>
                </thead>
                <tbody>
                    ${branchDetails}
                </tbody>
            </table>
            <hr>
            <p><b>Total Sales:</b> ₱${totalSales.toLocaleString()}</p>
            <p><b>Total Expenses:</b> ₱${totalExpenses.toLocaleString()}</p>
            <button class="swal2-print-btn" onclick='printBranchReport("${businessName}", ${JSON.stringify(branches)})'>
                <i class="fas fa-print me-2"></i> Print Report
            </button>
        `,
        width: '600px',
        showConfirmButton: false,
        allowOutsideClick: true
    });
}

function printBranchReport(businessName, branches) {
    const totalSales = branches.reduce((sum, branch) => sum + branch.sales, 0);
    const totalExpenses = branches.reduce((sum, branch) => sum + branch.expenses, 0);

    let reportContent = `
        <html>
        <head>
            <title>${businessName} Report</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                }
                h1 {
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
                    background-color: #f1f1f1;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <h1>${businessName} Branch Report</h1>
            <table>
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Sales (₱)</th>
                        <th>Expenses (₱)</th>
                    </tr>
                </thead>
                <tbody>
                    ${branches.map(branch => `
                        <tr>
                            <td>${branch.branch}</td>
                            <td>₱${branch.sales.toLocaleString()}</td>
                            <td>₱${branch.expenses.toLocaleString()}</td>
                        </tr>
                    `).join('')}
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td>₱${totalSales.toLocaleString()}</td>
                        <td>₱${totalExpenses.toLocaleString()}</td>
                    </tr>
                </tfoot>
            </table>
        </body>
        </html>
    `;

    let newWindow = window.open('', '_blank', 'width=800, height=600');
    if (newWindow) {
        newWindow.document.write(reportContent);
        newWindow.document.close();
        newWindow.print();
    } else {
        alert("Unable to open a new window. Please allow popups for this site.");
    }
}

