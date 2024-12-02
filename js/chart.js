

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