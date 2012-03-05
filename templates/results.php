<?php $reach = new Twitter_Reach( $q ); ?>

      <div class="hero-unit">
        <h1>Total Reach: <?php echo number_format( $reach->reach ); ?></h1>
        <p><strong><?php echo number_format( sizeof( $reach->tweets->results ) ); ?></strong> Results for "<em><?php echo addslashes( $q ); ?></em>"</p>
      </div>
      
		<?php foreach( $reach->tweets->results as $tweet ) { ?>
		<div class="row">
		    <div class="reach span3">
		    	<h3><?php echo number_format( $tweet->reach ); ?></h3>
		    </div>
		    <div class="tweet span9">
		    	<?php echo $reach->render_tweet( $tweet ); ?>
		    </div>
		</div>
		<?php } ?>
		