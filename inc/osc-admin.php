<?php
function orian_shipping_init() {
    register_setting('orian_general','orian_main_setting');
    
    add_settings_section( 'orian_api','API Credentials','orian_api_description_html','orian_general' );
    add_settings_field( 'orian_username', 'Username','orian_api_fields_cb','orian_general','orian_api',array('label_for' => 'username','class'=>'orian_username') );
    add_settings_field( 'orian_password', 'Password','orian_api_fields_cb','orian_general','orian_api',array('label_for' => 'password','class'=>'orian_password') );

    add_settings_section( 'orian_main', 'Orian Main Settings','orian_main_description_html','orian_general' );
    add_settings_field( 'orian_consignee', 'Consignee','orian_main_fields_cb','orian_general','orian_main',array('label_for' => 'consignee','class'=>'orian_consignee') );
}
add_action('admin_init','orian_shipping_init');

function orian_api_description_html() {
    ?>
    <p>Set these API Credentials to Integrate Orian to WooCommerce</p>
    <?php
}

function orian_main_description_html() {
    ?>
    <p>Set main details for the Orian</p>
    <?php
}

function orian_api_fields_cb($args) {
    $options = get_option('orian_main_setting');
    $label_for = $args['label_for'];
    if (isset($options))
        $value = $options[$label_for];
    ?>
    <input type="<?php echo $label_for === "password" ? 'password' : 'text'; ?>" name="orian_main_setting[<?php echo $label_for; ?>]" value="<?php echo isset($options) ? $value : ''; ?>">
    <?php
}

function orian_main_fields_cb($args) {
    $options = get_option('orian_main_setting');
    $label_for = $args['label_for'];
    if (isset($options))
        $value = $options[$label_for];
    ?>
    <input type="text" name="orian_main_setting[<?php echo $label_for; ?>]" value="<?php echo isset($options) ? $value : ''; ?>">
    <?php
}

function orian_shipping_menu() {
    add_menu_page(
        'Orian',
        'Orian Options',
        'manage_options',
        'orian',
        'orian_shipping_settings_page',
        'dashicons-car',
        20
    );
}
add_action('admin_menu','orian_shipping_menu');

function orian_shipping_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
                settings_fields( 'orian_general' );
                do_settings_sections('orian_general');
                submit_button( __('Save Settings', 'textdomain') );
            ?>
        </form>
    </div>
    <?php
}