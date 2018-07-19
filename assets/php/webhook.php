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

//-----------------------------------------------------
//TODO: GET API KEY FROM DB: SELECT -> foreach
//      if mode = test -> API KEY -> ...
//-----------------------------------------------------

$mollie = new Mollie_API_Client;
$mollie->setApiKey('<MY-API-KEY>');

$payment = $mollie->payments->get($_POST["id"]);
// $payment = $mollie->payments->get('tr_824ASBHvJk');

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

//UPDATE TO NEW DATA
$wpdb->query("UPDATE {$fc_submissions_table} SET content='" . $content . "' WHERE id='" . $payment->metadata->submission_id . "'");