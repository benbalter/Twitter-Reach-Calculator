<?php
/**
 * Given a twitter search query, calculates the a rough number representing the direct reach
 * 
 * Caveats:
 * 
 * 1) Doesn't take into account the fact that a given user may follow two people that tweet a URL
 *     e.g., just adds follower counts of all matching tweets
 *
 * 2) Doesn't take into acount RTs
 *     e.g., if a tweet mentions the query parameter, and then is RT'd, the RT will not be counted
 * 
 * 3) Limited to first 100 results (no paging)
 *
 * @author Benjamin J. Balter
 * @license GPL3
 * @version 1.0
 */
class Twitter_Reach {

	public $tweets = array(); //array of tweet objects
	public $users = array(); //an array of screen names that tweeted the search
	public $reach = array(); //array of user IDs that represent the total reach
	public $embed_code = '<blockquote class="twitter-tweet"><p>%1$s</p>&mdash; %2$s (@%3$s) <a href="https://twitter.com/twitterapi/status/%4$d" data-datetime="%5$s">%6$s</a></blockquote>';
	public $search_base = 'http://search.twitter.com/search.json';
	public $user_lookup_base = 'http://api.twitter.com/1/followers/ids.json?screen_name=%s';
	public $reach; // (int) total reach
	
	/**
	 * The class's main action
	 */
	function __construct( $query = null ) {
		
		if ( $query == null )
			die( 'Please pass query via construct' );
			
		$this->get_tweets( $query );
		$this->build_user_array();
		$this->get_users();
		$this->propegate_follower_counts();
		
	}
	
	/**
	 * Gets a URL and JSON decodes it
	 */
	function get_and_decode( $url ) {
	
		$data = file_get_contents( $url );
		
		if ( !$data )
			return false;
			
		$data = json_decode( $data );
		
		if ( !$data )
			return false;
			
		return $data;

	}
	
	/**
	 * Retrieve array of tweet objects for given query
	 */
	function get_tweets( $q ) {
	
		//starting URL
		$url = sprintf( $this->search_base . '?rpp=100&q=%s', urlencode( $q ) );
		
		while( $url ) {
			
			var_dump( $url );
			$response = $this->get_and_decode( $url );
			
			if ( $response )
				$this->tweets = array_merge( $this->tweets, $response->results );
				
			$url = ( $response !== false && isset( $response->next_page ) ) ? $this->search_base . $response->next_page : false;			
		}
		
		return $this->tweets;
		
	}
	
	/**
	 * Propegates empty follower count array from given set of tweets
	 */
	function build_user_array( $data = null ) {
		
		if ( $data == null )
			$data = $this->tweets;
		
		//loop through tweets and move users into an array
		foreach ( $data as $tweet )
			if ( !in_array( $tweet->from_user, $this->users ) )
				$this->users[] = $tweet->from_user;

		return $this->follower_counts;
		
	}
	
	/**
	 * Retrieve extended user data for a set of users
	 */
	function get_users( $users = array() ) {
		
		if ( empty( $users ) )
			$users = array_keys( $this->follower_counts );
			
		foreach ( array_chunk( $users, 100 ) as $chunk ) {
			
			$user_string = implode( ',', $chunk );
			$url = sprintf( $this->user_lookup_base, $user_string );
			$this->users = array_merge( $this->users, $this->get_and_decode( $url ) );
		}
			
		

	
		return $this->users;
		
	}
	
	/**
	 * Push follower counts into follower_count array and into tweets so we can sort
	 */
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
		
		$this->sort( $tweets );
							
		return $this->follower_counts;
		
	}
	
	/**
	 * Render tweet in blackbird pie compatible format
	 */
	function render_tweet( $tweet ) {		
			
		return sprintf( $this->embed_code, $tweet->text, $tweet->from_user_name, $tweet->from_user, $tweet->id, $tweet->created_at, date( 'F j, Y', strtotime( $tweet->created_at ) ) );
		
	}
	
	/**
	 * Helper function to sort objects by property
	 * source: http://stackoverflow.com/questions/124266/sort-object-in-php
	 */
	function compare( $a, $b ) { 
	  if( $a->reach == $b->reach ){ return 0 ; } 
	  return ($a->reach > $b->reach ) ? -1 : 1;
	} 

	/**
	 * Sorts tweets by reach DESC
	 * source: http://stackoverflow.com/questions/124266/sort-object-in-php
	 */
	function sort( &$array ) {
		usort( $array, array( &$this, 'compare' ) );
	}
	
}