<?php
/**
* Plugin Name: WooCommerce Coupons Replace Variations
* Plugin URI: https://github.com/Zulmamwe/woocommerce-coupons-replace-variation
* Description: Adds new WooCommerce coupon type to change one product variation to another
* Version: 1.2
* Author: John Zuxer
* Author URI: https://www.upwork.com/o/profiles/users/_~01f35acec4c4e5f366/
* License: GPLv2 or later
*/

WC_Coupon_Replace_Variation::init();

class WC_Coupon_Replace_Variation {
	
	public static $coupon_type_name = "replace_variations";
	public static $coupon_type_label = "Replace variations";
	
	public static $field_name = "replace_variations";
	public static $old = "removed_variation";
	public static $new = "added_variation";
	
	public static function init(){
		// Add custom coupon type
		add_filter( 'woocommerce_coupon_discount_types', __CLASS__ . '::add_coupon_type');
		
		//Here we check that all fields are not empty
		add_filter('woocommerce_subscriptions_validate_coupon_type', __CLASS__ . '::validate_coupon_type', 10, 3);
		
		//This is the custom logic for the new coupon type.
		//After coupon is validated we check if cart contains variations marked to remove and replaces them with selected
		add_action('woocommerce_applied_coupon', __CLASS__ . '::replace_variations');
	}
	
	public static function add_coupon_type($discount_types){
		return array_merge(
				$discount_types,
				array(
					self::$coupon_type_name => __( self::$coupon_type_label, self::$coupon_type_name ),
				)
			);
	}
	
	public static function validate_coupon_type($arg1, $coupon, $valid){
	
		if( $coupon->is_type( self::$coupon_type_name ) ){
			$replace_variations_arr = get_field(self::$field_name, $coupon->id);
			
			if( empty($replace_variations_arr) ){
				return false;
			}
			
			foreach($replace_variations_arr as $ids){
				if( empty( $ids[ self::$old ] ) || empty( $ids[ self::$new ] ) ){
					return false;
				}
			}
		}

		return true;
	}
	
	public static function replace_variations($coupon_code){
		// Get the coupon
		$coupon = new WC_Coupon( $coupon_code );
		if($coupon->is_type( self::$coupon_type_name )){
			
			$cart = WC()->cart->get_cart();
			$replace_variations = get_field(self::$field_name, $coupon->id);
			
			if( !empty($cart) ){
				
				foreach($cart as $cart_key => $cart_item){
					$variation_id = $cart_item["variation_id"];
					//Dont check anything if its not variable product
					if( $variation_id === 0 ) continue;
					
					foreach($replace_variations as $ids){
						if( $variation_id == $ids[ self::$old ] ){
							WC()->cart->remove_cart_item($cart_key);
							WC()->cart->add_to_cart($ids[ self::$new ]);
						}
					}
				}
			}
		}
	}
	
}
?>