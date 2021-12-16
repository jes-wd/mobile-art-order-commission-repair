<?php
/** 
 * Here we add an action in woocommerce orders that allows the vendor commission to be regenerated. 
 * The default "Generate Vendor Commission" action does not allow commissions to be recalculated after they have already been made.
*/

if (is_admin()) {
    add_filter('woocommerce_order_actions', 'ma_ocr_regenerate_commission_order_action');
}

// add the action that woocommerce will trigger
add_action('woocommerce_order_action_ma_ocr_regenerate_commission_order_action', 'ma_ocr_process_manual_regenerate_commission_action');

/** 
 * add the new action name the list of action names that woocommerce loops through
 */
function ma_ocr_regenerate_commission_order_action($actions) {
    if (!isset($_REQUEST['post'])) {
        return $actions;
    }

    $actions['ma_ocr_regenerate_commission_order_action'] = __('Regenerate Vendor Commission', 'woocommerce-product-vendors');

    return $actions;
}

/**
 * regenerate commissions for all line items on the order
 */
function ma_ocr_process_manual_regenerate_commission_action($order) {
    $order_id = $order->get_id();
    $WC_Product_Vendors_Order = new WC_Product_Vendors_Order(new WC_Product_Vendors_Commission(new WC_Product_Vendors_PayPal_MassPay()));
    $WC_Product_Vendors_Order->remove_affected_commissions($order_id);
    $fix_successful = $WC_Product_Vendors_Order->process($order_id);

    if ($fix_successful) {
        error_log('fix_successful');
        return true;
    } else {
        error_log('an order regeneration failed. logged below.');
        error_log(print_r($fix_successful));
    }

    return false;
}
