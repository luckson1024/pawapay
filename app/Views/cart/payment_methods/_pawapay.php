<?php
use function Myzuwa\PawaPay\Support\base_url;
?>
<div class="payment-method pawapay-method">
    <form id="pawapay-form" method="post" action="<?= base_url('cart/pawapay-payment-post') ?>">
        <!-- Hidden fields for transaction data -->
        <input type="hidden" name="payment_amount" value="<?= htmlspecialchars($amount) ?>">
        <input type="hidden" name="currency" value="ZMW">
        <input type="hidden" name="mds_payment_token" value="<?= htmlspecialchars($orderId) ?>">
        <input type="hidden" name="mds_payment_type" value="product">
        
        <!-- Phone number input -->
        <div class="form-group">
            <label for="msisdn">Phone Number:</label>
            <input type="tel" 
                   id="msisdn" 
                   name="msisdn" 
                   class="form-control" 
                   placeholder="e.g., 260976000000"
                   pattern="^260[0-9]{9}$"
                   required>
            <small class="form-text text-muted">Enter your mobile money number starting with 260</small>
        </div>

        <!-- Mobile Network Operator selection -->
        <div class="form-group">
            <label for="operator">Mobile Network Operator:</label>
            <select id="operator" name="operator" class="form-control" required>
                <option value="">Select your network</option>
                <?php foreach ($operators as $code => $operator): ?>
                    <option value="<?= htmlspecialchars($code) ?>">
                        <?= htmlspecialchars($operator['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Error display -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Submit button -->
        <button type="submit" class="btn btn-primary btn-pay">
            Pay <?= htmlspecialchars(number_format($amount, 2)) ?> ZMW
        </button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('pawapay-form');
    const phoneInput = document.getElementById('msisdn');
    const operatorSelect = document.getElementById('operator');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger d-none';
    form.insertBefore(errorDiv, form.querySelector('button'));
    
    // Phone number validation and operator prediction
    phoneInput.addEventListener('input', async function() {
        const number = this.value;
        if (number.length >= 12) {
            try {
                const response = await fetch('<?= base_url('cart/predict-operator') ?>?phone=' + encodeURIComponent(number));
                const data = await response.json();
                
                if (data.success && data.provider) {
                    operatorSelect.value = data.provider.code;
                    // Update the phone number field with the formatted number
                    phoneInput.value = data.provider.phoneNumber;
                }
            } catch (error) {
                console.error('Failed to predict operator:', error);
            }
        }
    });

    // Handle form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.result === 1) {
                // Success - Show success message
                errorDiv.className = 'alert alert-success';
                errorDiv.textContent = result.message;
            } else {
                // Error - Show error message
                errorDiv.className = 'alert alert-danger';
                errorDiv.textContent = result.message || 'Payment failed. Please try again.';
            }
        } catch (error) {
            console.error('Payment error:', error);
            errorDiv.className = 'alert alert-danger';
            errorDiv.textContent = 'A network or server error occurred. Please try again.';
        }
    });
});
</script>