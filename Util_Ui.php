<?php
namespace W3TC;

class Util_Ui {
	/**
	 * Returns button html
	 *
	 * @param string  $text
	 * @param string  $onclick
	 * @param string  $class
	 * @return string
	 */
	static public function button( $text, $onclick = '', $class = 'button',
		$name = '' ) {
		$maybe_name = ( empty( $name ) ? '' :
			' name="' . htmlspecialchars( $name ) . '"' );
		return '<input type="button"' . $maybe_name . ' class="' .
			htmlspecialchars( $class ) . '" value="' . htmlspecialchars( $text ) .
			'" onclick="' . htmlspecialchars( $onclick ) . '" />';
	}

	/**
	 * Returns button link html
	 *
	 * @param string  $text
	 * @param string  $url
	 * @param boolean $new_window
	 * @return string
	 */
	static public function button_link( $text, $url, $new_window = false,
		$class = 'button', $name = '' ) {
		$url = str_replace( '&amp;', '&', $url );

		if ( $new_window ) {
			$onclick = sprintf( 'window.open(\'%s\');', addslashes( $url ) );
		} else {
			$onclick = '';

			if ( strpos( $class, 'w3tc-button-ignore-change' ) >= 0 )
				$onclick .= 'w3tc_beforeupload_unbind(); ';

			$onclick .= sprintf( 'document.location.href=\'%s\';', addslashes( $url ) );
		}

		return Util_Ui::button( $text, $onclick, $class, $name );
	}

	static public function url( $addon ) {
		if ( !isset( $addon['page'] ) )
			$addon['page'] = Util_Request::get_string( 'page', 'w3tc_dashboard' );

		$url = 'admin.php';
		$amp = '?';
		foreach ( $addon as $key => $value ) {
			$url .= $amp . urlencode( $key ) . '=' . urlencode( $value );
			$amp = '&';
		}

		$url = wp_nonce_url( $url, 'w3tc' );

		return $url;
	}

	/**
	 * Returns hide note button html
	 *
	 * @param string  $text
	 * @param string  $note
	 * @param string  $redirect
	 * @param boolean $admin         if to use config admin
	 * @param string  $page
	 * @param string  $custom_method
	 * @return string
	 */
	static public function button_hide_note( $text, $note, $redirect = '',
		$admin = false, $page = '',
		$custom_method = 'w3tc_default_hide_note' ) {
		if ( $page == '' ) {
			$page = Util_Request::get_string( 'page', 'w3tc_dashboard' );
		}

		$url = sprintf( 'admin.php?page=%s&%s&note=%s', $page, $custom_method, $note );

		if ( $admin )
			$url .= '&admin=1';

		if ( $redirect != '' ) {
			$url .= '&redirect=' . urlencode( $redirect );
		}

		$url = wp_nonce_url( $url, 'w3tc' );

		return Util_Ui::button_link( $text, $url, false, 'button',
			'w3tc_hide_' . $custom_method );
	}

	static public function button_hide_note2( $parameters ) {
		return Util_Ui::button_link(
			__( 'Hide this message', 'w3-total-cache' ),
			Util_Ui::url( $parameters ),
			false, 'button',
			'w3tc_hide_' . Util_Ui::config_key_to_http_name( $parameters['key'] ) );
	}

	static public function action_button( $action, $url, $class = '',
		$new_window = false ) {
		return Util_Ui::button_link( $action, $url, $new_window, $class );
	}
	/**
	 * Returns popup button html
	 *
	 * @param string  $text
	 * @param string  $action
	 * @param string  $params
	 * @param integer $width
	 * @param integer $height
	 * @return string
	 */
	static public function button_popup( $text, $action, $params = '', $width = 800, $height = 600 ) {
		$url = wp_nonce_url( sprintf( 'admin.php?page=w3tc_dashboard&w3tc_%s%s', $action, ( $params != '' ? '&' . $params : '' ) ), 'w3tc' );
		$url = str_replace( '&amp;', '&', $url );

		$onclick = sprintf( 'window.open(\'%s\', \'%s\', \'width=%d,height=%d,status=no,toolbar=no,menubar=no,scrollbars=yes\');', $url, $action, $width, $height );

		return Util_Ui::button( $text, $onclick );
	}

