document.getElementById('branchRadio').checked = true;

document.getElementById('businessSelect').addEventListener('change', function() {
    var businessName = this.value;
    var branchSelect = document.getElementById('branchSelect');
    var branchGroup = document.getElementById('branchGroup');

    document.getElementById('expensesPanel').classList.remove('show');

    if (businessName) {
        branchGroup.style.display = 'block';

        // Populate branch dropdown based on selected business
        if (businessName === 'A') {
            branchSelect.innerHTML = `
                <option value="">Select Branch</option>
                <option value="Branch1">Branch 1</option>
                <option value="Branch2">Branch 2</option>
            `;
        } else if (businessName === 'B') {
            branchSelect.innerHTML = `
                <option value="">Select Branch</option>
                <option value="Branch3">Branch 3</option>
                <option value="Branch4">Branch 4</option>
            `;
        }
    } else {
        branchGroup.style.display = 'none';
        branchSelect.innerHTML = '<option value="">Select Branch</option>';
    }
});


document.getElementById('branchSelect').addEventListener('change', function() {
    var branchName = this.value;
    document.getElementById('branchName').textContent = branchName;

    var expenses = [];

    if (branchName === 'Branch1') {
        expenses = [{
                description: 'Rent',
                amount: '$3000'
            },
            {
                description: 'Utilities',
                amount: '$150'
            }
        ];
    } else if (branchName === 'Branch2') {
        expenses = [{
                description: 'Marketing',
                amount: '$1000'
            },
            {
                description: 'Salaries',
                amount: '$5000'
            }
        ];
    } else if (branchName === 'Branch3') {
        expenses = [{
                description: 'Rent',
                amount: '$2500'
            },
            {
                description: 'Utilities',
                amount: '$200'
            }
        ];
    } else if (branchName === 'Branch4') {
        expenses = [{
                description: 'Salaries',
                amount: '$6000'
            },
            {
                description: 'Supplies',
                amount: '$800'
            }
        ];
    }

    // Populate expenses table
    var expensesList = document.getElementById('expensesList');
    expensesList.innerHTML = '';
    expenses.forEach(function(expense) {
        var row = document.createElement('tr');
        row.innerHTML = `<td>${expense.description}</td><td>${expense.amount}</td>`;
        expensesList.appendChild(row);
    });

    if (branchName) {
        document.getElementById('expensesPanel').classList.add('show');
    } else {
        document.getElementById('expensesPanel').classList.remove('show');
    }
});