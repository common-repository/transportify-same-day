jQuery( 'div.woocommerce' ).on( 'change', '.qty', function () {
	jQuery( "[name='update_cart']" ).trigger( "click" );
} );