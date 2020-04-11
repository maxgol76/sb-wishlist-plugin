<?php $products = SB_Wishlist_Admin::get_wishlist(); ?>
<h2>Wishlist</h2>
<div class="sbws-content">      
    <div class="sbws-wishlist">
        <div class="sbws-wishlist-filters">
            <?php get_wishlist_filter(); ?>
            <?php get_wishlist_pagination($products->max_num_pages); ?>
        </div>
        <div class="sbws-wishlist-header">
            <div class="sbws-col" data-col="image">image</div>
            <div class="sbws-col" data-col="sku">SKU</div>
            <div class="sbws-col" data-col="name">name</div>
            <div class="sbws-col" data-col="size">size</div>
            <div class="sbws-col" data-col="stock">stock</div>
            <div class="sbws-col" data-col="wishlisted">wishlisted</div>
        </div>
        <ul class="sbws-wishlist-list">
            <?php foreach ($products->products as $item): $data = $item->get_data();  $img = wp_get_attachment_image( $data['image_id'], 'thumbnail' );?>
            <li class="list-item">
                <div class="list-item-inner">
                    <div class="sbws-col" data-col="image"><?php echo $img; ?></div>
                    <div class="sbws-col" data-col="sku"><?php echo $item->is_type( 'variable' ) ? '<a class="show-variations button-secondary">Show variations</a>' : $item->get_sku(); ?></div>
                    <div class="sbws-col" data-col="name"><?php echo $data['name']; ?></div>
                    <div class="sbws-col" data-col="size"><?php echo $item->get_attribute('pa_storlek'); ?></div>
                    <div class="sbws-col" data-col="stock"><?php echo $item->get_stock_quantity(); ?></div>
                    <div class="sbws-col" data-col="wishlisted"></div>
                </div>
            
                <?php if($item->is_type( 'variable' ) && $variables = $item->get_available_variations()): ?>
                <div class="list-item-variations">
                    <?php foreach ($variables as $variable): ?>
                    <div class="item-variation">
                        <div class="sbws-col" data-col="image"><?php echo $img; ?></div>
                        <div class="sbws-col" data-col="sku"><?php echo $variable['sku']; ?></div>
                        <div class="sbws-col" data-col="name"><?php echo $data['name']; ?></div>
                        <div class="sbws-col" data-col="size"><?php echo $variable['attributes']['attribute_pa_storlek']; ?></div>
                        <div class="sbws-col" data-col="stock"><?php echo $variable['max_qty'] ? $variable['max_qty'] : '0'; ?></div>
                        <div class="sbws-col" data-col="wishlisted"></div>
                    </div>
                    <?php endforeach;?>
                </div>
                <?php endif; ?>
            </li>
            <?php endforeach;?>
        </ul>
        <?php get_wishlist_pagination($products->max_num_pages); ?>
    </div>
    <?php /*foreach ($products as $item): ?>
        <pre><?php print_r($img_id); ?></pre>
        <br>
        <br>
        <br>
        <br>
        <br>
        <?php endforeach; */?>
</div>