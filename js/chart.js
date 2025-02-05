// Global variables to store chart instances
let financialChart = null;
let salesExpensesChart = null;
let profitMarginChart = null;
let cashFlowChart = null;
let selectedBusinessName = null;

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

// Initialize the financial chart
function initFinancialChart() {
    const ctx = document.getElementById('financialChart').getContext('2d');
    financialChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Sales (₱)',
                    data: salesData,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Expenses (₱)',
                    data: expensesData,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            },
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                }
            }
        }
    });
}

// Function to update all charts for a selected business
function showBusinessData(businessName) {
    selectedBusinessName = businessName; // Store the selected business name
    const branches = chartData[businessName];

    // Update financial chart
    if (financialChart) {
        financialChart.data.labels = [];
        financialChart.data.datasets[0].data = [];
        financialChart.data.datasets[1].data = [];

        for (var branchLocation in branches) {
            if (branches.hasOwnProperty(branchLocation)) {
                financialChart.data.labels.push(branchLocation);
                financialChart.data.datasets[0].data.push(branches[branchLocation].sales);
                financialChart.data.datasets[1].data.push(branches[branchLocation].expenses);
            }
        }
        financialChart.update();
    }

    // Filter data for selected business
    const filteredDailyData = dailyData.filter(item => item.business_name === businessName);
    const filteredMonthlyData = monthlyData.filter(item => item.business_name === businessName);

    // Update other charts
    updateSalesExpensesChart(filteredDailyData);
    updateProfitMarginChart(filteredDailyData);
    updateCashFlowChart(filteredMonthlyData);

    // Update UI
    document.querySelectorAll('.card').forEach(card => {
        card.classList.remove('active');
    });
    const activeCard = document.querySelector(`div[data-business-name="${businessName}"]`);
    if (activeCard) {
        activeCard.classList.add('active');
    }
}

// Sales vs Expenses Chart
function updateSalesExpensesChart(data) {
    const ctx = document.getElementById('salesExpensesChart').getContext('2d');
    
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
                    const monday = new Date(date);
                    monday.setDate(diff);
                    const sunday = new Date(monday);
                    sunday.setDate(monday.getDate() + 6);
                    key = `${monday.toISOString().split('T')[0]} to ${sunday.toISOString().split('T')[0]}`;
                    break;
                case 'monthly':
                    // Format as "Month YYYY"
                    key = date.toLocaleString('default', { month: 'long', year: 'numeric' });
                    break;
            }
            
            if (!grouped[key]) {
                grouped[key] = { sales: 0, expenses: 0 };
            }
            grouped[key].sales += parseFloat(item.sales) || 0;
            grouped[key].expenses += parseFloat(item.expenses) || 0;
        });

        // Sort the keys
        const sortedKeys = Object.keys(grouped).sort((a, b) => {
            if (period === 'daily') {
                return new Date(a) - new Date(b);
            } else if (period === 'weekly') {
                return new Date(a.split(' to ')[0]) - new Date(b.split(' to ')[0]);
            } else {
                // For monthly, convert back to date for sorting
                return new Date(a) - new Date(b);
            }
        });

        // Create a new sorted object
        const sortedGrouped = {};
        sortedKeys.forEach(key => {
            sortedGrouped[key] = grouped[key];
        });
        
        return sortedGrouped;
    }

    // Function to update chart with period data
    function updateChartWithPeriod(period) {
        // Make sure we're using the correct data for the selected business
        const currentData = selectedBusinessName ? 
            dailyData.filter(item => item.business_name === selectedBusinessName) : 
            data;

        const groupedData = groupDataBy(currentData, period);
        const labels = Object.keys(groupedData);
        const sales = labels.map(key => groupedData[key].sales);
        const expenses = labels.map(key => groupedData[key].expenses);

        if (salesExpensesChart) {
            salesExpensesChart.destroy();
        }

        salesExpensesChart = new Chart(ctx, {
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

    // Create or update period selection buttons
    const container = document.getElementById('salesExpensesChart').parentElement;
    let buttonGroup = container.querySelector('.btn-group');
    
    // Create button group if it doesn't exist
    if (!buttonGroup) {
        buttonGroup = document.createElement('div');
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
                updateChartWithPeriod(period.toLowerCase());
            };
            buttonGroup.appendChild(button);
        });

        // Insert button group before chart
        container.insertBefore(buttonGroup, container.firstChild);
    }

    // Initialize with daily view
    updateChartWithPeriod('daily');
    buttonGroup.querySelector('button').classList.add('active');
}

// Profit Margin Chart
function updateProfitMarginChart(data) {
    const ctx = document.getElementById('profitMarginChart').getContext('2d');
    
    if (profitMarginChart) {
        profitMarginChart.destroy();
    }

    profitMarginChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.date),
            datasets: [{
                label: 'Profit Margin (%)',
                data: data.map(d => d.profit_margin),
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
function updateCashFlowChart(data) {
    const ctx = document.getElementById('cashFlowChart').getContext('2d');
    
    if (cashFlowChart) {
        cashFlowChart.destroy();
    }

    cashFlowChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(d => d.month),
            datasets: [{
                label: 'Cash Inflow',
                data: data.map(d => d.inflow),
                fill: true,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }, {
                label: 'Cash Outflow',
                data: data.map(d => d.outflow),
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
    // Initialize the main financial chart
    initFinancialChart();
    
    // Get the first business name and show its data
    const firstBusinessName = Object.keys(chartData)[0];
    if (firstBusinessName) {
        selectedBusinessName = firstBusinessName; // Set initial selected business
        showBusinessData(firstBusinessName);
    }
});