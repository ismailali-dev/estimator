<script src="https://js.stripe.com/v3/"></script>
<script>
    var stripe = Stripe("pk_test_51HkX3bItMIhfPIHR6kXxkS8Qq5VfegO4yrDLoH58hyRBefKSS11ELKGsO9ufL4DRkzxFxS9YE8pdTGekiJT4G2sl00hKT4ZCfz");
</script>
<form id="payment-form">
    <div id="card-element"><!--Stripe.js injects the Card Element--></div>
    <button id="submit">
        Submit Payment
    </button>
</form>
<script>
    var elements = stripe.elements();
    var cardElement = elements.create('card');
    cardElement.mount('#card-element');

    var form = document.getElementById('payment-form');

    form.addEventListener('submit', function(event) {
        event.preventDefault();

        stripe.createToken(cardElement).then(function(result) {
            if (result.error) {
                // Inform the user if there was an error
                console.error(result.error.message);
            } else {
                // Send the token to your server
                var token = result.token;
                console.log("token : ",token);
                console.log("result : ",result);
                // Now you can use this token in your server-side code
            }
        });
    });
</script>
