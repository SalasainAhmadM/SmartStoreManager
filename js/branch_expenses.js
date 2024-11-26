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
                amount: '$3000',
                type: 'Non-operating Expense'
            },
            {
                description: 'Utilities',
                amount: '$150',
                type: 'Variable Expense'
            }
        ];
    } else if (branchName === 'Branch2') {
        expenses = [{
                description: 'Marketing',
                amount: '$1000',
                type: 'Variable Expense'
            },
            {
                description: 'Salaries',
                amount: '$5000',
                type: 'Non-operating Expense'
            }
        ];
    } else if (branchName === 'Branch3') {
        expenses = [{
                description: 'Rent',
                amount: '$2500',
                type: 'Fixed Expense'
            },
            {
                description: 'Utilities',
                amount: '$200',
                type: 'Fixed Expense'
            }
        ];
    } else if (branchName === 'Branch4') {
        expenses = [{
                description: 'Salaries',
                amount: '$6000',
                type: 'Variable Expense'
            },
            {
                description: 'Supplies',
                amount: '$800',
                type: 'Variable Expense'
            }
        ];
    }

    // Populate expenses table
    var expensesList = document.getElementById('expensesList');
    expensesList.innerHTML = '';
    expenses.forEach(function(expense) {
        var row = document.createElement('tr');
        row.innerHTML = `<td>${expense.type}</td>
                        <td>${expense.description}</td>
                        <td>${expense.amount}</td>
                        <td>
                            <a href="#" class="text-primary me-3"><i class="fas fa-edit"></i></a>
                            <a href="#" class="text-danger"><i class="fas fa-trash"></i></a>
                        </td>`;
        expensesList.appendChild(row);
    });

    if (branchName) {
        document.getElementById('expensesPanel').classList.add('show');
    } else {
        document.getElementById('expensesPanel').classList.remove('show');
    }
});

document.getElementById('addExpenseBtn').addEventListener('click', function() {
    Swal.fire({
        title: 'Add Expense',
        html: `
            <input type="text" id="expenseDescription" class="swal2-input" placeholder="Description">
            <input type="number" id="expenseAmount" class="swal2-input" placeholder="Amount">
            <select id="expenseType" class="swal2-input">
                <option value="Fixed Expense">Fixed Expense</option>
                <option value="Variable Expense">Variable Expense</option>
                <option value="Operating Expense">Operating Expense</option>
                <option value="Non-operating Expense">Non-operating Expense</option>
                <option value="Capital Expense">Capital Expense</option>
            </select>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Add Expense',
        cancelButtonText: 'Close',
        preConfirm: () => {
            const description = document.getElementById('expenseDescription').value;
            const amount = document.getElementById('expenseAmount').value;
            const type = document.getElementById('expenseType').value;
            if (!description || !amount || !type) {
                Swal.showValidationMessage('Please fill out all fields');
                return false;
            }
            return { description, amount, type };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Added!', `Expense: ${result.value.description} added successfully.`, 'success');
            // You can add AJAX code here to send the data to the server.
        }
    });
});
