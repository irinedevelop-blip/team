<?php
/**
 * Plugin Name: Logout → страница входа
 */

add_filter( 'logout_redirect', function ( $redirect_to, $requested, $user ) {
    return 'https://partners.yourweb.kz/wp-login.php?redirect_to=https%3A%2F%2Fpartners.yourweb.kz%2F';
}, 999, 3 );

add_action( 'wp_logout', function () {
    wp_safe_redirect( 'https://partners.yourweb.kz/wp-login.php?redirect_to=https%3A%2F%2Fpartners.yourweb.kz%2F' );
    exit;
}, 999 );

// Страховка: перехватывает финальный редирект WordPress, даже если его подменил другой плагин
add_filter( 'wp_redirect', function ( $location, $status ) {
    if ( strpos( $location, 'loggedout=true' ) !== false ) {
        return 'https://partners.yourweb.kz/wp-login.php?redirect_to=https%3A%2F%2Fpartners.yourweb.kz%2F';
    }
    return $location;
}, 999, 2 );
