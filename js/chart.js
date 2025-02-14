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
            // Update button classes for dark theme
            button.className = 'btn btn-outline-light';
            button.style.cssText = `
                background-color: #343a40;
                color: #fff;
                border: 1px solid #6c757d;
                margin-right: 5px;
                padding: 8px 16px;
                border-radius: 5px;
                transition: all 0.3s ease;
            `;
            button.textContent = period;

            // Add hover effect
            button.onmouseover = () => {
                if (!button.classList.contains('active')) {
                    button.style.backgroundColor = '#495057';
                }
            };
            button.onmouseout = () => {
                if (!button.classList.contains('active')) {
                    button.style.backgroundColor = '#343a40';
                }
            };

            button.onclick = () => {
                // Remove active class and reset styles for all buttons
                buttonGroup.querySelectorAll('button').forEach(btn => {
                    btn.classList.remove('active');
                    btn.style.backgroundColor = '#343a40';
                    btn.style.color = '#fff';
                });
                // Add active class and update styles for clicked button
                button.classList.add('active');
                button.style.backgroundColor = '#6c757d';
                button.style.color = '#fff';
                updateChartWithPeriod(period.toLowerCase());
            };
            buttonGroup.appendChild(button);
        });

        // Insert button group before chart
        container.insertBefore(buttonGroup, container.firstChild);
    }

    // Initialize with daily view and set active state
    updateChartWithPeriod('daily');
    const firstButton = buttonGroup.querySelector('button');
    firstButton.classList.add('active');
    firstButton.style.backgroundColor = '#6c757d';
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

// Business Performance Comparison Chart
function updateBusinessPerformanceChart() {
    const ctx = document.getElementById('businessPerformanceChart').getContext('2d');
    
    // Calculate total sales and expenses for each business
    const businessPerformance = {};
    
    // Initialize business performance data from chartData
    for (const businessName in chartData) {
        businessPerformance[businessName] = {
            sales: 0,
            expenses: 0
        };
        
        // Sum up sales and expenses from all branches
        for (const branchLocation in chartData[businessName]) {
            const branchData = chartData[businessName][branchLocation];
            businessPerformance[businessName].sales += parseFloat(branchData.sales) || 0;
            businessPerformance[businessName].expenses += parseFloat(branchData.expenses) || 0;
        }
    }

    // Prepare data for chart
    const businesses = Object.keys(businessPerformance);
    const salesData = businesses.map(b => businessPerformance[b].sales);
    const expensesData = businesses.map(b => businessPerformance[b].expenses);

    // Create chart
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: businesses,
            datasets: [
                {
                    label: 'Sales (₱)',
                    data: salesData,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    barThickness: 30
                },
                {
                    label: 'Expenses (₱)',
                    data: expensesData,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1,
                    barThickness: 30
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',  // Make bars horizontal
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        display: true,
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                },
                y: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'start',
                    labels: {
                        boxWidth: 15,
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            return `${context.dataset.label}: ₱${value.toLocaleString()}`;
                        }
                    }
                }
            },
            layout: {
                padding: {
                    top: 20,
                    bottom: 20,
                    left: 20,
                    right: 20
                }
            }
        }
    });
}

