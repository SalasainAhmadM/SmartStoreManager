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

      let tableHTML = `
          <h2 class="mt-5 mb-3"><b>Today’s Sales for ${businesses[selectedBusiness]} (${new Date().toLocaleDateString("en-PH", { timeZone: "Asia/Manila" })})</b></h2>
          <div class="scrollable-table" id="businessSalesTable">
              <table class="table table-striped table-hover">
                  <thead class="table-dark">
                      <tr>
                          <th>Product</th>
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
          tableHTML += `
              <tr>
                  <td>${sale.product_name}</td>
                  <td>${sale.quantity || "No Sales For Today"}</td>
                  <td>${sale.total_sales ? `₱${sale.total_sales}` : "₱0.00"}</td>
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
                          <th>₱${totalSales.toFixed(2)}</th>
                          <th></th>
                      </tr>
                  </tfoot>
              </table>
          </div>
          <button class="btn btn-primary mt-2 mb-5" onclick="printContent('businessSalesTable', '${businesses[selectedBusiness]} Sales (${new Date().toLocaleDateString("en-PH", { timeZone: "Asia/Manila" })})')">
              <i class="fas fa-print me-2"></i> Print Report (Today’s Sales for ${businesses[selectedBusiness]} Log) 
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
  const filter = document.getElementById('saleSearchBar').value.toLowerCase();

  const rows = document.querySelectorAll('tbody tr');

  rows.forEach(row => {
      const productCell = row.querySelector('td:first-child'); 
      if (productCell) {
          const productName = productCell.textContent.toLowerCase();
          row.style.display = productName.includes(filter) ? '' : 'none'; 
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


// Handle "Add Sale" button click = functional
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

  const products = productsByBusiness[selectedBusiness] || [];
  let productOptions = '<option value="">Select a Product</option>';
  products.forEach((product) => {
      productOptions += `<option value="${product.id}" data-price="${product.price}">${product.name} (₱${product.price})</option>`;
  });

  // Get the current date in Asia/Manila timezone
  const today = new Date().toLocaleDateString("en-CA", {
      timeZone: "Asia/Manila",
  });

  Swal.fire({
      title: "Add Sales",
      html: `
          <label for="productSelect">Product</label>
          <select id="productSelect" class="form-control mb-2">${productOptions}</select>
          <label for="amountSold">Amount Sold</label>
          <input type="number" id="amountSold" class="form-control mb-2" min="1" placeholder="Enter amount sold">
          <label for="totalSales">Total Sales</label>
          <input type="text" id="totalSales" class="form-control mb-2" readonly placeholder="₱0">
          <label for="saleDate">Sales Date</label>
          <input type="date" id="saleDate" class="form-control mb-2" value="${today}" readonly>
      `,
      showCancelButton: true,
      confirmButtonText: "Add Sales",
      preConfirm: () => {
          const productSelect = document.getElementById("productSelect");
          const productId = productSelect.value;
          const amountSold = parseInt(document.getElementById("amountSold").value, 10);
          const totalSales = parseFloat(document.getElementById("totalSales").value.replace("₱", ""));
          const saleDate = document.getElementById("saleDate").value;

          if (!productId || !amountSold || isNaN(totalSales)) {
              Swal.showValidationMessage("Please complete all fields.");
              return false;
          }

          return {
              productId,
              amountSold,
              totalSales,
              saleDate
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
                  product_id: saleData.productId,
                  quantity: saleData.amountSold,
                  total_sales: saleData.totalSales,
                  sale_date: saleData.saleDate,
              }),
          })
              .then((response) => response.json())
              .then((data) => {
                  if (data.success) {
                      Swal.fire("Success", data.message, "success");
                      addSaleToTable(
                          selectedBusiness,
                          saleData.productId,
                          saleData.amountSold,
                          saleData.totalSales,
                          saleData.saleDate
                      );
                  } else {
                      Swal.fire("Error", data.message, "error");
                  }
              });
      }
  });

  document.addEventListener("input", (event) => {
      if (event.target.id === "amountSold") {
          const productSelect = document.getElementById("productSelect");
          const selectedProduct = productsByBusiness[selectedBusiness].find(
              (product) => product.id == productSelect.value
          );
          if (selectedProduct) {
              const total = selectedProduct.price * parseInt(event.target.value || 0, 10);
              document.getElementById("totalSales").value = `₱${total.toFixed(2)}`;
          }
      }
  });
});

// Function to add sale to the table dynamically
function addSaleToTable(businessId, productId, amountSold, totalSales, saleDate) {
  const table = document.getElementById(`salesTable${businessId}`).getElementsByTagName("tbody")[0];
  const productName = productsByBusiness[businessId].find((product) => product.id == productId).name;
  const newRow = table.insertRow();
  newRow.innerHTML = `
      <td>${productName}</td>
      <td>${amountSold}</td>
      <td>₱${totalSales.toFixed(2)}</td>
      <td>${saleDate}</td> 
  `;
}
