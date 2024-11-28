document.getElementById('businessSelect').addEventListener('change', function() {
    var selectedBusiness = this.value;
    var salesPanel = document.getElementById('salesPanel');
    var salesTitle = document.getElementById('salesTitle');
    var salesTableBody = document.getElementById('salesTableBody');

    salesTableBody.innerHTML = '';

    if (selectedBusiness === 'A') {
        salesTitle.textContent = 'Sales for Business A';
        // Example Sales Data for Business A
        salesTableBody.innerHTML = `
            <tr><td>Product 1</td><td>100</td><td>$1000</td></tr>
            <tr><td>Product 2</td><td>50</td><td>$500</td></tr>
        `;
    } else if (selectedBusiness === 'B') {
        salesTitle.textContent = 'Sales for Business B';
        // Example Sales Data for Business B
        salesTableBody.innerHTML = `
            <tr><td>Product 3</td><td>200</td><td>$2000</td></tr>
            <tr><td>Product 4</td><td>75</td><td>$750</td></tr>
        `;
    }

    if (selectedBusiness) {
        salesPanel.classList.add('show');
    } else {
        salesPanel.classList.remove('show');
    }
});

document.getElementById('addSaleBtn').addEventListener('click', function() {
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
        `,
        showCancelButton: true,
        confirmButtonText: 'Add Sale',
        cancelButtonText: 'Close',
        preConfirm: () => {
            const product = document.getElementById('productSelect').value;
            const quantity = document.getElementById('quantitySold').value;
            if (!product || !quantity) {
                Swal.showValidationMessage('Please fill in all fields');
                return false;
            }
            return {
                product,
                quantity
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const {
                product,
                quantity
            } = result.value;
            // Here you can add logic to save the sale and update the table
            Swal.fire('Sale Added!', `Product: ${product}, Quantity: ${quantity}`, 'success');
            // You can add the sale data to the table dynamically here
        }
    });
});