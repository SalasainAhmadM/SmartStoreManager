
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

                // Formatter for currency
                const currencyFormatter = new Intl.NumberFormat('en-PH', {
                    style: 'currency',
                    currency: 'PHP'
                });

                // Check if there are expenses
                if (data.data.length === 0) {
                    const noExpensesRow = document.createElement('tr');
                    noExpensesRow.innerHTML = `
                        <td colspan="4" style="text-align:center;">No expenses found</td>
                    `;
                    expensesList.appendChild(noExpensesRow);
                } else {
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


// Add event listeners for edit and delete actions
document.getElementById('expensesList').addEventListener('click', function (e) {
    if (e.target.closest('.fa-edit')) {
        handleEditExpense(e.target.closest('tr'));
    } else if (e.target.closest('.fa-trash')) {
        handleDeleteExpense(e.target.closest('tr'));
    }
});

document.getElementById("monthDropdownMenu").addEventListener("click", function (e) {
    const monthValue = e.target.getAttribute("data-value");
    if (monthValue) {
        const businessId = document.getElementById("businessSelect").value;

        if (businessId) {
            fetch(`../endpoints/expenses/fetch_expenses_business.php?category_id=${businessId}&month=${monthValue}`)
                .then(response => response.json())
                .then(data => {
                    const expensesList = document.getElementById('expensesList');
                    expensesList.innerHTML = ''; 

                    if (!data.success) {
                        console.error(data.message);
                        return;
                    }

                    if (data.data.length === 0) {
                        const noExpensesRow = document.createElement('tr');
                        noExpensesRow.innerHTML = `
                            <td colspan="4" style="text-align:center;">No expenses found for the selected month</td>
                        `;
                        expensesList.appendChild(noExpensesRow);
                    } else {
                        data.data.forEach(expense => {
                            const row = document.createElement('tr');
                            row.setAttribute('data-expense-id', expense.id); 
                            row.setAttribute('data-month', expense.month); // Store the month for editing
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
        }
    }
});

document.getElementById("monthDropdownMenu").addEventListener("click", function (e) {
    const monthText = e.target.textContent;
    document.getElementById("currentMonthYear").textContent = `${monthText} ${new Date().getFullYear()}`;
});


function fetchExpenseTypes() {
    return fetch('../endpoints/expenses/get_expense_types.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                return data.types; // Return the list of expense types
            } else {
                throw new Error('Failed to fetch expense types');
            }
        })
        .catch(error => {
            console.error('Error fetching expense types:', error);
        });
}


// Function to handle editing an expense
function handleEditExpense(row) {
    const expenseType = row.children[0].textContent;
    const description = row.children[1].textContent;
    const formattedAmount = row.children[2].textContent;
    const currentMonth = row.dataset.month; 

    const amount = parseFloat(formattedAmount.replace(/[^0-9.-]+/g, ''));

    fetchExpenseTypes().then(expenseTypes => {
        const options = expenseTypes.map(type => 
            `<option value="${type}" ${type === expenseType ? 'selected' : ''}>${type}</option>`
        ).join('');

        const monthOptions = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ].map((month, index) => 
            `<option value="${index + 1}" ${parseInt(currentMonth, 10) === index + 1 ? 'selected' : ''}>${month}</option>`
        ).join('');

        Swal.fire({
            title: 'Edit Expense',
            html: `
                <input type="text" id="editDescription" class="swal2-input" placeholder="Description" value="${description}">
                <input type="number" id="editAmount" class="swal2-input" placeholder="Amount" value="${amount}">
                <select id="editType" class="swal2-input">
                    ${options}
                </select>
                <select id="editMonth" class="swal2-input">
                    ${monthOptions}
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
        }).then(result => {
            if (result.isConfirmed) {
                const expenseId = row.dataset.expenseId;

                fetch('../endpoints/expenses/edit_expense_business.php', {
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
    }).catch(err => {
        Swal.fire('Error', 'Failed to load expense types.', 'error');
        console.error('Error:', err);
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
            fetch('../endpoints/expenses/delete_expense_business.php', {
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

document.getElementById('addExpenseBtn').addEventListener('click', function () {
    // Get the current date in Asia/Manila timezone
    const currentDate = new Date();
    const options = { timeZone: 'Asia/Manila', month: 'numeric' };
    const currentMonth = new Intl.DateTimeFormat('en-US', options).format(currentDate);

    fetchExpenseTypes().then(expenseTypes => {
        // Add a default option and map the other options
        const options = `
            <option value="" selected>Select Expense Type</option>
            ${expenseTypes.map(type => `<option value="${type}">${type}</option>`).join('')}
        `;

        Swal.fire({
            title: 'Add Business Expenses',
            html: `
                <input type="text" id="expenseDescription" class="swal2-input" placeholder="Description">
                <input type="number" id="expenseAmount" class="swal2-input" placeholder="Amount">
                <select id="expenseType" class="swal2-input">
                    ${options}
                </select>
                <select id="expenseMonth" class="swal2-input">
                    <option value="1" ${currentMonth == 1 ? 'selected' : ''}>January</option>
                    <option value="2" ${currentMonth == 2 ? 'selected' : ''}>February</option>
                    <option value="3" ${currentMonth == 3 ? 'selected' : ''}>March</option>
                    <option value="4" ${currentMonth == 4 ? 'selected' : ''}>April</option>
                    <option value="5" ${currentMonth == 5 ? 'selected' : ''}>May</option>
                    <option value="6" ${currentMonth == 6 ? 'selected' : ''}>June</option>
                    <option value="7" ${currentMonth == 7 ? 'selected' : ''}>July</option>
                    <option value="8" ${currentMonth == 8 ? 'selected' : ''}>August</option>
                    <option value="9" ${currentMonth == 9 ? 'selected' : ''}>September</option>
                    <option value="10" ${currentMonth == 10 ? 'selected' : ''}>October</option>
                    <option value="11" ${currentMonth == 11 ? 'selected' : ''}>November</option>
                    <option value="12" ${currentMonth == 12 ? 'selected' : ''}>December</option>
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
                const month = document.getElementById('expenseMonth').value;

                if (!description || !amount || !type || !month) {
                    Swal.showValidationMessage('Please fill out all fields');
                    return false;
                }

                return { description, amount, type, month };
            }
        }).then(result => {
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
                        month: result.value.month,
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
    }).catch(err => {
        Swal.fire('Error', 'Failed to load expense types.', 'error');
        console.error('Error:', err);
    });
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