<?php
/**
 * Plugin Name: WooCommerce Dawnwing API
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly


/***********************************thankyou page generate order url*********************************************/

add_action('woocommerce_order_status_shipped', 'woocommerce_order_status_shipped', 999, 1);
function woocommerce_order_status_shipped($order_id)
{
    if (!get_post_meta($order_id, 'dawnwing_labels', true)) {
        // get order object and order details
        $order = new WC_Order($order_id);
        $order_number = $order_id;
        if (!empty($order->get_items('shipping'))) {
            $shipping_method_instance_id = '';
            foreach ($order->get_items('shipping') as $item_id => $shipping_item_obj) {
                $shipping_method_instance_id = $shipping_item_obj->get_instance_id();
            }
        }
        $instance_id = $shipping_method_instance_id;

        if ($instance_id == 1) {
            $shipping_type = "ECON";
        } elseif ($instance_id == 2) {
            $shipping_type = "ECON";
        } elseif ($instance_id == 5) {
            $shipping_type = "ECON";
        } elseif ($instance_id == 6) {
            $shipping_type = "ECON";
        } elseif ($instance_id == 3) {
            $shipping_type = "ONX";
        } else {
            $shipping_type = "ECON";
        }

        // get product details//
        $items = $order->get_items();

        $parcels = [];
        foreach ($items as $item_id => $item_data) {
            $id = $item_data['product_id'];
            $variation_id = $item_data['variation_id'];
            $product_name = $item_data['name'];
            $quantity = $item_data['quantity'];

            // Product id
            $product_id = $variation_id > 0 ? $variation_id : $id;
            // Product details
            $product = wc_get_product($product_id);
            $weight = wc_get_weight($product->get_weight(), 'lbs');
            $height = wc_get_dimension($product->get_height(), 'in');
            $width = wc_get_dimension($product->get_width(), 'in');
            $length = wc_get_dimension($product->get_length(), 'in');
            $parcels[] = [
                'waybillNo' => $order_id,
                'length' => 0.1,
                'height' => 0.1,
                'width' => 0.1,
                'mass' => 0.1,
                'parcelDescription' => $product_name,
                'parcelNo' => $product_id,
                'parcelCount' => $product_id
            ];
        }

        // Shipping address
        $shipping_address = $order->get_address('shipping');

        // Billing address
        $billing_address = $order->get_address('billing');
        $phone = $first_name = $last_name = '';
        extract($billing_address);

        $address_1 = $address_2 = $city = $state = $postcode = '';
        extract($shipping_address);
        !strlen($address_2) > 0 ? $address_2 = 'empty' : '';

        // setup the data which has to be sent//
        $datawaybill = [
            "WaybillNo" => $order_number,
            "SendAccNo" => "CPT3685",
            "SendCompany" => "LITTLE BRAND BOX",
            "SenDAdd1" => "101 Bree Castle House",
            "SendAdd2" => "68 Bree Street",
            "SendAdd3" => "",
            "SendAdd4" => "Cape Town",
            "SendAdd5" => "8000",
            "SendContactPerson" => "Zak",
            "SendWorkTel" => "0214236868",
            "RecAdd1" => $address_1,
            "RecAdd2" => $address_2,
            "RecAdd3" => $city,
            "RecAdd4" => $state,
            "RecAdd5" => $postcode,
            "RecContactPerson" => $first_name . ' ' . $last_name,
            "RecHomeTel" => "",
            "RecWorkTel" => $phone,
            "RecCell" => $phone,
            "SpecialInstructions" => $order->get_customer_note(),
            "ServiceType" => $shipping_type,
            "parcels" => $parcels,
            "CompleteWaybillAfterSave" => true
        ];

        $token = 'eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJJZCI6IjQwIiwiZXhwIjoxNjE5MjY1MTA2LCJpc3MiOiJodHRwOi8vNDEuMC42OS4xOTcvIiwiYXVkIjoiaHR0cDovLzQxLjAuNjkuMTk3LyJ9.rUhw0gLPXFzt806EbluWNN9vuqK-un-YoHfBfCuSbJZ09l8GxDfch5HwFhe87-H9T1di9NtLBjc5e7nm0DX5jg';
//    $token = 'eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJJZCI6IjQwIiwiZXhwIjoxNjE1ODcxOTM2LCJpc3MiOiJodHRwOi8vNDEuMC42OS4xOTcvIiwiYXVkIjoiaHR0cDovLzQxLjAuNjkuMTk3LyJ9.83zUvdf_c7BGoPT2B9RKUV-5n-fDZijBCLiizBU2MHI_VvAEC8ZXlckz48lC0-C6OGNTEswZRxJzjPidQw06IA';
//    $ch = curl_init('https://swatws.dawnwing.co.za/dwwebservices/v2/uat/api/waybill'); // Initialise cURL
        $ch = curl_init('http://swatws.dawnwing.co.za/dwwebservices/live/api/waybill'); // Initialise cURL
        $authorization = "Authorization: Bearer " . $token; // Prepare the authorisation token
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); // Inject the token into the header
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datawaybill)); // Set the posted fields
        $response = curl_exec($ch); // Execute the cURL statement
        curl_close($ch); // Close the cURL connection
        update_post_meta($order_id, 'dawnwing_api_response', $response);
        $response_array = json_decode($response, true);
        if (isset($response_array, $response_array['data'])) {
            update_post_meta($order_id, 'dawnwing_labels', json_encode($response_array['data']));
        }
    }
}