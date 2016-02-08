<?php
/**
 * Plugin Name: MA - Age Gate
 * Plugin URI: http://marsapril.se
 * Description: A plugin that blocks users from accessing your site until they agree that they are over a certain age.
 * Author: Mathias Åström, MarsApril
 * Version: 0.1
 */

if ( !defined('ABSPATH') ){
	die('No script kiddies please!');
}


// Plugin hooks
add_action( 'init', 'maag_verify_age_form_submit' );
add_action( 'template_redirect', 'maag_age_redirect' );
add_shortcode( 'age_gate_login', 'maag_get_age_login_form' );




/**
 * Function returns the requested URL.
 * @return string
 */
function maag_get_requested_url(){
	$http = ( $_SERVER['HTTPS'] ) ? 'https://' : 'http://';
    return esc_url( $http . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
}


/**
 * Function controls the page redirect flow for the age gate depending if the user has an valid age session active.
 */
function maag_age_redirect(){

	// Setup the requested permalink
    $permalink = maag_get_requested_url();

	// Get the verification page id
	$page_id = (int) apply_filters( 'maag_set_verification_page_id', 4 );

	if( $page_id <= 0 ){
		return;
	}

	// If the user is requesting admin panel, the verification page or if WP is doing an ajax request
    if( is_admin() || is_page( $page_id ) || defined( 'DOING_AJAX' ) ){
        return;
    }

	// Simple regexp test to see if the request comes from a bot, we need to allow them through the age gate
	if( isset( $_SERVER['HTTP_USER_AGENT'] ) && preg_match('/bot|crawl|slurp|spider/i', $_SERVER['HTTP_USER_AGENT'] ) ){
		return;
	}

	// If the user has a valid session
    if( maag_check_valid_age_session() ){
        return;
    }else{
        wp_redirect( get_permalink( $page_id ) . '?redirect_url=' . esc_url( $permalink ) );
        exit();
    }

}


/**
 * Sets the age gate cookie.
 */
function maag_set_age_session_cookie(){
	setcookie( "maag_user_allowed", '1', time() + 3600, '/' );
}


/**
 * Function can be used in true/false cases to see if a valid age session exists for the current user.
 * @return bool
 */
function maag_check_valid_age_session(){
	return ( isset( $_COOKIE['maag_user_allowed'] ) && $_COOKIE['maag_user_allowed'] === '1' );
}


/**
 * Function handles the POST request from the form login for the age gate, redirects accordingly.
 */
function maag_verify_age_form_submit(){

	if( is_admin() ){
		return;
	}

	if( isset( $_POST['maag_age_submit'] ) && $_POST['maag_age_submit'] ){

	    $verified_age = ( isset( $_POST['maag_age_verification'] ) && $_POST['maag_age_verification'] === '1' ) ? true : false;

	    if( $verified_age ){

		    // Set the age session cookie, so the user does not need to do this the next hour...
	        maag_set_age_session_cookie();

	        // Set the redirect url
            $redirect_url = ( $_GET['redirect_url'] && isset( $_GET['redirect_url'] ) ) ? $_GET['redirect_url'] : home_url();
            wp_redirect( esc_url( $redirect_url ) );
            exit();
	    }
	}
}


/**
 * Used to get the markup for the age gate login form.
 * @return string
 */
function maag_get_age_login_form(){
	$output = sprintf(
		'<form method="post" action="" class="age-gate-form">
			<h2>%3$s</h2>
			<p>
				<input type="checkbox" name="maag_age_verification" value="1" id="age-gate-verification-input">
				<label for="age-gate-verification-input">%1$s</label>
			</p>
			<p>
				<input type="submit" value="%2$s" name="maag_age_submit" id="age-gate-submit-button" class="age-gate-button">
			</p>
		</form>',
		esc_html( apply_filters( 'maag_age_gate_label_text', __('I confirm that I am over 18 years old') ) ),
		esc_attr( apply_filters( 'maag_age_gate_submit_value', __('To the site') ) ),
		esc_html( apply_filters( 'maag_age_gate_title', __('Verify your age') ) )
	);

	return $output;

}

/**
 * Simple output function that can be used instead of the shortcode.
 */
function maag_the_age_login_form(){
	echo maag_get_age_login_form();
}

