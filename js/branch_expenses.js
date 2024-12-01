document.getElementById('branchRadio').checked = true;

document.getElementById('businessSelect').addEventListener('change', function () {
    const businessId = this.value;
    const branchSelect = document.getElementById('branchSelect');
    const branchGroup = document.getElementById('branchGroup');
    branchGroup.style.display = 'none';
    branchSelect.innerHTML = '<option value="">Select Branch</option>';

    if (businessId) {
        fetch(`../endpoints/expenses/fetch_branches.php?business_id=${businessId}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error(data.message);
                    return;
                }

                if (data.data.length > 0) {
                    branchGroup.style.display = 'block';
                    branchSelect.innerHTML = '<option value="">Select Branch</option>';

                    // Populate branch dropdown
                    data.data.forEach(branch => {
                        const option = document.createElement('option');
                        option.value = branch.id; // Use branch.id as value
                        option.textContent = branch.location; // Display branch location
                        branchSelect.appendChild(option);
                    });
                }
            })
            .catch(err => console.error('Error fetching branches:', err));
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
            const branchId = document.getElementById('branchSelect').value; 
            const branchName = document.getElementById('branchSelect').selectedOptions[0].text; 
            const description = document.getElementById('expenseDescription').value;
            const amount = document.getElementById('expenseAmount').value;
            const type = document.getElementById('expenseType').value;
            const month = document.getElementById('expenseMonth').value;

            if (!branchId || !description || !amount || !type || !month) {
                Swal.showValidationMessage('Please fill out all fields');
                return false;
            }

            return { branchId, branchName, description, amount, type, month };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Send data to the backend
            fetch('../endpoints/expenses/add_expenses_branch.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    branch_id: result.value.branchId, 
                    branch_name: result.value.branchName, 
                    description: result.value.description,
                    amount: result.value.amount,
                    expense_type: result.value.type,
                    user_id: ownerId, 
                    month: result.value.month
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Added!', data.message, 'success');
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error!', 'An unexpected error occurred', 'error');
                    console.error('Error:', error);
                });
        }
    });
});


document.getElementById('branchSelect').addEventListener('change', function () {
    const branchId = this.value;
    const branchNameElement = document.getElementById('branchName');
    const selectedBranch = this.selectedOptions[0].text;

    branchNameElement.textContent = selectedBranch;
    const expensesList = document.getElementById('expensesList');
    const expensesPanel = document.getElementById('expensesPanel');

    expensesList.innerHTML = '';
    expensesPanel.classList.remove('show');

    if (branchId) {
        fetch(`../endpoints/expenses/fetch_expenses_branch.php?branch_id=${branchId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.data.length > 0) {
                        // Populate expenses table
                        data.data.forEach(expense => {
                            const row = document.createElement('tr');
                            row.setAttribute('data-expense-id', expense.id); // Add expense ID as a data attribute
                            row.innerHTML = `
                                <td>${expense.expense_type}</td>
                                <td>${expense.description}</td>
                                <td>${expense.amount}</td>
                                <td style="text-align:center;">
                                    <a href="#" class="text-primary me-3"><i class="fas fa-edit"></i></a>
                                    <a href="#" class="text-danger"><i class="fas fa-trash"></i></a>
                                </td>`;
                            expensesList.appendChild(row);
                        });
                        
                    } else {
                        // Add "No expenses found!" row
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td colspan="4" style="text-align: center;">No expenses found</td>`;
                        expensesList.appendChild(row);
                    }
                    expensesPanel.classList.add('show');
                } else {
                    console.error(data.message);
                }
            })
            .catch(err => console.error('Error fetching expenses:', err));
    }
});

// Add event listeners for edit and delete actions
document.getElementById('expensesList').addEventListener('click', function (e) {
    if (e.target.closest('.fa-edit')) {
        handleEditExpense(e.target.closest('tr'));
    } else if (e.target.closest('.fa-trash')) {
        handleDeleteExpense(e.target.closest('tr'));
    }
});

// Function to handle editing an expense
function handleEditExpense(row) {
    const expenseType = row.children[0].textContent;
    const description = row.children[1].textContent;
    const amount = row.children[2].textContent;

    Swal.fire({
        title: 'Edit Expense',
        html: `
            <input type="text" id="editDescription" class="swal2-input" placeholder="Description" value="${description}">
            <input type="number" id="editAmount" class="swal2-input" placeholder="Amount" value="${amount}">
            <select id="editType" class="swal2-input">
                <option value="Fixed Expense" ${expenseType === 'Fixed Expense' ? 'selected' : ''}>Fixed Expenses</option>
                <option value="Variable Expense" ${expenseType === 'Variable Expense' ? 'selected' : ''}>Variable Expenses</option>
                <option value="Operating Expense" ${expenseType === 'Operating Expense' ? 'selected' : ''}>Operating Expenses</option>
                <option value="Non-operating Expense" ${expenseType === 'Non-operating Expense' ? 'selected' : ''}>Non-operating Expenses</option>
                <option value="Capital Expense" ${expenseType === 'Capital Expense' ? 'selected' : ''}>Capital Expenses</option>
            </select>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Save Changes',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const newDescription = document.getElementById('editDescription').value.trim();
            const newAmount = document.getElementById('editAmount').value.trim();
            const newType = document.getElementById('editType').value;

            if (!newDescription || !newAmount || !newType) {
                Swal.showValidationMessage('Please fill out all fields');
                return false;
            }

            return { newDescription, newAmount, newType };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Assuming expense ID is stored in a data attribute
            const expenseId = row.dataset.expenseId;

            fetch('../endpoints/expenses/edit_expense_branch.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    expense_id: expenseId,
                    expense_type: result.value.newType,
                    description: result.value.newDescription,
                    amount: result.value.newAmount
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Updated!', 'Expense updated successfully.', 'success');
                    // Optionally, refresh the expenses list or update the row directly
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
}

// Function to handle deleting an expense
function handleDeleteExpense(row) {
    const expenseId = row.dataset.expenseId; // Assuming expense ID is stored in a data attribute

    Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('../endpoints/expenses/delete_expense_branch.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ expense_id: expenseId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Deleted!', 'Expense deleted successfully.', 'success');
                    // Optionally, remove the row from the table
                    row.remove();
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
}