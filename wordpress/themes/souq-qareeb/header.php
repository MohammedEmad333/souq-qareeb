<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#main"><?php esc_html_e( 'Skip to content', 'souq-qareeb' ); ?></a>
<header class="sq-header">
  <div class="sq-container sq-header-inner">
    <div>
      <a class="sq-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
<?php if ( file_exists( get_template_directory() . '/assets/images/logo-header.png' ) ) : ?>
<img class="sq-brand-image" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo-header.png' ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ?: 'سوق قريب' ); ?>" width="190" height="98">
<?php else : ?>
<?php echo esc_html( get_bloginfo( 'name' ) ?: 'سوق قريب' ); ?>
<?php endif; ?>
</a>
      <p class="sq-tagline">بيع، بدّل، تبرّع</p>
    </div>
    <button class="sq-menu-toggle" type="button" aria-controls="sq-primary-nav" aria-expanded="false" aria-label="فتح القائمة">
      <span aria-hidden="true">☰</span><span class="screen-reader-text">القائمة</span>
    </button>
    <nav id="sq-primary-nav" class="sq-nav" aria-label="<?php esc_attr_e( 'Primary navigation', 'souq-qareeb' ); ?>">
      <?php
      wp_nav_menu( array(
          'theme_location' => 'primary',
          'container'      => false,
          'fallback_cb'    => 'wp_page_menu',
      ) );
      ?>
    </nav>
  </div>
</header>
