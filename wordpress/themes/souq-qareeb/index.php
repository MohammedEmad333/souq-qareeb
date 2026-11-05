<?php get_header(); ?>
<main id="main" class="sq-container sq-main">
<?php if ( have_posts() ) : ?>
    <?php while ( have_posts() ) : the_post(); ?>
      <article <?php post_class(); ?>>
        <h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
        <?php the_excerpt(); ?>
      </article>
    <?php endwhile; ?>
    <?php the_posts_pagination(); ?>
<?php else : ?>
    <section class="sq-panel sq-empty-state">
      <?php if ( is_404() ) : ?>
        <h1>الصفحة غير موجودة</h1>
        <p>قد يكون الرابط قد تغيّر أو أُزيلت الصفحة.</p>
      <?php else : ?>
        <h1>لا توجد نتائج</h1>
        <p>جرّب البحث بكلمات أخرى أو تصفّح المنتجات المتاحة.</p>
      <?php endif; ?>
      <?php $shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ); ?>
      <a class="sq-button" href="<?php echo esc_url( $shop_url ); ?>">تصفّح المنتجات</a>
    </section>
<?php endif; ?>
</main>
<?php get_footer(); ?>
