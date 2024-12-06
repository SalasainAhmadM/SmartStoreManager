// Extract labels and data for the initial state of the chart
var initialBusinessName = Object.keys(chartData)[0]; 
var initialLabels = [];
var initialSalesData = [];
var initialExpensesData = [];

// Initialize with the first business data
for (var branchLocation in chartData[initialBusinessName]) {
    if (chartData[initialBusinessName].hasOwnProperty(branchLocation)) {
        initialLabels.push(branchLocation); 
        initialSalesData.push(chartData[initialBusinessName][branchLocation].sales);
        initialExpensesData.push(chartData[initialBusinessName][branchLocation].expenses);
    }
}

// Create the Chart.js line chart
var ctx = document.getElementById('financialChart').getContext('2d');
var financialChart = new Chart(ctx, {
    type: 'line', // Use line chart for smooth trend lines
    data: {
        labels: initialLabels,
        datasets: [
            {
                label: 'Sales (₱)',
                data: initialSalesData,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)', 
                borderWidth: 2,
                tension: 0.4, 
                fill: true 
            },
            {
                label: 'Expenses (₱)',
                data: initialExpensesData,
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
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
        },
        animation: {
            duration: 1000, 
            easing: 'easeInOutCubic' 
        }
    }
});

// Function to update the chart progressively with new data
function updateChartProgressively(businessName) {
    const branches = chartData[businessName];

    const updatedLabels = [];
    const updatedSalesData = [];
    const updatedExpensesData = [];

    for (var branchLocation in branches) {
        if (branches.hasOwnProperty(branchLocation)) {
            updatedLabels.push(branchLocation);
            updatedSalesData.push(branches[branchLocation].sales);
            updatedExpensesData.push(branches[branchLocation].expenses);
        }
    }

    financialChart.data.labels = updatedLabels;
    financialChart.data.datasets[0].data = updatedSalesData;
    financialChart.data.datasets[1].data = updatedExpensesData;

    financialChart.update();

    document.querySelectorAll('.card').forEach(card => {
        card.classList.remove('active');
    });
    const activeCard = document.querySelector(`div[data-business-name="${businessName}"]`);
    if (activeCard) {
        activeCard.classList.add('active');
    }
}

document.querySelectorAll('.card').forEach(card => {
    card.addEventListener('click', function() {
        const businessName = this.getAttribute('data-business-name');
        updateChartProgressively(businessName);
    });
});


window.onload = () => {
    updateChartProgressively(initialBusinessName); // Show the first business data
};
