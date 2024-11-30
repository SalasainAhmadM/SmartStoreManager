let financialChart = null;

// Predefine the chart
function initializeChart() {
    const ctx = document.getElementById('financialChart').getContext('2d');
    financialChart = new Chart(ctx, {
        type: 'bar',  // Example chart type
        data: {
            labels: ['Sales', 'Expenses'],
            datasets: [{
                label: '',
                data: [0, 0],
                backgroundColor: ['#ade38b', '#ee786a'],  // Colors for Sales and Expenses
                borderColor: ['#28a745', '#dc3545'],
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

// This function will be called when a business is clicked
function showBusinessData(businessName) {

    const data = businessData[businessName];

    // Update the chart data
    if (financialChart) {

        financialChart.data.datasets[0].label = businessName;
        financialChart.data.datasets[0].data = [data.sales, data.expenses];

        // Update the chart
        financialChart.update();
    }

    // Remove active class from all buttons
    document.querySelectorAll('.card').forEach(button => {
        button.classList.remove('active');
    });

    // Add active class to the clicked button
    const activeButton = document.querySelector(`button[data-business-name="${businessName}"]`);
    if (activeButton) {
        activeButton.classList.add('active');
    }
}


initializeChart();


window.onload = () => {
    const firstBusinessName = Object.keys(businessData)[0];  // Get the first business name
    showBusinessData(firstBusinessName);  // Display its data on the chart
};
