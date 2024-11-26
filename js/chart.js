        // Financial data
        const labels = ['January', 'February', 'March', 'April', 'May', 'June'];
        const data = {
            labels: labels,
            datasets: [{
                    label: 'Sales',
                    data: [1200, 1500, 1100, 1800, 1700, 2100],
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                },
                {
                    label: 'Expenses',
                    data: [800, 700, 900, 1100, 950, 1200],
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1,
                },
            ],
        };

        // Chart configuration
        const config = {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Sales and Expenses'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        };

        // Render the chart
        const financialChart = new Chart(
            document.getElementById('financialChart'),
            config
        );