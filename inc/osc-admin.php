<?php
function orian_shipping_init() {
    register_setting('orian_general','orian_main_setting',array('sanitize_callback' => 'orian_sanitize_settings_callback'));
    
    add_settings_section( 'orian_api','API Credentials','orian_api_description_html','orian_general' );
    add_settings_field( 'orian_username', 'Username','orian_common_text_field_cb','orian_general','orian_api',array('label_for' => 'username','class'=>'orian_username') );
    add_settings_field( 'orian_password', 'Password','orian_common_password_field_cb','orian_general','orian_api',array('label_for' => 'password','class'=>'orian_password') );

    add_settings_section( 'orian_main', 'Orian Main Settings','orian_main_description_html','orian_general' );
    add_settings_field( 'orian_consignee', 'Consignee','orian_common_text_field_cb','orian_general','orian_main',array('label_for' => 'consignee','class'=>'orian_consignee') );
    add_settings_field( 'orian_referenceorder2', 'REFERENCEORDER2','orian_common_text_field_cb','orian_general','orian_main',array('label_for' => 'referenceorder2','class'=>'orian_referenceorder2') );
    add_settings_field( 'orian_sync_time', 'Order Sync Time (In Minutes)','orian_common_number_field_cb','orian_general','orian_main',array('label_for' => 'sync_time','class'=>'orian_sync_time') );
    add_settings_field( 'orian_nonbusiness_days', 'Non Business Days','orian_common_text_field_cb','orian_general','orian_main',array('label_for' => 'nonbusiness_days','class'=>'orian_nonbusiness_days') );
    add_settings_section( 'orian_source', 'Orian Source Settings','orian_source_description_html','orian_general' );
    add_settings_field( 'orian_source_sitename', 'SITENAME','orian_common_text_field_cb','orian_general','orian_source',array('label_for' => 'source_sitename','class'=>'orian_source_sitename') );
    add_settings_field( 'orian_source_street1', 'STREET1','orian_common_text_field_cb','orian_general','orian_source',array('label_for' => 'source_street1','class'=>'orian_source_street1') );
    add_settings_field( 'orian_source_city', 'CITY','orian_common_text_field_cb','orian_general','orian_source',array('label_for' => 'source_city','class'=>'orian_source_city') );
    add_settings_field( 'orian_source_contact1name', 'CONTACT1NAME','orian_common_text_field_cb','orian_general','orian_source',array('label_for' => 'source_contact1name','class'=>'orian_source_contact1name') );
    add_settings_field( 'orian_source_contact1phone', 'CONTACT1PHONE','orian_common_text_field_cb','orian_general','orian_source',array('label_for' => 'source_contact1phone','class'=>'orian_source_contact1phone') );
    add_settings_section( 'orian_sla', 'Orian SLA Settings','orian_sla_description_html','orian_general' );
    add_settings_field( 'orian_pickup_sla', 'Pickup Location SLA','orian_common_number_field_cb','orian_general','orian_sla',array('label_for' => 'pickup_sla','class'=>'orian_pickup_sla') );
    add_settings_field( 'orian_delivery_sla', 'Home Delivery SLA','orian_common_number_field_cb','orian_general','orian_sla',array('label_for' => 'delivery_sla','class'=>'orian_delivery_sla') );
    add_settings_field( 'orian_delivery_far_sla', 'Home Delivery Far Destination SLA','orian_common_number_field_cb','orian_general','orian_sla',array('label_for' => 'delivery_far_sla','class'=>'orian_delivery_far_sla') );
}
add_action('admin_init','orian_shipping_init');

function orian_sanitize_settings_callback( $input ) {
    $output = $input;
    if ($input['source_sitename'] === "abc")
        $output['source_sitename'] = "abc1";
    return $output;
}

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

function orian_source_description_html() {
    ?>
    <p>Set Source details for the Orian</p>
    <?php
}

function orian_sla_description_html() {
    ?>
    <p>Set SLA for following options</p>
    <?php
}

function orian_common_password_field_cb($args) {
    $options = get_option('orian_main_setting');
    $label_for = $args['label_for'];
    if (isset($options))
        $value = $options[$label_for];
    ?>
    <input type="password" id="<?php echo $label_for; ?>" name="orian_main_setting[<?php echo $label_for; ?>]" value="<?php echo isset($options) ? $value : ''; ?>">
    <?php
}

function orian_common_text_field_cb($args) {
    $options = get_option('orian_main_setting');
    $label_for = $args['label_for'];
    if (isset($options))
        $value = $options[$label_for];
    ?>
    <input type="text" id="<?php echo $label_for; ?>" name="orian_main_setting[<?php echo $label_for; ?>]" value="<?php echo isset($options) ? $value : ''; ?>">
    <?php
    if ($label_for === "nonbusiness_days"):
        ?>
        <p>Use the format dd/mm where dd is for day and mm for month. Use two digits for days and months. The days will be separated by commas. For example 10/06,09/05,02/04</p>
        <?php
    endif;
}
function orian_common_number_field_cb($args) {
    $options = get_option('orian_main_setting');
    $label_for = $args['label_for'];
    if (isset($options))
        $value = $options[$label_for];
    ?>
    <input type="number" id="<?php echo $label_for; ?>" name="orian_main_setting[<?php echo $label_for; ?>]" value="<?php echo isset($options) ? $value : ''; ?>">
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
    <?php settings_errors(); ?>
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