// Revenue Contribution Chart
function updateRevenueContributionChart() {
    const ctx = document.getElementById('revenueContributionChart').getContext('2d');
    
    // Calculate total revenue for each business
    const revenueByBusiness = {};
    let totalRevenue = 0;
    
    dailyData.forEach(item => {
        if (!revenueByBusiness[item.business_name]) {
            revenueByBusiness[item.business_name] = 0;
        }
        const revenue = parseFloat(item.sales) || 0;
        revenueByBusiness[item.business_name] += revenue;
        totalRevenue += revenue;
    });

    // Calculate percentages and prepare data
    const businesses = Object.keys(revenueByBusiness);
    const percentages = businesses.map(b => 
        ((revenueByBusiness[b] / totalRevenue) * 100).toFixed(1)
    );

    // Create chart
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: businesses.map(b => `${b} (${percentages[businesses.indexOf(b)]}%)`),
            datasets: [{
                data: Object.values(revenueByBusiness),
                backgroundColor: [
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(54, 162, 235, 0.2)'
                ],
                borderColor: [
                    'rgb(75, 192, 192)',
                    'rgb(255, 99, 132)',
                    'rgb(153, 102, 255)',
                    'rgb(255, 206, 86)',
                    'rgb(54, 162, 235)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 20
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const percentage = ((value / totalRevenue) * 100).toFixed(1);
                            return `₱${value.toLocaleString()} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// Product Analysis Charts
function updateProductCharts() {
    // Sort products by revenue
    const sortedProducts = [...productData].sort((a, b) => b.revenue - a.revenue);
    const topProducts = sortedProducts.slice(0, 10);
    const lowProducts = [...sortedProducts].reverse().slice(0, 10);

    // Top Products Chart
    const topCtx = document.getElementById('topProductsChart').getContext('2d');
    new Chart(topCtx, {
        type: 'bar',
        data: {
            labels: topProducts.map(p => p.product_name),
            datasets: [{
                label: 'Revenue',
                data: topProducts.map(p => p.revenue),
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                },
                y: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            return `Revenue: ₱${value.toLocaleString()}`;
                        }
                    }
                }
            }
        }
    });

    // Low Performing Products Chart
    const lowCtx = document.getElementById('lowProductsChart').getContext('2d');
    new Chart(lowCtx, {
        type: 'bar',
        data: {
            labels: lowProducts.map(p => p.product_name),
            datasets: [{
                label: 'Revenue',
                data: lowProducts.map(p => p.revenue),
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                },
                y: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            return `Revenue: ₱${value.toLocaleString()}`;
                        }
                    }
                }
            }
        }
    });

    // Product Profitability Chart
    const profitCtx = document.getElementById('productProfitabilityChart').getContext('2d');
    new Chart(profitCtx, {
        type: 'scatter',
        data: {
            datasets: [{
                label: 'Products',
                data: productData.map(p => ({
                    x: p.revenue,
                    y: p.profit,
                    product: p.product_name,
                    business: p.business_name
                })),
                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1,
                pointRadius: 8,
                pointHoverRadius: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Revenue (₱)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Profit (₱)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const point = context.raw;
                            return [
                                `Product: ${point.product}`,
                                `Business: ${point.business}`,
                                `Revenue: ₱${point.x.toLocaleString()}`,
                                `Profit: ₱${point.y.toLocaleString()}`
                            ];
                        }
                    }
                }
            }
        }
    });
}

// Customer Demographics Chart
function updateDemographicsChart() {
    const ctx = document.getElementById('demographicsChart').getContext('2d');
    
    // Process data for visualization
    const locations = [...new Set(demographicsData.map(d => d.location))];
    const products = [...new Set(demographicsData.map(d => d.product_name))];
    
    // Create datasets
    const datasets = products.map(product => {
        const data = locations.map(location => {
            const entry = demographicsData.find(d => 
                d.location === location && d.product_name === product
            );
            return entry ? entry.total_revenue : 0;
        });
        
        return {
            label: product,
            data: data,
            backgroundColor: `hsla(${Math.random() * 360}, 70%, 50%, 0.2)`,
            borderColor: `hsla(${Math.random() * 360}, 70%, 50%, 1)`,
            borderWidth: 1
        };
    });

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: locations,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: true,
                    title: {
                        display: true,
                        text: 'Location'
                    }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Revenue (₱)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        font: {
                            size: 10
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            return `${context.dataset.label}: ₱${value.toLocaleString()}`;
                        }
                    }
                }
            }
        }
    });
}

// Trend Analysis Charts
function updateTrendCharts() {
    // Format month labels
    const formatMonth = (monthStr) => {
        const date = new Date(monthStr + '-01');
        return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    };

    // Seasonal Trends Chart
    const seasonalCtx = document.getElementById('seasonalTrendsChart').getContext('2d');
    new Chart(seasonalCtx, {
        type: 'line',
        data: {
            labels: trendData.map(d => formatMonth(d.month)),
            datasets: [
                {
                    label: 'Sales',
                    data: trendData.map(d => d.sales),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Expenses',
                    data: trendData.map(d => d.expenses),
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ₱' + context.raw.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Growth Rate Chart
    const growthCtx = document.getElementById('growthRateChart').getContext('2d');
    
    // Calculate percentage changes between months
    const growthRates = trendData.map((data, index) => {
        if (index === 0) return {
            month: data.month,
            sales: 0,
            expenses: 0,
            profit: 0
        };
        
        const prevMonth = trendData[index - 1];
        return {
            month: data.month,
            sales: ((data.sales - prevMonth.sales) / prevMonth.sales) * 100,
            expenses: ((data.expenses - prevMonth.expenses) / prevMonth.expenses) * 100,
            profit: ((data.profit - prevMonth.profit) / prevMonth.profit) * 100
        };
    });

    new Chart(growthCtx, {
        type: 'line',
        data: {
            labels: growthRates.map(d => formatMonth(d.month)),
            datasets: [
                {
                    label: 'Sales Growth',
                    data: growthRates.map(d => d.sales),
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.4,
                    fill: false
                },
                {
                    label: 'Expenses Growth',
                    data: growthRates.map(d => d.expenses),
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.4,
                    fill: false
                },
                {
                    label: 'Profit Growth',
                    data: growthRates.map(d => d.profit),
                    borderColor: 'rgb(153, 102, 255)',
                    tension: 0.4,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(1) + '%';
                        }
                    },
                    title: {
                        display: true,
                        text: 'Growth Rate (%)'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw.toFixed(1) + '%';
                        }
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
        selectedBusinessName = firstBusinessName;
        showBusinessData(firstBusinessName);
    }

    // Initialize comparison charts
    updateBusinessPerformanceChart();
    updateRevenueContributionChart();

    // Initialize product analysis charts
    updateProductCharts();

    // Initialize demographics chart
    updateDemographicsChart();

    // Initialize trend charts
    updateTrendCharts();
});