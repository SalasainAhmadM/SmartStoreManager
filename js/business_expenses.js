document.getElementById('businessRadio').addEventListener('click', function() {
    document.getElementById('businessPanel').style.display = 'block';
    document.getElementById('branchPanel').style.display = 'none';
});

document.getElementById('branchRadio').addEventListener('click', function() {
    document.getElementById('branchPanel').style.display = 'none'; // Hide branch panel when selected
    document.getElementById('businessPanel').style.display = 'none';
});

document.getElementById('businessSelect').addEventListener('change', function() {
    var businessName = this.value;
    document.getElementById('businessName').textContent = businessName;

    var expenses = businessName === 'A' ? [{
            description: 'Rent',
            amount: '$5000'
        },
        {
            description: 'Utilities',
            amount: '$300'
        }
    ] : businessName === 'B' ? [{
            description: 'Marketing',
            amount: '$2000'
        },
        {
            description: 'Salaries',
            amount: '$12000'
        }
    ] : [];

    var expensesList = document.getElementById('expensesList');
    expensesList.innerHTML = '';
    expenses.forEach(function(expense) {
        var row = document.createElement('tr');
        row.innerHTML = `<td>${expense.description}</td><td>${expense.amount}</td>`;
        expensesList.appendChild(row);
    });

    if (businessName) {
        document.getElementById('expensesPanel').classList.add('show');
    } else {
        document.getElementById('expensesPanel').classList.remove('show');
    }
});