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
                        // Formatter for currency
                        const currencyFormatter = new Intl.NumberFormat('en-PH', {
                            style: 'currency',
                            currency: 'PHP'
                        });

                        // Populate expenses table
                        data.data.forEach(expense => {
                            const row = document.createElement('tr');
                            row.setAttribute('data-expense-id', expense.id); // Add expense ID as a data attribute
                            row.innerHTML = `
                                <td>${expense.expense_type}</td>
                                <td>${expense.description}</td>
                                <td>${currencyFormatter.format(expense.amount)}</td>
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

function fetchExpenseTypes() {
    return fetch('../endpoints/expenses/get_expense_types.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                return data.types; // Return the list of types
            } else {
                throw new Error('Failed to fetch expense types');
            }
        });
}

document.getElementById('addExpenseBtn').addEventListener('click', async function () {
    let expenseTypesOptions = '<option value="">Select Expense Type</option>';
    
    try {
        const expenseTypes = await fetchExpenseTypes();
        expenseTypes.forEach(type => {
            expenseTypesOptions += `<option value="${type}">${type}</option>`;
        });
    } catch (error) {
        console.error('Error fetching expense types:', error);
        Swal.fire('Error', 'Failed to load expense types. Please try again.', 'error');
        return;
    }

    Swal.fire({
        title: 'Add Expenses',
        html: `
            <input type="text" id="expenseDescription" class="swal2-input" placeholder="Description">
            <input type="number" id="expenseAmount" class="swal2-input" placeholder="Amount">
            <select id="expenseType" class="swal2-input">
                ${expenseTypesOptions}
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

function handleEditExpense(row) {
    const expenseType = row.children[0].textContent;
    const description = row.children[1].textContent;
    const formattedAmount = row.children[2].textContent;

    // Remove currency formatting to get the plain numerical amount
    const amount = parseFloat(formattedAmount.replace(/[^0-9.-]+/g, ''));

    fetchExpenseTypes().then(expenseTypes => {
        let expenseTypesOptions = '';
        expenseTypes.forEach(type => {
            expenseTypesOptions += `<option value="${type}" ${type === expenseType ? 'selected' : ''}>${type}</option>`;
        });

        Swal.fire({
            title: 'Edit Expense',
            html: `
                <input type="text" id="editDescription" class="swal2-input" placeholder="Description" value="${description}">
                <input type="number" id="editAmount" class="swal2-input" placeholder="Amount" value="${amount}">
                <select id="editType" class="swal2-input">
                    ${expenseTypesOptions}
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
    }).catch(error => {
        Swal.fire('Error', 'Failed to load expense types. Please try again.', 'error');
        console.error('Error:', error);
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

document.getElementById("monthDropdownMenu").addEventListener("click", function (e) {
    const monthValue = e.target.getAttribute("data-value");
    if (monthValue) {
        const branchId = document.getElementById("branchSelect").value;
        const branchNameElement = document.getElementById("branchName");
        const selectedBranch = document.getElementById("branchSelect").selectedOptions[0].text;

        branchNameElement.textContent = selectedBranch;
        const expensesList = document.getElementById("expensesList");
        const expensesPanel = document.getElementById("expensesPanel");

        expensesList.innerHTML = ""; // Clear current list
        expensesPanel.classList.remove("show");

        if (branchId) {
            fetch(`../endpoints/expenses/fetch_expenses_branch.php?branch_id=${branchId}&month=${monthValue}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.data.length > 0) {
                            data.data.forEach(expense => {
                                const row = document.createElement("tr");
                                row.setAttribute("data-expense-id", expense.id);
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
                            const noExpensesRow = document.createElement("tr");
                            noExpensesRow.innerHTML = `
                                <td colspan="4" style="text-align: center;">No expenses found for the selected month</td>`;
                            expensesList.appendChild(noExpensesRow);
                        }
                        expensesPanel.classList.add("show");
                    } else {
                        console.error(data.message);
                    }
                })
                .catch(err => console.error("Error fetching expenses:", err));
        }
    }
});

document.getElementById("monthDropdownMenu").addEventListener("click", function (e) {
    const monthText = e.target.textContent;
    const currentYear = new Date().getFullYear();
    document.getElementById("currentMonthYear").textContent = `${monthText} ${currentYear}`;
});


function getCurrentDateInManila() {
    const now = new Date();
    const manilaOffset = 8 * 60 * 60 * 1000;
    const manilaTime = new Date(now.getTime() + manilaOffset - now.getTimezoneOffset() * 60 * 1000);
    // Format the date as YYYY-MM-DD
    return manilaTime.toISOString().split("T")[0];
  }
  
  function formatDate(date) {
    const options = { year: "numeric", month: "2-digit", day: "2-digit" };
    return new Date(date).toLocaleDateString("en-US", options);
  }