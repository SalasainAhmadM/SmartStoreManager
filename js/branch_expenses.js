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
                type: 'Non-operating Expenses'
            },
            {
                description: 'Utilities',
                amount: '$150',
                type: 'Variable Expenses'
            }
        ];
    } else if (branchName === 'Branch2') {
        expenses = [{
                description: 'Marketing',
                amount: '$1000',
                type: 'Variable Expenses'
            },
            {
                description: 'Salaries',
                amount: '$5000',
                type: 'Non-operating Expenses'
            }
        ];
    } else if (branchName === 'Branch3') {
        expenses = [{
                description: 'Rent',
                amount: '$2500',
                type: 'Fixed Expenses'
            },
            {
                description: 'Utilities',
                amount: '$200',
                type: 'Fixed Expenses'
            }
        ];
    } else if (branchName === 'Branch4') {
        expenses = [{
                description: 'Salaries',
                amount: '$6000',
                type: 'Variable Expenses'
            },
            {
                description: 'Supplies',
                amount: '$800',
                type: 'Variable Expenses'
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
                        <td style="text-align:center;">
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
        title: 'Add Expenses',
        html: `
            <input type="text" id="expenseDescription" class="swal2-input" placeholder="Description">
            <input type="number" id="expenseAmount" class="swal2-input" placeholder="Amount">
            <select id="expenseType" class="swal2-input">
                <option value="Fixed Expense">Fixed Expenses</option>
                <option value="Variable Expense">Variable Expenses</option>
                <option value="Operating Expense">Operating Expenses</option>
                <option value="Non-operating Expense">Non-operating Expenses</option>
                <option value="Capital Expense">Capital Expenses</option>
            </select>
            <select id="expenseMonth" class="swal2-input">
                <option value="January">January</option>
                <option value="February">February</option>
                <option value="March">March</option>
                <option value="April">April</option>
                <option value="May">May</option>
                <option value="June">June</option>
                <option value="July">July</option>
                <option value="August">August</option>
                <option value="September">September</option>
                <option value="October">October</option>
                <option value="November">November</option>
                <option value="December">December</option>
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
            const month = document.getElementById('expenseMonth').value;

            if (!description || !amount || !type || !month) {
                Swal.showValidationMessage('Please fill out all fields');
                return false;
            }

            return { description, amount, type, month };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Added!', `Expense: ${result.value.description} added successfully for ${result.value.month}.`, 'success');
            // You can add AJAX code here to send the data to the server, including the month.
        }
    });
});

