<?php get_header(); ?>
<main id="main" class="sq-container sq-main">
<?php while ( have_posts() ) : the_post(); ?>
<article <?php post_class(); ?>>
<h1><?php the_title(); ?></h1>
<?php the_content(); ?>
</article>
<?php endwhile; ?>
</main>
<?php get_footer(); ?>
