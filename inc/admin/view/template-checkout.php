<?php

/*
 *  
 * Template name: Template Checkout
 * 
*/
if ( is_checkout() && !empty( is_wc_endpoint_url('order-received') ) ) {
    get_header('empty');
}
else{
    get_header();
} ?>
	
<div class="container">
    <?php while ( have_posts() ) : the_post(); ?>
        <div <?php post_class( 'clearfix' ); ?> id="<?php the_ID(); ?>">
            <?php the_content(); ?>
        </div>
    <?php endwhile; ?>
</div>

<?php get_footer('empty'); ?>
