@extends('layouts.app')

@section('title', 'Sale Return & Modification')

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('sale-returns.index') }}">Sale Returns</a></li>
        <li class="breadcrumb-item active">Return/Modify Sale</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">
        @include('utils.alerts')
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-arrow-return-left"></i> Process Sale Return</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-8 mx-auto">
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-lg" id="sale-reference" 
                                           placeholder="Enter sale reference or customer name" autocomplete="off">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="button" onclick="searchSale()">
                                            <i class="bi bi-search"></i> Search
                                        </button>
                                        <button class="btn btn-outline-secondary" type="button" onclick="showRecentSales()">
                                            <i class="bi bi-clock-history"></i> Recent
                                        </button>
                                    </div>
                                </div>
                                <div id="suggestions" class="mt-2" style="display: none;"></div>
                            </div>
                        </div>
                        
                        <!-- Search Results -->
                        <div id="search-results" class="mt-4"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Transaction Details & Modification -->
        <div id="transaction-section" class="row" style="display: none;">
            <div class="col-12">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-receipt"></i> Step 2: Original Transaction Details</h5>
                    </div>
                    <div class="card-body">
                        <!-- Transaction Info -->
                        <div id="transaction-info" class="row mb-4"></div>
                        
                        <!-- Products Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Original Qty</th>
                                        <th>Unit Price</th>
                                        <th>Line Total</th>
                                        <th>Return Qty</th>
                                        <th>New Line Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="products-table">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Summary & Confirmation -->
        <div id="summary-section" class="row mt-4" style="display: none;">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-calculator"></i> Step 3: Changes Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Financial Impact</h6>
                                        <div id="financial-summary"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Inventory Impact</h6>
                                        <div id="inventory-summary"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="return-reason">Reason for Return/Modification</label>
                                    <textarea id="return-reason" class="form-control" rows="3" 
                                              placeholder="Enter reason for this return/modification..."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button class="btn btn-success btn-lg mr-3" onclick="confirmChanges()">
                                <i class="bi bi-check-circle"></i> Confirm Changes
                            </button>
                            <button class="btn btn-secondary btn-lg" onclick="resetForm()">
                                <i class="bi bi-arrow-clockwise"></i> Start Over
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('page_scripts')
<script>
const currencySymbol = '{{ settings()->currency->symbol }}';
let currentSale = null;
let modifications = {};

function searchSale() {
    const query = document.getElementById('sale-reference').value.trim();
    if (!query) {
        alert('Please enter a sale reference number or customer name');
        return;
    }
    
    // Show loading
    document.getElementById('search-results').innerHTML = '<div class="text-center"><i class="bi bi-hourglass-split"></i> Searching...</div>';
    document.getElementById('search-results').style.display = 'block';
    
    // API call with query parameter
    fetch(`/api/sales/search?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.sales && data.sales.length > 0) {
                if (data.sales.length === 1) {
                    displaySaleDetails(data.sales[0]);
                } else {
                    displayMultipleSales(data.sales);
                }
            } else {
                document.getElementById('search-results').innerHTML = 
                    '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> ' + (data.message || 'No sales found matching your search') + '</div>';
            }
        })
        .catch(error => {
            document.getElementById('search-results').innerHTML = 
                '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Error searching for sale. Please try again.</div>';
        });
}

function showRecentSales() {
    document.getElementById('search-results').innerHTML = '<div class="text-center"><i class="bi bi-hourglass-split"></i> Loading recent sales...</div>';
    document.getElementById('search-results').style.display = 'block';
    
    // Simulate API call for recent sales
    fetch('/api/sales/recent')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayRecentSales(data.sales);
            } else {
                document.getElementById('search-results').innerHTML = 
                    '<div class="alert alert-danger">Error loading recent sales</div>';
            }
        })
        .catch(error => {
            document.getElementById('search-results').innerHTML = 
                '<div class="alert alert-danger">Error loading recent sales</div>';
        });
}

function displayRecentSales(sales) {
    let html = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    sales.forEach(sale => {
        html += `
            <tr>
                <td><strong>${sale.reference}</strong></td>
                <td>${sale.customer_name}</td>
                <td>${sale.date}</td>
                <td>${currencySymbol}${(sale.total_amount / 100).toFixed(2)}</td>
                <td><span class="badge badge-${sale.status === 'Completed' ? 'success' : 'warning'}">${sale.status}</span></td>
                <td><button class="btn btn-sm btn-primary" onclick="selectSale('${sale.reference}')">Select</button></td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    document.getElementById('search-results').innerHTML = html;
}

function selectSale(saleId, saleData = null) {
    if (saleData) {
        currentSale = saleData;
    } else {
        // If no sale data provided, fetch it
        fetch(`/api/sales/${saleId}/products`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentSale = data.sale;
                    currentSale.products = data.products;
                    showTransactionSection();
                }
            });
        return;
    }
    
    showTransactionSection();
}

