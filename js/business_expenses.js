
document.getElementById('businessRadio').addEventListener('click', function () {
    document.getElementById('businessPanel').style.display = 'block';
    document.getElementById('branchPanel').style.display = 'none';
});

document.getElementById('branchRadio').addEventListener('click', function () {
    document.getElementById('branchPanel').style.display = 'none'; // Hide branch panel when selected
    document.getElementById('businessPanel').style.display = 'none';
});

document.getElementById('businessSelect').addEventListener('change', function () {
    var businessName = this.value;
    document.getElementById('businessName').textContent = businessName;

    var expenses = businessName === 'A' ? [
        {
            description: 'Rent',
            amount: '$5000',
            type: 'Fixed Expense'
        },
        {
            description: 'Utilities',
            amount: '$300',
            type: 'Variable Expense'
        }
    ] : businessName === 'B' ? [
        {
            description: 'Marketing',
            amount: '$2000',
            type: 'Operating Expense'
        },
        {
            description: 'Salaries',
            amount: '$12000',
            type: 'Non-operating Expense'
        }
    ] : [];

    var expensesList = document.getElementById('expensesList');
    expensesList.innerHTML = '';
    expenses.forEach(function (expense) {
        var row = document.createElement('tr');
        row.setAttribute('type', expense.type); // Set the 'type' attribute
        row.innerHTML = `<td>${expense.type}</td>
                        <td>${expense.description}</td>
                        <td>${expense.amount}</td>
                        <td style="text-align:center;">
                            <a href="#" class="text-primary me-3"><i class="fas fa-edit"></i></a>
                            <a href="#" class="text-danger"><i class="fas fa-trash"></i></a>
                        </td>`;
        expensesList.appendChild(row);
    });

    if (businessName) {
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

