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

// Handle "Add Sale" button click
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

  let productOptions = '<option value="">Select a Product</option>';
  businessProducts[selectedBusiness].forEach((product) => {
    productOptions += `<option value="${product.name}" data-price="${product.price}">${product.name} (₱${product.price})</option>`;
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
                    <input type="date" id="saleDate" class="form-control mb-2" value="${
                      new Date().toISOString().split("T")[0]
                    }" readonly>
                `,
    showCancelButton: true,
    confirmButtonText: "Add Sale",
    preConfirm: () => {
      const product = document.getElementById("productSelect").value;
      const amountSold = parseInt(
        document.getElementById("amountSold").value,
        10
      );
      const totalSales = parseFloat(
        document.getElementById("totalSales").value.replace("₱", "")
      );

      if (!product || !amountSold || isNaN(totalSales)) {
        Swal.showValidationMessage("Please complete all fields.");
        return false;
      }

      return {
        product,
        amountSold,
        totalSales,
      };
    },
  }).then((result) => {
    if (result.isConfirmed) {
      const { product, amountSold, totalSales } = result.value;
      addSaleToTable(selectedBusiness, product, amountSold, totalSales);
      addSaleToLog(selectedBusiness, product, amountSold, totalSales);
    }
  });

  // Update total sales dynamically
  document.addEventListener("input", (event) => {
    if (event.target.id === "amountSold") {
      const productSelect = document.getElementById("productSelect");
      const selectedProduct = businessProducts[selectedBusiness].find(
        (product) => product.name === productSelect.value
      );
      if (selectedProduct) {
        const total = selectedProduct.price * parseInt(event.target.value, 10);
        document.getElementById("totalSales").value = `₱${total.toFixed(2)}`;
      }
    }
  });
});

// Add sale to the respective business table
function addSaleToTable(business, product, amountSold, totalSales) {
  const table = document
    .getElementById(business === "A" ? "salesTableA" : "salesTableB")
    .getElementsByTagName("tbody")[0];
  const newRow = table.insertRow();
  newRow.innerHTML = `
                <td>${product}</td>
                <td>${amountSold}</td>
                <td>₱${totalSales.toFixed(2)}</td>
                <td>${new Date().toLocaleDateString()}</td>
            `;
  updateFooter(business);
}

// Add sale to the sales log
function addSaleToLog(business, product, amountSold, totalSales) {
  const logTable = document
    .getElementById("salesLogTable")
    .getElementsByTagName("tbody")[0];
  const newRow = logTable.insertRow();
  newRow.innerHTML = `
                <td>${product}</td>
                <td>${amountSold}</td>
                <td>₱${totalSales.toFixed(2)}</td>
                <td>${business}</td>
                <td>${new Date().toLocaleDateString()}</td>
            `;
}

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
