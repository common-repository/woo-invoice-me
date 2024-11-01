<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Invoice Me Admin List
 *
 * Provides a Invoice Gateway, mainly for manual invoice purposes.
 *
 * @class WC_InvoiceMe_Admin
 */
class WC_InvoiceMeGateway_Admin {

    /**
     * Constructor for the invoice admin.
     */
		public function __construct() {
			add_action( 'show_user_profile', array( $this, 'wc_invoice_me_customer_extra_profile_fields' ) );
			add_action( 'edit_user_profile', array( $this, 'wc_invoice_me_customer_extra_profile_fields' ) );

			add_action( 'personal_options_update', array( $this, 'wc_invoice_me_customer_save_extra_profile_fields' ) );
			add_action( 'edit_user_profile_update', array( $this, 'wc_invoice_me_customer_save_extra_profile_fields' ) );

			add_filter('manage_edit-shop_order_columns', array( $this, 'set_wc_invoice_me_order_column' ), 11);

			add_filter('manage_edit-shop_order_sortable_columns', array( $this, 'set_wc_invoice_me_order_sortable_column' ), 11);

			add_filter( 'request', array( $this, 'query_wc_invoice_me_order_column_orderby' ) );

			add_action( 'manage_shop_order_posts_custom_column' , array( $this, 'add_wc_invoice_me_order_column' ), 11, 2 );

	   }


    /**
     * Add checkbox to profile fields
     *
     * @param int $user
     * @return void
     */
		public function wc_invoice_me_customer_extra_profile_fields( $user ) { ?>
			<table class="form-table">
				<tr>
					<th><label for="invoice_me">Invoice Me</label></th>
					<td>
						<input type="checkbox" name="invoice_me" id="invoice_me" value="yes" <?php if(esc_attr( get_the_author_meta( 'invoice_me', $user->ID ) ) == 'yes'){ ?> checked="checked" <?php } ?> /> <span class="description">Enable Invoice Me Option for Customer In Checkout Page</span>
					</td>
				</tr>
			</table>
		<?php }

    /**
     * Save checkbox at profile fields
     *
     * @param int $user_id
     * @return void
     */
		public function wc_invoice_me_customer_save_extra_profile_fields( $user_id ) {

			if ( !current_user_can( 'edit_user', $user_id ) )
				return false;

				if(isset($_POST['invoice_me'])){
					update_user_meta( $user_id, 'invoice_me', sanitize_text_field($_POST['invoice_me']) );
				}else{
					update_user_meta( $user_id, 'invoice_me', false );
				}
		}


    /**
     * Get currnet URL in shop order list page
     *
     * @return current url
     */
		public function get_current_url_invoice_me_payment() {
			 $pageURL = 'http';
			 if (isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] == "on")) {$pageURL .= "s";}
			 $pageURL .= "://";
			 if ($_SERVER["SERVER_PORT"] != "80") {
			  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
			 } else {
			  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
			 }
			 return $pageURL;
		}

    /**
     * Add column to shop order page
     *
     * @param $columns
     * @return columns
     */
		public function set_wc_invoice_me_order_column($columns) {
			$columns['invoiceme_orders'] = "Invoice Me";
			return $columns;
		}




    /**
     * Make new added column sortable
     *
     * @param $columns
     * @return columns
     */
		public function set_wc_invoice_me_order_sortable_column($columns) {
			$columns['invoiceme_orders'] = "invoiceme_orders";
			return $columns;
		}




    /**
     * Add query to reset order by query for invoieme option
     *
     * @param $vars
     * @return vars
     */
		public function query_wc_invoice_me_order_column_orderby( $vars ) {
			if ( isset( $vars['orderby'] ) && 'invoiceme_orders' == $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' => '_invoice_me_status',
					'orderby' => 'meta_value'
				) );
			}

			return $vars;
		}




    /**
     * Add column data to shop order page
     *
     * @param $column, $post_id
     * @return columns
     */
		public function add_wc_invoice_me_order_column( $column, $post_id ) {
			switch ( $column ) {
				case 'invoiceme_orders' :

				date_default_timezone_set("UTC");
				if (isset($_GET['invoice_sent_nonce']) && wp_verify_nonce($_GET['invoice_sent_nonce'], 'mark_sent')) {
					if(isset($_GET['shop_order_id']) && isset($_GET['invoice_sent']) && ($_GET['invoice_sent'] == 'yes')){
						$shop_order_id = intval($_GET['shop_order_id']);
						update_post_meta( $shop_order_id, '_invoice_me_status', 'sent' );
						$date_time = date("Y-m-d H:i:s");
						update_post_meta( $shop_order_id, '_invoice_me_date', $date_time );
					}
				}

				$current_list_page_url = $this->get_current_url_invoice_me_payment();

				if(get_post_meta( $post_id , '_invoice_me' , true ) == 'yes'){
					if(get_post_meta( $post_id , '_invoice_me_status' , true ) == 'pending'){
						$invoice_sent_link_pram = add_query_arg( array('invoice_sent' => 'yes', 'shop_order_id' => $post_id), $current_list_page_url );
						$invoice_sent_link = wp_nonce_url($invoice_sent_link_pram, 'mark_sent', 'invoice_sent_nonce');
						echo 'Pending<br /><a href="'.$invoice_sent_link.'">Mark as Sent</a>';
					}elseif(get_post_meta( $post_id , '_invoice_me_status' , true ) == 'sent'){
						echo 'Invoice Sent<br />';
						echo get_post_meta( $post_id , '_invoice_me_date' , true );
					}
				}

					break;

			}
		}

}

new WC_InvoiceMeGateway_Admin();
