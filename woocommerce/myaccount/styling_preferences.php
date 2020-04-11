<?php 
    $user = wp_get_current_user();
    $userdata = SB_Wishlist_Form::get_current_user($user->ID); 
    $userform = unserialize($userdata[0]->user_data);
    $fields = SB_Wishlist_Admin::get_user_fields();
?>
<div class="styling-profile">
    <h3 class="title">Your styling profile</h3>
    <p>This is used for automatically recommending items to you!</p>
    <div class="settings-wrap">
        <div class="settings">
            <?php foreach ($fields as $row): ?>
                <div class="item-row">
                    <h4 class="field-title"><?php echo $row->meta_name; ?></h4>
                    <div class="field-value">
                        <?php if($row->meta_type === 'checkbox' || $row->meta_type === 'radiobox') : $cats = get_terms( $row->meta_category, array( 'orderby' => 'name',  'order' => 'asc', 'hide_empty' => false)); ?>
                            <select class="field-options-select"  <?php if($row->meta_type === 'checkbox') echo 'multiple="multiple"'; ?>>
                                <?php foreach($cats as $item): ?>
                                    <option value="<?php echo $item->term_id; ?>"><?php echo $item->name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif($row->meta_type === 'imagebox'): $items = unserialize($row->meta_category_data);?>
                            <select class="field-options-select"  <?php if($row->meta_type === 'checkbox') echo 'multiple="multiple"'; ?>>
                                <?php foreach($items as $item): ?>
                                    <option value="<?php echo $item['cat_id']; ?>"><?php echo $item['title']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif($row->meta_type === 'yes_or_no'):?>
                            <div class="form-check form-check-inline form-radio">
                                <input class="form-check-input" type="radio" name="field_option_<?php echo $row->meta_id ?>" id="field_id_<?php echo $row->meta_id ?>_1" value="1">
                                <label class="form-check-label" for="field_id_<?php echo $row->meta_id ?>_1">Yes</label>
                            </div>
                            <div class="form-check form-check-inline form-radio">
                                <input class="form-check-input" type="radio" name="field_option_<?php echo $row->meta_id ?>" id="field_id_<?php echo $row->meta_id ?>_2" value="0">
                                <label class="form-check-label" for="field_id_<?php echo $row->meta_id ?>_2">No</label>
                            </div>
                        <?php elseif($row->meta_type === 'textbox'): $value = array_map('get_preference', $userform, $row);?>
                            <div class='form-group'>
                                <textarea name="field_option_<?php echo $row->meta_id ?>" value="<?php ?>" class='form-control'></textarea>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php /*
    <div class="settings-wrap">
        <div class="settings">
            <div><p>Size on bottoms</p><span>M</span></div>
            <div><p>Size on tops</p><span>S</span></div>
            <div><p>Size full body</p><span>S</span></div>
        </div>
        <div class="settings">
            <div><p>Style profile</p><span>Chic</span></div>
            <div><p>Body type</p><span>Triangle</span></div>
        </div>
    </div>

    <h3>Good to know information</h3>
    <p>This is used in cases you use our styling service and is good information for us to have when closing new clothing for our wardrobe!</p>

    <div class="settings">
        <div><p>Color profile</p><span>No color</span></div>
        <div><p>Your notes to us:</p></div>
        <p class="notes">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Rem voluptate magnam dignissimos vero recusandae adipisci?</p>
    </div>*/ ?>

    <h3>Your disliked items</h3>
    <p>These items will never be recommended to you</p>
    <div class="edit-profile"><a href="#" class="btn ">Edit profile</a></div>
    
    <?php
        $args = array('post_type' => 'product', 'posts_per_page' => 3, 'product_cat' => 'chic');
        $loop = new WP_Query($args);
        while ($loop->have_posts()) : $loop->the_post();
        global $product;
    ?>
    <div class="disliked-product">
        <img src="<?= get_the_post_thumbnail_url($loop->post->ID); ?>" alt="shop_catalog">
        <h3 class="product-title"><?= get_the_title() ?></h3>
        <a href="#" class="btn ">Delete from dislike list</a>
    </div>
    <?php endwhile;
    wp_reset_query();
    ?>
</div>