	/**
	 * Returns label string for a config key.
	 *
	 * @param string  $config_key
	 * @param string  $area
	 */
	static public function config_label( $config_key ) {
		static $config_labels = null;
		if ( is_null( $config_labels ) )
			$config_labels = apply_filters( 'w3tc_config_labels', array() );

		if ( isset( $config_labels[$config_key] ) )
			return $config_labels[$config_key];

		return '';
	}

	/**
	 * Prints the label string for a config key.
	 *
	 * @param string  $config_key
	 * @param string  $area
	 */
	static public function e_config_label( $config_key ) {
		echo Util_Ui::config_label( $config_key );
	}

	/**
	 * Returns postbox header
	 *
	 * @param string  $title
	 * @param string  $class
	 * @param string  $id
	 * @return string
	 */
	static public function postbox_header( $title, $class = '', $id = '' ) {
		if ( !empty( $id ) ) {
			$id = ' id="' . esc_attr( $id ) . '"';
		}
		echo '<div' . $id . ' class="postbox ' . $class . '"><div class="handlediv" title="' . __( 'Click to toggle', 'w3-total-cache' ) . '"><br /></div><h3 class="hndle"><span>' . $title . '</span></h3><div class="inside">';
	}

	/**
	 * Returns postbox footer
	 *
	 * @return string
	 */
	static public function postbox_footer() {
		echo '</div></div>';
	}




	static public function button_config_save( $id = '', $extra = '' ) {
		$b1_id = 'w3tc_save_options_' . $id;
		$b2_id = 'w3tc_default_save_and_flush_' . $id;

?>
        <p class="submit">
            <?php echo Util_Ui::nonce_field( 'w3tc' ); ?>
            <input type="submit" id="<?php echo $b1_id ?>"
                name="w3tc_save_options"
                class="w3tc-button-save button-primary"
                value="<?php _e( 'Save all settings', 'w3-total-cache' ); ?>" />
            <?php echo $extra ?>
            <?php if ( !is_network_admin() ): ?>
            <input type="submit" id="<?php echo $b2_id ?>"
                name="w3tc_default_save_and_flush" style="float: right"
                class="w3tc-button-save button-primary"
                value="<?php _e( 'Save Settings & Purge Caches', 'w3-total-cache' ); ?>" />
            <?php endif ?>
        </p>
        <?php
	}

	static public function sealing_disabled( $key ) {
		$c = Dispatcher::config();
		if ( $c->is_sealed( $key ) )
			echo 'disabled="disabled" ';
	}

	/**
	 * Returns nonce field HTML
	 *
	 * @param string  $action
	 * @param string  $name
	 * @param bool    $referer
	 * @internal param bool $echo
	 * @return string
	 */
	static public function nonce_field( $action = -1, $name = '_wpnonce', $referer = true ) {
		$name = esc_attr( $name );
		$return = '<input type="hidden" name="' . $name . '" value="' . wp_create_nonce( $action ) . '" />';

		if ( $referer ) {
			$return .= wp_referer_field( false );
		}

		return $return;
	}

	/**
	 * Returns an notification box
	 *
	 * @param string  $message
	 * @param string  $id      adds an id to the notification box
	 * @return string
	 */
	static public function get_notification_box( $message, $id = '' ) {
		if ( !isset( $_GET['page'] ) || ( isset( $_GET['page'] ) && substr( $_GET['page'], 0, 5 ) != 'w3tc_' ) )
			$logo = sprintf( '<img src="%s" alt="W3 Total Cache" style="height:30px" />"', plugins_url( '/pub/img/W3TC_dashboard_logo_title.png', W3TC_FILE ) .  '' );
		else
			$logo = '';
		return sprintf( '<div %s class="updated">%s</div>', $id? "id=\"$id\"" : '' , $logo . $message );
	}

