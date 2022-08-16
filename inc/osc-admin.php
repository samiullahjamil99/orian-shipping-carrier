<?php
function orian_shipping_init() {
    register_setting('orian_general','orian_main_setting',array('sanitize_callback' => 'orian_sanitize_settings_callback'));
    
    add_settings_section( 'orian_api',__('API Credentials','orian-shipping-carrier'),'orian_api_description_html','orian_general' );
    add_settings_field( 'orian_username', __('Username','orian-shipping-carrier'),'orian_common_text_field_cb','orian_general','orian_api',array('label_for' => 'username','class'=>'orian_username') );
    add_settings_field( 'orian_password', __('Password','orian-shipping-carrier'),'orian_common_password_field_cb','orian_general','orian_api',array('label_for' => 'password','class'=>'orian_password') );

    add_settings_section( 'orian_main', __('Orian Main Settings','orian-shipping-carrier'),'orian_main_description_html','orian_general' );
    add_settings_field( 'orian_consignee', __('Consignee','orian-shipping-carrier'),'orian_common_text_field_cb','orian_general','orian_main',array('label_for' => 'consignee','class'=>'orian_consignee') );
    add_settings_field( 'orian_referenceorder2', __('REFERENCEORDER2','orian-shipping-carrier'),'orian_common_text_field_cb','orian_general','orian_main',array('label_for' => 'referenceorder2','class'=>'orian_referenceorder2') );
    add_settings_field( 'orian_sync_time', __('Order Sync Time (In Minutes)','orian-shipping-carrier'),'orian_common_number_field_cb','orian_general','orian_main',array('label_for' => 'sync_time','class'=>'orian_sync_time') );
    add_settings_field( 'orian_nonbusiness_days', __('Non Business Days','orian-shipping-carrier'),'orian_multi_date_field_cb','orian_general','orian_main',array('label_for' => 'nonbusiness_days','class'=>'orian_nonbusiness_days') );
    add_settings_field( 'orian_businessday_end', __('Business Day End Time','orian-shipping-carrier'),'orian_common_time_field_cb','orian_general','orian_main',array('label_for' => 'businessday_end','class'=>'orian_businessday_end') );
    add_settings_field( 'orian_label_logo', __('PDF Label Logo','orian-shipping-carrier'),'orian_media_uploader_cb','orian_general','orian_main',array('label_for' => 'label_logo','class'=>'orian_label_logo') );
    add_settings_section( 'orian_source', __('Orian Source Settings','orian-shipping-carrier'),'orian_source_description_html','orian_general' );
    add_settings_field( 'orian_source_sitename', __('SITENAME','orian-shipping-carrier'),'orian_common_text_field_cb','orian_general','orian_source',array('label_for' => 'source_sitename','class'=>'orian_source_sitename') );
    add_settings_field( 'orian_source_street1', __('STREET1','orian-shipping-carrier'),'orian_common_text_field_cb','orian_general','orian_source',array('label_for' => 'source_street1','class'=>'orian_source_street1') );
    add_settings_field( 'orian_source_city', __('CITY','orian-shipping-carrier'),'orian_common_text_field_cb','orian_general','orian_source',array('label_for' => 'source_city','class'=>'orian_source_city') );
    add_settings_field( 'orian_source_contact1name', __('CONTACT1NAME','orian-shipping-carrier'),'orian_common_text_field_cb','orian_general','orian_source',array('label_for' => 'source_contact1name','class'=>'orian_source_contact1name') );
    add_settings_field( 'orian_source_contact1phone', __('CONTACT1PHONE','orian-shipping-carrier'),'orian_common_text_field_cb','orian_general','orian_source',array('label_for' => 'source_contact1phone','class'=>'orian_source_contact1phone') );
    add_settings_section( 'orian_sla', __('Orian SLA Settings','orian-shipping-carrier'),'orian_sla_description_html','orian_general' );
    add_settings_field( 'orian_pickup_sla', __('Pickup Location SLA','orian-shipping-carrier'),'orian_common_number_field_cb','orian_general','orian_sla',array('label_for' => 'pickup_sla','class'=>'orian_pickup_sla') );
    add_settings_field( 'orian_delivery_sla', __('Home Delivery SLA','orian-shipping-carrier'),'orian_common_number_field_cb','orian_general','orian_sla',array('label_for' => 'delivery_sla','class'=>'orian_delivery_sla') );
    add_settings_field( 'orian_delivery_far_sla', __('Home Delivery Far Destination SLA','orian-shipping-carrier'),'orian_common_number_field_cb','orian_general','orian_sla',array('label_for' => 'delivery_far_sla','class'=>'orian_delivery_far_sla') );
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
    <p><?php _e("Set these API Credentials to Integrate Orian to WooCommerce","orian-shipping-carrier"); ?></p>
    <?php
}

function orian_main_description_html() {
    ?>
    <p><?php _e("Set main details for the Orian","orian-shipping-carrier"); ?></p>
    <?php
}

function orian_source_description_html() {
    ?>
    <p><?php _e("Set Source details for the Orian","orian-shipping-carrier"); ?></p>
    <?php
}

