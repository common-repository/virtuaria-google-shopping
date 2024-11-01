<?php
/**
 * Plugin name: Virtuaria - Google Shopping
 * Plugin URI: https://virtuaria.com.br
 * Description: Generate xml from products in store and shopping.
 * Version: 1.0.7
 * Author: Virtuaria
 * License: GPLv2 or later
 *
 * @package Virtuaria/Integration/Google.
 */

defined( 'ABSPATH' ) || exit;

register_activation_hook( __FILE__, array( 'Virtuaria_Google_Shopping', 'initialize_google_shopping_integration' ) );
register_deactivation_hook( __FILE__, array( 'Virtuaria_Google_Shopping', 'deactivation_event_schedule' ) );
if ( ! class_exists( 'Virtuaria_Google_Shopping' ) ) :
	require_once 'includes/class-google-products-xml.php';
	/**
	 * Class definition.
	 */
	class Virtuaria_Google_Shopping extends Google_Products_XML {
		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;

		/**
		 * Instance from log.
		 *
		 * @var WC_Logger
		 */
		protected static $log;

		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Singleton Constructor.
		 */
		private function __construct() {
			if ( ! class_exists( 'Woocommerce' ) ) {
				add_action( 'admin_notices', array( $this, 'missing_dependency' ) );
				return;
			}

			add_action( 'google_generate_feed', array( $this, 'generate_feed' ) );
			add_filter( 'woocommerce_structured_data_product_offer', array( $this, 'change_price_woo_structure_data' ), 10, 2 );
			add_action( 'init', array( $this, 'register_endpoint' ) );
			add_action( 'admin_init', array( $this, 'regenerate_feed' ) );
			add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
			add_action( 'template_include', array( $this, 'redirect_to_shopping_file' ) );
			add_action( 'admin_menu', array( $this, 'add_submenu' ) );
			add_action( 'save_google_setup', array( $this, 'save_google_setup' ) );
			add_action( 'virtuaria_ignore_product_to_feed_shopping', array( $this, 'ignore_products_from_category_groups' ), 10, 3 );
			add_filter( 'cron_schedules', array( $this, 'google_cron_events_frequency' ) );
			add_action( 'wp_head', array( $this, 'gtagmanager_head' ), 1 );
			add_action( 'woocommerce_product_options_pricing', array( $this, 'add_product_brand_field' ) );
			add_action( 'save_post_product', array( $this, 'save_product_brand_field' ), 20 );
			add_action( 'in_admin_footer', array( $this, 'display_review_info' ) );
			$this->log = wc_get_logger();
		}

		/**
		 * Display warning about missing dependency.
		 */
		public function missing_dependency() {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_attr_e( 'Virtuaria Google Shopping need Woocommerce 4.0+ to work!', 'virtuaria-google-integration' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Run action to initialiaze google shopping integrate.
		 *
		 * @return void
		 */
		public static function initialize_google_shopping_integration() {
			if ( ! wp_next_scheduled( 'google_generate_feed' ) ) {
				wp_schedule_event( strtotime( '05:00:00' ), 'daily', 'google_generate_feed' );
			}
		}

		/**
		 * Uneschedule event on deactivation plugin.
		 *
		 * @return void
		 */
		public static function deactivation_event_schedule() {
			wp_clear_scheduled_hook( 'google_generate_feed' );
		}

		/**
		 * Generate xml from products.
		 *
		 * @return void
		 */
		public function generate_feed() {
			$this->build_products_xml( plugin_dir_path( __FILE__ ), 'google shopping' );
		}

		/**
		 * Fix fraction price in woo structure context data.
		 *
		 * @param array      $markup the current context.
		 * @param wc_product $product the product data.
		 */
		public function change_price_woo_structure_data( $markup, $product ) {
			$box_metric = floatval( get_post_meta( $product->get_id(), '_box_metric', true ) );
			if ( $box_metric ) {
				if ( is_plugin_active( 'virtuaria-price-metric/virtuaria-price-metric.php' ) ) {
					$price = number_format( floatval( $product->get_price() ) / floatval( $box_metric ), 2, ',', '.' );
				}

				if ( class_exists( 'Virtuaria_Linx_Integration' ) ) {
					$price = get_post_meta( $product->get_id(), '_price', true );
				}
				$markup['price']                       = wc_format_decimal( $price, wc_get_price_decimals() );
				$markup['priceSpecification']['price'] = wc_format_decimal( $price, wc_get_price_decimals() );
			}
			return $markup;
		}

		/**
		 * Endpoint to homolog file.
		 */
		public function register_endpoint() {
			add_rewrite_rule( 'virtuaria-google-shopping(/)?', 'index.php?virtuaria-google-shopping=sim', 'top' );
		}

		/**
		 * Add query vars.
		 *
		 * @param array $query_vars the query vars.
		 * @return array
		 */
		public function add_query_vars( $query_vars ) {
			$query_vars[] = 'virtuaria-google-shopping';
			return $query_vars;
		}

		/**
		 * Redirect access to confirm page.
		 *
		 * @param string $template the template path.
		 * @return string
		 */
		public function redirect_to_shopping_file( $template ) {
			if ( false == get_query_var( 'virtuaria-google-shopping' ) ) {
				return $template;
			}

			return plugin_dir_path( __FILE__ ) . '/includes/download-shopping-file.php';
		}

		/**
		 * Add submenu in marketing.
		 */
		public function add_submenu() {
			global $submenu;

			if ( isset( $submenu['marketing'] ) ) {
				add_submenu_page(
					'marketing',
					'Integração Google',
					'Integração Google',
					'remove_users',
					'google_integration',
					array( $this, 'setup_google_integration_page' )
				);
			} else {
				add_menu_page(
					'Integração Google',
					'Integração Google',
					'remove_users',
					'google_integration',
					array( $this, 'setup_google_integration_page' ),
					'dashicons-google'
				);
			}
		}

		/**
		 * Display Google integration setup.
		 */
		public function setup_google_integration_page() {
			require_once 'templates/form-setup-page.php';
		}

		/**
		 * Save google setup.
		 */
		public function save_google_setup() {
			if ( isset( $_POST['_wpnonce'] )
				&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'pixel_integration' )
				&& isset( $_POST['store_code'] )
				&& isset( $_POST['analytics'] ) ) {
				update_option( 'virtuaria_gstore_code', sanitize_text_field( wp_unslash( $_POST['store_code'] ) ) );
				update_option( 'virtuaria_google_analytics', sanitize_text_field( wp_unslash( $_POST['analytics'] ) ) );

				$categories = isset( $_POST['tax_input']['product_cat'] )
					? array_map( 'sanitize_text_field', wp_unslash( $_POST['tax_input']['product_cat'] ) )
					: '';
				update_option( 'virtuaria_google_ignore_categories', $categories );

				$groups = isset( $_POST['tax_input']['product_group'] )
					? array_map( 'sanitize_text_field', wp_unslash( $_POST['tax_input']['product_group'] ) )
					: '';
				update_option( 'virtuaria_google_ignore_groups', $groups );

				if ( isset( $_POST['frequency'] ) ) {
					update_option( 'virtuaria_google_frequency_feed', sanitize_text_field( wp_unslash( $_POST['frequency'] ) ) );

					$event = wp_get_scheduled_event( 'google_generate_feed' );
					if ( $event && $_POST['frequency'] !== $event->schedule ) {
						wp_clear_scheduled_hook( 'google_generate_feed' );
						wp_schedule_event(
							strtotime( '05:00:00' ),
							sanitize_text_field( wp_unslash( $_POST['frequency'] ) ),
							'google_generate_feed'
						);
					}
				}
				echo '<div id="message" class="updated success">Configuração salva com sucesso!</div>';
			} else {
				echo '<div id="message" class="updated error">Desculpe! Não foi possível salvar esta configuração, tente novamente.</div>';
			}
		}

		/**
		 * Ignore product categories.
		 *
		 * @param boolean    $ignore  true if product should be ignored.
		 * @param wc_product $product instance from product.
		 * @param string     $caller  identify caller from xml generate.
		 */
		public function ignore_products_from_category_groups( $ignore, $product, $caller ) {
			if ( 'google shopping' !== $caller ) {
				return $ignore;
			}

			$categories = get_option( 'virtuaria_google_ignore_categories' );
			$groups     = get_option( 'virtuaria_google_ignore_groups' );

			if ( $categories ) {
				$terms = wp_get_post_terms(
					$product->get_id(),
					'product_cat',
					array( 'include' => $categories )
				);

				if ( ! empty( $terms ) ) {
					$ignore = true;
				}
			}

			if ( ! $ignore && $groups ) {
				$terms = wp_get_post_terms(
					$product->get_id(),
					'product_group',
					array( 'include' => $groups )
				);

				if ( ! empty( $terms ) ) {
					$ignore = true;
				}
			}
			return $ignore;
		}

		/**
		 * Force regenerate feed.
		 */
		public function regenerate_feed() {
			if ( isset( $_GET['page'] )
				&& isset( $_GET['_wpnonce'] )
				&& 'google_integration' === $_GET['page']
				&& ! isset( $_POST['store_code'] ) ) {
				if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'force_regenerate_feed' ) ) {
					$this->generate_feed();
					$message = '<div id="message" class="updated success">Feed atualizado com sucesso!</div>';
				} else {
					$message = '<div id="message" class="updated error">Desculpe! Não foi possível atualizar o feed, tente novamente.</div>';
				}
			}

			if ( $message ) {
				set_transient(
					'virtuaria_google_feed_message',
					$message,
					60
				);
			}
		}

		/**
		 * Add custom schedules time.
		 *
		 * @param array $schedules the current schedules.
		 * @return array
		 */
		public function google_cron_events_frequency( $schedules ) {
			if ( ! isset( $schedules['daily'] ) ) {
				$schedules['daily'] = array(
					'interval' => 1 * DAY_IN_SECONDS,
					'display'  => 'Uma vez ao dia',
				);
			}

			if ( ! isset( $schedules['twice_day'] ) ) {
				$schedules['twice_day'] = array(
					'interval' => 12 * HOUR_IN_SECONDS,
					'display'  => 'A cada 12 horas',
				);
			}

			if ( ! isset( $schedules['every_eight_hours'] ) ) {
				$schedules['every_eight_hours'] = array(
					'interval' => 8 * HOUR_IN_SECONDS,
					'display'  => 'A cada 8 horas',
				);
			}

			if ( ! isset( $schedules['every_six_hours'] ) ) {
				$schedules['every_six_hours'] = array(
					'interval' => 6 * HOUR_IN_SECONDS,
					'display'  => 'A cada 6 horas',
				);
			}

			return $schedules;
		}

		/**
		 * Print scripts from Google tag manager and analytics in header.
		 */
		public function gtagmanager_head() {
			$analytics = get_option( 'virtuaria_google_analytics' );

			if ( $analytics ) {
				?>
				<!-- Global site tag (gtag.js) - Google Analytics -->
				<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_html( $analytics ); ?>"></script>
				<script>
						window.dataLayer = window.dataLayer || [];
						function gtag(){dataLayer.push(arguments);}
						gtag('js', new Date());
						gtag('config', '<?php echo esc_html( $analytics ); ?>');
				</script>
				<?php
			}
		}

		/**
		 * Add brand field.
		 */
		public function add_product_brand_field() {
			woocommerce_wp_text_input(
				array(
					'id'    => '_product_brand',
					'class' => 'brand short',
					'label' => __( 'Marca', 'woocommerce' ),
					'type'  => 'text',
				)
			);
			wp_nonce_field( 'brand_update', 'brand_nonce' );
		}

		/**
		 * Save brand field.
		 *
		 * @param int $post_id the product id.
		 */
		public function save_product_brand_field( $post_id ) {
			if ( is_admin()
				&& isset( $_POST['brand_nonce'] )
				&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['brand_nonce'] ) ), 'brand_update' )
				&& isset( $_POST['_product_brand'] ) ) {
				update_post_meta( $post_id, '_product_brand', sanitize_text_field( wp_unslash( $_POST['_product_brand'] ) ) );
			}
		}

		/**
		 * Review info.
		 */
		public function display_review_info() {
			if ( isset( $_GET['page'] )
				&& 'google_integration' === $_GET['page'] ) {
				echo '<style>#wpfooter{display: block;position:static;}
				#wpbody-content {
					padding-bottom: 0;
				}
				h4.stars {
					margin-bottom: 0;
				}
				#wpcontent {
					display: table;
				}</style>';
				echo '<h4 class="stars">Avalie nosso trabalho ⭐</h4>';
				echo '<p class="review-us">Apoie o nosso trabalho. Se gostou do plugin, deixe uma avaliação positiva clicando <a href="https://wordpress.org/support/plugin/virtuaria-google-shopping/reviews?rate=5#new-post " target="_blank">aqui</a>. Desde já, nossos agradecimentos.</p>';
				echo '<h4 class="stars">Tecnologia Virtuaria ✨</h4>';
				echo '<p class="disclaimer">Desenvolvimento, implantação e manutenção de e-commerces e marketplaces para atacado e varejo. Soluções personalizadas para cada cliente. <a target="_blank" href="https://virtuaria.com.br">Saiba mais</a>.</p>';
			}
		}
	}

	add_action( 'plugins_loaded', array( 'Virtuaria_Google_Shopping', 'get_instance' ) );

endif;