	/**
	 * Echos an notification box
	 *
	 * @param string  $message
	 * @param string  $id      adds an id to the notification box
	 */
	static public function e_notification_box( $message, $id = '' ) {
		echo Util_Ui::get_notification_box( $message, $id );
	}

	/**
	 * Echos an error box
	 *
	 * @param unknown $message
	 * @param string  $id
	 */
	static public function error_box( $message, $id = '' ) {
		if ( !isset( $_GET['page'] ) || ( isset( $_GET['page'] ) && substr( $_GET['page'], 0, 5 ) != 'w3tc_' ) )
			$logo = sprintf( '<img src="%s" alt="W3 Total Cache" style="height:30px" />', plugins_url( '/pub/img/W3TC_dashboard_logo_title.png', W3TC_FILE ) .  '' );
		else
			$logo = '';
		$v = sprintf( '<div %s class="error">%s</div>', $id? "id=\"$id\"" : '' , $logo . $message );

		echo $v;
	}

	/**
	 * Format bytes into B, KB, MB, GB and TB
	 *
	 * @param unknown $bytes
	 * @param int     $precision
	 * @return string
	 */
	static public function format_bytes( $bytes, $precision = 2 ) {
		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );

		$bytes = max( $bytes, 0 );
		$pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow = min( $pow, count( $units ) - 1 );

		// Uncomment one of the following alternatives
		$bytes /= pow( 1024, $pow );
		// $bytes /= (1 << (10 * $pow));

