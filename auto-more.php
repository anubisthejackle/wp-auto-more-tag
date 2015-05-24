<?php

/*
  Plugin Name: Auto More Tag
  Plugin URI: https://github.com/anubisthejackle/wp-auto-more-tag
  Description: Automatically add a More tag to your posts upon publication. No longer are you required to spend your time figuring out the perfect position for the more tag, you can now set a rule and this plugin will--to the best of it's abilities--add a proper more tag at or at the nearest non-destructive location.
  Author: Travis Weston, Tobias Vogel
  Author URI: https://github.com/anubisthejackle
  Version: 4.1.0
 */

class tw_auto_more_tag {

	public function __construct() {

		add_action(    'admin_init'  , array( $this, 'initOptionsPage' )         );
		add_action(    'admin_menu'  , array( $this, 'addPage'         )         );

		add_filter(    'the_content' , array( $this, 'addTag'          ), '-1', 2);

		add_shortcode( 'amt_override', '__return_null'                           );

	}

	public function addTag($data, $arr = array()) {
		global $post, $pages, $page;

		if( $page > count( $pages ) )
			$page = count( $pages );

		$data = $pages[ $page - 1 ];

		$options = get_option('tw_auto_more_tag');

		if( ( $post->post_type != 'post' && $options['set_pages'] != true ) || ( mb_strlen( strip_tags( $data ) ) <= 0 ) )
			return $data;


		$data = str_replace('<!--more-->', '', $data);
		
		$break = ( $options['break'] === 2 ) ? PHP_EOL : ' ';

		if( mb_strpos( $data, '[amt_override]' ) !== false ){

			$data = str_replace('[amt_override]', '<!--more-->', $data);
			$options['units'] = -1;

		}

		switch ($options['units']) {

			case 1:
				$data = $this->byCharacter($data, $options['quantity'], $break);
				break;

			case 2:
			default:
				$data = $this->byWord($data, $options['quantity'], $break);
				break;

			case 3:
				$data = $this->byPercent($data, $options['quantity'], $break);
				break;

			case -1:
				break;

		}

		$pages[ $page - 1 ] = $data;
		return get_the_content();

	}

	private function insertTag( $data, $length, $break ) {

		

	}

	private function byWord($data, $length, $break) {

		$stripped_data = strip_tags($data);

		$fullLength = mb_strlen($data);

		$strippedLocation = 0;
		$wordCount = 0;
		$insertSpot = $fullLength;
		for ($i = 0; $i < $fullLength; $i++) {
			if (mb_substr($stripped_data, $strippedLocation, 1) != mb_substr($data, $i, 1)) {
				continue;
			}

			if ($wordCount >= $length) {
				if (mb_substr($stripped_data, $strippedLocation, 1) == $break) {
					$insertSpot = $i;
					break;
				}
			}

			if (mb_substr($stripped_data, $strippedLocation, 1) == ' ') {
				$wordCount++;
			}

			$strippedLocation++;
		}

		$start = mb_substr($data, 0, $insertSpot);
		$end = mb_substr($data, $insertSpot);

		if (mb_strlen(trim($start)) > 0 && mb_strlen(trim($end)) > 0)
			$data = $start . '<!--more-->' . $end;

		return $data;
	}

	private function byCharacter($data, $length, $break) {

		$stripped_data = strip_tags($data);
		$fullLength = mb_strlen($data);
		$strippedLocation = 0;
		$insertSpot = $fullLength;
		for ($i = 0; $i < $fullLength; $i++) {
			if (mb_substr($stripped_data, $strippedLocation, 1) != mb_substr($data, $i, 1)) {
				continue;
			}
			if ($strippedLocation >= $length) {
				if (mb_substr($stripped_data, $strippedLocation, 1) == $break) {
					$insertSpot = $i;
					break;
				}
			}
			$strippedLocation++;
		}

		$start = mb_substr($data, 0, $insertSpot);
		$end = mb_substr($data, $insertSpot);

		if (mb_strlen(trim($start)) > 0 && mb_strlen(trim($end)) > 0)
			$data = $start . '<!--more-->' . $end;

		return $data;
	}

	private function byPercent($data, $length, $break) {

		/* Strip Tags, get length */
		$stripped_data = strip_tags($data);
		$lengthOfPost  = mb_strlen($stripped_data);
		$fullLength    = mb_strlen($data);

		/* Find location to insert */
		$insert_location = $lengthOfPost * ($length / 100);

		/* iterate through post, look for differences between stripped and unstripped. If found, continue */
		$strippedLocation = 0;

		$insertSpot = $fullLength;
		for ($i = 0; $i < $fullLength; $i++) {
			if ( mb_substr($stripped_data, $strippedLocation, 1) != mb_substr($data, $i, 1) )
				continue;

			if ( $strippedLocation >= $insert_location ) {
				if ( mb_substr($stripped_data, $strippedLocation, 1) == $break ) {
					$insertSpot = $i;
					break;
				}
			}
			$strippedLocation++;
		}

		$start = mb_substr($data, 0, $insertSpot);
		$end = mb_substr($data, $insertSpot);

		if (mb_strlen(trim($start)) > 0 && mb_strlen(trim($end)) > 0)
			$data = $start . '<!--more-->' . $end;

		return $data;

	}

	public function initOptionsPage() {

		register_setting('tw_auto_more_tag', 'tw_auto_more_tag', array($this, 'validateOptions'));

	}

	public function validateOptions($input) {

		$start = $input;

		$input['messages'] = array(
			'errors' => array(),
			'notices' => array(),
			'warnings' => array()
		);

		$input['quantity'] = ( isset( $input['quantity'] ) && (int)$input['quantity'] > 0 ) ? ( (int)$input['quantity'] ) : 0;

		if( $input['quantity'] != $start['quantity'] ) {

			$input['messages']['notices'][] = 'Quantity cannot be less than 0, and has been set to 0.';

		}

		$input['ignore_man_tag'] = ( isset( $input['ignore_man_tag'] ) && ( (bool)$input['ignore_man_tag'] === true ) ) ? true : false;

		$input['units'] = ( (int)$input['units'] == 1 ) ? 1 : ( ( (int)$input['units'] == 2 ) ? 2 : 3 );

		if($input['units'] == 3 && $input['quantity'] > 100) {

			$input['messages']['notices'][] = 'While using Percentage breaking, you cannot us a number larger than 100%. This field has been reset to 50%.';
			$input['quantity'] = 50;

		}

		$input['break'] = (isset($input['break']) && (int) $input['break'] == 2) ? 2 : 1;

		return $input;

	}

	public function buildOptionsPage() {

		require_once( dirname( __FILE__ ) . '/options.php');

	}

	public function addPage() {

		$this->option_page = add_options_page('Auto More Tag', 'Auto More Tag', 'manage_options', 'tw_auto_more_tag', array($this, 'buildOptionsPage'));

	}

}

$tw_auto_more_tag = new tw_auto_more_tag();

