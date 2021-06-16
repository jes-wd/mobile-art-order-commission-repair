<?php

/*
Plugin Name: Mobile Art Order Commission Repair
Plugin URI: https://github.com/jes-wd/mobile-art-order-commission-repair
Description: Finds and repairs any orders that commission calculations have failed on.
Author: Jesse Sugden
Version: 1.0
Author URI: https://jeswebdevelopment.com
*/

add_action('admin_menu', 'order_commission_repair_menu');

function order_commission_repair_menu() {
    add_submenu_page(
        'tools.php',
        'Order Commission Repair',
        'Commission Repair',
        'manage_options',
        'ma-order-commission-repair',
        'order_commission_repair_admin_page'
    );
}

function order_commission_repair_admin_page() {
    // This function creates the output for the admin page.
    // It also checks the value of the $_POST variable to see whether
    // there has been a form submission. 

    // The check_admin_referer is a WordPress function that does some security
    // checking and is recommended good practice.

    // General check for user permissions.
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient privileges to access this page.'));
    }

    // Start building the page
    echo '<div class="wrap">';
    echo '<h2>Mobile Art Order Commission Repair</h2>';

    // Check whether the button has been pressed AND also check the nonce
    if (isset($_POST['order_commission_repair']) && check_admin_referer('order_commission_repair_clicked')) {
        // the button has been pressed AND we've passed the security check
        order_commission_repair_action();
    }

    echo '<form action="tools.php?page=ma-order-commission-repair" method="post">';

    // this is a WordPress security feature - see: https://codex.wordpress.org/WordPress_Nonces
    wp_nonce_field('order_commission_repair_clicked');
    echo '<input type="hidden" value="true" name="order_commission_repair" />';
    submit_button('Repair All Orders');
    echo '</form>';
    echo '</div>';
}

function order_commission_repair_action() {
    if (class_exists('WC_Product_Vendors_Order')) {
        global $wpdb;
        $broken_commissions = $wpdb->get_results("SELECT * FROM wp_postmeta WHERE meta_key='_wcpv_commission_added' AND post_id NOT IN ( SELECT order_id FROM wp_wcpv_commissions )");
        $have_deleted_commissions = $wpdb->query("DELETE FROM wp_postmeta WHERE meta_key='_wcpv_commission_added' AND post_id NOT IN ( SELECT order_id FROM wp_wcpv_commissions )");

        if ($have_deleted_commissions && count($broken_commissions) > 0) {
            $fixed_order_count = 0;
            $error_count = 0;
            $WC_Product_Vendors_Order = new WC_Product_Vendors_Order(new WC_Product_Vendors_Commission(new WC_Product_Vendors_PayPal_MassPay()));
            $log = '<b>Fixed Commissions Log:</b></br>';
            $log .= '<div style="max-height: 500px; overflow: scroll; width: fit-content;">';
            foreach ($broken_commissions as $broken_commission) {
                // run the product vendor plugin's calculate commission function on the order. if not return true, add to error count
                $fix_successful = $WC_Product_Vendors_Order->process($broken_commission->post_id);
                $fix_successful ? $fixed_order_count++ : $error_count++;
                $log .=
                    "Order ID: " . $broken_commission->post_id . '<br />' .
                    "Meta ID: " . $broken_commission->meta_key . '<br />' .
                    "Status: " . ($fix_successful ? 'successful' : 'failed') . '<br />' .
                    "---------------------------------------------" . '<br />';
            }
            $log .= '</div>';
            file_put_contents('./log_' . date("j.n.Y") . '.txt', $log, FILE_APPEND);
            echo '
                <div class="notice notice-success">
                    <p>The function ran successfully.</p>
                </div>
            ';
            echo '
                <p><b>Orders fixed:</b> ' . $fixed_order_count . '</p>
                <p><b>Errors:</b> ' . $error_count . '</p>
            ';
            echo $log;
        } else if (count($broken_commissions) === 0) {
            echo '
                <div class="notice notice-warning">
                    <p>There were no broken commissions to be fixed.</p>
                </div>
            ';
        } else {
            echo '
                <div class="notice notice-error">
                    <p>There was an error. Further details may have been printed below.</p>
                </div>
            ';
            echo 'Deleted commissions: ';
            echo '<br />';
            echo '<pre>';
            print_r($have_deleted_commissions);
            echo '</pre>';
            echo '<br />';
            echo 'All relevent orders:';
            echo '<br />';
            echo '<pre>';
            print_r($broken_commissions);
            echo '</pre>';
        }
    } else {
        echo '
            <div class="notice notice-error">
                <p>The Woocommerce Product Vendors plugin must be active. Please activate it before running this function.</p>
            </div>
        ';
    }
}
