<h2>Users</h2>
<div class="sbws-content">
    <?php 
    $users  = SB_Wishlist_Admin::get_subscribed_users(); /* array of objects from the sbws_users    table*/
    $fields = SB_Wishlist_Admin::get_user_fields();      /* array of objects from the sbws_formmeta table*/
    ?>

    <div class="input-customer-group">

            <select class="field-options-select filter-customer" placeholder="Filter Customer" style="min-width: 400px; min-height: 60px;">

                <option value="" disabled selected>Select user</option>

                <?php foreach( $users as $user ) : ?>
                    <?php $usermeta = get_user_meta( $user->user_id );
                          $userdata = get_userdata( $user->user_id );
                    ?>

                    <option value="<?php echo $user->user_id; ?>"><?php _e( $usermeta['first_name'][0] . " " . $usermeta['last_name'][0] . " ( " .$userdata->user_email . " )", 'sb-wishlist' ); ?></option>
                <?php endforeach; ?>

            </select>
    </div>


    <ul class="sbws-users-list">
    <?php foreach ( $users as $user ) :
        $userdata = get_userdata( $user->user_id );  /* Gets a WP_User object that contains all the data for the current user */
        $usermeta = get_user_meta( $user->user_id ); /* Gets array of all meta fields of the current user */
        $userform = unserialize( $user->user_data ); /* Gets data from user_data field of sbws_users table */
    ?>
        <li class="list-item">
            <div class="list-item-inner">
                <div class="sbws-col">
                    <?php _e( $usermeta['first_name'][0] . " " . $usermeta['last_name'][0] . " ( " .$userdata->user_email . " )", 'sb-wishlist' ); ?>
                </div>
                <div class="sbws-col">
                    <a class="show-variations button-secondary">Show info</a>
                </div>
                
            </div>
            <div class="list-item-detailed">
                <div class="item-left">
                    <h3><?php _e( 'Profile from Survey', 'sb-wishlist' ); ?></h3>
                    <?php if ( ! empty ( $user->user_data ) ) : ?>
                        <?php foreach ( $fields as $row ): ?>
                            <?php if( $row->meta_type === 'imagebox' ) {
                                $items = unserialize( $row->meta_category_data );
                            } ?>
                        <div class="item-row">
                            <h4 class="field-title"><?php echo $row->meta_name; ?></h4>
                            <div class="field-value">
                                <?php foreach ( $userform as $value ): ?>
                                    <?php
                                    if ( $row->meta_id === $value['meta_id'] && $row->meta_type == 'checkbox' ) {
                                       $term = get_term( $value['value'] );
                                       echo $term->name . ' ';
                                    } elseif ( $row->meta_id === $value['meta_id'] && $row->meta_type == 'imagebox' ) {

                                        foreach( $items as $item ) :

                                        if ( $item['cat_id'] == $value['value'] ) {
                                            echo $item['title'];
                                        }
                                        endforeach;
                                        /*$term = get_term( $value['value'] );
                                        echo $term->name . ' ';*/
                                    } elseif ( $row->meta_id === $value['meta_id'] && $row->meta_type == 'yes_or_no' ) {
                                       echo $value['value'] === '1' ? 'Yes' : 'No';
                                    } elseif ( $row->meta_id === $value['meta_id'] && $row->meta_type == 'textbox' ) {
                                        echo $value['value'];
                                    }
                                    ?>

                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                    <div class="item-row">
                        <h4 class="field-title"><?php _e( 'Profile is empty', 'sb-wishlist' ); ?></h4>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="item-right">

                <?php SB_Wishlist_Form::get_suggested_list( $user->user_id );  ?>

                </div>

                <!--<div class="item-right">
                    <h3 class="field-title"><?php /*_e( 'Top recommendations', 'sb-wishlist' ); */?></h3>
                    <?php /*$recommendations = SB_Wishlist_Admin::get_suggested_list( $user->user_id, $userform );  */?>
                    <ul class="sbws-recommendation-list">
                    <?php /*foreach ( $recommendations as $item ): $data = $item->get_data();  $img = wp_get_attachment_image( $data['image_id'], 'thumbnail' );*/?>
                        <li class="list-item">
                            <div class="list-item-inner">
                                <div class="sbws-col" data-col="image"><?php /*echo $img; */?></div>
                                <div class="sbws-col" data-col="sku"><?php /*echo $item->get_sku(); */?></div>
                                <div class="sbws-col" data-col="name"><?php /*echo $data['name']; */?></div>
                                <div class="sbws-col" data-col="size"><?php /*echo $item->get_attribute('pa_storlek'); */?></div>
                            </div>
                        </li>
                    <?php /*endforeach; */?>
                    </ul>

                </div>-->
            </div>
        </li>
    <?php endforeach; ?>
    </ul>
</div>