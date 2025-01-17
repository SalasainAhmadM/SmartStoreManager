document.getElementById('branchSelect').addEventListener('change', function() {
    var selectedBusiness = this.value;
    var salesPanel = document.getElementById('salesPanel');
    var salesTitle = document.getElementById('salesTitle');
    var salesTableBody = document.getElementById('salesTableBody');

    salesTableBody.innerHTML = '';

    if (selectedBusiness === 'A') {
        salesTitle.textContent = 'Sales for Branch A';
        // Example Sales Data for Business A
        salesTableBody.innerHTML = `

            <tr>
            <td>Product 1</td>
            <td>₱150</td>
            <td>300</td>
            <td>₱45000</td>
            <td>2024-09-29 00:06:49</td>
            </tr>

            <tr>
            <td>Product 2</td>
            <td>₱100</td>
            <td>300</td>
            <td>₱30000</td>
            <td>2024-11-29 00:06:49</td>
            </tr>

        `;
    }

    if (selectedBusiness) {
        salesPanel.classList.add('show');
    } else {
        salesPanel.classList.remove('show');
    }
});

document.getElementById('addSaleBtn').addEventListener('click', function() {
    // Get today's date in YYYY-MM-DD format
    const today = new Date().toISOString().split('T')[0];

    // SweetAlert for adding a sale
    Swal.fire({
        title: 'Add New Sale',
        html: `
            <div class="mb-3">
                <label for="productSelect" class="form-label">Product</label>
                <select id="productSelect" class="form-select">
                    <option value="Product 1">Product 1</option>
                    <option value="Product 2">Product 2</option>
                    <option value="Product 3">Product 3</option>
                    <option value="Product 4">Product 4</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="quantitySold" class="form-label">Quantity Sold</label>
                <input type="number" id="quantitySold" class="form-control" placeholder="Enter quantity">
            </div>
            <div class="mb-3">
                <label for="saleDate" class="form-label">Date</label>
                <input type="date" id="saleDate" class="form-control" value="${today}">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Add Sale',
        cancelButtonText: 'Close',
        preConfirm: () => {
            const product = document.getElementById('productSelect').value;
            const quantity = document.getElementById('quantitySold').value;
            const date = document.getElementById('saleDate').value;
            if (!product || !quantity || !date) {
                Swal.showValidationMessage('Please fill in all fields');
                return false;
            }
            return {
                product,
                quantity,
                date
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const {
                product,
                quantity,
                date
            } = result.value;   
            Swal.fire('Sale Added!', `Product: ${product}, Quantity: ${quantity}, Date: ${date}`, 'success');
        }
    });
});
