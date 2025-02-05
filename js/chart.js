// Extract labels and data for the chart
var labels = [];
var salesData = [];
var expensesData = [];

// Loop through the processed data to extract labels (branch names) and the corresponding sales and expenses
for (var businessName in chartData) {
    if (chartData.hasOwnProperty(businessName)) {
        for (var branchLocation in chartData[businessName]) {
            if (chartData[businessName].hasOwnProperty(branchLocation)) {
                labels.push(branchLocation); // Use branch name as the label
                salesData.push(chartData[businessName][branchLocation].sales);
                expensesData.push(chartData[businessName][branchLocation].expenses);
            }
        }
    }
}

// Create the Chart.js chart
var ctx = document.getElementById('financialChart').getContext('2d');
var financialChart = new Chart(ctx, {
    type: 'bar', // Use bar chart for sales vs expenses
    data: {
        labels: labels, // Branch locations as labels
        datasets: [
            {
                label: 'Sales (₱)', // Sales data
                data: salesData,
                backgroundColor: 'rgba(75, 192, 192, 0.2)', // Light green for sales
                borderColor: 'rgba(75, 192, 192, 1)', // Dark green for sales
                borderWidth: 1
            },
            {
                label: 'Expenses (₱)', // Expenses data
                data: expensesData,
                backgroundColor: 'rgba(255, 99, 132, 0.2)', // Light red for expenses
                borderColor: 'rgba(255, 99, 132, 1)', // Dark red for expenses
                borderWidth: 1
            }
        ]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true, // Ensure the y-axis starts at zero
                ticks: {
                    callback: function(value) {
                        return '₱' + value.toLocaleString(); // Format the y-axis ticks as currency
                    }
                }
            }
        },
        responsive: true, // Make the chart responsive
        plugins: {
            legend: {
                position: 'top' // Position the legend at the top
            }
        }
    }
});

// This function will be called when a business is clicked
function showBusinessData(businessName) {
    const branches = chartData[businessName];

    // Update the chart data for the selected business's branches
    if (financialChart) {
        financialChart.data.labels = []; // Clear the labels
        financialChart.data.datasets[0].data = []; // Clear sales data
        financialChart.data.datasets[1].data = []; // Clear expenses data

        // Loop through the branches and add their sales and expenses to the chart
        for (var branchLocation in branches) {
            if (branches.hasOwnProperty(branchLocation)) {
                financialChart.data.labels.push(branchLocation);
                financialChart.data.datasets[0].data.push(branches[branchLocation].sales);
                financialChart.data.datasets[1].data.push(branches[branchLocation].expenses);
            }
        }

        // Update the chart
        financialChart.update();
    }

    // Remove active class from all divs (cards)
    document.querySelectorAll('.card').forEach(card => {
        card.classList.remove('active');
    });

    // Add active class to the clicked card
    const activeCard = document.querySelector(`div[data-business-name="${businessName}"]`);
    if (activeCard) {
        activeCard.classList.add('active');
    }
}

// Initialize the chart with the first business data
window.onload = () => {
    const firstBusinessName = Object.keys(chartData)[0];  // Get the first business name
    showBusinessData(firstBusinessName);  // Display its data on the chart
};

// Create the original financial chart
function createFinancialChart(chartData) {
    // ... existing chart code ...
}

// Sales vs Expenses Chart
function createSalesExpensesChart(dailyData) {
    const salesExpensesCtx = document.getElementById('salesExpensesChart').getContext('2d');
    let chart = null;

    // Function to group data by time period
    function groupDataBy(data, period) {
        const grouped = {};
        
        data.forEach(item => {
            let key;
            const date = new Date(item.date);
            
            switch(period) {
                case 'daily':
                    key = item.date;
                    break;
                case 'weekly':
                    // Get the Monday of the week
                    const day = date.getDay();
                    const diff = date.getDate() - day + (day === 0 ? -6 : 1);
                    key = new Date(date.setDate(diff)).toISOString().split('T')[0];
                    break;
                case 'monthly':
                    key = date.toISOString().slice(0, 7); // YYYY-MM format
                    break;
            }
            
            if (!grouped[key]) {
                grouped[key] = { sales: 0, expenses: 0 };
            }
            grouped[key].sales += item.sales;
            grouped[key].expenses += item.expenses;
        });
        
        return grouped;
    }

    // Function to update chart
    function updateChart(period) {
        const groupedData = groupDataBy(dailyData, period);
        const labels = Object.keys(groupedData);
        const sales = labels.map(key => groupedData[key].sales);
        const expenses = labels.map(key => groupedData[key].expenses);

        // Destroy existing chart if it exists
        if (chart) {
            chart.destroy();
        }

        // Create new chart
        chart = new Chart(salesExpensesCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Sales',
                    data: sales,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 1
                }, {
                    label: 'Expenses',
                    data: expenses,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgb(255, 99, 132)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Amount (₱)'
                        }
                    }
                }
            }
        });
    }

    // Create period selection buttons
    const container = document.getElementById('salesExpensesChart').parentElement;
    const buttonGroup = document.createElement('div');
    buttonGroup.className = 'btn-group mb-3';
    buttonGroup.style.marginBottom = '1rem';

    ['Daily', 'Weekly', 'Monthly'].forEach(period => {
        const button = document.createElement('button');
        button.className = 'btn btn-outline-primary';
        button.textContent = period;
        button.onclick = () => {
            // Remove active class from all buttons
            buttonGroup.querySelectorAll('button').forEach(btn => {
                btn.classList.remove('active');
            });
            // Add active class to clicked button
            button.classList.add('active');
            updateChart(period.toLowerCase());
        };
        buttonGroup.appendChild(button);
    });

    // Insert button group before chart
    container.insertBefore(buttonGroup, container.firstChild);

    // Initialize with daily view
    updateChart('daily');
    buttonGroup.querySelector('button').classList.add('active');
}

// Profit Margin Chart
function createProfitMarginChart(dailyData) {
    const profitMarginCtx = document.getElementById('profitMarginChart').getContext('2d');
    new Chart(profitMarginCtx, {
        type: 'bar',
        data: {
            labels: dailyData.map(d => d.date),
            datasets: [{
                label: 'Profit Margin (%)',
                data: dailyData.map(d => d.profit_margin),
                backgroundColor: 'rgba(153, 102, 255, 0.5)',
                borderColor: 'rgb(153, 102, 255)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Profit Margin (%)'
                    }
                }
            }
        }
    });
}

// Cash Flow Chart
function createCashFlowChart(monthlyData) {
    const cashFlowCtx = document.getElementById('cashFlowChart').getContext('2d');
    new Chart(cashFlowCtx, {
        type: 'line',
        data: {
            labels: monthlyData.map(d => d.month),
            datasets: [{
                label: 'Cash Inflow',
                data: monthlyData.map(d => d.inflow),
                fill: true,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }, {
                label: 'Cash Outflow',
                data: monthlyData.map(d => d.outflow),
                fill: true,
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgb(255, 99, 132)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount (₱)'
                    }
                }
            }
        }
    });
}

// Initialize all charts when the document is ready
document.addEventListener('DOMContentLoaded', function() {
    createFinancialChart(chartData);
    createSalesExpensesChart(dailyData);
    createProfitMarginChart(dailyData);
    createCashFlowChart(monthlyData);
});