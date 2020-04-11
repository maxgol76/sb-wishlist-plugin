<form method="POST" action="<?php echo admin_url( 'options.php' ); ?>">
    <?php settings_fields( sbws_init()->get_slug() ); ?>
    
    <?php do_settings_sections(sbws_init()->get_slug()); ?>
    
    <?php submit_button( 'Save Settings' ); ?>
</form>


