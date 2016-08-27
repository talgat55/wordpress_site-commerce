<!DOCTYPE html>
<html <?php language_attributes(); ?>  >
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<?php if ( is_singular() && pings_open( get_queried_object() ) ) : ?>
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	 <?php endif; ?>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php


	$options = get_theme_options();


?>
<div id="page" class="site">
 
		<header id="masthead" class="site-header clearfix">
			<div class="container">
			<div class="site-header-main">
				<div class="site-logo"> 
					 <a href="<?php echo home_url(); ?>">
					 	<img src="<?php echo  get_template_directory_uri()."/images/logo.png";  ?>"/>
					 	<h3>MIGHTY</h3>

					 </a>
				</div><!-- .site-branding -->

				<?php if ( has_nav_menu( 'primary' ) ) : ?>
					 
					<div id="site-header-menu" class="site-header-menu">
						<?php if ( has_nav_menu( 'primary' ) ) : ?>
							<nav id="site-navigation" class="main-navigation" role="navigation" aria-label="<?php esc_attr_e( 'Primary Menu', 'twentysixteen' ); ?>">
								<?php
									wp_nav_menu( array(
										'theme_location' => 'primary',
										'menu_class'     => 'primary-menu',
									 ) );
								?>
							</nav><!-- .main-navigation -->
						<?php endif; ?>

					 
					</div><!-- .site-header-menu -->
				<?php endif; ?>

				<!-- cart   -->
				<?php
				
				if($options["mode_cart"]){
				?>

					<div class="cart-header">

						<a href="#" class="link-header-cart"> 
							<i class="fa fa-shopping-cart" aria-hidden="true"></i>
							
							<div class="main-cart-content">
								
								<div class="top-cart-content">
								<h2><?php  _e( "Cart" , "light");  ?></h2>
										<ul>
											<li class="display-table clearfix"> 
												<span   class="link-item-cart"> 
													<span class="item-cart-first-part">
														<img src="https://hbr.org/resources/images/article_assets/2014/10/R1411C_LABROOY.jpg">
													</span>
													<span class="item-cart-second-part">
														<h3>Title</h3>
														<p>$ 40 </p> 
														
													</span>
												</span>
												<span class="delete-cart-item">
													<i class="fa fa-times" aria-hidden="true"></i>
												</span>

											</li>
										</ul>

										<div class="bottom-cart-contetn">
											<div class="total-cart">
												
												<?php  _e( "Total: " , "light");  ?>

											</div>
											<span  class="cart-butt-show-checkout">
												
												<i class="fa fa-list-alt" aria-hidden="true"></i><?php  _e( "Checkout" , "light");  ?>

											</span>	

										</div>

								</div>



							</div>

						</a>
						
					</div>

				<?php
				}

				?>
				<!-- end cart   -->



			</div><!-- .site-header-main -->
			</div>
	 
		</header><!-- .site-header -->

		<div id="content" class="site-content">
