//window.PiMockConfig = {
//    production_domain: false,
//    debug: true,
//    username: 'john_doe',
//    uid: '12345678-1234-414e-b578-42e89d1f3c02',
//    payment_found: {
//        amount: 1, // Amount of � to be paid
//        memo: "Please pay for your order #12345", // User-facing explanation of the payment
//        metadata: {orderId: 12345}, // Developer-facing metadata
//    },
//    payment_error: 'There has been an error with your payment',
//    payment_cancelled: 'Your payment was cancelled',
//}

//mock pi requests when outside the Pi Browser or in dev mode
//PiMock();
define('piApiBase', 'https://api.minepi.com/v2/payments');

jQuery(document).ready(function($){

    const PiNetwork = ({
        Pi,
        authenticateUser: function() {
            // Authenticate the user, and get permission to request payments from them:
            const scopes = ['payments'];
    
            // Read more about this callback in the SDK reference:
            function onIncompletePaymentFound(payment) { /* ... */ };
    
            Pi.authenticate(scopes, onIncompletePaymentFound).then(function(auth) {
                console.log('pi network auth', auth)
                console.log(`Hi there! You're ready to make payments!`);
            }).catch(function(error) {
                
                console.error(error);
            });
        },
        createPayment: function(amount = $total) {
            Pi.createPayment({
                // Amount of � to be paid:
                amount,
                // An explanation of the payment - will be shown to the user:
                memo: "...", // e.g: "Digital kitten #1234",
                // An arbitrary developer-provided metadata object - for your own usage:
                metadata: { id: 123 }, // e.g: { kittenId: 1234 }
            }, {
                // Callbacks you need to implement - read more about those in the detailed docs linked below:
                onReadyForServerApproval: PiNetworkRestApi.approvePayment,
                onReadyForServerCompletion: PiNetworkRestApi.completePayment,
                onCancel: function(paymentId) { /* ... */ },
                onError: function(error, payment) { /* ... */ },
            });
        },
    })
    
    const PiNetworkRestApi = ({
        approvePayment: function (paymentId) {
            console.log(paymentId)
            $.ajax({
                url: piApiBase + '/approve',
                data: JSON.stringify({paymentId}),
                contentType: "application/json",
                method: 'POST',
              }).done(function() {
                console.log('approved')
              });
        },
        completePayment: function(paymentId, txid) {
            console.log(paymentId)
            console.log(txid)
            $.ajax({
                url: piApiBase + '/complete',
                data: JSON.stringify({paymentId, txid}),
                contentType: "application/json",
                method: 'POST',
              }).done(function() {
                console.log('completed')
              });
        }
    })
    
    $('.pi-payment-button').bind('click', [], PiNetwork.createPayment)


    PiNetwork.authenticateUser();
});
