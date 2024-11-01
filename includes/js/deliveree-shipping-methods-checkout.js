jQuery( 'body' ).on( 'change', 'input[name^="billing_"], input[name^="shipping_"]', function() {
	jQuery( 'body' ).trigger( 'update_checkout' );
} );
