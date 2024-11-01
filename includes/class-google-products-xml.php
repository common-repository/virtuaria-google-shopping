<?php
/**
 * Handle generate XML from store products.
 *
 * @package Virtuaria/Integrations.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Google_Products_XML' ) ) :
	/**
	 * Class definition.
	 */
	abstract class Google_Products_XML {
		/**
		 * Generate xml from products.
		 *
		 * @param string $dir_file the dir to generate file.
		 * @param string $tag the tag to log.
		 * @return void
		 */
		protected function build_products_xml( $dir_file, $tag ) {
			$products = null;
			$offset   = 0;
			$per_page = 50;
			$log      = wc_get_logger();
			if ( is_multisite() && is_main_site() && class_exists( 'Virtuaria_Search_Filters' ) ) {
				$products = $this->get_shopping_products( $offset, $per_page );
			} else {
				$products = wc_get_products(
					array(
						'status'       => 'publish',
						'stock_status' => 'instock',
						'limit'        => $per_page,
						'offset'       => $offset,
					)
				);
			}

			if ( $products ) {
				$log->add( $tag, 'Gerando xml do ' . $tag, WC_Log_Levels::INFO );
				$xml     = new SimpleXMLElement( '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0"></rss>' );
				$channel = $xml->addChild( 'channel' );
				$channel->addChild(
					'title',
					$this->escape_text( get_bloginfo( 'name' ) . ' Produtos' )
				);
				$channel->addChild( 'link', home_url() );
				$channel->addChild(
					'description',
					$this->escape_text( get_option( 'blogdescription' ) )
				);
				$store_cod = $this->escape_text( get_option( 'virtuaria_gstore_code' ) );

				$count = 0;
				try {
					while ( ! empty( $products ) ) {
						$this->add_products_in_xml( $products, $channel, $store_cod, $tag );

						$offset += $per_page;
						$count   = $offset;
						if ( ! is_multisite() || ! is_main_site() ) {
							$products = wc_get_products(
								array(
									'status'       => 'publish',
									'stock_status' => 'instock',
									'limit'        => $per_page,
									'offset'       => $offset,
								)
							);
						} elseif ( class_exists( 'Virtuaria_Search_Filters' ) ) {
							$products = $this->get_shopping_products( $offset, $per_page );
						}
						if ( 0 === ( $count % 1000 ) ) {
							$log->add( $tag, '1000 produtos no xml ' . $tag, WC_Log_Levels::INFO );
						}
					}
				} catch ( Exception $e ) {
					$log->add( $tag, $e->getMessage(), WC_Log_Levels::ERROR );
				}

				$dir = $dir_file . 'feeds/sites/' . get_current_blog_id() . '/';
				if ( ! is_dir( $dir ) ) {
					mkdir( $dir, 0755, true );
				}

				$xml->saveXML( $dir . '/produtos.xml' );
				$log->add( $tag, 'XML do ' . $tag . ' gerado', WC_Log_Levels::INFO );
			}
		}

		/**
		 * Add products in xml.
		 *
		 * @param array            $products  the products.
		 * @param SimpleXMLElement $channel   the chanel.
		 * @param string           $store_cod the store cod.
		 * @param string           $tag       the caller log tag.
		 * @return void
		 */
		private function add_products_in_xml( $products, &$channel, $store_cod, $tag ) {
			foreach ( $products as $product ) {
				$item = null;
				if ( is_multisite() && is_main_site() && class_exists( 'Virtuaria_Search_Filters' ) ) {
					$item = $this->get_formated_shopping_product( $product );
				} else {
					$item = $this->get_formated_single_store_product( $product, $tag );
				}

				if ( ! empty( $item ) ) {
					$item_node = $channel->addChild( 'item' );
					$item_node->addChild( 'g:id', $item['id'], 'http://base.google.com/ns/1.0' );
					$item_node->addChild(
						'title',
						ucwords(
							mb_strtolower(
								$this->escape_text( $item['title'] )
							)
						)
					);
					$item_node->addChild(
						'g:description',
						ucwords(
							mb_strtolower(
								$this->escape_text( $item['description'] )
							)
						),
						'http://base.google.com/ns/1.0'
					);
					$item_node->addChild( 'link', $item['link'] );
					$item_node->addChild( 'g:image_link', $item['image_link'], 'http://base.google.com/ns/1.0' );
					$item_node->addChild( 'g:availability', $item['availability'], 'http://base.google.com/ns/1.0' );
					$item_node->addChild( 'g:price', $item['price'] . ' BRL', 'http://base.google.com/ns/1.0' );
					$item_node->addChild( 'g:condition', 'new', 'http://base.google.com/ns/1.0' );
					$item_node->addChild(
						'g:brand',
						$this->escape_text( $item['brand'] ),
						'http://base.google.com/ns/1.0'
					);

					if ( $item['sale_price'] ) {
						$item_node->addChild( 'g:sale_price', $item['sale_price'] . ' BRL', 'http://base.google.com/ns/1.0' );
					}

					if ( $item['unit_pricing_measure'] && $item['unit_pricing_base_measure'] ) {
						$item_node->addChild( 'g:unit_pricing_measure', $item['unit_pricing_measure'], 'http://base.google.com/ns/1.0' );
						$item_node->addChild( 'g:unit_pricing_base_measure', $item['unit_pricing_base_measure'], 'http://base.google.com/ns/1.0' );
					}

					if ( $store_cod ) {
						$item_node->addChild( 'g:store_code', $store_cod, 'http://base.google.com/ns/1.0' );
					}
				}

				$item_node = apply_filters( 'feed_product_xml_item', $item_node, $product );
			}
		}

		/**
		 * Gerenate single store xml.
		 *
		 * @param wc_product $product the product.
		 * @param string     $tag     the log tag.
		 * @return array
		 */
		private function get_formated_single_store_product( $product, $tag ) {
			$data = array();

			// Prevent send product to merchant google.
			if ( apply_filters( 'virtuaria_ignore_product_to_feed_shopping', false, $product, $tag ) ) {
				return $data;
			}

			if ( $this->is_valid_product( $product ) ) {
				$data['id']           = $product->get_id();
				$data['title']        = ucwords( mb_strtolower( $product->get_title() ) );
				$desc                 = $product->get_short_description() ? $product->get_short_description() : $product->get_description();
				$data['description']  = $desc ? substr( $desc, 0, 9950 ) : $data['title'];
				$data['link']         = $product->get_permalink();
				$data['image_link']   = wp_get_attachment_image_src( $product->get_image_id(), 'woocommerce_thumbnail' )[0];
				$data['availability'] = $product->is_in_stock() ? 'in_stock' : 'out_of_stock';
				$data['brand']        = $product->get_meta( '_product_brand' ) ? substr( $product->get_meta( '_product_brand' ), 0, 69 ) : get_bloginfo( 'name' );
				if ( $product->is_type( 'variable' ) ) {
					$data['price'] = number_format( floatval( $product->get_variation_price( 'min', true ) ), 2, ',', '.' );
				} else {
					$data['price'] = number_format( floatval( $product->get_regular_price() ), 2, ',', '.' );
					if ( $product->is_on_sale() ) {
						$data['sale_price'] = number_format( floatval( $product->get_sale_price() ), 2, ',', '.' );
					}
				}

				if ( strlen( $data['description'] ) > 800 ) {
					$data['description'] = substr( trim( $data['description'] ), 0, 800 ) . '...';
				}

				$unity_metric = get_post_meta( $product->get_id(), '_unity_metric', true );
				$box_metric   = get_post_meta( $product->get_id(), '_box_metric', true );

				if ( 'PC' === $unity_metric || 'UN' === $unity_metric ) {
					$unity_metric = 'ct';
				}

				if ( 'm²' === $unity_metric || 'M2' === $unity_metric ) {
					$unity_metric = 'sqm';
				}

				if ( $unity_metric && $box_metric && is_plugin_active( 'virtuaria-price-metric/virtuaria-price-metric.php' ) ) {
					$data['price'] = number_format( floatval( $product->get_regular_price() ) / floatval( $box_metric ), 2, ',', '.' );
					if ( $product->is_on_sale() ) {
						$data['sale_price'] = number_format( floatval( $product->get_sale_price() ) / floatval( $box_metric ), 2, ',', '.' );
					}
				}

				if ( $box_metric > 0 && class_exists( 'Virtuaria_Linx_Integration' ) && $GLOBALS['linx_price_metric'] ) {
					remove_filter( 'woocommerce_product_get_price', array( $GLOBALS['linx_price_metric'], 'product_unit_price' ), 90, 2 );
					$data['price'] = number_format( floatval( $product->get_regular_price() ), 2, ',', '.' );
					if ( $product->is_on_sale() ) {
						$data['price'] = number_format( floatval( $product->get_sale_price() ), 2, ',', '.' );
					}
					add_filter( 'woocommerce_product_get_price', array( $GLOBALS['linx_price_metric'], 'product_unit_price' ), 90, 2 );
				}
			}
			return $data;
		}

		/**
		 * Gerenate single store xml.
		 *
		 * @param object $product the product.
		 * @return array
		 */
		private function get_formated_shopping_product( $product ) {
			$data       = array();
			$extra_info = json_decode( $product->extra_info );

			if ( $extra_info->image && ! empty( $extra_info->link ) && $product->product_title ) {
				preg_match( '@src="([^"]+)"@', $extra_info->image, $match );
				$image = str_replace( array( 'src=', '"' ), '', array_pop( $match ) );
				$price = str_replace( 'R$', '', html_entity_decode( $extra_info->price_html ) );
				preg_match( '/\d+[\d,.]*/', $price, $matches );
				$price = $matches[0];

				$data['id']           = $product->blog_id . $product->product_id;
				$data['title']        = ucwords(
					mb_strtolower(
						$product->product_title
					)
				);
				$data['description']  = $extra_info->excerpt ? $extra_info->excerpt : $data['title'];
				$data['link']         = $extra_info->link;
				$data['image_link']   = $image;
				$data['availability'] = 'in_stock';
				$data['price']        = $price;
				$data['brand']        = ucwords( $product->store_name );

				if ( 1 === intval( $product->is_sale ) ) {
					$data['sale_price'] = number_format( floatval( $product->product_price ), 2, ',', '.' );
				}

				switch_to_blog( $product->blog_id );
				$is_external = is_plugin_active( 'virtuaria-b2-integration/virtuaria-b2-integration.php' );
				restore_current_blog();

				if ( 'Externo' === $product->filters || $is_external ) {
					$link         = str_replace( $product->blog_domain . '/loja/', '', $data['link'] );
					$store        = str_replace( array( 'http://', 'https://' ), '', $product->blog_domain );
					$store        = substr( $store, 0, strpos( $store, '.' ) );
					$data['link'] = network_site_url( 'produto/' . $store . '/' . $link );
				} else {
					switch_to_blog( $product->blog_id );
					$payments = WC()->payment_gateways->get_available_payment_gateways();
					restore_current_blog();
					if ( empty( $payments ) ) {
						return array();
					}
				}
			}

			return $data;
		}

		/**
		 * Escape specials characters from text.
		 *
		 * @param string $text text.
		 * @return string
		 */
		private function escape_text( $text ) {
			$text = str_replace( array( '', '♦' ), '', $text );
			return htmlspecialchars(
				html_entity_decode(
					$text
				)
			);
		}

		/**
		 * Get shopping products.
		 *
		 * @param int $offset   the query offset.
		 * @param int $per_page the limit for page.
		 * @return array
		 */
		protected function get_shopping_products( $offset, $per_page ) {
			global $wpdb;

			$list = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM wp_search_filters WHERE available = 1 AND store_categories NOT LIKE %s AND is_virtual <> 1 ORDER BY product_date DESC LIMIT %d, %d',
					'%' . $wpdb->esc_like( 'Imoveis' ) . '%',
					$offset,
					$per_page
				)
			);

			return $list;
		}

		/**
		 * Check product is valid to feed.
		 *
		 * @param wc_product $product the product.
		 */
		private function is_valid_product( $product ) {
			if ( $product->get_price()
				&& $product->get_regular_price()
				&& $product->get_image_id()
				&& ! $product->is_virtual()
				&& $product->is_purchasable()
				&& ! $product->is_type( 'grouped' )
				&& ! $product->is_type( 'woosg' )
				) {
				return true;
			}
			return false;
		}
	}
endif;
