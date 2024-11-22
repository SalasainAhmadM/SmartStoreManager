<?php
// main.php

require 'config.php';

// Use the $apiKey variable or other environment variables as needed
echo "The API key is: $apiKey";

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Random Businesses</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .business-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .business-name {
            font-weight: bold;
            font-size: 1.2em;
            color: #333;
        }

        .sales {
            color: #007BFF;
            font-size: 1.1em;
        }

        button {
            padding: 10px;
            background-color: #007BFF;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background-color: #0056b3;
        }

        .insights {
            margin-top: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <h1>Random Businesses</h1>

    <div class="business-card" data-business="TechSphere Solutions">
        <div class="business-name">TechSphere Solutions</div>
        <div class="sales">Annual Sales: $3,500,000</div>
        <button class="insights-btn">Get Insights</button>
        <div class="insights"></div>
    </div>

    <div class="business-card" data-business="Green Earth Grocery">
        <div class="business-name">Green Earth Grocery</div>
        <div class="sales">Annual Sales: $1,200,000</div>
        <button class="insights-btn">Get Insights</button>
        <div class="insights"></div>
    </div>

    <div class="business-card" data-business="UrbanFit Gym">
        <div class="business-name">UrbanFit Gym</div>
        <div class="sales">Annual Sales: $850,000</div>
        <button class="insights-btn">Get Insights</button>
        <div class="insights"></div>
    </div>

    <div class="business-card" data-business="Skyline Architects">
        <div class="business-name">Skyline Architects</div>
        <div class="sales">Annual Sales: $4,200,000</div>
        <button class="insights-btn">Get Insights</button>
        <div class="insights"></div>
    </div>

    <div class="business-card" data-business="Bright Minds Academy">
        <div class="business-name">Bright Minds Academy</div>
        <div class="sales">Annual Sales: $950,000</div>
        <button class="insights-btn">Get Insights</button>
        <div class="insights"></div>
    </div>

    <script>
        document.querySelectorAll('.insights-btn').forEach(button => {
            button.addEventListener('click', async function () {
                const businessCard = this.closest('.business-card');
                const businessName = businessCard.getAttribute('data-business');
                const insightsDiv = businessCard.querySelector('.insights');

                // Show loading feedback
                insightsDiv.textContent = 'Fetching insights...';

                try {
                    const response = await fetch('gpt.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `business=${encodeURIComponent(businessName)}`
                    });

                    if (!response.ok) {
                        throw new Error('Failed to fetch insights.');
                    }

                    const data = await response.json();

                    if (data.error) {
                        insightsDiv.textContent = `Error: ${data.error}`;
                    } else if (data.choices && data.choices[0]?.text) {
                        insightsDiv.textContent = data.choices[0].text.trim();
                    } else {
                        insightsDiv.textContent = 'No insights available.';
                    }
                } catch (error) {
                    insightsDiv.textContent = 'Error fetching insights. Please try again.';
                }
            });
        });
    </script>

</body>

</html>