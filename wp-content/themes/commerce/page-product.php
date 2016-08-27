<?php
/*
Template Name: Product Page
*/
get_header(); 


	$options = get_theme_options();


?>
<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		<div class="container">
			<div class="wrap clearfix">
			<div class="row">
				<?php
					global $wpdb; 
					$search_table = $wpdb->prefix . "products";
					$search_table2 = $wpdb->prefix . "products_cat";
					$search_table3 = $wpdb->prefix . "products_addfield";


					$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
				 
					$limit = $options["items-products"]; // number of rows in page
					$offset = ( $pagenum - 1 ) * $limit;
					$total = $wpdb->get_var( "SELECT COUNT(`id`) FROM $search_table" );


					$num_of_pages = ceil( $total / $limit );

					#$resultscats  = $wpdb->get_results("SELECT * FROM ".$search_table2."");	
					$resultsproducts = $wpdb->get_results( "SELECT * FROM $search_table LIMIT $offset, $limit" );
				 
				if($options["layout-mode"] == "full"){
				
					$optfullwidth = true;
					$optleft = false;
					$optright = false;

				}else if($options["layout-mode"] == "left"){
					$optfullwidth = false;
					$optleft = true;
					$optright = false;


				}elseif($options["layout-mode"] == "right"){
					$optfullwidth = false;
					$optleft = false;
					$optright = true;


				}


				if($optfullwidth){


					echo "<div class='full-width'>";

				} elseif($optleft){

					echo "<div class='col-md-4'>";
					 	
					 	custom_sidebar("left");

					echo "</div>";
					echo "<div class='col-md-8'>";


				}elseif($optright){
					
					echo "<div class='col-md-8'>";

				}
			

				?>
				<ul class="product-list"> 

				<?php

				foreach ($resultsproducts as $key => $resultsproduct) {

					?>

					<li > 
						<div class="item-product  <?php  echo $options["product-items-mode"];   ?>">
							<img src="https://hbr.org/resources/images/article_assets/2014/10/R1411C_LABROOY.jpg">

							<h3>Title</h3> 
							<span class="bottom-block-item clearfix">
								<span>
								<p>$ 45</p>
								 
								</span> 
									<span>
										<a href="#" class="add-cart"><?php _e( "Add Cart", "light"); ?></a>
									</span>   
							</span>

						</div>
					</li>

	 					<li > 
						<div class="item-product  <?php  echo $options["product-items-mode"];   ?>">
							<img src="https://hbr.org/resources/images/article_assets/2014/10/R1411C_LABROOY.jpg">

							<h3>Title</h3> 
							<span class="bottom-block-item clearfix">
								<span>
								<p>$ 45</p>
								 
								</span> 
									<span>
										<a href="#" class="add-cart"><?php _e( "Add Cart", "light"); ?></a>
									</span>   
							</span>

						</div>
					</li>

	 					<li > 
						<div class="item-product  <?php  echo $options["product-items-mode"];   ?>">
							<img src="https://hbr.org/resources/images/article_assets/2014/10/R1411C_LABROOY.jpg">

							<h3>Title</h3> 
							<span class="bottom-block-item clearfix">
								<span>
								<p>$ 45</p>
								 
								</span> 
									<span>
										<a href="#" class="add-cart"><?php _e( "Add Cart", "light"); ?></a>
									</span>   
							</span>

						</div>
					</li>

	 
					<?php
				}
					 
				?>
				</ul>
				<?php


				// pagination
				$page_links = paginate_links( array(
				    'base' => add_query_arg( 'pagenum', '%#%' ),
				    'format' => '',
				    'prev_text' => __( '&laquo;', 'text-domain' ),
				    'next_text' => __( '&raquo;', 'text-domain' ),
				    'total' => $num_of_pages,
				    'current' => $pagenum
				) );

				if ( $page_links ) {
				    echo '<div class="pagination"><div class="tablenav-pages clearfix" style="margin: 1em 0">' . $page_links . '</div></div>';
				};				
				if($optright){
					echo "</div>";

					echo "<div class='col-md-4'>";
					 	
					custom_sidebar("right");

					

				}


					echo "</div>";

				 


				?>
			</div>
			</div>
		</div>
	</main><!-- .site-main -->
 

</div><!-- .content-area -->
 
<?php get_footer(); ?>
