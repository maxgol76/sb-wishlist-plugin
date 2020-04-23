<?php

function sbws_form(){
   return SB_Wishlist_Form::instance();
}

function sbws_get_step(){
    return sbws_form()->get_step_data();
}
function sbws_get_step_meta($step_id){
    return sbws_form()->get_meta_data($step_id);
}
function get_wishlist_pagination($pages = 1){
    $paged = $_GET['paged'] ? absint($_GET['paged']) : 1;
?>
    <div class="sbws-wishlist-pagination">
        <?php $i = 0;  $p = $_GET['paged'] ? $_GET['paged'] : 1; ?>
        <?php while($i < $pages): ?>
        <a href="<?php echo sbws_init()->get_page_url(); ?>&tab=wishlist&paged=<?php echo ++$i; ?>" class="page-num <?php echo $i == $p ? 'active' : ''; ?>" ><?php echo $i; ?></a>
        <?php endwhile; ?>
    </div>
<?php
}

function get_wishlist_filter(){
?>
<form class="filter-item" method="get" action="<?php echo sbws_init()->get_page_url(); ?>">
    <input type="hidden" name="page" value="<?php echo sbws_init()->get_slug(); ?>" />
    <input type="hidden" name="tab" value="wishlist" />
    <div class="input-group">
        <div class="input-group-prepend">
            <select class="form-control">
                <option>Filter by name</option>
                <option>Filter by SKU</option>
                <option>Filter by category</option>
            </select>
        </div>
        <input type="text" placeholder="Enter here" class="form-control" name="s"/>
        <div class="input-group-append">
            <button type="submit" class="button button-secondary"><i class="fas fa-search"></i></button>
        </div>
    </div>   
</form>
<?php
}

function clean_submit_form($item){
    $temp = array();
    $temp['meta_id'] = str_replace(array('field_option_', '[]'), '', $item['name']);
    $temp['value'] = $item['value'];
    return $temp;
}
function get_preference($data, $row){
    $result = '';
    foreach ($data as $value) {
        if($row->meta_id === $value['meta_id']){
            $result = $value;
        }
    }
    return $result;
}


add_action( 'woocommerce_thankyou', 'SB_Wishlist_Form::get_wishlist_styling_form', 20 );


//add_action( 'yith_wcwl_before_wishlist', 'wishlist_dropdown'  );

function wishlist_dropdown () {
    //printf( '<div class="bag-product clearfix"><p>%1$s</p></div>', esc_html( 'recommendation' ) );
    //return '<div class="bag-product clearfix"><p>Recommendation</p></div>';
    echo ('<p>Recommendation</p>' );
}

// SB_Wishlist_Admin::get_suggested_list

add_action( 'yith_wcwl_before_wishlist_share', 'wishlist_recommendation'  );

add_action( 'yith_wcwl_before_wishlist_form', 'SB_Wishlist_Form::get_suggested_list'  );
//add_action( 'yith_wcwl_before_wishlist_form', 'wishlist_recommendation'  );
function wishlist_recommendation () {
    echo "<h2>Wishlist recomendation<h2>";
}

add_action( 'woocommerce_after_add_to_cart_button', 'product_dislike_button', 20 );

function product_dislike_button( $extra_class = "" ) {

    global $product, $yith_wcwl;
    $product_id = method_exists( $product, 'get_id' ) ? $product->get_id() : $product->id;
    $product_type = method_exists( $product, 'get_type' ) ? $product->get_type() : $product->product_type;


        $tooltip      = __("Dislike", 'atelier');


        $classes = get_option( 'yith_wcwl_use_button' ) == 'yes' ? 'class="add_to_wishlist single_add_to_wishlist button alt"' : 'class="add_to_wishlist"';



    //fas fa-times

        $html = '<div class="clear"></div><div class="product-dislike-button" data-toggle="tooltip" data-placement="top" title="' . $tooltip . '">';
        $html .= '<a href="#" rel="nofollow" data-ajaxurl="#" data-product-id="' . $product_id . '" data-product-type="' . $product_type . '" class="product-dislike">';
        $html .= '<i class="fas fa-thumbs-down"></i></a></div>';



        echo wp_kses_post($html);
    //}
}



