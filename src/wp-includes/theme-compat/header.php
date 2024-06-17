<?php
/**
 * @package WordPress
 * @subpackage Theme_Compat
 * @deprecated 3.0.0
 *
 * This file is here for backward compatibility with old themes and will be removed in a future version.
 */
_deprecated_file(
	/* translators: %s: Template name. */
	sprintf( __( 'Theme without %s' ), basename( __FILE__ ) ),
	'3.0.0',
	null,
	/* translators: %s: Template name. */
	sprintf( __( 'Please include a %s template in your theme.' ), basename( __FILE__ ) )
);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<title><?php echo wp_get_document_title(); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<link rel="stylesheet" href="<?php echo esc_url( get_stylesheet_uri() ); ?>" type="text/css" media="all" />
	<?php
	if ( is_singular() && pings_open() ) {
		echo '<link rel="pingback" href="', esc_url( get_bloginfo( 'pingback_url' ) ), '" />', "\n";
	}
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
	?>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page">

<?php if ( ! empty( get_bloginfo( 'name' ) ) ) : ?>
	<div id="header" role="banner">
		<div id="headerimg">
			<h1><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
			<div class="description"><?php bloginfo( 'description' ); ?></div>
		</div>
	</div>
	<hr aria-hidden="true" />
<?php endif; ?>
