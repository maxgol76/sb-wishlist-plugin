<?php

/* 
 * Admin Page Tabs
 * 
 * @package SB Wishlist
 * @since 1.0.0
 */

$current = ( ! empty( $_GET['tab'] ) ) ? esc_attr( $_GET['tab'] ) : 'settings';
$tabs = SB_Wishlist_Admin::get_tabs();
?>

<div class="sbws-tab-container sbws-container">
    <ul class="sbws-tabs-nav">
        <?php foreach( $tabs as $tab => $value ): ?>
        <li class="sbws-tab-item <?php echo ($current == $tab) ? 'active' : ''; ?>">
            <a href="<?php echo sbws_init()->get_page_url(); ?>&tab=<?php echo $tab; ?>" class="sbws-tab-link"><?php echo $value; ?></a>
        </li>
        <?php endforeach; ?>
    </ul>
    <div class="sbws-tabs-panels">
        <div class="sbws-panel">
        <?php if($current == "settings"): ?>
            <?php SB_Wishlist_Admin::render_view_page_settings(); ?>
        <?php elseif($current == "form"): ?>
            <?php SB_Wishlist_Admin::render_view_page_form(); ?>
        <?php elseif($current == "styling"): ?>
            <?php SB_Wishlist_Admin::render_view_page_variables(); ?>
        <?php elseif($current == "wishlist"): ?>
            <?php SB_Wishlist_Admin::render_view_page_wishlist(); ?>
        <?php elseif($current == "users"): ?>
            <?php SB_Wishlist_Admin::render_view_page_users(); ?>
        <?php elseif($current == "analytics"): ?>
            <h2 style="font-weight: 300; text-transform: uppercase;">Analytics tab options</h2>
        <?php else: ?>
            <h2 style="font-weight: 300; text-transform: uppercase;">Export tab options</h2>
        <?php endif; ?>
        </div>
    </div>
</div>
