document.getElementById("businessSelect").addEventListener("change", function () {
    const selectedBusiness = this.value;
    const salesDiv = document.getElementById("salesContainer");
    salesDiv.innerHTML = "";
  
    if (salesByBusiness[selectedBusiness]) {
        // Get today's date in Asia/Manila timezone
        const today = new Date().toLocaleDateString("en-CA", { timeZone: "Asia/Manila" }); // YYYY-MM-DD format
  
        // Filter sales data for today only
        const salesData = salesByBusiness[selectedBusiness].filter(
            (sale) => sale.date === today
        );
  
        if (salesData.length === 0) {
            // No sales for today
            salesDiv.innerHTML = `
                <h2 class="mt-5 mb-3"><b>Today’s Sales for ${businesses[selectedBusiness]} (${new Date().toLocaleDateString("en-PH", { timeZone: "Asia/Manila" })})</b></h2>
                <p class="text-center mt-3">No Sales for Today</p>
            `;
            salesDiv.style.display = "block";
            return;
        }
  
        // Custom function to format numbers with commas and the peso sign
        const formatNumberWithCommas = (number) => {
            const num = parseFloat(number).toFixed(2); // Ensure two decimal places
            const parts = num.split("."); // Split integer and decimal parts
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ","); // Add commas to integer part
            return `₱${parts.join(".")}`; // Join integer and decimal parts with peso sign
        };
  
        let tableHTML = `
            <h2 class="mt-5 mb-3"><b>Today’s Sales for ${businesses[selectedBusiness]} (${new Date().toLocaleDateString("en-PH", { timeZone: "Asia/Manila" })})</b> <i class="fas fa-info-circle" onclick="showInfo('Todays Sale', 
                        'Today’s sales refer to all transactions made within the day, showing total revenue and helping track business performance in real time.');">
                        </i></h2>
            <div class="scrollable-table" id="businessSalesTable">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Product</th>
                            <th>Business/Branch</th>
                            <th>Amount Sold</th>
                            <th>Total Sales</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
  
        let totalQuantity = 0;
        let totalSales = 0;
  
        salesData.forEach((sale) => {
            const formattedTotalSales = formatNumberWithCommas(sale.total_sales || 0);
            tableHTML += `
                <tr>
                    <td>${sale.product_name}</td>
                    <td>${sale.business_or_branch_name || "Unknown"}</td>
                    <td>${sale.quantity || "No Sales For Today"}</td>
                    <td>${formattedTotalSales}</td>
                    <td>${sale.date}</td>
                </tr>
            `;
            
            totalQuantity += parseInt(sale.quantity || 0, 10);
            totalSales += parseFloat(sale.total_sales || 0);
        });
        
  
        tableHTML += `
                    </tbody>
                    <tfoot>
                        <tr>
                            <th><strong>Total</strong></th>
                            <th>${totalQuantity || "0"}</th>
                            <th>${formatNumberWithCommas(totalSales)}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <button class="btn btn-primary mt-2 mb-5" 
    onclick="printContent('businessSalesTable', '${businesses[selectedBusiness]} Sales (${new Date().toLocaleDateString("en-PH", { timeZone: "Asia/Manila" })})')">
    <i class="fas fa-print me-2"></i> Generate Report (Today’s Sales for ${businesses[selectedBusiness]} Log) 
</button>

        `;
  
        salesDiv.innerHTML = tableHTML;
        salesDiv.style.display = "block";
    } else {
        // No data for the selected business
        salesDiv.innerHTML = `
            <p class="text-center mt-3">No sales data found.</p>
        `;
        salesDiv.style.display = "block";
    }
  });
  



// Update footer with total sales
function updateFooter(business) {
  const table = document.getElementById(
    business === "A" ? "salesTableA" : "salesTableB"
  );
  const rows = table
    .getElementsByTagName("tbody")[0]
    .getElementsByTagName("tr");
  let totalAmount = 0;
  let totalSales = 0;

  for (let row of rows) {
    totalAmount += parseInt(row.cells[1].textContent, 10);
    totalSales += parseFloat(row.cells[2].textContent.replace("₱", ""));
  }

  const footer = table.getElementsByTagName("tfoot")[0];
  footer.getElementsByTagName("th")[1].textContent = totalAmount;
  footer.getElementsByTagName("th")[2].textContent = `₱${totalSales.toFixed(
    2
  )}`;
}

// Sales search functionality
function searchSales() {
    const filter = document.getElementById('saleSearchBar').value.toLowerCase(); // Get search input
    const rows = document.querySelectorAll('tbody tr'); // Select all table rows in tbody

    rows.forEach(row => {
        const productCell = row.querySelector('td:first-child'); // First column (Product Name)
        const businessBranchCell = row.querySelector('td:nth-child(2)'); // Second column (Business/Branch Name)

        if (productCell && businessBranchCell) {
            const productName = productCell.textContent.toLowerCase(); // Product name text
            const businessBranchName = businessBranchCell.textContent.toLowerCase(); // Business/Branch name text

            // Check if either product name or business/branch name matches the filter
            const matches = productName.includes(filter) || businessBranchName.includes(filter);

            // Show row if match found, otherwise hide
            row.style.display = matches ? '' : 'none';
        }
    });
}



// Filter sales log by date
function filterSalesLog() {
  const startDate = document.getElementById("startDate").value;
  const endDate = document.getElementById("endDate").value;
  const rows = document
    .getElementById("salesLogTable")
    .getElementsByTagName("tbody")[0]
    .getElementsByTagName("tr");

  for (let row of rows) {
    const saleDate = row.cells[4].textContent;
    if (
      (startDate && saleDate < startDate) ||
      (endDate && saleDate > endDate)
    ) {
      row.style.display = "none";
    } else {
      row.style.display = "";
    }
  }
}


document.getElementById("addSaleButton").addEventListener("click", function () {
    const selectedBusiness = document.getElementById("businessSelect").value;

    if (!selectedBusiness) {
        Swal.fire({
            icon: "warning",
            title: "No Business Selected",
            text: "Please select a business first.",
        });
        return;
    }

    fetch("../endpoints/sales/fetch_branches.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ business_id: selectedBusiness }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const branches = data.branches;
                let branchOptions = '<option value="">Select a Branch</option>';
                branches.forEach((branch) => {
                    branchOptions += `<option value="${branch.id}">${branch.location}</option>`;
                });

                const products = (productsByBusiness[selectedBusiness] || []).filter(product => product.status !== 'Unavailable');
                let productOptions = '<option value="">Select a Product</option>';
                        const uniqueProducts = new Set();

                        products.forEach((product) => {
                            const productKey = `${product.id}-${product.name}-${product.size}-${product.price}`;
                            if (!uniqueProducts.has(productKey) && product.status !== 'Unavailable') {
                                uniqueProducts.add(productKey);
                                productOptions += `<option value="${product.id}" data-price="${product.price}">${product.name} - ${product.size} (₱${product.price})</option>`;
                            }
                        });

                // Get the current date in Asia/Manila timezone
                const today = new Date().toLocaleDateString("en-CA", {
                    timeZone: "Asia/Manila",
                });

                Swal.fire({
                    title: "Add Sales",
                    html: `
                        <label for="branchSelect">Branch<span style="color:red">*</span></label>
                        <select id="branchSelect" class="form-control mb-2">${branchOptions}</select>

                        <label for="productSelect">Product<span style="color:red">*</span></label>
                        <select id="productSelect" class="form-control mb-2">${productOptions}</select>

                        <label for="amountSold">Amount Sold<span style="color:red">*</span></label>
                        <input type="number" id="amountSold" class="form-control mb-2" min="1" placeholder="Enter amount sold">

                        <label for="totalSales">Total Sales<span style="color:red">*</span></label>
                        <input type="text" id="totalSales" class="form-control mb-2" readonly placeholder="₱0">

                        <label for="saleDate">Sales Date<span style="color:red">*</span></label>
                        <input type="date" id="saleDate" class="form-control mb-2" value="${today}" readonly>
                    `,
                    didOpen: () => {
                        // Initialize Select2 for searchable product dropdown
                        $("#productSelect").select2({
                            width: "100%",
                            placeholder: "Select a Product",
                            allowClear: true
                        });
                    },
                    showCancelButton: true,
                    confirmButtonText: "Add Sales",
                    preConfirm: () => {
                        const branchId = document.getElementById("branchSelect").value;
                        const productSelect = document.getElementById("productSelect");
                        const productId = productSelect.value;
                        const amountSold = parseInt(document.getElementById("amountSold").value, 10);
                        const totalSales = parseFloat(document.getElementById("totalSales").value.replace("₱", ""));
                        const saleDate = document.getElementById("saleDate").value;

                        if ( !productId || !amountSold || isNaN(totalSales)) {
                            Swal.showValidationMessage("Please complete all fields.");
                            return false;
                        }

                        return {
                            branchId,
                            productId,
                            amountSold,
                            totalSales,
                            saleDate,
                            businessId: selectedBusiness,
                        };
                    },
                }).then((result) => {
                    if (result.isConfirmed) {
                        const saleData = result.value;

                        fetch("../endpoints/sales/add_sales.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify({
                                branch_id: saleData.branchId,
                                product_id: saleData.productId,
                                quantity: saleData.amountSold,
                                total_sales: saleData.totalSales,
                                sale_date: saleData.saleDate,
                                business_id: saleData.businessId,
                            }),
                        })
                            .then((response) => response.json())
                            .then((data) => {
                                if (data.success) {
                                    Swal.fire("Success", data.message, "success").then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire("Error", data.message, "error");
                                }
                            });
                    }
                });

               

                 // Function to update total sales dynamically
    function updateTotalSales() {
        const productSelect = document.getElementById("productSelect");
        const amountSold = document.getElementById("amountSold");
        const totalSales = document.getElementById("totalSales");

        if (!productSelect || !amountSold || !totalSales) return;

        amountSold.addEventListener("input", () => {
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const price = selectedOption ? parseFloat(selectedOption.getAttribute("data-price")) : 0;
            const quantity = parseInt(amountSold.value || 0, 10);
            totalSales.value = `₱${(price * quantity).toFixed(2)}`;
        });

        productSelect.addEventListener("change", () => {
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const price = selectedOption ? parseFloat(selectedOption.getAttribute("data-price")) : 0;
            const quantity = parseInt(amountSold.value || 0, 10);
            totalSales.value = `₱${(price * quantity).toFixed(2)}`;
        });
    }

    updateTotalSales(); // Initialize on modal open

    // Fetch product availability when branch is selected
    document.getElementById("branchSelect").addEventListener("change", function () {
        const selectedBranch = this.value;

        fetch("../endpoints/sales/fetch_product_availability.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ business_id: selectedBusiness, branch_id: selectedBranch }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    const productSelect = document.getElementById("productSelect");
                    let productOptions = '<option value="">Select a Product</option>';
                    data.products.forEach((product) => {
                        if (product.status !== "Unavailable") {
                            productOptions += `<option value="${product.id}" data-price="${product.price}">${product.name} - ${product.size} (₱${product.price})</option>`;
                        }
                    });
                    productSelect.innerHTML = productOptions;
                    updateTotalSales(); // Re-bind total sales calculation after updating product list
                } else {
                    Swal.fire("Error", data.message, "error");
                }
            })
            .catch(() => {
                Swal.fire("Error", "Failed to fetch product availability.", "error");
            });
    });

            } else {
                Swal.fire("Error", data.message, "error");
            }
        })
        .catch(() => {
            Swal.fire("Error", "Failed to fetch branches.", "error");
        });
});


  // Function to add sale to the table dynamically
  function addSaleToTable(branchId, productId, amountSold, totalSales, saleDate) {
    const table = document.getElementById(`salesTable${branchId}`).getElementsByTagName("tbody")[0];
    const productName = productsByBusiness[branchId].find((product) => product.id == productId).name;
    const branchName = document.querySelector(`#branchSelect option[value="${branchId}"]`).textContent;

    const newRow = table.insertRow();
    newRow.innerHTML = `
        <td>${branchName}</td>
        <td>${productName}</td>
        <td>${amountSold}</td>
        <td>₱${totalSales.toFixed(2)}</td>
        <td>${saleDate}</td>
    `;
}
