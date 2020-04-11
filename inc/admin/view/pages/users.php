<h2>Users</h2>
<div class="sbws-content">
    <?php 
    $users = SB_Wishlist_Admin::get_subscribed_users();
    $fields = SB_Wishlist_Admin::get_user_fields();
    ?>
    <ul class="sbws-users-list">
    <?php foreach ($users as $user): 
        $userdata = get_userdata($user->user_id); 
        $usermeta = get_user_meta( $user->user_id);
        $userform = unserialize($user->user_data);
    ?>
        <li class="list-item">
            <div class="list-item-inner">
                <div class="sbws-col">
                    <?php echo $usermeta['first_name'][0] . " " . $usermeta['last_name'][0] . " ( " .$userdata->user_email . " )"; ?>
                </div>
                <div class="sbws-col">
                    <a class="show-variations button-secondary">Show info</a>
                </div>
                
            </div>
            <div class="list-item-detailed">
                <div class="item-left">
                    <h3>Profile from Survey</h3>
                    <?php foreach ($fields as $row): ?>
                    <div class="item-row">
                        <h4 class="field-title"><?php echo $row->meta_name; ?></h4>
                        <div class="field-value">
                            <?php foreach ($userform as $value): ?>
                                <?php 
                                if($row->meta_id === $value['meta_id'] && $row->meta_type == 'checkbox'){
                                   $term = get_term($value['value']);
                                   echo $term->name . ' ';
                                }else if($row->meta_id === $value['meta_id'] && $row->meta_type == 'imagebox'){
                                    $term = get_term($value['value']);
                                    echo $term->name . ' ';
                                }else if($row->meta_id === $value['meta_id'] && $row->meta_type == 'yes_or_no'){
                                   echo $value['value'] === '1' ? 'Yes' : 'No';
                                }else if($row->meta_id === $value['meta_id'] && $row->meta_type == 'textbox'){
                                    echo $value['value'];
                                }
                                ?>
                                 
                            <?php endforeach; ?>
                        </div>
                    </div>
                   
                    <?php endforeach; ?>
                    
                </div>
                <div class="item-right">
                    <h3 class="field-title">Top recommendations</h3>
                    <?php $recommendations = SB_Wishlist_Admin::get_suggested_list($user->user_id, $userform);  ?>
                    <ul class="sbws-recommendation-list">
                    <?php foreach ($recommendations as $item): $data = $item->get_data();  $img = wp_get_attachment_image( $data['image_id'], 'thumbnail' );?>
                        <li class="list-item">
                            <div class="list-item-inner">
                                <div class="sbws-col" data-col="image"><?php echo $img; ?></div>
                                <div class="sbws-col" data-col="sku"><?php echo $item->get_sku(); ?></div>
                                <div class="sbws-col" data-col="name"><?php echo $data['name']; ?></div>
                                <div class="sbws-col" data-col="size"><?php echo $item->get_attribute('pa_storlek'); ?></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                    <pre><?php// var_dump ($recommendations); ?></pre>
                </div>
            </div>
        </li>
    <?php endforeach; ?>
    </ul>
</div>