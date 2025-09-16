@extends('layouts.app')

@section('title', 'Purchase Return & Modification')

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('purchase-returns.index') }}">Purchase Returns</a></li>
        <li class="breadcrumb-item active">Return/Modify Purchase</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">
        @include('utils.alerts')
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-arrow-return-left"></i> Process Purchase Return</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-8 mx-auto">
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-lg" id="purchase-reference" 
                                           placeholder="Enter purchase reference or supplier name" autocomplete="off">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="button" onclick="searchPurchase()">
                                            <i class="bi bi-search"></i> Search
                                        </button>
                                        <button class="btn btn-outline-secondary" type="button" onclick="showRecentPurchases()">
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
let currentPurchase = null;
let modifications = {};

function searchPurchase() {
    const query = document.getElementById('purchase-reference').value.trim();
    if (!query) {
        alert('Please enter a purchase reference number or supplier name');
        return;
    }
    
    // Show loading
    document.getElementById('search-results').innerHTML = '<div class="text-center"><i class="bi bi-hourglass-split"></i> Searching...</div>';
    document.getElementById('search-results').style.display = 'block';
    
    // API call with query parameter
    fetch(`/api/purchases/search?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.purchases && data.purchases.length > 0) {
                if (data.purchases.length === 1) {
                    displayPurchaseDetails(data.purchases[0]);
                } else {
                    displayMultiplePurchases(data.purchases);
                }
            } else {
                document.getElementById('search-results').innerHTML = 
                    '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> ' + (data.message || 'No purchases found matching your search') + '</div>';
            }
        })
        .catch(error => {
            document.getElementById('search-results').innerHTML = 
                '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Error searching for purchase. Please try again.</div>';
        });
}

function showRecentPurchases() {
    document.getElementById('search-results').innerHTML = '<div class="text-center"><i class="bi bi-hourglass-split"></i> Loading recent purchases...</div>';
    document.getElementById('search-results').style.display = 'block';
    
    // API call for recent purchases
    fetch('/api/purchases/recent')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayRecentPurchases(data.purchases);
            } else {
                document.getElementById('search-results').innerHTML = 
                    '<div class="alert alert-danger">Error loading recent purchases</div>';
            }
        })
        .catch(error => {
            document.getElementById('search-results').innerHTML = 
                '<div class="alert alert-danger">Error loading recent purchases</div>';
        });
}

function displayRecentPurchases(purchases) {
    let html = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Supplier</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    purchases.forEach(purchase => {
        html += `
            <tr>
                <td><strong>${purchase.reference}</strong></td>
                <td>${purchase.supplier_name}</td>
                <td>${purchase.date}</td>
                <td>${currencySymbol}${(purchase.total_amount).toFixed(2)}</td>
                <td><span class="badge badge-${purchase.status === 'Completed' ? 'success' : 'warning'}">${purchase.status}</span></td>
                <td><button class="btn btn-sm btn-primary" onclick="selectPurchase('${purchase.reference}')">Select</button></td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    document.getElementById('search-results').innerHTML = html;
}

function selectPurchase(purchaseId, purchaseData = null) {
    if (purchaseData) {
        currentPurchase = purchaseData;
    } else {
        // If no purchase data provided, fetch it
        fetch(`/api/purchases/${purchaseId}/products`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentPurchase = data.purchase;
                    currentPurchase.products = data.products;
                    showTransactionSection();
                }
            });
        return;
    }
    
    showTransactionSection();
}

function displayMultiplePurchases(purchases) {
    let html = `
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-list"></i> Multiple Purchases Found</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Supplier</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    purchases.forEach(purchase => {
        html += `
            <tr>
                <td><strong>${purchase.reference}</strong></td>
                <td>${purchase.supplier_name}</td>
                <td>${purchase.date}</td>
                <td>${currencySymbol}${(purchase.total_amount).toFixed(2)}</td>
                <td><span class="badge badge-info">${purchase.status}</span></td>
                <td><button class="btn btn-sm btn-primary" onclick="selectPurchase(${purchase.id}, ${JSON.stringify(purchase).replace(/"/g, '&quot;')})">Select</button></td>
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

