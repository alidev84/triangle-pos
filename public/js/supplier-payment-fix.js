document.addEventListener('livewire:initialized', () => {
    // Format the payment amount input to ensure proper decimal handling
    const paymentInput = document.getElementById('supplier_payment_amount_input');
    
    if (paymentInput) {
        paymentInput.addEventListener('blur', function() {
            // Convert to number and back to string to remove any extra zeros
            let value = parseFloat(this.value);
            if (!isNaN(value)) {
                this.value = value.toFixed(2);
                // Trigger Livewire to update the model
                this.dispatchEvent(new Event('input'));
            }
        });
        
        // Prevent non-numeric input
        paymentInput.addEventListener('keydown', function(e) {
            // Allow: backspace, delete, tab, escape, enter, decimal point
            if ([46, 8, 9, 27, 13, 110, 190].includes(e.keyCode) ||
                // Allow: Ctrl+A, Command+A
                (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) ||
                // Allow: Ctrl+C, Command+C
                (e.keyCode === 67 && (e.ctrlKey === true || e.metaKey === true)) ||
                // Allow: Ctrl+X, Command+X
                (e.keyCode === 88 && (e.ctrlKey === true || e.metaKey === true)) ||
                // Allow: home, end, left, right
                (e.keyCode >= 35 && e.keyCode <= 39)) {
                // Let it happen, don't do anything
                return;
            }
            
            // Ensure that it is a number and stop the keypress if not
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });
    }
});