function displayMultipleSales(sales) {
    let html = `
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-list"></i> Multiple Sales Found</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    sales.forEach(sale => {
        html += `
            <tr>
                <td><strong>${sale.reference}</strong></td>
                <td>${sale.customer_name}</td>
                <td>${sale.date}</td>
                <td>${currencySymbol}${(sale.total_amount / 100).toFixed(2)}</td>
                <td><span class="badge badge-info">${sale.status}</span></td>
                <td><button class="btn btn-sm btn-primary" onclick="selectSale(${sale.id}, ${JSON.stringify(sale).replace(/"/g, '&quot;')})">Select</button></td>
            </tr>
        `;
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('search-results').innerHTML = html;
}

function displaySaleDetails(sale) {
    currentSale = sale;
    
    let html = `
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-check-circle"></i> Sale Found</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Reference:</strong> ${sale.reference}</p>
                        <p><strong>Customer:</strong> ${sale.customer_name}</p>
                        <p><strong>Date:</strong> ${sale.date}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Total Amount:</strong> ${currencySymbol}${(sale.total_amount / 100).toFixed(2)}</p>
                        <p><strong>Status:</strong> <span class="badge badge-info">${sale.status}</span></p>
                        <p><strong>Payment Status:</strong> <span class="badge badge-warning">${sale.payment_status}</span></p>
                    </div>
                </div>
                <button class="btn btn-primary" onclick="selectSale(${sale.id}, ${JSON.stringify(sale).replace(/"/g, '&quot;')})">Select This Sale</button>
            </div>
        </div>
    `;
    
    document.getElementById('search-results').innerHTML = html;
}

function showTransactionSection() {
    // Show transaction section
    document.getElementById('transaction-section').style.display = 'block';
    document.getElementById('summary-section').style.display = 'block';
    
    // Display products
    displayProducts(currentSale);
    
    // Update summary
    updateSummary();
}

function displayProducts(sale) {
    // Use actual product data from the sale
    const products = sale.products || [];
    
    let html = '';
    products.forEach(product => {
        html += `
            <tr id="product-row-${product.id}">
                <td><strong>${product.name}</strong></td>
                <td><span class="badge badge-info">${product.quantity}</span></td>
                <td>${currencySymbol}${(product.unit_price / 100).toFixed(2)}</td>
                <td>${currencySymbol}${(product.line_total / 100).toFixed(2)}</td>
                <td>
                    <input type="number" class="form-control" id="return-qty-${product.id}" 
                           min="0" max="${product.quantity}" value="0" 
                           onchange="updateProductModification(${product.id}, ${product.quantity}, ${product.unit_price})">
                </td>
                <td id="new-total-${product.id}">${currencySymbol}${(product.line_total / 100).toFixed(2)}</td>
                <td>
                    <button class="btn btn-sm btn-warning" onclick="returnAll(${product.id}, ${product.quantity})">
                        Return All
                    </button>
                </td>
            </tr>
        `;
    });
    
    document.getElementById('products-table').innerHTML = html;
}

function updateProductModification(productId, originalQty, unitPrice) {
    const returnQty = parseInt(document.getElementById(`return-qty-${productId}`).value) || 0;
    const newQty = originalQty - returnQty;
    const newTotal = newQty * unitPrice;
    
    // Update display
    document.getElementById(`new-total-${productId}`).innerHTML = 
        `${currencySymbol}${(newTotal / 100).toFixed(2)}`;
    
    // Store modification
    modifications[productId] = {
        product_id: productId,
        originalQty: originalQty,
        return_quantity: returnQty,
        newQty: newQty,
        unitPrice: unitPrice,
        newTotal: newTotal
    };
    
    updateSummary();
}

function returnAll(productId, quantity) {
    document.getElementById(`return-qty-${productId}`).value = quantity;
    updateProductModification(productId, quantity, modifications[productId]?.unitPrice || 0);
}

function updateSummary() {
    let totalReturn = 0;
    let inventoryChanges = [];
    
    Object.keys(modifications).forEach(productId => {
        const mod = modifications[productId];
        if (mod.return_quantity > 0) {
            totalReturn += mod.return_quantity * mod.unitPrice;
            inventoryChanges.push(`+${mod.return_quantity} units returned to stock`);
        }
    });
    
    // Financial summary
    const financialHtml = `
        <p><strong>Original Total:</strong> ${currencySymbol}${(currentSale?.total_amount / 100).toFixed(2) || '0.00'}</p>
        <p><strong>Return Amount:</strong> <span class="text-danger">-${currencySymbol}${(totalReturn / 100).toFixed(2)}</span></p>
        <p><strong>New Total:</strong> <span class="text-success">${currencySymbol}${((currentSale?.total_amount - totalReturn) / 100).toFixed(2) || '0.00'}</span></p>
        <hr>
        <p><strong>Refund Due:</strong> <span class="h6 text-primary">${currencySymbol}${(totalReturn / 100).toFixed(2)}</span></p>
    `;
    document.getElementById('financial-summary').innerHTML = financialHtml;
    
    // Inventory summary
    const inventoryHtml = inventoryChanges.length > 0 ? 
        inventoryChanges.map(change => `<p><i class="bi bi-box-arrow-in-up text-success"></i> ${change}</p>`).join('') :
        '<p class="text-muted">No inventory changes</p>';
    document.getElementById('inventory-summary').innerHTML = inventoryHtml;
}

function confirmChanges() {
    if (Object.keys(modifications).length === 0 || 
        !Object.values(modifications).some(mod => mod.return_quantity > 0)) {
        alert('Please select items to return/modify before confirming.');
        return;
    }
    
    const reason = document.getElementById('return-reason').value.trim() || 'Return/Modification';
    
    // Prepare data for submission - convert modifications object to array
    const modificationsArray = Object.values(modifications).filter(mod => mod.return_quantity > 0);
    
    const data = {
        sale_id: currentSale.id,
        modifications: modificationsArray,
        reason: reason,
        _token: '{{ csrf_token() }}'
    };
    
    // Submit changes
    fetch('{{ route("sale-returns.process") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Sale return/modification processed successfully!');
            window.location.href = '{{ route("sale-returns.index") }}';
        } else {
            alert('Error processing return: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error processing return. Please try again.');
    });
}

function resetForm() {
    currentSale = null;
    modifications = {};
    document.getElementById('sale-reference').value = '';
    document.getElementById('search-results').style.display = 'none';
    document.getElementById('transaction-section').style.display = 'none';
    document.getElementById('summary-section').style.display = 'none';
    document.getElementById('return-reason').value = '';
}

// Add autocomplete functionality
let suggestionTimeout;
document.getElementById('sale-reference').addEventListener('input', function(e) {
    const query = e.target.value.trim();
    
    clearTimeout(suggestionTimeout);
    
    if (query.length < 2) {
        document.getElementById('suggestions').style.display = 'none';
        return;
    }
    
    suggestionTimeout = setTimeout(() => {
        fetch(`/api/sales/suggestions?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.suggestions.length > 0) {
                    showSuggestions(data.suggestions);
                } else {
                    document.getElementById('suggestions').style.display = 'none';
                }
            })
            .catch(() => {
                document.getElementById('suggestions').style.display = 'none';
            });
    }, 300);
});

// Allow Enter key to search
document.getElementById('sale-reference').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchSale();
    }
});

function showSuggestions(suggestions) {
    let html = '<div class="list-group">';
    
    suggestions.forEach(suggestion => {
        html += `
            <a href="#" class="list-group-item list-group-item-action" onclick="selectSuggestion('${suggestion.value}')">
                <i class="bi bi-${suggestion.type === 'reference' ? 'receipt' : 'person'}"></i> ${suggestion.label}
            </a>
        `;
    });
    
    html += '</div>';
    
    document.getElementById('suggestions').innerHTML = html;
    document.getElementById('suggestions').style.display = 'block';
}

function selectSuggestion(value) {
    document.getElementById('sale-reference').value = value;
    document.getElementById('suggestions').style.display = 'none';
    searchSale();
}

// Hide suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#sale-reference') && !e.target.closest('#suggestions')) {
        document.getElementById('suggestions').style.display = 'none';
    }
});
</script>
@endpush
