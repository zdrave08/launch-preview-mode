<?php
/**
 * Plugin Name: Launch Preview Mode
 * Plugin URI: https://github.com/zdrave08/launch-preview-mode
 * Description: Allows selected products to stay visible before stock is available, without purchase actions or stock notices, and adds a "NOVO" badge.
 * Version: 1.3.0
 * Author: Bitwise Software
 * Author URI: https://bitwisesoftware.rs/
 * Text Domain: launch-preview-mode
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC tested up to: 11.3
 *
 * @package LaunchPreviewMode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LPM_Launch_Preview_Mode' ) ) {

	final class LPM_Launch_Preview_Mode {

		/**
		 * Meta key for launch preview mode.
		 *
		 * @var string
		 */
		const META_ENABLED = '_lpm_launch_preview_enabled';

		/**
		 * Meta key for optional custom note.
		 *
		 * @var string
		 */
		const META_NOTE = '_lpm_launch_preview_note';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'init' ) );
		}

		/**
		 * Init plugin.
		 *
		 * @return void
		 */
		public function init() {
			if ( ! class_exists( 'WooCommerce' ) ) {
				return;
			}

			add_action( 'init', array( $this, 'load_textdomain' ) );

			// Admin product fields.
			add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'add_product_fields' ) );
			add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_fields' ) );

			// Admin list enhancements.
			add_filter( 'manage_edit-product_columns', array( $this, 'add_admin_column' ), 20 );
			add_action( 'manage_product_posts_custom_column', array( $this, 'render_admin_column' ), 10, 2 );
			add_filter( 'manage_edit-product_sortable_columns', array( $this, 'register_sortable_columns' ) );
			add_action( 'pre_get_posts', array( $this, 'handle_admin_sorting' ) );
			add_action( 'restrict_manage_posts', array( $this, 'add_admin_filter_dropdown' ) );
			add_filter( 'parse_query', array( $this, 'apply_admin_filter_dropdown' ) );

			// Row actions / quick toggle.
			add_action( 'admin_post_lpm_toggle_preview_mode', array( $this, 'handle_toggle_preview_mode' ) );

			// Bulk actions.
			add_filter( 'bulk_actions-edit-product', array( $this, 'register_bulk_actions' ) );
			add_filter( 'handle_bulk_actions-edit-product', array( $this, 'handle_bulk_actions' ), 10, 3 );
			add_action( 'admin_notices', array( $this, 'render_bulk_action_notice' ) );

			// Nice-to-have admin links.
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_action_links' ) );

			// Frontend behavior.
			add_filter( 'pre_option_woocommerce_hide_out_of_stock_items', array( $this, 'override_hide_out_of_stock_option' ), 10, 3 );
			add_filter( 'posts_where', array( $this, 'exclude_non_preview_out_of_stock' ), 10, 2 );
			add_filter( 'woocommerce_product_is_visible', array( $this, 'filter_product_visibility' ), 10, 2 );
			add_filter( 'woocommerce_is_purchasable', array( $this, 'disable_purchase_for_preview_products' ), 10, 2 );
			add_filter( 'woocommerce_variation_is_purchasable', array( $this, 'disable_variation_purchase_for_preview_products' ), 10, 2 );
			add_filter( 'woocommerce_get_availability_text', array( $this, 'filter_availability_text' ), 10, 2 );
			add_filter( 'woocommerce_get_availability_class', array( $this, 'filter_availability_class' ), 10, 2 );
			add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'remove_loop_add_to_cart_link' ), 10, 3 );

			add_action( 'woocommerce_single_product_summary', array( $this, 'render_single_product_note' ), 25 );
			add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'render_loop_badge' ), 9 );
			add_action( 'woocommerce_before_single_product_summary', array( $this, 'render_single_badge' ), 9 );

			// Assets.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		}

		/**
		 * Load translations.
		 *
		 * @return void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'launch-preview-mode', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Check if preview mode is enabled for product.
		 *
		 * @param int $product_id Product ID.
		 * @return bool
		 */
		private function is_preview_enabled( $product_id ) {
			return 'yes' === get_post_meta( $product_id, self::META_ENABLED, true );
		}

		/**
		 * Add admin fields to product inventory tab.
		 *
		 * @return void
		 */
		public function add_product_fields() {
			echo '<div class="options_group">';

			woocommerce_wp_checkbox(
				array(
					'id'          => self::META_ENABLED,
					'label'       => __( 'Launch preview mode', 'launch-preview-mode' ),
					'description' => __( 'Keeps this product visible before stock arrives, hides stock notice, disables purchase, and shows the "NOVO" badge.', 'launch-preview-mode' ),
					'desc_tip'    => true,
				)
			);

			woocommerce_wp_textarea_input(
				array(
					'id'          => self::META_NOTE,
					'label'       => __( 'Preview note', 'launch-preview-mode' ),
					'description' => __( 'Optional short text shown on the single product page while this product is in preview mode.', 'launch-preview-mode' ),
					'desc_tip'    => true,
					'rows'        => 3,
				)
			);

			echo '</div>';
		}

		/**
		 * Save admin fields.
		 *
		 * @param int $post_id Product ID.
		 * @return void
		 */
		public function save_product_fields( $post_id ) {
			$enabled = isset( $_POST[ self::META_ENABLED ] ) ? 'yes' : 'no';
			update_post_meta( $post_id, self::META_ENABLED, $enabled );

			if ( isset( $_POST[ self::META_NOTE ] ) ) {
				$note = sanitize_textarea_field( wp_unslash( $_POST[ self::META_NOTE ] ) );
				update_post_meta( $post_id, self::META_NOTE, $note );
			}
		}

		/**
		 * Add admin column.
		 *
		 * @param array $columns Product columns.
		 * @return array
		 */
		public function add_admin_column( $columns ) {
			$new_columns = array();

			foreach ( $columns as $key => $label ) {
				$new_columns[ $key ] = $label;

				if ( 'is_in_stock' === $key ) {
					$new_columns['lpm_preview_mode'] = __( 'Preview', 'launch-preview-mode' );
				}
			}

			if ( ! isset( $new_columns['lpm_preview_mode'] ) ) {
				$new_columns['lpm_preview_mode'] = __( 'Preview', 'launch-preview-mode' );
			}

			return $new_columns;
		}

		/**
		 * Render admin column content.
		 *
		 * @param string $column  Column key.
		 * @param int    $post_id Product ID.
		 * @return void
		 */
		public function render_admin_column( $column, $post_id ) {
			if ( 'lpm_preview_mode' !== $column ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				echo esc_html__( 'No access', 'launch-preview-mode' );
				return;
			}

			$enabled = $this->is_preview_enabled( $post_id );

			$toggle_url = wp_nonce_url(
				admin_url(
					add_query_arg(
						array(
							'action'     => 'lpm_toggle_preview_mode',
							'product_id' => $post_id,
						),
						'admin-post.php'
					)
				),
				'lpm_toggle_preview_mode_' . $post_id
			);

			printf(
				'<span class="lpm-admin-status-wrap"><span class="lpm-admin-status-badge %1$s">%2$s</span><span class="lpm-admin-status-separator">&middot;</span><a class="lpm-admin-action-link" href="%3$s">%4$s</a></span>',
				esc_attr( $enabled ? 'lpm-admin-status-enabled' : 'lpm-admin-status-disabled' ),
				esc_html( $enabled ? __( 'Enabled', 'launch-preview-mode' ) : __( 'Disabled', 'launch-preview-mode' ) ),
				esc_url( $toggle_url ),
				esc_html( $enabled ? __( 'Disable', 'launch-preview-mode' ) : __( 'Enable', 'launch-preview-mode' ) )
			);
		}

		/**
		 * Register sortable columns.
		 *
		 * @param array $columns Columns.
		 * @return array
		 */
		public function register_sortable_columns( $columns ) {
			$columns['lpm_preview_mode'] = 'lpm_preview_mode';
			return $columns;
		}

		/**
		 * Sort by preview mode.
		 *
		 * @param WP_Query $query Query.
		 * @return void
		 */
		public function handle_admin_sorting( $query ) {
			if ( ! is_admin() || ! $query->is_main_query() ) {
				return;
			}

			if ( 'product' !== $query->get( 'post_type' ) ) {
				return;
			}

			if ( 'lpm_preview_mode' === $query->get( 'orderby' ) ) {
				$query->set( 'meta_key', self::META_ENABLED );
				$query->set( 'orderby', 'meta_value' );
			}
		}

		/**
		 * Add admin filter dropdown on product list.
		 *
		 * @return void
		 */
		public function add_admin_filter_dropdown() {
			global $typenow;

			if ( 'product' !== $typenow ) {
				return;
			}

			$current = isset( $_GET['lpm_preview_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['lpm_preview_filter'] ) ) : '';

			echo '<select name="lpm_preview_filter">';
			echo '<option value="">' . esc_html__( 'All preview statuses', 'launch-preview-mode' ) . '</option>';
			echo '<option value="enabled"' . selected( $current, 'enabled', false ) . '>' . esc_html__( 'Preview enabled', 'launch-preview-mode' ) . '</option>';
			echo '<option value="disabled"' . selected( $current, 'disabled', false ) . '>' . esc_html__( 'Preview disabled', 'launch-preview-mode' ) . '</option>';
			echo '</select>';
		}

		/**
		 * Apply admin filter dropdown.
		 *
		 * @param WP_Query $query Query.
		 * @return WP_Query
		 */
		public function apply_admin_filter_dropdown( $query ) {
			global $pagenow;

			if ( ! is_admin() || 'edit.php' !== $pagenow ) {
				return $query;
			}

			$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';

			if ( 'product' !== $post_type ) {
				return $query;
			}

			$filter = isset( $_GET['lpm_preview_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['lpm_preview_filter'] ) ) : '';

			if ( 'enabled' === $filter ) {
				$query->query_vars['meta_query'][] = array(
					'key'   => self::META_ENABLED,
					'value' => 'yes',
				);
			} elseif ( 'disabled' === $filter ) {
				$query->query_vars['meta_query'][] = array(
					'relation' => 'OR',
					array(
						'key'     => self::META_ENABLED,
						'value'   => 'yes',
						'compare' => '!=',
					),
					array(
						'key'     => self::META_ENABLED,
						'compare' => 'NOT EXISTS',
					),
				);
			}

			return $query;
		}

		/**
		 * Handle quick toggle from admin list.
		 *
		 * @return void
		 */
		public function handle_toggle_preview_mode() {
			if ( ! current_user_can( 'edit_products' ) ) {
				wp_die( esc_html__( 'You do not have permission to do that.', 'launch-preview-mode' ) );
			}

			$product_id = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;

			if ( ! $product_id ) {
				wp_safe_redirect( admin_url( 'edit.php?post_type=product' ) );
				exit;
			}

			check_admin_referer( 'lpm_toggle_preview_mode_' . $product_id );

			$enabled = $this->is_preview_enabled( $product_id );
			update_post_meta( $product_id, self::META_ENABLED, $enabled ? 'no' : 'yes' );

			$redirect_url = add_query_arg(
				array(
					'post_type'   => 'product',
					'lpm_toggled' => 1,
				),
				admin_url( 'edit.php' )
			);

			wp_safe_redirect( $redirect_url );
			exit;
		}

		/**
		 * Register bulk actions.
		 *
		 * @param array $bulk_actions Bulk actions.
		 * @return array
		 */
		public function register_bulk_actions( $bulk_actions ) {
			$bulk_actions['lpm_enable_preview_mode']  = __( 'Enable launch preview mode', 'launch-preview-mode' );
			$bulk_actions['lpm_disable_preview_mode'] = __( 'Disable launch preview mode', 'launch-preview-mode' );

			return $bulk_actions;
		}

		/**
		 * Handle bulk actions.
		 *
		 * @param string $redirect_to Redirect URL.
		 * @param string $action      Action.
		 * @param array  $post_ids    Product IDs.
		 * @return string
		 */
		public function handle_bulk_actions( $redirect_to, $action, $post_ids ) {
			if ( ! in_array( $action, array( 'lpm_enable_preview_mode', 'lpm_disable_preview_mode' ), true ) ) {
				return $redirect_to;
			}

			$count = 0;
			$value = 'lpm_enable_preview_mode' === $action ? 'yes' : 'no';

			foreach ( $post_ids as $post_id ) {
				if ( current_user_can( 'edit_post', $post_id ) ) {
					update_post_meta( $post_id, self::META_ENABLED, $value );
					$count++;
				}
			}

			return add_query_arg(
				array(
					'lpm_bulk_action' => $action,
					'lpm_count'       => $count,
				),
				$redirect_to
			);
		}

		/**
		 * Render admin notice after bulk actions.
		 *
		 * @return void
		 */
		public function render_bulk_action_notice() {
			if ( empty( $_GET['lpm_bulk_action'] ) || empty( $_GET['lpm_count'] ) ) {
				return;
			}

			$action = sanitize_key( wp_unslash( $_GET['lpm_bulk_action'] ) );
			$count  = absint( $_GET['lpm_count'] );

			if ( 'lpm_enable_preview_mode' === $action ) {
				$message = sprintf(
					_n( 'Launch preview mode enabled for %d product.', 'Launch preview mode enabled for %d products.', $count, 'launch-preview-mode' ),
					$count
				);
			} elseif ( 'lpm_disable_preview_mode' === $action ) {
				$message = sprintf(
					_n( 'Launch preview mode disabled for %d product.', 'Launch preview mode disabled for %d products.', $count, 'launch-preview-mode' ),
					$count
				);
			} else {
				return;
			}

			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
		}

		/**
		 * Add plugin action links.
		 *
		 * @param array $links Action links.
		 * @return array
		 */
		public function add_plugin_action_links( $links ) {
			$custom_links = array(
				'<a href="' . esc_url( admin_url( 'edit.php?post_type=product&lpm_preview_filter=enabled' ) ) . '">' . esc_html__( 'Preview products', 'launch-preview-mode' ) . '</a>',
			);

			return array_merge( $custom_links, $links );
		}

		/**
		 * Temporarily disable built-in hide-out-of-stock logic on frontend,
		 * then emulate it ourselves so only chosen preview products remain visible.
		 *
		 * @param mixed  $pre_option Pre option value.
		 * @param string $option     Option name.
		 * @param mixed  $default    Default value.
		 * @return mixed
		 */
		public function override_hide_out_of_stock_option( $pre_option, $option, $default ) {
			if ( is_admin() && ! wp_doing_ajax() ) {
				return $pre_option;
			}

			if ( 'woocommerce_hide_out_of_stock_items' === $option ) {
				return 'no';
			}

			return $pre_option;
		}

		/**
		 * Real (stored) value of the WooCommerce "hide out of stock" option,
		 * bypassing our own override.
		 *
		 * @return bool
		 */
		private function store_hides_out_of_stock() {
			remove_filter( 'pre_option_woocommerce_hide_out_of_stock_items', array( $this, 'override_hide_out_of_stock_option' ), 10 );
			$value = get_option( 'woocommerce_hide_out_of_stock_items' );
			add_filter( 'pre_option_woocommerce_hide_out_of_stock_items', array( $this, 'override_hide_out_of_stock_option' ), 10, 3 );

			return 'yes' === $value;
		}

		/**
		 * Because the built-in hide-out-of-stock query filter is disabled on the frontend
		 * (so preview products can be listed), exclude the other out-of-stock products
		 * in the query itself. Otherwise they are counted and paginated, then hidden
		 * at render time, which leaves empty slots and wrong page counts.
		 *
		 * @param string   $where Current WHERE clause.
		 * @param WP_Query $query Query object.
		 * @return string
		 */
		public function exclude_non_preview_out_of_stock( $where, $query ) {
			global $wpdb;

			if ( ( is_admin() && ! wp_doing_ajax() ) || ! $query instanceof WP_Query || $query->is_singular() ) {
				return $where;
			}

			// Taxonomy archives (e.g. product_cat) do not have post_type set on the query yet.
			$post_type  = $query->get( 'post_type' );
			$is_product = ( 'product' === $post_type || array( 'product' ) === (array) $post_type )
				|| $query->is_post_type_archive( 'product' )
				|| $query->is_tax( get_object_taxonomies( 'product' ) );
			if ( ! $is_product ) {
				return $where;
			}

			if ( ! $this->store_hides_out_of_stock() ) {
				return $where;
			}

			$term = get_term_by( 'slug', 'outofstock', 'product_visibility' );
			if ( ! $term ) {
				return $where;
			}

			$where .= $wpdb->prepare(
				" AND ( {$wpdb->posts}.ID NOT IN ( SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d )
				OR {$wpdb->posts}.ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = 'yes' ) )",
				(int) $term->term_taxonomy_id,
				self::META_ENABLED
			);

			return $where;
		}

		/**
		 * Control visibility for out-of-stock products.
		 *
		 * @param bool $visible    Current visibility.
		 * @param int  $product_id Product ID.
		 * @return bool
		 */
		public function filter_product_visibility( $visible, $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product instanceof WC_Product ) {
				return $visible;
			}

			if ( $this->is_preview_enabled( $product_id ) ) {
				return true;
			}

			if ( ! $product->is_in_stock() ) {
				return false;
			}

			return $visible;
		}

		/**
		 * Disable purchase for preview products.
		 *
		 * @param bool       $purchasable Current value.
		 * @param WC_Product $product     Product object.
		 * @return bool
		 */
		public function disable_purchase_for_preview_products( $purchasable, $product ) {
			if ( ! $product instanceof WC_Product ) {
				return $purchasable;
			}

			if ( $this->is_preview_enabled( $product->get_id() ) ) {
				return false;
			}

			return $purchasable;
		}

		/**
		 * Disable variation purchase for preview products.
		 *
		 * @param bool       $purchasable Current value.
		 * @param WC_Product $product     Product object.
		 * @return bool
		 */
		public function disable_variation_purchase_for_preview_products( $purchasable, $product ) {
			if ( ! $product instanceof WC_Product ) {
				return $purchasable;
			}

			$parent_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();

			if ( $this->is_preview_enabled( $parent_id ) ) {
				return false;
			}

			return $purchasable;
		}

		/**
		 * Hide stock availability text for preview products.
		 *
		 * @param string     $availability Availability text.
		 * @param WC_Product $product      Product object.
		 * @return string
		 */
		public function filter_availability_text( $availability, $product ) {
			if ( ! $product instanceof WC_Product ) {
				return $availability;
			}

			if ( $this->is_preview_enabled( $product->get_id() ) ) {
				return '';
			}

			return $availability;
		}

		/**
		 * Hide stock availability CSS class for preview products.
		 *
		 * @param string     $class   Availability class.
		 * @param WC_Product $product Product object.
		 * @return string
		 */
		public function filter_availability_class( $class, $product ) {
			if ( ! $product instanceof WC_Product ) {
				return $class;
			}

			if ( $this->is_preview_enabled( $product->get_id() ) ) {
				return 'lpm-hidden-stock';
			}

			return $class;
		}

		/**
		 * Remove loop add to cart link for preview products.
		 *
		 * @param string     $html    Button HTML.
		 * @param WC_Product $product Product object.
		 * @param array      $args    Button args.
		 * @return string
		 */
		public function remove_loop_add_to_cart_link( $html, $product, $args ) {
			if ( ! $product instanceof WC_Product ) {
				return $html;
			}

			if ( $this->is_preview_enabled( $product->get_id() ) ) {
				return '';
			}

			return $html;
		}

		/**
		 * Render optional custom note on single product page.
		 *
		 * @return void
		 */
		public function render_single_product_note() {
			global $product;

			if ( ! $product instanceof WC_Product ) {
				return;
			}

			if ( ! $this->is_preview_enabled( $product->get_id() ) ) {
				return;
			}

			$note = get_post_meta( $product->get_id(), self::META_NOTE, true );

			if ( empty( $note ) ) {
				return;
			}

			echo '<div class="lpm-preview-note">' . wp_kses_post( wpautop( $note ) ) . '</div>';
		}

		/**
		 * Render NOVO badge in product loops.
		 *
		 * @return void
		 */
		public function render_loop_badge() {
			global $product;

			if ( ! $product instanceof WC_Product ) {
				return;
			}

			if ( ! $this->is_preview_enabled( $product->get_id() ) ) {
				return;
			}

			echo '<span class="lpm-badge">NOVO</span>';
		}

		/**
		 * Render NOVO badge on single product page.
		 *
		 * @return void
		 */
		public function render_single_badge() {
			global $product;

			if ( ! $product instanceof WC_Product ) {
				return;
			}

			if ( ! $this->is_preview_enabled( $product->get_id() ) ) {
				return;
			}

			echo '<span class="lpm-badge lpm-badge-single">NOVO</span>';
		}

		/**
		 * Enqueue admin styles for the products list.
		 *
		 * @param string $hook_suffix Current admin page.
		 * @return void
		 */
		public function enqueue_admin_assets( $hook_suffix ) {
			if ( 'edit.php' !== $hook_suffix ) {
				return;
			}

			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

			if ( ! $screen || 'edit-product' !== $screen->id ) {
				return;
			}

			wp_enqueue_style(
				'lpm-admin',
				plugin_dir_url( __FILE__ ) . 'assets/css/admin.css',
				array(),
				'1.2.2'
			);
		}

		/**
		 * Enqueue frontend styles.
		 *
		 * @return void
		 */
		public function enqueue_assets() {
			if ( is_admin() ) {
				return;
			}

			wp_enqueue_style(
				'lpm-frontend',
				plugin_dir_url( __FILE__ ) . 'assets/css/frontend.css',
				array(),
				'1.2.0'
			);
		}
	}

	new LPM_Launch_Preview_Mode();
}
