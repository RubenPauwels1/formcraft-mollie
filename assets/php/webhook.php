<?php


//Mollie
require_once '../../Mollie/API/Autoloader.php';

//WP-load
$path = preg_replace('/wp-content(?!.*wp-content).*/','',__DIR__);
include_once($path.'wp-load.php');

global $fc_meta, $fc_forms_table, $fc_submissions_table, $fc_views_table, $fc_files_table, $wpdb;
$fc_forms_table = $wpdb->prefix . "formcraft_3_forms";
$fc_submissions_table = $wpdb->prefix . "formcraft_3_submissions";
$fc_views_table = $wpdb->prefix . "formcraft_3_views";
$fc_files_table = $wpdb->prefix . "formcraft_3_files";

//GET API KEY
$addons = $wpdb->get_results( "SELECT addons FROM {$fc_forms_table}" );

foreach($addons as $addon){
    $addons_arr = json_decode(stripslashes($addon->addons));
    if($addons_arr->Mollie){
        $mollie_data = $addons_arr->Mollie;
        break;
    }
}

$mollie_data->mode == 'test' ? $apikey = $mollie_data->test_secret_key : $apikey = $mollie_data->live_publishable_key;
$apikey = trim($apikey);


$mollie = new Mollie_API_Client;
$mollie->setApiKey($apikey);

$payment = $mollie->payments->get($_POST["id"]);

$status = $payment->status;
$amount = $payment->amount;
$paymentid = $payment->id;
$date = $payment->createdDatetime;

//GET ORIGINAL DATA
$content = $wpdb->get_var( "SELECT content FROM {$fc_submissions_table} WHERE id='" . $payment->metadata->submission_id . "'" );

//ORIGINAL CONTENT AS ARRAY
$content = json_decode(stripslashes($content));

foreach($content as $field){
    if($field->label == 'Mollie'){
        $field->value = "Date (created): " .$date . "<br>Payment ID: " . $paymentid . "<br>Amount: " . $amount . "<br>Status: " . $status;
    }
}

//NEW CONTENT IN JSON
$content = json_encode($content);


$args = array(
    'post_title'    => 'webhook ' . $payment->metadata->first_name,
    'post_content'  => serialize($content),
    'post_status'   => 'draft',
);

wp_insert_post( $args );

//UPDATE TO NEW DATA
$wpdb->query("UPDATE {$fc_submissions_table} SET content='" . $content . "' WHERE id='" . $payment->metadata->submission_id . "'");

//DO ACTION
do_action( 'formcraft_after_mollie_payment',  array($payment) );