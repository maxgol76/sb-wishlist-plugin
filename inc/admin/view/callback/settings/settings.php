<?php
/**
 * Settings options
 *
 * @package Envato_Market
 * @since 1.0.0
 */
?>
<?php if($args['id']== 'settings_enable_styling'): ?>
<input 
    type="checkbox" 
    name="<?php echo esc_attr( sbws_init()->get_option_name() ); ?>[enable_styling]" 
    class="widefat" 
    value="1" 
    <?php echo (sbws_init()->get_option( 'enable_styling' ) == 1) ? 'checked' : ''; ?>
    autocomplete="off"
>
<?php elseif($args['id']== 'settings_enable_wishlist'): ?>
<input 
    type="checkbox" 
    name="<?php echo esc_attr( sbws_init()->get_option_name() ); ?>[enable_wishlist]" 
    class="widefat" 
    value="1" 
    <?php echo (sbws_init()->get_option( 'enable_wishlist' ) == 1) ? 'checked' : ''; ?>
    autocomplete="off"
>
<?php elseif($args['id']== 'settings_enable_form'): ?>
<input 
    type="checkbox" 
    name="<?php echo esc_attr( sbws_init()->get_option_name() ); ?>[enable_form]" 
    class="widefat" 
    value="1" 
    <?php echo (sbws_init()->get_option( 'enable_form' ) == 1) ? 'checked' : ''; ?>
    autocomplete="off"
>
<?php $args = array(
    'post_type'      => 'product',
    'posts_per_page' => -1,
    'product_cat'    => 'okategoriserad'
);
$products = get_posts($args);
?>
<div class="">
    <label>For following subscription products:</label>
    <div class="form-group">
        <select class="field-options-select" multiple="multiple" name="<?php echo esc_attr( sbws_init()->get_option_name() ); ?>[form_product][]" style="min-width: 400px;">
            <?php foreach($products as $product): ?>
            <option <?php echo in_array($product->ID, sbws_init()->get_option( 'form_product' )) ? 'selected' : ''; ?> value="<?php echo $product->ID; ?>"><?php echo $product->post_title ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<?php endif; ?>