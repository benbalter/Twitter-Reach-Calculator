<?php

class Twitter_Reach {

	public $tweets = array();
	public $users = array();
	public $follower_counts = array();
	public $embed_code = '<blockquote class="twitter-tweet"><p>%1$s</p>&mdash; %2$s (@%3$s) <a href="https://twitter.com/twitterapi/status/%4$d" data-datetime="%5$s">%6$s</a></blockquote>';
	public $search_base = 'http://search.twitter.com/search.json?rpp=100&q=%s';
	public $user_lookup_base = 'http://api.twitter.com/1/users/lookup.json?include_entities=true&screen_name=%s';
	public $reach;
	
	function __construct( $query = null ) {
		
		if ( $query == null )
			die( 'Please pass query via construct' );
			
		$this->get_tweets( $query );
		$this->build_follower_array();
		$this->get_users();
		$this->propegate_follower_counts();
		
	}
	
	function get_and_decode( $url ) {
	
		$data = file_get_contents( $url );
		
		if ( !$data )
			die( "Can't retrieve data from url: $url" );
			
		$data = json_decode( $data );
		
		if ( !$data )
			die( "Can't parse data from url: $url" );
			
		return $data;

	}
	
	function get_tweets( $q ) {
	
		$url = sprintf( $this->search_base, urlencode( $q ) );
		$this->tweets = $this->get_and_decode( $url );
		return $this->tweets;
		
	}
	
	function build_follower_array( $data = null ) {
		
		if ( $data == null )
			$data = $this->tweets;
		
		foreach ( $data->results as $tweet )
			$this->follower_counts[ $tweet->from_user ] = 0;

		return $this->follower_counts;
		
	}
	
	function get_users( $users = array() ) {
		
		if ( empty( $users ) )
			$users = array_keys( $this->follower_counts );
			
		$user_string = implode( ',', $users );
		$url = sprintf( $this->user_lookup_base, $user_string );

		$this->users = $this->get_and_decode( $url );
	
		return $this->users;
		
	}
	
	function propegate_follower_counts( $users = array(), $tweets = array() ) {
		
		if ( empty( $users ) )
			$users = $this->users;
			
		if ( empty( $tweets ) )
			$tweets = $this->tweets;

		foreach ( $users as $user )
			$this->follower_counts[ $user->screen_name ] = (int) $user->followers_count;	
		
		foreach ( $tweets->results as &$tweet )
			$tweet->reach = $this->follower_counts[ $tweet->from_user ];
			
		$this->reach = array_sum( $this->follower_counts );
		
		$this->sort( $tweets->results );
							
		return $this->follower_counts;
		
	}
	
	function render_tweet( $tweet ) {		
			
		return sprintf( $this->embed_code, $tweet->text, $tweet->from_user_name, $tweet->from_user, $tweet->id, $tweet->created_at, date( 'F j, Y', strtotime( $tweet->created_at ) ) );
		
	}
	
	function compare( $a, $b ) { 
	  if( $a->reach == $b->reach ){ return 0 ; } 
	  return ($a->reach > $b->reach ) ? -1 : 1;
	} 
	
	//http://stackoverflow.com/questions/124266/sort-object-in-php
	function sort( &$array ) {
		usort( $array, array( &$this, 'compare' ) );
	}
	
}