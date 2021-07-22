 console.log('hello piwoo')

 jQuery(document).ready(function($){
     function test() {
         alert('test')
     }

     $("form.woocommerce-checkout")
     .on('submit', function(e) { 
         e.preventDefault();
         console.log('here in form submit')
         e.preventDefault();
         console.log('place order clicked');
         const data = {
             'action': 'wp_ajax_piwoo_get_cart_total'
         }
         $.post(ajaxurl, data).promise().then((result) => {
             console.log('result', result)
         });
      } ); 

     $(".checkout-button").click(function(e) {
         e.preventDefault();

         // do something
         console.log('here')
         console.log('go to checkout clicked');
         const data = {
             'action': 'wp_ajax_piwoo_get_cart_total'
         }
         $.post(ajaxurl, data).promise().then((result) => {
             console.log('result', result)
         });


         // //make an ajax call to php function to get cart total
         // Pi.createPayment({
         //     // Amount of π to be paid:
         //     amount: 10,
         //     // An explanation of the payment - will be shown to the user:
         //     memo: "...", // e.g: "Digital kitten #1234",
         //     // An arbitrary developer-provided metadata object - for your own usage:
         //     metadata: { id: 123 }, // e.g: { kittenId: 1234 }
         // }, {
         //     // Callbacks you need to implement - read more about those in the detailed docs linked below:
         //     onReadyForServerApproval: PiNetworkRestApi.approvePayment,
         //     onReadyForServerCompletion: PiNetworkRestApi.completePayment,
         //     onCancel: function(paymentId) { /* ... */ },
         //     onError: function(error, payment) { /* ... */ },
         // });
     });

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
 });