		return round( $bytes, $precision ) . ' ' . $units[$pow];
	}

	static public function format_mbytes( $bytes, $precision = 2 ) {
		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );

		$bytes = max( $bytes, 0 );
		$pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow = min( $pow, count( $units ) - 1 );

		// Uncomment one of the following alternatives
		$bytes /= pow( 1024, $pow );
		// $bytes /= (1 << (10 * $pow));

		return round( $bytes, $precision ) . ' ' . $units[$pow + 2];
	}

	/**
	 * Echos an input text element
	 *
	 * @param string  $id
	 * @param string  $name
	 * @param string  $value
	 * @param bool    $disabled
	 * @param int     $size
	 */
	static public function hidden( $id, $name, $value ) {
		echo '<input type="hidden" id="' . esc_attr( $id ) .
			'" name="' . esc_attr( $name ) .
			'" value="' . esc_attr( $value ) . '" />';
	}

	/**
	 * Echos an label element
	 *
	 * @param string  $id
	 * @param string  $text
	 */
	static public function label( $id, $text ) {
		echo '<label for="' . esc_attr( $id ) . '">';
		echo $text;
		echo '</label>';
	}

	/**
	 * Echos an input text element
	 *
	 * @param string  $id
	 * @param string  $name
	 * @param string  $value
	 * @param bool    $disabled
	 * @param int     $size
	 */
	static public function textbox( $id, $name, $value, $disabled = false, $size = 40 ) {
		echo '<input class="enabled" type="text" id="' . esc_attr( $id ) .
			'" name="' . esc_attr( $name ) .
			'" value="' . esc_attr( $value ) . '" ';
		disabled( $disabled );
		echo ' size="' . esc_attr( $size ) . '" />';
	}

	/**
	 * Echos an input password element
	 *
	 * @param string  $id
	 * @param string  $name
	 * @param string  $value
	 * @param bool    $disabled
	 * @param int     $size
	 */
	static public function passwordbox( $id, $name, $value, $disabled = false, $size = 40 ) {
		echo '<input class="enabled" type="password" id="' . esc_attr( $id );
		echo '" name="'. esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" ';
		disabled( $disabled );
		echo ' size="';
		esc_attr_e( $size );
		echo '" />';
	}

	/**
	 * Echos an select element
	 *
	 * @param string  $id
	 * @param string  $name
	 * @param bool    $state     whether checked or not
	 * @param bool    $disabled
	 * @param array   $optgroups
	 */
	static public function selectbox( $id, $name, $value, $values,
		$disabled = false, $optgroups = null ) {
		echo '<select id="' . esc_attr( $id ) .
			'" name="' . esc_attr( $name ) .'" ';
		disabled( $disabled );
		echo ">\n";

		if ( !is_array( $optgroups ) ) {
			// simle control
			foreach ( $values as $key => $descriptor )
				self::option( $key, $value, $descriptor );
		} else {
			// with optgroups
			$current_optgroup = -1;
			foreach ( $values as $key => $descriptor ) {
				$optgroup = ( isset( $descriptor['optgroup'] ) ? $descriptor['optgroup'] : -1 );
				if ( $optgroup != $current_optgroup ) {
					if ( $current_optgroup != -1 )
						echo '</optgroup>';
					echo '<optgroup label="' . esc_attr( $optgroups[$optgroup] ) .
						'">' . "\n";
					$current_optgroup = $optgroup;
				}

				self::option( $key, $value, $descriptor );
			}

			if ( $current_optgroup != -1 )
				echo '</optgroup>';
		}


		echo '</select>';
	}

	static private function option( $key, $selected_value, $descriptor ) {
		if ( !is_array( $descriptor ) ) {
			$label = $descriptor;
			$disabled = false;
		} else {
			$label = $descriptor['label'];
			$disabled = !empty( $descriptor['disabled'] );
		}

		echo '<option value="' . esc_attr( $key ) . '" ';
		selected( $selected_value, $key );
		disabled( $disabled );
		echo '>';
		echo $label;
		echo '</option>' . "\n";
	}

	/**
	 * Echos a group of radio elements
	 *
	 * @param string  $id
	 * @param string  $name
	 * @param bool    $state    whether checked or not
	 * @param bool    $disabled
	 */
	static public function radiogroup( $name, $value, $values,
		$disabled = false ) {
		foreach ( $values as $key => $label ) {
			echo '<label><input type="radio" name="' . esc_attr( $name )  .
				'" value="' . esc_attr( $key ) . '"';
			checked( $value, $key );
			disabled( $disabled );
			echo ' />';
			echo $label;
			echo '</label>' . "\n";
		}
	}

	/**
	 * Echos an input text element
	 *
	 * @param string  $id
	 * @param string  $name
	 * @param string  $value
	 * @param bool    $disabled
	 */
	static public function textarea( $id, $name, $value, $disabled = false ) {?>
        <textarea class="enabled" id="<?php echo esc_attr( $id )?>"
            name="<?php echo esc_attr( $name )?>" rows="5" cols=25 style="width: 100%"
            <?php disabled( $disabled ) ?>><?php echo esc_textarea( $value )?></textarea>
    <?php
	}

	/**
	 * Echos an input checkbox element
	 *
	 * @param string  $id
	 * @param string  $name
	 * @param bool    $state    whether checked or not
	 * @param bool    $disabled
	 */
	static public function checkbox( $id, $name, $state, $disabled = false, $label = null ) {
		if ( !is_null( $label ) )
			echo '<label>';

		echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' .
			( !$disabled ? '0' : ( $state ? '1' : '0' ) ). '">' .
			"\n";
		echo '<input class="enabled" type="checkbox" id="' . esc_attr( $id ) .
			'" name="' . esc_attr( $name ) . '" value="1" ';
		checked( $state );
		disabled( $disabled );
		echo ' /> ';

		if ( !is_null( $label ) )
			echo $label . '</label>';
	}

	/**
	 * Echos an element
	 *
	 * @param string  $type
	 * @param string  $id
	 * @param string  $name
	 * @param mixed   $value
	 * @param bool    $disabled
	 */
	static public function element( $type, $id, $name, $value, $disabled = false ) {
		switch ( $type ) {
		case 'textbox':
			Util_Ui::textbox( $id, $name, $value, $disabled );
			break;
		case 'password':
			Util_Ui::passwordbox( $id, $name, $value, $disabled );
			break;
		case 'textarea':
			Util_Ui::textarea( $id, $name, $value, $disabled );
			break;
		case 'checkbox':
		default:
			Util_Ui::checkbox( $id, $name, $value, $disabled );
			break;

		}
	}



	/**
	 * Renders <tr> element with controls
	 * id =>
	 * label =>
	 * label_class =>
	 * <control> => details
	 * style - default is label,controls view,
	 *         alternative is one-column view
	 */
	static public function table_tr( $a ) {
		$id = isset( $a['id'] ) ? $a['id'] : '';
		$a = apply_filters( 'w3tc_ui_settings_item', $a );

		if ( isset( $a['style'] ) ) {
			echo '<tr><th colspan="2">';
		} else {
			echo '<tr><th';

			if ( isset( $a['label_class'] ) )
				echo ' class="' . $a['label_class'] . '"';
			echo '>';
			if ( isset( $a['label'] ) )
				Util_Ui::label( $id, $a['label'] );

			echo "</th>\n<td>\n";
		}

		foreach ( $a as $key => $e ) {
			if ( $key == 'checkbox' )
				Util_Ui::checkbox( $id,
					isset( $e['name'] ) ? $e['name'] : null,
					$e['value'],
					( isset( $e['disabled'] ) ? $e['disabled'] : false ),
					( isset( $e['label'] ) ? $e['label'] : null ) );
			elseif ( $key == 'description' )
				echo '<br /><span class="description">' . $e . '</span>';
			elseif ( $key == 'hidden' )
				Util_Ui::hidden( '', $e['name'], $e['value'] );
			elseif ( $key == 'html' )
				echo $e;
			elseif ( $key == 'radiogroup' )
				Util_Ui::radiogroup( $e['name'], $e['value'], $e['values'] );
			elseif ( $key == 'selectbox' )
				Util_Ui::selectbox( $id, $e['name'], $e['value'], $e['values'],
					( isset( $e['disabled'] ) ? $e['disabled'] : false ),
					( isset( $e['optgroups'] ) ? $e['optgroups'] : null ) );
			elseif ( $key == 'textbox' )
				Util_Ui::textbox( $id, $e['name'], $e['value'],
					( isset( $e['disabled'] ) ? $e['disabled'] : false ),
					( !empty( $e['size'] ) ? $e['size'] : 20 ) );
			elseif ( $key == 'textarea' )
				Util_Ui::textarea( $id, $e['name'], $e['value'],
					( isset( $e['disabled'] ) ? $e['disabled'] : false ) );
		}

		if ( isset( $a['style'] ) )
			echo "</th>";
		else
			echo "</td>";

		echo "</tr>\n";
	}

	/**
	 * Prints configuration item UI based on description
	 *   key => configuration key
	 *   label => configuration key's as its introduced to the user
	 *   value => it's value
	 *   disabled => if disabled
	 *
	 *   control => checkbox | radiogroup | selectbox | textbox
	 *   checkbox_label => text shown after the textbox
	 *   radiogroup_values => array of possible values for radiogroup
	 *   selectbox_values => array of possible values for dropdown
	 *   selectbox_optgroups =>
	 *   textbox_size =>
	 *
	 *   control_after => something after control to add
	 *   description => description shown to the user below
	 */
	static public function config_item( $a ) {
		$c = Dispatcher::config();

		if ( !isset( $a['value'] ) || is_null( $a['value'] ) ) {
			$a['value'] = $c->get( $a['key'] );
			if ( is_array( $a['value'] ) )
				$a['value'] = implode( "\n", $a['value'] );
		}

		if ( isset( $a['disabled'] ) && !is_null( $a['disabled'] ) )
			$disabled = $a['disabled'];
		else
			$disabled = $c->is_sealed( $a['key'] );

		if ( empty( $a['label'] ) )
			$a['label'] = Util_Ui::config_label( $a['key'] );

		$action_key = $a['key'];
		if ( is_array( $action_key ) )
			$action_key = 'extension.' . $action_key[0] . '.' . $action_key[1];

		$a = apply_filters( 'w3tc_ui_config_item_' . $action_key, $a );

		// convert to table_tr data
		$table_tr = array(
			'id' => Util_Ui::config_key_to_http_name( $a['key'] ),
			'label' => $a['label']
		);

		if ( isset( $a['style'] ) )
			$table_tr['style'] = $a['style'];

		if ( $a['control'] == 'checkbox' ) {
			$table_tr['label_class'] = 'w3tc_config_checkbox';
			$table_tr['checkbox'] = array(
				'name' => Util_Ui::config_key_to_http_name( $a['key'] ),
				'value' => $a['value'],
				'disabled' => $disabled,
				'label' => $a['checkbox_label']
			);
		} else if ( $a['control'] == 'radiogroup' ) {
				$table_tr['radiogroup'] = array(
					'name' => Util_Ui::config_key_to_http_name( $a['key'] ),
					'value' => $a['value'],
					'disabled' => $disabled,
					'values' => $a['radiogroup_values']
				);
			} else if ( $a['control'] == 'selectbox' ) {
				$table_tr['selectbox'] = array(
					'name' => Util_Ui::config_key_to_http_name( $a['key'] ),
					'value' => $a['value'],
					'disabled' => $disabled,
					'values' => $a['selectbox_values'],
					'optgroups' => isset( $a['selectbox_optgroups'] ) ?
					$a['selectbox_optgroups'] : null
				);
			} else if ( $a['control'] == 'textbox' ) {
				$table_tr['textbox'] = array(
					'name' => Util_Ui::config_key_to_http_name( $a['key'] ),
					'value' => $a['value'],
					'disabled' => $disabled,
					'size' => isset( $a['textbox_size'] ) ?  $a['textbox_size'] : null
				);
			} else if ( $a['control'] == 'textarea' ) {
				$table_tr['textarea'] = array(
					'name' => Util_Ui::config_key_to_http_name( $a['key'] ),
					'value' => $a['value'],
					'disabled' => $disabled
				);
			}

		if ( isset( $a['control_after'] ) )
			$table_tr['html'] = $a['control_after'];

		if ( isset( $a['description'] ) )
			$table_tr['description'] = $a['description'];

		Util_Ui::table_tr( $table_tr );
	}



	/**
	 * Displays config item - caching engine selectbox
	 */
	static public function config_item_engine( $a ) {
		if ( isset( $a['empty_value'] ) && $a['empty_value'] ) {
			$values[''] = array(
				'label' => 'Please select a method'
			);
		}

		$values['file'] = array(
			'label' => __( 'Disk', 'w3-total-cache' ),
			'optgroup' => 0
		);
		$values['apc'] = array(
			'disabled' => !Util_Installed::apc(),
			'label' => __( 'Opcode: Alternative PHP Cache (APC / APCu)', 'w3-total-cache' ),
			'optgroup' => 1
		);
		$values['eaccelerator'] = array(
			'disabled' => !Util_Installed::eaccelerator(),
			'label' => __( 'Opcode: eAccelerator', 'w3-total-cache' ),
			'optgroup' => 1
		);
		$values['xcache'] = array(
			'disabled' => !Util_Installed::xcache(),
			'label' => __( 'Opcode: XCache', 'w3-total-cache' ),
			'optgroup' => 1
		);
		$values['wincache'] = array(
			'disabled' => !Util_Installed::wincache(),
			'label' => __( 'Opcode: WinCache', 'w3-total-cache' ),
			'optgroup' => 1
		);
		$values['memcached'] = array(
			'disabled' => !Util_Installed::memcached(),
			'label' => __( 'Memcached', 'w3-total-cache' ),
			'optgroup' => 2
		);
		$values['redis'] = array(
			'disabled' => !Util_Installed::redis(),
			'label' => __( 'Redis', 'w3-total-cache' ),
			'optgroup' => 2
		);

		Util_Ui::config_item( array(
				'key' => $a['key'],
				'label' => ( isset( $a['label'] ) ? $a['label'] : null ),
				'disabled' => ( isset( $a['disabled'] ) ? $a['disabled'] : null ),
				'control' => 'selectbox',
				'selectbox_values' => $values,
				'selectbox_optgroups' => array(
					__( 'Shared Server:', 'w3-total-cache' ),
					__( 'Dedicated / Virtual Server:', 'w3-total-cache' ),
					__( 'Multiple Servers:', 'w3-total-cache' )
				),
			) );
	}



	/**
	 * On subblogs - shows button to enable/disable custom configuration
	 *   $a['key'] - config key *_overloaded which are managed
	 */
	static public function config_overloading_button( $a ) {
		$c = Dispatcher::config();
		if ( $c->is_master() )
			return;

		if ( $c->get_boolean( $a['key'] ) ) {
			$name = 'w3tc_config_overloaded_disable~' .
				Util_Ui::config_key_to_http_name( $a['key'] );
			$value = __( 'Use common settings', 'w3-total-cache' );
		} else {
			$name = 'w3tc_config_overloaded_enable~' .
				Util_Ui::config_key_to_http_name( $a['key'] );
			$value = __( 'Use specific settings', 'w3-total-cache' );
		}

		echo '<div style="float: right">';
		echo '<input type="submit" class="button" name="' .
			esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" />';
		echo '</div>';
	}

	/**
	 *
	 *
	 * @param string  $path
	 * @return string|void
	 */
	static public function admin_url( $path ) {
		if ( is_network_admin() ) {
			return network_admin_url( $path );
		}
		return admin_url( $path );
	}

	/**
	 * Returns a preview link with current state
	 *
	 * @return string
	 */
	static public function preview_link() {
		return Util_Ui::button_link( __( 'Preview', 'w3-total-cache' ),
			Util_Ui::url( array( 'w3tc_default_previewing' => 'y' ) ), true );
	}

	/**
	 * Takes seconds and converts to array('Nh ','Nm ', 'Ns ', 'Nms ') or "Nh Nm Ns Nms"
	 *
	 * @param unknown $input
	 * @param bool    $string
	 * @return array|string
	 */
	static public function secs_to_time( $input, $string = true ) {
		$input = (double)$input;
		$time = array();
		$msecs = floor( $input*1000 % 1000 );
		$seconds = $input % 60;
		$input = floor( $input / 60 );
		$minutes = $input % 60;
		$input = floor( $input / 60 );
		$hours = $input % 60;
		if ( $hours )
			$time[] = sprintf( __( '%dh', 'w3-total-cache' ), $hours );
		if ( $minutes )
			$time[] = sprintf( __( '%dm', 'w3-total-cache' ), $minutes );
		if ( $seconds )
			$time[] = sprintf( __( '%ds', 'w3-total-cache' ), $seconds );
		if ( $msecs )
			$time[] = sprintf( __( '%dms', 'w3-total-cache' ), $msecs );

		if ( empty( $time ) )
			$time[] = sprintf( __( '%dms', 'w3-total-cache' ), 0 );
		if ( $string )
			return implode( ' ', $time );
		return $time;
	}



	/**
	 * Returns option name accepted by W3TC as http paramter
	 * from it's id (full name from config file)
	 */
	static public function config_key_to_http_name( $id ) {
		if ( is_array( $id ) )
			$id = $id[0] . '___' . $id[1];

		return str_replace( '.', '__', $id );
	}



	/*
     * Converts configuration key returned in http _GET/_POST
     * to configuration key
     */
	static public function config_key_from_http_name( $http_key ) {
		$a = explode( '___', $http_key );
		if ( count( $a ) == 2 ) {
			$a[0] = Util_Ui::config_key_from_http_name( $a[0] );
			$a[1] = Util_Ui::config_key_from_http_name( $a[1] );
			return $a;
		}

		return str_replace( '__', '.', $http_key );
	}
}
