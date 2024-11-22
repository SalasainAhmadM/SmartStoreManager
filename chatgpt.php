<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPT Plugin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        #chat-container {
            max-width: 600px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        textarea {
            width: 100%;
            height: 100px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 10px;
        }

        button {
            padding: 10px 20px;
            background-color: #007BFF;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        .response {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div id="chat-container">
        <h2>Ask GPT</h2>
        <textarea id="user-input" placeholder="Type your question..."></textarea>
        <button id="send-btn">Send</button>
        <div id="response" class="response"></div>
    </div>

    <script>
        document.getElementById('send-btn').addEventListener('click', async () => {
            const userInput = document.getElementById('user-input').value;
            const responseDiv = document.getElementById('response');

            responseDiv.textContent = 'Loading...';

            const response = await fetch('gpt.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `input=${encodeURIComponent(userInput)}`
            });

            const data = await response.json();
            responseDiv.textContent = data.choices[0].text.trim();
        });
    </script>
</body>

</html>