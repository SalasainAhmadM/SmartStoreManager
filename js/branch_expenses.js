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
    
    // Default to the current month if not explicitly set
    const selectedMonth = document.getElementById("currentMonthYear").getAttribute("data-month-value") || (new Date().getMonth() + 1); 

    // Fetch branch expenses for the selected branch and month
    fetchBranchExpenses(branchId, selectedMonth);
});

function fetchBranchExpenses(branchId, selectedMonth = new Date().getMonth() + 1) {
    const expensesList = document.getElementById('expensesList');
    const expensesPanel = document.getElementById('expensesPanel');

    expensesList.innerHTML = '';
    expensesPanel.classList.remove('show');

    if (branchId) {
        fetch(`../endpoints/expenses/fetch_expenses_branch.php?branch_id=${branchId}&month=${selectedMonth}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.data.length > 0) {
                        const currencyFormatter = new Intl.NumberFormat('en-PH', {
                            style: 'currency',
                            currency: 'PHP'
                        });

                        data.data.forEach(expense => {
                            const row = document.createElement('tr');
                            row.setAttribute('data-expense-id', expense.id);
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
                        const row = document.createElement('tr');
                        row.innerHTML = `<td colspan="4" style="text-align: center;">No expenses found for the selected month</td>`;
                        expensesList.appendChild(row);
                    }
                    expensesPanel.classList.add('show');
                } else {
                    console.error(data.message);
                }
            })
            .catch(err => console.error('Error fetching expenses:', err));
    }
}



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
    <option value="1">January</option>
    <option value="2">February</option>
    <option value="3">March</option>
    <option value="4">April</option>
    <option value="5">May</option>
    <option value="6">June</option>
    <option value="7">July</option>
    <option value="8">August</option>
    <option value="9">September</option>
    <option value="10">October</option>
    <option value="11">November</option>
    <option value="12">December</option>
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
    const amount = parseFloat(formattedAmount.replace(/[^0-9.-]+/g, ''));
    const currentMonth = new Date().getMonth() + 1;

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
                <select id="editMonth" class="swal2-input">
                    ${Array.from({ length: 12 }, (_, i) => `<option value="${i + 1}" ${i + 1 === currentMonth ? 'selected' : ''}>${new Date(0, i).toLocaleString('default', { month: 'long' })}</option>`).join('')}
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
                const newMonth = document.getElementById('editMonth').value;

                if (!newDescription || !newAmount || !newType || !newMonth) {
                    Swal.showValidationMessage('Please fill out all fields');
                    return false;
                }

                return { newDescription, newAmount, newType, newMonth };
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
                        amount: result.value.newAmount,
                        month: result.value.newMonth
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

        // Set selected month to the dropdown's value
        document.getElementById("currentMonthYear").setAttribute("data-month-value", monthValue);

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
    const monthValue = e.target.getAttribute("data-value");
    const monthText = e.target.textContent;
    const currentYear = new Date().getFullYear();
    const branchId = document.getElementById("branchSelect").value;

    if (monthValue) {
        document.getElementById("currentMonthYear").textContent = `${monthText} ${currentYear}`;
        document.getElementById("currentMonthYear").setAttribute("data-month-value", monthValue);

        if (branchId) {
            fetchBranchExpenses(branchId, monthValue);
        }
    }
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