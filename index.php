<?php
require_once( 'class.twitter-reach.php' );
$q = isset( $_GET['q'] ) ? $_GET['q'] : false;

include( dirname( __FILE__ ) . '/templates/header.php' );

if ( !$q )
	include( dirname( __FILE__ ) . '/templates/form.php' );
else
	include( dirname( __FILE__ ) . '/templates/results.php' );

include( dirname( __FILE__ ) . '/templates/footer.php' );
	