function orian_sla_description_html() {
    ?>
    <p><?php _e("Set SLA for following options","orian-shipping-carrier"); ?></p>
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
}

function orian_multi_date_field_cb($args) {
    $options = get_option('orian_main_setting');
    $label_for = $args['label_for'];
    if (isset($options))
        $value = $options[$label_for];
    $day = date('w');
    ?>
    <div class="multi-date-select">
    </div>
    <input type="hidden" id="<?php echo $label_for; ?>" name="orian_main_setting[<?php echo $label_for; ?>]" value="<?php echo isset($options) ? $value : ''; ?>">
    <?php
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
function orian_common_time_field_cb($args) {
    $options = get_option('orian_main_setting');
    $label_for = $args['label_for'];
    if (isset($options))
        $value = $options[$label_for];
    ?>
    <input type="time" id="<?php echo $label_for; ?>" name="orian_main_setting[<?php echo $label_for; ?>]" value="<?php echo isset($options) ? $value : ''; ?>">
    <?php
}
function orian_media_uploader_cb($args) {
    $options = get_option('orian_main_setting');
    $label_for = $args['label_for'];
    if (isset($options))
        $value = $options[$label_for];
        if( $image = wp_get_attachment_image_src( $value,'full' ) ) {
        ?>
        <a href="#" class="osc-upl" style="display:inline-block;"><img src="<?php echo $image[0]; ?>"  style="max-width:100%;width:300px;" /></a>
	      <a href="#" class="osc-rmv"><?php _e("Remove image","orian-shipping-carrier"); ?></a>
          <input type="hidden" name="orian_main_setting[<?php echo $label_for; ?>]" value="<?php echo $value; ?>">
          <?php
        } else {
            ?>
        <a href="#" class="osc-upl" style="display:inline-block;"><?php _e("Upload image","orian-shipping-carrier"); ?></a>
	      <a href="#" class="osc-rmv" style="display:none"><?php _e("Remove image","orian-shipping-carrier"); ?></a>
	      <input type="hidden" name="orian_main_setting[<?php echo $label_for; ?>]" value="">
        <?php
        }
}

function orian_shipping_menu() {
    add_menu_page(
        __('Orian','orian-shipping-carrier'),
        __('Orian Options','orian-shipping-carrier'),
        'manage_options',
        'orian',
        'orian_shipping_settings_page',
        'dashicons-car',
        20
    );
    add_submenu_page(
        'orian',
        __('Import SLA Details','orian-shipping-carrier'),
        __('Import SLA Details','orian-shipping-carrier'),
        'manage_options',
        'orian_sla',
        'orian_sla_import_page',
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
                submit_button( __('Save Settings', 'orian-shipping-carrier') );
            ?>
        </form>
    </div>
    <?php
}

function orian_sla_import_page() {
    if ($_POST['submit']) {
        $uploadedfile = $_FILES['import_csv'];
        $upload_dir = wp_upload_dir();
        $filename = basename($uploadedfile["name"]);
        $upload_overrides = array(
            'test_form' => false,
            'mimes' => array('csv' => 'text/csv'),
        );
         
        $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
         
        if ( $movefile && ! isset( $movefile['error'] ) ) {
            //print_r($movefile);
            $filename = basename($movefile['url']);
            $csvfile = $upload_dir['path'] . '/' . $filename;
            $file = fopen( $csvfile,"r");
            $cities_data = array();
            if ($file) {
                while(!feof($file)) {
                  $line = fgetcsv($file);
                  //echo $line[0];
                  //echo "\n";
                  $cities_data[] = $line;
                }
                fclose($file);
            }
            if (!empty($cities_data)) {
                update_option( 'orian_cities',$cities_data );
            }
        }
    }
    $orian_cities = get_option('orian_cities');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form method="post" enctype="multipart/form-data">
            <?php
            if ($orian_cities):
                ?>
                <p><?php _e("Cities data is uploaded. Upload a new file to update it.","orian-shipping-carrier"); ?></p>
                <?php
            endif;
            ?>
            <input type="file" name="import_csv" accept=".csv">
            <input type="submit" name="submit" value="<?php _e('Import Data','orian-shipping-carrier'); ?>">
        </form>
    </div>
    <?php
}

function orian_include_admin_js() {
    if ( ! did_action( 'wp_enqueue_media' ) ) {
		wp_enqueue_media();
	}
 
 	wp_enqueue_script( 'oscuploadscript', plugin_dir_url(OSC_PLUGIN_FILE) . 'assets/js/admin-media-upload.js', array( 'jquery' ) );
    wp_enqueue_script( 'multi-date', plugin_dir_url(OSC_PLUGIN_FILE) . 'assets/js/multi-date-select.js', array( 'jquery' ) );
    wp_enqueue_style( 'multi-date', plugin_dir_url(OSC_PLUGIN_FILE) . 'assets/css/multi-date-select.css');
}
add_action( 'admin_enqueue_scripts', 'orian_include_admin_js' );