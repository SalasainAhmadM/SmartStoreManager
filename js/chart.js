let chart;

function updateChart(businessName) {
    // Clear previous chart data
    const salesData = [];
    const expensesData = [];
    const branchNames = [];

    // Get rows under the clicked business name
    const rows = document.querySelectorAll('#btn_' + businessName + ' .branch_row');
    
    rows.forEach(row => {
        const branchName = row.querySelector('.branch_name').innerText;
        const sales = parseInt(row.querySelector('.sales').innerText, 10);
        const expenses = parseInt(row.querySelector('.expenses').innerText, 10);

        // Push data to respective arrays
        branchNames.push(branchName);
        salesData.push(sales);
        expensesData.push(expenses);
    });

    // Update chart data
    if (chart) {
        chart.data.labels = branchNames;
        chart.data.datasets[0].data = salesData;
        chart.data.datasets[1].data = expensesData;
        chart.update();
    }
}

// Initialize chart
function initializeChart() {
    const ctx = document.getElementById('chartCanvas').getContext('2d');
    
    chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [], // Branch names will go here
            datasets: [{
                label: 'Sales (₱)',
                data: [], // Sales data will go here
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            },
            {
                label: 'Expenses (₱)',
                data: [], // Expenses data will go here
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

window.onload = initializeChart;