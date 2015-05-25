<?php

class AutoMoreTag_Options {

	public function __construct() {

		add_action(    'admin_init'  , array( $this, 'initOptionsPage' )         );
		add_action(    'admin_menu'  , array( $this, 'addPage'         )         );

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

		$input['units'] = ( (int)$input['units'] == 1 ) ? 1 : ( ( (int)$input['units'] == 2 ) ? 2 : 3 );

		if($input['units'] == 3 && $input['quantity'] > 100) {

			$input['messages']['notices'][] = 'While using Percentage breaking, you cannot us a number larger than 100%. This field has been reset to 50%.';
			$input['quantity'] = 50;

		}

		$input['break'] = (isset($input['break']) && (int) $input['break'] == 2) ? 2 : 1;

		return $input;

	}

	public function buildOptionsPage() {

		require_once( dirname( __FILE__ ) . '/options-template.php');

	}

	public function addPage() {

		$this->option_page = add_options_page('Auto More Tag', 'Auto More Tag', 'manage_options', 'tw_auto_more_tag', array($this, 'buildOptionsPage'));

	}

}

new AutoMoreTag_Options();
