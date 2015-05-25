<?php

/*
  Plugin Name: Auto More Tag
  Plugin URI: https://github.com/anubisthejackle/wp-auto-more-tag
  Description: Automatically add a More tag to your posts upon publication. No longer are you required to spend your time figuring out the perfect position for the more tag, you can now set a rule and this plugin will--to the best of it's abilities--add a proper more tag at or at the nearest non-destructive location.
  Author: Travis Weston, Tobias Vogel
  Author URI: https://github.com/anubisthejackle
  Version: 4.1.0
 */
require_once( dirname( __FILE__ ) . '/options.php' );
class AutoMoreTag {

	public function __construct() {

		add_filter(    'the_content' , array( $this, 'addTag'          ), '-1', 2);

		add_shortcode( 'amt_override', '__return_null'                           );

	}

	public function addTag() {

		$options = get_option('tw_auto_more_tag');

		$data = &$this->getPage();
		$data = str_replace('<!--more-->', '', $data );
		
		$break = $this->getBreakPoint( $options['break'] );

		if( mb_strpos( $data, '[amt_override]' ) !== false ){

			$data = str_replace('[amt_override]', '<!--more-->', $data);
			return get_the_content();

		}

		$data = $this->insertTag( $data, $options['quantity'], $options['units'], $break );

		return get_the_content( empty( $options['custom_content'] ) ? null : $options['custom_content'] );

	}

	private function &getPage() {
		global $post, $pages, $page;

		if( $page > count( $pages ) )
			$page = count( $pages );

		return $pages[ $page - 1 ];

	}

	private function getBreakPoint( $breakOn ) {

		switch( $breakOn ){
			case 2:
				return PHP_EOL;
				break;
			case 1:
			default:
				return ' ';
				break;
		}

	}

	private function insertTag( $data, $length, $units, $break ) {

		if( mb_strlen( strip_tags( $data ) ) <= 0 )
			return $data;

		switch( $units ) {

			case 3:
				$length = ceil( mb_strlen( strip_tags( $data ) ) * ( $length / 100 ) );
			case 1:
				$location = $this->getInsertLocation($data, $length, $break, 'characters');
				break;
			case 2:
			default:
				$location = $this->getInsertLocation($data, $length, $break, 'words');
				break;

		}
	
		return $this->splitAndInsert( $data, $location );	

	}

	private function splitAndInsert( $data, $location ) {

		$start = mb_substr( $data, 0, $location);
		$end = mb_substr( $data, $location );

		if( !empty( $location ) && mb_strlen( trim( $start ) ) > 0 && mb_strlen( trim( $end ) ) > 0 )
			$data = $start . '<!--more-->' . $end;

		return $data;

	}

	private function getInsertLocation( $data, $insertLocation, $break, $decider = 'words' ) {
		
		$words = 0;
		$characters = 0;
	
		$stripped_data = strip_tags( $data );

		for( $i = 0; $i < mb_strlen( $data ); $i++ ) {

			if ( mb_substr( $stripped_data, $characters, 1 ) != mb_substr( $data, $i, 1 ) )
				continue;

			if (mb_substr($stripped_data, $characters, 1) == ' ')	
				$words++;
			
			$characters++;

			if( ${$decider} < ( $insertLocation + 1 ) || ( mb_substr( $stripped_data, ( $characters - 1 ), 1 ) != $break ) )
				continue;
			
			return $i;

		}

	}

}

new AutoMoreTag();
