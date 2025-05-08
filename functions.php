<?php
	function my_theme_enqueue_styles() {

	    $parent_style = 'parent-style'; 

	    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
	    wp_enqueue_style( 'child-style',
	        get_stylesheet_directory_uri() . '/style.css',
	        array( $parent_style ),
			wp_get_theme()->get('Version')
	    );
	}
	add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );

	// No related products
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
	
	// Style Map Layouts
	add_action( 'wp_head', 'style_the_map' );
	function style_the_map() {
		if( get_the_ID() == 548 ) {
			// We are on the Map Purchase page - do the damn thing
			echo '<style type="text/css">';
			$booth_args = array(
				'posts_per_page' => -1,
				'post_type' => 'product',
				'tax_query' => array(
					array(
						'taxonomy' => 'product_cat',
						'field' => 'slug',
						'terms' => 'booth'
					)
				)
			);
			$booth_query = new WP_Query( $booth_args );
			
			if( $booth_query->have_posts() ) {
				while( $booth_query->have_posts() ) {
					$booth_query->the_post();
					global $product;
					if( $product->get_stock_quantity() > 0 ) {
						$fill_color = '#77a464';
						$hover_color = '#8cc475';
					} else { $fill_color = '#F00'; $hover_color = '#f66220'; }

					$polygon_array = array('booth111', 'booth113', 'booth421', 'booth510');
					$sku = $product->get_sku();
					if( in_array( $sku, $polygon_array ) ) {
						echo 'g#' . $sku . ' polygon { ';
							echo 'fill: ' . $fill_color . ';';	
						echo '}';
						echo 'a.woo-booth:hover g#' . $sku . ' polygon { ';
							echo 'fill: ' . $hover_color . ';';	
						echo '}';
						
					} else {
						echo 'g#' . $sku . ' rect { ';
							echo 'fill: ' . $fill_color . ';';
						echo '}';
						echo 'a.woo-booth:hover g#' . $sku . ' rect { ';
							echo 'fill: ' . $hover_color . ';';
						echo '}';	
					}
				}
			}
			wp_reset_postdata();
			
			$table_args = array(
				'posts_per_page' => -1,
				'post_type' => 'product',
				'tax_query' => array(
					array(
						'taxonomy' => 'product_cat',
						'field' => 'slug',
						'terms' => 'table'
					)
				)
			);
			$table_query = new WP_Query( $table_args );
			
			if( $table_query->have_posts() ) {
				while( $table_query->have_posts() ) {
					$table_query->the_post();
					global $product;

					if( $product->get_stock_quantity() > 0 ) {
						$fill_color = '#77a464';
						$hover_color = '#8cc475';
					} else { $fill_color = '#F00'; $hover_color = '#f66220'; }
					
					$sku = $product->get_sku();
					echo 'g#' . $sku . ' path.st22, g#' . $sku . ' polygon.st0, g#' . $sku . ' path.st0 { ';
						echo 'fill: ' . $fill_color . ';';
					echo '}';
					echo 'a.woo-table:hover g#' . $sku . ' path.st22, a.woo-table:hover g#' . $sku . ' polygon.st0, a.woo-table:hover g#' . $sku . ' path.st0 { ';
						echo 'fill: ' . $hover_color . ';';
					echo '}';
						
				}
			}
			wp_reset_postdata();
			echo '</style>';
		} elseif( is_product() ) {
			echo '<style type="text/css">';
			$product = new WC_Product( get_the_ID() );
			$sku = $product->get_sku();
			if( $product->get_stock_quantity() > 0 ) {
				$fill_color = '#77a464';
			} else { $fill_color = '#F00'; }
			$terms = get_the_terms(get_the_ID(), 'product_cat');
			if( $terms[0]->slug == 'booth' ) {
				$polygon_array = array('booth111', 'booth113', 'booth421', 'booth510');
				if( in_array( $sku, $polygon_array ) ) {
					echo 'g#' . $sku . ' polygon { ';
						echo 'fill: ' . $fill_color . ';';	
					echo '}';	
				} else {
					echo 'g#' . $sku . ' rect { ';
						echo 'fill: ' . $fill_color . ';';
					echo '}';
				}
				
			} elseif( $terms[0]->slug == 'table' ) {
				echo 'g#' . $sku . ' path.st22, g#' . $sku . ' polygon.st0, g#' . $sku . ' path.st0 { ';
					echo 'fill: ' . $fill_color . ';';
				echo '}';
			}
			echo '</style>';
		}
		
	}
	
	// Change 'out of stock' to 'sold out'
	function change_availability_text( $availability, $product ) {
	    if( $availability['class'] == 'out-of-stock' ){ 
	        $availability['availability'] = 'Sold Out';
	    } 
	    return $availability;
	}
	add_filter( 'woocommerce_get_availability', 'change_availability_text', 10, 2 );
		
	// Add "Lock it in Now" text after product pricing
	function add_lock_in_price( $price ) {
		// Check if product is on sale
		$product_id = get_the_ID();
		$product = new WC_Product( $product_id );
		if( $product->is_on_sale() ) {
			if( $product->get_type() == 'simple') {
			$price .= '<div class="lock-in-now"><strong>Early Bird Price - Lock it in today.</strong><br /><em>Prices will increase on June 1, 2017.</em></div>';
			}
		}
		return $price;
	}
	add_filter( 'woocommerce_get_price_html', 'add_lock_in_price' );

	// Add text to ticket sales "from - to" products - REGULAR PRICE
	function add_membership_text( $price ) {
		$this_variable_product = new WC_Product_Variable( get_the_ID() );
		$prices = $this_variable_product->get_variation_prices( false );
		$min_price = current( $prices['price'] );
		$min_price = '$' . number_format($min_price, 0);
		$max_price = end( $prices['price'] );
		$max_price = '$' . number_format($max_price, 0);
		$price = '<span class="member-price">' . $min_price . '</span> TBNA Membership Rate <br />';
		$price .= '<span class="member-price">' . $max_price . '</span> Non-Member Rate';
		if( $this_variable_product->is_on_sale() ) {
			$price .= '<p class="earlybird-tickets"><em>Earlybird prices valid through June 1, 2017.</em></p>';
		}
		return $price;
	}
	add_filter( 'woocommerce_variable_price_html', 'add_membership_text', 10 );

	// Add SVG on Single Product Pages
	function change_to_svg( $html ) {
		$terms = get_the_terms(get_the_ID(), 'product_cat');
		if( $terms[0]->slug == 'ticket' ) {
			return $html;
		} else {
			$svg_url = get_stylesheet_directory_uri() . '/TBNAfloorplan.svg';
			$arrContextOptions=array(
				"ssl"=>array(
	    		"cafile" => "/home/tbconv/ca-file/cacert.pem",
	    		"verify_peer"=> true,
	    		"verify_peer_name"=> true,
				),
			);
			$response = file_get_contents($svg_url, false, stream_context_create($arrContextOptions));
			return $response;
		}
	}
	add_filter( 'woocommerce_single_product_image_thumbnail_html', 'change_to_svg' );

	// remove product thumbnails from cart
	add_filter( 'woocommerce_cart_item_thumbnail', '__return_false' );

?>