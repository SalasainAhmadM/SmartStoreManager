
document.getElementById('businessRadio').addEventListener('click', function () {
    document.getElementById('businessPanel').style.display = 'block';
    document.getElementById('branchPanel').style.display = 'none';
});

document.getElementById('branchRadio').addEventListener('click', function () {
    document.getElementById('branchPanel').style.display = 'none'; 
    document.getElementById('businessPanel').style.display = 'none';
}); 

document.getElementById('businessSelect').addEventListener('change', function () {
    const businessId = this.value;
    const businessName = businesses[businessId]; 
    document.getElementById('businessName').textContent = businessName;

    if (businessId) {
        // Fetch expenses for the selected business
        fetch(`../endpoints/expenses/fetch_expenses_business.php?category_id=${businessId}`)
            .then(response => response.json())
            .then(data => {
                const expensesList = document.getElementById('expensesList');
                expensesList.innerHTML = ''; // Clear the current list

                if (!data.success) {
                    console.error(data.message);
                    return;
                }

                // Check if there are expenses
                if (data.data.length === 0) {
                    const noExpensesRow = document.createElement('tr');
                    noExpensesRow.innerHTML = `
                        <td colspan="4" style="text-align:center;">No expenses found</td>
                    `;
                    expensesList.appendChild(noExpensesRow);
                } else {
                    // Populate the table with fetched expenses
                    data.data.forEach(expense => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${expense.expense_type}</td>
                            <td>${expense.description}</td>
                            <td>${expense.amount}</td>
                            <td style="text-align:center;">
                                <a href="#" class="text-primary me-3"><i class="fas fa-edit"></i></a>
                                <a href="#" class="text-danger"><i class="fas fa-trash"></i></a>
                            </td>
                        `;
                        expensesList.appendChild(row);
                    });
                }
            })
            .catch(err => console.error('Error fetching expenses:', err));

        document.getElementById('expensesPanel').classList.add('show');
    } else {
        document.getElementById('expensesPanel').classList.remove('show');
    }
});



document.getElementById('addExpenseBtn').addEventListener('click', function () {
    Swal.fire({
        title: 'Add Business Expenses',
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
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Add Expense',
        cancelButtonText: 'Close',
        preConfirm: () => {
            const description = document.getElementById('expenseDescription').value.trim();
            const amount = document.getElementById('expenseAmount').value.trim();
            const type = document.getElementById('expenseType').value;

            if (!description || !amount || !type) {
                Swal.showValidationMessage('Please fill out all fields');
                return false;
            }

            return { description, amount, type };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const businessId = document.getElementById('businessSelect').value;
            fetch('../endpoints/expenses/add_expenses_business.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    category: 'business',
                    category_id: businessId,
                    expense_type: result.value.type,
                    amount: result.value.amount,
                    description: result.value.description,
                    user_id: ownerId 
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Added!', 'Expense added successfully.', 'success');
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'An unexpected error occurred.', 'error');
                console.error('Error:', err);
            });
            
        }
    });
});
