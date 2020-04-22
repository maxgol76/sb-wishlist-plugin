<?php 
    $user = wp_get_current_user();
    $userdata = SB_Wishlist_Form::get_current_user( $user->ID );  /* Gets array of objects from the sbws_users  table*/
    $userform = unserialize( $userdata[0]->user_data ); /* Gets data from user_data field of sbws_users table */
    $fields = SB_Wishlist_Admin::get_user_fields();  /* Gets array of objects from the sbws_formmeta table*/
?>

<?php

if ( wcs_user_has_subscription( $user->ID, '', 'active' ) ) :

?>

<div class="styling-profile">

    <!--<h3 class="title"><?php /*_e( 'Your styling profile', 'sb-wishlist' ); */?></h3>
    <p><?php /*_e( 'This is used for automatically recommending items to you!', 'sb-wishlist' ); */?></p>-->


    <form class="sbws-form-styling" method="POST">
        <input type="hidden" value="<?php echo $user->ID; ?>" name="sb_user_id" />

    <div class="settings-wrap" style="width: 100%;">
        <div class="settings">

            <?php foreach ( $fields as $row ) : ?>
            <?php $arr_user_term = array();


            if ( ! empty ( $userform ) ) {

                foreach ($userform as $value):
                    if ($row->meta_id === $value['meta_id']) {
                        $arr_user_term[] = $value['value'];
                    }
                endforeach;
            }
                ?>
                <div class="item-row">

                    <?php if ( $row->meta_type !== 'plain_text' ) : ?>

                    <label for="field_option_<?php echo $row->meta_id ?>"><?php _e( $row->meta_name, 'sb-wishlist' ); ?></label>

                    <div class="field-value">

                        <?php if( $row->meta_type === 'checkbox' || $row->meta_type === 'radiobox' ) :

                            $cats = get_terms( $row->meta_category, array( 'orderby' => 'name',  'order' => 'asc', 'hide_empty' => false ) );

                        ?>
                            <select class="field-options-select"  name="field_option_<?php echo $row->meta_id ?>" <?php if( $row->meta_type === 'checkbox' ) echo 'multiple="multiple"'; ?>>
                                <?php foreach( $cats as $item ) : ?>
                                    <option value="<?php echo $item->term_id; ?>" <?php echo in_array( $item->term_id, $arr_user_term ) ? 'selected' : ''; ?> ><?php echo $item->name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif( $row->meta_type === 'imagebox' ) :
                            $items = unserialize( $row->meta_category_data );

                        ?>
                            <select class="field-options-select"  name="field_option_<?php echo $row->meta_id ?>" <?php if( $row->meta_type === 'checkbox' ) echo 'multiple="multiple"'; ?>>
                                <?php foreach( $items as $item ) : ?>
                                    <option value="<?php echo $item['cat_id']; ?>" <?php echo in_array( $item['cat_id'], $arr_user_term ) ? 'selected' : ''; ?>><?php echo $item['title']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif( $row->meta_type === 'yes_or_no' ) : ?>

                        <? if ( empty ( $arr_user_term[0] ) ) { $arr_user_term[0] = -1; } ?>

                            <div class="form-check form-check-inline form-radio">
                                <input class="form-check-input" type="radio" name="field_option_<?php echo $row->meta_id ?>" id="field_id_<?php echo $row->meta_id ?>_1"  <?php echo $arr_user_term[0] == 1 ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="field_id_<?php echo $row->meta_id ?>_1"><?php _e( 'Yes', 'sb-wishlist' ); ?></label>
                            </div>
                            <div class="form-check form-check-inline form-radio">
                                <input class="form-check-input" type="radio" name="field_option_<?php echo $row->meta_id ?>" id="field_id_<?php echo $row->meta_id ?>_2"  <?php echo $arr_user_term[0] == 0 ? 'checked' : ''; ?> >
                                <label class="form-check-label" for="field_id_<?php echo $row->meta_id ?>_2"><?php _e( 'No', 'sb-wishlist' ); ?></label>
                            </div>
                        <?php elseif( $row->meta_type === 'textbox' ) : ?>

                            <div class='form-group'>
                                <textarea name="field_option_<?php echo $row->meta_id ?>" value="<?php echo $arr_user_term[0]; ?>" class='form-control'><?php echo $arr_user_term[0]; ?></textarea>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php  else : ?>

                        <?php if ( $row->meta_name === 'Survey' ) { echo __( $row->meta_category_data, 'sb-wishlist' ); } ?>

                    <?php  endif;  ?>

                </div>
            <?php endforeach; ?>
        </div>
    </div>

        <div class="save-profile">
            <button type="submit" class="woocommerce-Button button" id="save-profile" name="save_profile" > <span class="ui-button-text"><?php esc_html_e( 'Save profile', 'sb-wishlist' ); ?></span></button>

        </div>

    </form>


    <!--<h3><?php /*_e( 'Your disliked items!', 'sb-wishlist' ); */?></h3>
    <p><?php /*_e( 'These items will never be recommended to you', 'sb-wishlist' ); */?></p>

    <div style="margin-bottom: 40px;"></div>

    <?php
/*        $args = array('post_type' => 'product', 'posts_per_page' => 3, 'product_cat' => 'chic');
        $loop = new WP_Query($args);
        while ($loop->have_posts()) : $loop->the_post();
        global $product;
    */?>
    <div class="disliked-product">
        <img src="<?/*= get_the_post_thumbnail_url($loop->post->ID); */?>" alt="shop_catalog">
        <h3 class="product-title"><?/*= get_the_title() */?></h3>
        <a href="#" class="btn ">Delete from dislike list</a>
    </div>
    --><?php /*endwhile;
    wp_reset_query();
    */?>
</div>

<?php  else: ?>
    <p class="no_subscriptions woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">

    <?php    printf( esc_html__( 'You have no active subscriptions. Find your first subscription in the %sstore%s.', 'woocommerce-subscriptions' ), '<a href="' . esc_url( apply_filters( 'woocommerce_subscriptions_message_store_url', get_permalink( wc_get_page_id( 'shop' ) ) ) ) . '">', '</a>' ); ?>

    </p>

 <?php   endif;