function displayPurchaseDetails(purchase) {
    currentPurchase = purchase;
    
    let html = `
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-check-circle"></i> Purchase Found</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Reference:</strong> ${purchase.reference}</p>
                        <p><strong>Supplier:</strong> ${purchase.supplier_name}</p>
                        <p><strong>Date:</strong> ${purchase.date}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Total Amount:</strong> ${currencySymbol}${(purchase.total_amount ).toFixed(2)}</p>
                        <p><strong>Status:</strong> <span class="badge badge-info">${purchase.status}</span></p>
                        <p><strong>Payment Status:</strong> <span class="badge badge-warning">${purchase.payment_status}</span></p>
                    </div>
                </div>
                <button class="btn btn-primary" onclick="selectPurchase(${purchase.id}, ${JSON.stringify(purchase).replace(/"/g, '&quot;')})">Select This Purchase</button>
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
    displayProducts(currentPurchase);
    
    // Update summary
    updateSummary();
}

function displayProducts(purchase) {
    // Use actual product data from the purchase
    const products = purchase.products || [];
    
    let html = '';
    products.forEach(product => {
        // Ensure we're working with proper numeric values
        const quantity = parseFloat(product.quantity) || 0;
        const unitPrice = parseFloat(product.unit_price) || 0;
        console.log("unit price in detail box", unitPrice);
        const lineTotal = parseFloat(product.line_total) || 0;
        console.log("total price in detail box", lineTotal);
       
                
        html += `
            <tr id="product-row-${product.id}">
                <td><strong>${product.name}</strong></td>
                <td><span class="badge badge-info">${quantity}</span></td>
                <td>${currencySymbol}${(unitPrice ).toFixed(2)}</td>
                <td>${currencySymbol}${(lineTotal ).toFixed(2)}</td>
                <td>
                    <input type="number" class="form-control" id="return-qty-${product.id}" 
                           min="0" max="${quantity}" value="0" 
                           onchange="updateProductModification(${product.id}, ${quantity}, ${unitPrice})">
                </td>
                <td id="new-total-${product.id}">${currencySymbol}${(lineTotal ).toFixed(2)}</td>
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
    // Ensure unit price is treated as cents (divide by 100)
    const unitPriceInCents = unitPrice;
    const newTotal = newQty * unitPriceInCents;
    
    // Update display - show formatted currency
    document.getElementById(`new-total-${productId}`).innerHTML = 
        `${currencySymbol}${newTotal.toFixed(2)}`;
    
    // Store modification - keep values in cents for consistency
    modifications[productId] = {
        product_id: productId,
        originalQty: originalQty,
        return_quantity: returnQty,
        newQty: newQty,
        unitPrice: unitPrice, // Keep as cents for storage
        newTotal: newTotal  // Convert back to cents for storage
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
            // Convert unit price from cents to dollars for calculation
            console.log('Unit Price:', mod.unitPrice);
            const unitPriceInDollars = mod.unitPrice.toFixed(2);
             console.log('Unit Price in Dollar:', unitPriceInDollars);
            const lineTotal = mod.return_quantity * unitPriceInDollars;
            console.log('Unit Price:', lineTotal);
            totalReturn += lineTotal;
            inventoryChanges.push(`-${mod.return_quantity} units removed from stock (${currencySymbol}${lineTotal.toFixed(2)})`);
        }
    });
    
    // Convert all values to dollars for consistent calculations
    const totalReturnInDollars = totalReturn; // Already in dollars from previous calculations
    const originalTotalInDollars = currentPurchase ? parseFloat(currentPurchase.total_amount) : 0;
    console.log('original total:', originalTotalInDollars); // Convert cents to dollars
    const newTotalInDollars = Math.max(0, originalTotalInDollars - totalReturnInDollars);
    console.log('new Total:', newTotalInDollars);
    
    // Format all monetary values with 2 decimal places and currency symbol
   /* const formatCurrency = (amount) => {
        return `${currencySymbol}${parseFloat(amount).toFixed(2)}`;
    };*/
    
    // Financial summary - all values in dollars for display
    const financialHtml = `
        <p><strong>Original Total:</strong> ${originalTotalInDollars}</p>
        <p><strong>Return Amount:</strong> <span class="text-danger">-${totalReturnInDollars}</span></p>
        <p><strong>New Total:</strong> <span class="text-success">${newTotalInDollars.toFixed(2)}</span></p>
        <hr>
        <p><strong>Credit Due:</strong> <span class="h6 text-primary">${totalReturnInDollars}</span></p>
    `;
    document.getElementById('financial-summary').innerHTML = financialHtml;
    
    // Inventory summary
    const inventoryHtml = inventoryChanges.length > 0 ? 
        inventoryChanges.map(change => `<p><i class="bi bi-box-arrow-down text-danger"></i> ${change}</p>`).join('') :
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
        purchase_id: currentPurchase.id,
        modifications: modificationsArray,
        reason: reason,
        _token: '{{ csrf_token() }}'
    };
    
    // Submit changes
    fetch('{{ route("purchase-returns.process") }}', {
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
            alert('Purchase return/modification processed successfully!');
            window.location.href = '{{ route("purchase-returns.index") }}';
        } else {
            alert('Error processing return: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error processing return. Please try again.');
    });
}

function resetForm() {
    currentPurchase = null;
    modifications = {};
    document.getElementById('purchase-reference').value = '';
    document.getElementById('search-results').style.display = 'none';
    document.getElementById('transaction-section').style.display = 'none';
    document.getElementById('summary-section').style.display = 'none';
    document.getElementById('return-reason').value = '';
}

// Add autocomplete functionality
let suggestionTimeout;
document.getElementById('purchase-reference').addEventListener('input', function(e) {
    const query = e.target.value.trim();
    
    clearTimeout(suggestionTimeout);
    
    if (query.length < 2) {
        document.getElementById('suggestions').style.display = 'none';
        return;
    }
    
    suggestionTimeout = setTimeout(() => {
        // Updated API endpoint
        fetch(`/api/purchase-returns/suggestions?q=${encodeURIComponent(query)}`)
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
document.getElementById('purchase-reference').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchPurchase();
    }
});

function showSuggestions(suggestions) {
    let html = '<div class="list-group">';
    
    suggestions.forEach(suggestion => {
        html += `
            <a href="#" class="list-group-item list-group-item-action" onclick="selectSuggestion('${suggestion.value}')">
                <i class="bi bi-${suggestion.type === 'reference' ? 'receipt' : 'building'}"></i> ${suggestion.label}
            </a>
        `;
    });
    
    html += '</div>';
    
    document.getElementById('suggestions').innerHTML = html;
    document.getElementById('suggestions').style.display = 'block';
}

function selectSuggestion(value) {
    document.getElementById('purchase-reference').value = value;
    document.getElementById('suggestions').style.display = 'none';
    searchPurchase();
}

// Hide suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#purchase-reference') && !e.target.closest('#suggestions')) {
        document.getElementById('suggestions').style.display = 'none';
    }
});
</script>
@endpush