const businessProducts = {
  A: [ // Name of the business
    {
      name: "Product 1", // Product name
      price: 10, // Product price
    },
    {
      name: "Product 2",
      price: 15,
    },
  ],
  B: [ // Name of the business
    {
      name: "Product 3",
      price: 20,
    },
    {
      name: "Product 4",
      price: 25,
    },
  ],
};

// Handle business selection
document
  .getElementById("businessSelect")
  .addEventListener("change", function () {
    const selectedBusiness = this.value;
    if (selectedBusiness === "A") {
      document.getElementById("businessA_sales").style.display = "block";
      document.getElementById("businessB_sales").style.display = "none";
    } else if (selectedBusiness === "B") {
      document.getElementById("businessB_sales").style.display = "block";
      document.getElementById("businessA_sales").style.display = "none";
    } else {
      document.getElementById("businessA_sales").style.display = "none";
      document.getElementById("businessB_sales").style.display = "none";
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
  const filter = document.getElementById("saleSearchBar").value.toLowerCase();
  const tables = [
    document.getElementById("salesTableA"),
    document.getElementById("salesTableB"),
    document.getElementById("salesLogTable"),
  ];
  tables.forEach((table) => {
    const rows = table
      .getElementsByTagName("tbody")[0]
      .getElementsByTagName("tr");
    for (let row of rows) {
      const product = row.cells[0].textContent.toLowerCase();
      row.style.display = product.includes(filter) ? "" : "none";
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

  Swal.fire({
      title: "Add Sale",
      html: `
          <label for="productSelect">Product</label>
          <select id="productSelect" class="form-control mb-2">${productOptions}</select>
          <label for="amountSold">Amount Sold</label>
          <input type="number" id="amountSold" class="form-control mb-2" min="1" placeholder="Enter amount sold">
          <label for="totalSales">Total Sales</label>
          <input type="text" id="totalSales" class="form-control mb-2" readonly placeholder="₱0">
          <label for="saleDate">Sale Date</label>
          <input type="date" id="saleDate" class="form-control mb-2" value="${new Date().toISOString().split("T")[0]}" readonly>
      `,
      showCancelButton: true,
      confirmButtonText: "Add Sale",
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
              })
              // .catch((error) => {
              //     console.error("Fetch error:", error);
              //     Swal.fire("Error", "An unexpected error occurred. Please try again later.", "error");
              // });
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