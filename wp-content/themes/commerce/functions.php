<?php
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;  
use PayPal\Api\CreditCard; 
use PayPal\Api\FundingInstrument; 


/*
* Uploader
*/
require_once  get_template_directory() ."/inc/class.uploader.php";

/*
* Redux hook
*/
function get_theme_options() {
 
	$current_options = get_option('options_redux');

	//use new options
	if(!empty($current_options)) {
		return $current_options;
	}  
}
$options = get_theme_options();

/*
* Coinbase
*/

if($options["switch-bitcoin"]){

require_once get_template_directory() . '/Coinbase/coinbase-php/lib/Coinbase.php';
    $args = array(
          'name'               => 'test',
          'price_string'       => '1.23',
          'price_currency_iso' => 'USD',
          'custom'             => 'Order123',
          'description'        => 'Sample description',
          'type'               => 'buy_now',
          'style'              => 'buy_now_large',
          'text'               => 'Pay with Bitcoin',
          'choose_price'       => false,
          'variable_price'     => false,
          'price1'             => '0.0',
          'price2'             => '0.0',
          'price3'             => '0.0',
          'price4'             => '0.0',
          'price5'             => '0.0',
    );

    for ($i = 1; $i <= 5; $i++) {
      if ($args["price$i"] == '0.0') {
        unset($args["price$i"]);
      }
    }


    $transient_name = 'cb_ecc_' . md5(serialize($args));
    $cached = get_transient($transient_name);
    if($cached !== false) {
      return $cached;
    }

    $api_key = $options["bitcoin-key"];
    $api_secret =  $options["bitcoin-apisecret"];
    if( $api_key && $api_secret ) {
      try {
        $coinbase = Coinbase::withApiKey($api_key, $api_secret);
        $button = $coinbase->createButtonWithOptions($args)->embedHtml;
      } catch (Exception $e) {
        $msg = $e->getMessage();
        error_log($msg);
        return "There was an error connecting to Coinbase: $msg. Please check your internet connection and API credentials.";
      }
      set_transient($transient_name, $button);
      return $button;
    } else {
      return "The Coinbase plugin has not been properly set up - please visit the Coinbase settings page in your administrator console.";
    }



} // end if bitoin


/*
* Enable QIWI
*/
if($options["switch-qiwi"]){

require_once  get_template_directory() ."/QIWI/qiwi.class.php";
function qiwi_payment(){

$Qiwi = new Qiwi();  
$bill_id = rand(10000000, 99999999);
$create_result = $Qiwi->create(
    '79001234567', // телефон
    100, // сумма
    date('Y-m-d', strtotime(date('Y-m-d') . " + 1 DAY")) . "T00:00:00", // Дата в формате ISO 8601. Здесь генерируется дата на день позже, чем текущая
    $bill_id, // ID платежа
    'Тестовая оплата' // комментарий
);
if($create_result->result_code !== 0){
    echo 'Ошибка в создании счета';
    exit;
}


// ПЕРЕАДРЕСАЦИЯ НА СТРАНИЦУ ОПЛАТЫ
$Qiwi->redir(              
    $bill_id, // ID счета
    'http://' . $_SERVER['SERVER_NAME'] . '/success_url', // URL, на который пользователь будет переброшен в случае успешного проведения операции (не обязательно)
    'http://' . $_SERVER['SERVER_NAME'] . '/fail_url' // URL, на который пользователь будет переброшен в случае неудачного завершения операции (не обязательно)
);
// ПОЛУЧЕНИЕ ИНФОРМАЦИИ О СЧЕТЕ
$info_result = $Qiwi->info($bill_id);
if($info_result->result_code !== 0)
    echo 'Ошибка в получении информации о счете';
else
    echo 'Статус счета: ' . $info_result->bill->status;
// ОТМЕНА СЧЕТА (не должна вызываться после redir(), иначе счет отменяется до того, как появится возможность оплатить его.
// Отмена должна происходить на другой странице, например на тех, которые указываются в success или fail url)
$reject_result = $Qiwi->reject($bill_id);
if($reject_result->bill->status === 'rejected')
    echo 'Не удалось отменить счет';
else
    echo 'Счет отменен';



}




} // end if qiwi 


/*
* Enable Paypal
*/
if($options["switch-paypal"]){
require_once  get_template_directory() ."/PayPal/autoload.php";
function paypal_payment_with_email(){


$options = get_theme_options();



$apiContext = new \PayPal\Rest\ApiContext(
    new \PayPal\Auth\OAuthTokenCredential(
        $options["paypal-clientid"],     // ClientID
        $options["paypal-secretkey"]      // ClientSecret
    )
);

$modework = $options["payment-mode"];

$apiContext->setConfig(
  array(
    'log.LogEnabled' => true,
    'log.FileName' => 'PayPal.log',
    'cache.enabled'=>false,
    'mode' => $modework,
    'cache.enabled'=>false,
    'log.LogLevel' => 'DEBUG'
  )
);
 

$payer = new Payer();
$payer->setPaymentMethod("paypal");

$item1 = new Item();
$item1->setName('Ground Coffee 40 oz')
    ->setCurrency('USD')
    ->setQuantity(1)
    ->setSku("123123") // Similar to `item_number` in Classic API
    ->setPrice(7.5);
$item2 = new Item();
$item2->setName('Granola bars')
    ->setCurrency('USD')
    ->setQuantity(5)
    ->setSku("321321") // Similar to `item_number` in Classic API
    ->setPrice(2);

$itemList = new ItemList();
$itemList->setItems(array($item1, $item2));

$details = new Details();
$details->setShipping(1.2)
    ->setTax(1.3)
    ->setSubtotal(17.50);

$amount = new Amount();
$amount->setCurrency("USD")
    ->setTotal(20)
    ->setDetails($details);

$transaction = new Transaction();
$transaction->setAmount($amount)
    ->setItemList($itemList)
    ->setDescription("Payment description")
    ->setInvoiceNumber(uniqid());


$baseUrl = getBaseUrl();
$redirectUrls = new RedirectUrls();
$redirectUrls->setReturnUrl("$baseUrl/ExecutePayment.php?success=true")
    ->setCancelUrl("$baseUrl/ExecutePayment.php?success=false");

$payment = new Payment();
$payment->setIntent("sale")
    ->setPayer($payer)
    ->setRedirectUrls($redirectUrls)
    ->setTransactions(array($transaction));

try {

	$payment->create($apiContext);
 

}
catch (\PayPal\Exception\PayPalConnectionException $ex) { 
    exit(1);
}     
 
$approvalUrl = $payment->getApprovalLink();

echo $approvalUrl;

}

function paypal_payment_with_card(){


$options = get_theme_options();


$apiContext = new \PayPal\Rest\ApiContext(
    new \PayPal\Auth\OAuthTokenCredential(
        $options["paypal-clientid"],     // ClientID
        $options["paypal-secretkey"]      // ClientSecret
    )
);

$modework = $options["payment-mode"];

$apiContext->setConfig(
  array(
    'log.LogEnabled' => true,
    'log.FileName' => 'PayPal.log',
    'cache.enabled'=>false,
    'mode' => $modework,
    'cache.enabled'=>false,
    'log.LogLevel' => 'DEBUG'
  )
);
 


$card = new CreditCard();
$card->setType("visa")
    ->setNumber("4669424246660779")
    ->setExpireMonth("11")
    ->setExpireYear("2019")
    ->setCvv2("012")
    ->setFirstName("Joe")
    ->setLastName("Shopper");


$fi = new FundingInstrument();
$fi->setCreditCard($card);


$payer = new Payer();
$payer->setPaymentMethod("credit_card")
    ->setFundingInstruments(array($fi));

$item1 = new Item();
$item1->setName('Ground Coffee 40 oz')
    ->setDescription('Ground Coffee 40 oz')
    ->setCurrency('USD')
    ->setQuantity(1)
    ->setTax(0.3)
    ->setPrice(7.50);
$item2 = new Item();
$item2->setName('Granola bars')
    ->setDescription('Granola Bars with Peanuts')
    ->setCurrency('USD')
    ->setQuantity(5)
    ->setTax(0.2)
    ->setPrice(2);

$itemList = new ItemList();
$itemList->setItems(array($item1, $item2));


$details = new Details();
$details->setShipping(1.2)
    ->setTax(1.3)
    ->setSubtotal(17.5);

$amount = new Amount();
$amount->setCurrency("USD")
    ->setTotal(20)
    ->setDetails($details);


$transaction = new Transaction();
$transaction->setAmount($amount)
    ->setItemList($itemList)
    ->setDescription("Payment description")
    ->setInvoiceNumber(uniqid());


$payment = new Payment();
$payment->setIntent("sale")
    ->setPayer($payer)
    ->setTransactions(array($transaction));            


try {

	$payment->create($apiContext);
 

}
catch (\PayPal\Exception\PayPalConnectionException $ex) { 
    exit(1);
}     
 
$approvalUrl = $payment->getApprovalLink();

echo $approvalUrl;



}
} // end paypal

/*
* create table
*/
function create_tables(){ 
global $wpdb;
	//create the name of the table including the wordpress prefix (wp_ etc)
	$search_table = $wpdb->prefix . "products";
	$search_table2 = $wpdb->prefix . "products_cat";
	$search_table3 = $wpdb->prefix . "products_addfield";
	//$wpdb->show_errors();
	//check if there are any tables of that name already
	$charset_collate = $wpdb->get_charset_collate();
	if($wpdb->get_var("SHOW TABLES LIKE '$search_table'") != $search_table)
	{
	//create your sql
	$sql = "CREATE TABLE ". $search_table . " (
	id INTEGER(12) NOT NULL AUTO_INCREMENT, 
	date DATETIME NOT NULL,
	name VARCHAR(255) NOT NULL,
	description text,
	sizs VARCHAR(255),
	colors VARCHAR(255),
	price DECIMAL(6,2) NOT NULL,
	price_descount INTEGER,
	uniqid VARCHAR(255) NOT NULL,
	PRIMARY KEY (id))$charset_collate;";
	}


	if($wpdb->get_var("SHOW TABLES LIKE '$search_table2'") != $search_table2)
	{
	//create your sql
	$sql2 = "CREATE TABLE ". $search_table2 . " (
	id INTEGER(12) NOT NULL AUTO_INCREMENT, 
	slug_cat VARCHAR(255) NOT NULL,
	name_cat VARCHAR(255) NOT NULL,
	PRIMARY KEY (id))
	$charset_collate;";
	}
	if($wpdb->get_var("SHOW TABLES LIKE '$search_table3'") != $search_table3)
	{
	//create your sql
	$sql3 = "CREATE TABLE ". $search_table3 . " (
	id INTEGER(12) NOT NULL AUTO_INCREMENT, 
	id_product INTEGER NOT NULL,
	field_option VARCHAR(255) NOT NULL,
	meta VARCHAR(255) NOT NULL,
	PRIMARY KEY (id))
	$charset_collate;";
	}

	//include the wordpress db functions
	require_once(ABSPATH . "wp-admin/includes/upgrade.php");

	if(isset($sql)) dbDelta($sql);
	if(isset($sql2)) dbDelta($sql2);
	if(isset($sql3)) dbDelta($sql3);
 
}
//add to front and backend inits
add_action("init", "create_tables");

/*
* Admin Panel
*/ 

if ( !class_exists( 'ReduxFramework' ) && file_exists( dirname( __FILE__ ) . '/framework/ReduxCore/framework.php' ) ) {
    require_once( dirname( __FILE__ ) . '/framework/ReduxCore/framework.php' );
    $using_nectar_redux_framework = true;
}
if ( !isset( $redux_demo ) && file_exists( dirname( __FILE__ ) . '/framework/options-config.php' ) ) {
    require_once( dirname( __FILE__ ) . '/framework/options-config.php' );
}

if ( ! function_exists( 'commerce_setup' ) ) :
 
function commerce_setup() {
 
	load_theme_textdomain( 'light', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	 
	add_theme_support( 'custom-logo', array(
		'height'      => 240,
		'width'       => 240,
		'flex-height' => true,
	) );
 
	add_theme_support( 'post-thumbnails' );
	set_post_thumbnail_size( 1200, 9999 );

	// This theme uses wp_nav_menu() in two locations.
	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'light' ),
		'social'  => __( 'Social Links Menu', 'light' ),
	) );

 
   
}
endif; 

add_action( 'after_setup_theme', 'commerce_setup' );

/*
* Special sidebar 
*/

function custom_sidebar($align){

$options = get_theme_options();

	
$pricerange = explode(",", $options["price-filter-range"]);

	global $wpdb; 
	$search_table = $wpdb->prefix . "products";
	#$search_table2 = $wpdb->prefix . "products_cat";
	#$search_table3 = $wpdb->prefix . "products_addfield";
	 
	$resultssizes = $wpdb->get_results( "SELECT sizs FROM $search_table" );
	$resultscolors = $wpdb->get_results( "SELECT colors FROM $search_table" );
		$appendsizes="";
		foreach ($resultssizes as $key => $resultssize) {
			$appendsizes .= ",".$resultssize->sizs;
		}
 	$appendsizes = explode(",", $appendsizes);
	$uniqarraysizes = array_unique($appendsizes, SORT_REGULAR);

  		$appendcolors="";
		foreach ($resultscolors as $key => $resultscolor) {
			$appendcolors .= ",".$resultscolor->colors;
		}
 	$appendcolors = explode(",", $appendcolors);
	$uniqarrayscolors = array_unique($appendcolors, SORT_REGULAR);
  

?>

	<aside class="<?php echo $align; ?>">
			<div class="widget">

				<h3><?php _e("Search", "light"); ?></h3>

				<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label>
						<span class="screen-reader-text"><?php echo _x( 'Search for:', 'label', 'light' ); ?></span>
						<input type="search" class="search-field" placeholder="<?php echo esc_attr_x( 'Search &hellip;', 'placeholder', 'light' ); ?>" value="<?php echo get_search_query(); ?>" name="s" />
					</label>
					<button type="submit" class="search-submit"><span class="screen-reader-text"><?php echo _x( 'Search', 'submit button', 'light' ); ?></span></button>
				</form>

			</div>

			<div class="widget">

				<h3><?php _e("Price range", "light"); ?></h3>

				<p>
				  <label for="amount">Price range:</label>
				  <input type="text" id="amount"  data-min="<?php echo $pricerange[0]; ?>" data-max="<?php echo $pricerange[1]; ?>"  readonly style="border:0; color:#00a69c; font-weight:bold; width: 54%;">
				</p>
				 
				<div id="slider-range"></div>

			</div>
			<div class="widget">

				<h3><?php _e("Sizes", "light"); ?></h3>

				 
				<div >
					<?php
						 
						foreach ($uniqarraysizes as $key => $uniqarraysize) {
							if($uniqarraysize){
							?>
								<a href="#" class="filter-size">
								<?php
									echo $uniqarraysize;
								?>
								</a>
							<?php
							}
						}

					?>

				</div>

			</div>

			<div class="widget">

				<h3><?php _e("Colors", "light"); ?></h3>

				 
				<div >
					<?php
						 
						foreach ($uniqarrayscolors as $key => $uniqarrayscolor) {
							if($uniqarrayscolor){
							?>
								<a href="#" class="filter-colors" style="border-color:<?php echo $uniqarrayscolor;  ?>; color:<?php echo $uniqarrayscolor; ?>;">
								<?php
									echo $uniqarrayscolor;
								?>
								</a>
							<?php
							}
						}

					?>

				</div>

			</div>



	</aside>

<?php
}
 
/**
 * Registers a widget area.
 *
 * @link https://developer.wordpress.org/reference/functions/register_sidebar/
 *
 * @since Twenty Sixteen 1.0
 */
/*
function light_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Sidebar', 'light' ),
		'id'            => 'sidebar-1',
		'description'   => __( 'Add widgets here to appear in your sidebar.', 'light' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );

	register_sidebar( array(
		'name'          => __( 'Content Bottom 1', 'light' ),
		'id'            => 'sidebar-2',
		'description'   => __( 'Appears at the bottom of the content on posts and pages.', 'light' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );

	register_sidebar( array(
		'name'          => __( 'Content Bottom 2', 'light' ),
		'id'            => 'sidebar-3',
		'description'   => __( 'Appears at the bottom of the content on posts and pages.', 'light' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );	
	register_sidebar( array(
		'name'          => __( 'Content Bottom 3', 'light' ),
		'id'            => 'sidebar-3',
		'description'   => __( 'Appears at the bottom of the content on posts and pages.', 'light' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );	
	register_sidebar( array(
		'name'          => __( 'Content Bottom 4', 'light' ),
		'id'            => 'sidebar-4',
		'description'   => __( 'Appears at the bottom of the content on posts and pages.', 'light' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
}
add_action( 'widgets_init', 'light_widgets_init' );
*/
  
function light_scripts() {  

	// Theme stylesheet.
	wp_enqueue_style( 'light-style', get_stylesheet_uri() );

	wp_enqueue_style( 'font-awesome', get_template_directory_uri() . '/css/font-awesome.css', array( 'light-style' ), ' ' );

	wp_enqueue_script( 'jquery',  '//code.jquery.com/jquery-1.12.4.js', array(), '' );
	wp_enqueue_script( 'jquery-ui',  '//code.jquery.com/ui/1.12.0/jquery-ui.js', array(), '' );
	
	wp_enqueue_script( 'light-script', get_template_directory_uri() . '/js/functions.js', array(  ), '', true );

	// Load the Internet Explorer specific stylesheet.
/*	
	wp_style_add_data( 'light-ie', 'conditional', 'lt IE 10' );

	// Load the Internet Explorer 8 specific stylesheet.
	wp_enqueue_style( 'light-ie8', get_template_directory_uri() . '/css/ie8.css', array( 'light-style' ), '20160412' );
	wp_style_add_data( 'light-ie8', 'conditional', 'lt IE 9' );

	// Load the Internet Explorer 7 specific stylesheet.
	wp_enqueue_style( 'light-ie7', get_template_directory_uri() . '/css/ie7.css', array( 'light-style' ), '20160412' );
	wp_style_add_data( 'light-ie7', 'conditional', 'lt IE 8' );
 

	 


 */
}
add_action( 'wp_enqueue_scripts', 'light_scripts' );
function light_admin_stylesheet(){

	wp_enqueue_style("style-admin",get_bloginfo('stylesheet_directory')."/css/style-admin.css");
 
	wp_enqueue_script( 'light-dinamic-select', get_template_directory_uri() . '/js/selectize.min.js', array(), '' ); 
	wp_enqueue_script( 'light-dinamic-filer', get_template_directory_uri() . '/js/jquery.filer.min.js', array(), '' ); 
	wp_enqueue_script( 'light-select2', get_template_directory_uri() . '/js/select2.full.min.js', array(), '' ); 



}
add_action('admin_head', 'light_admin_stylesheet');  
/*
*  Add caps
*/

  function add_theme_caps() { 

    $rolef = get_role( 'administrator' );
    $rolef->add_cap( 'admin_options' );       
}
add_action( 'admin_init', 'add_theme_caps');

/*----------------------------------
* Add Menu Category Products
-----------------------------------*/

add_action('admin_menu', 'add_menu_category_product');

// action function for above hook
function add_menu_category_product() {
 add_menu_page('Categories Products', 'Categories Products',  "admin_options", "products_categies", 'products_cat_function');
}
function products_cat_function(){
	global $wpdb; 
	#$search_table = $wpdb->prefix . "products";
	$search_table2 = $wpdb->prefix . "products_cat";
	#$search_table3 = $wpdb->prefix . "products_addfield";
		 


	$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
 
	$limit = 10; // number of rows in page
	$offset = ( $pagenum - 1 ) * $limit;
	$total = $wpdb->get_var( "SELECT COUNT(`id`) FROM $search_table2" );


	$num_of_pages = ceil( $total / $limit );

	#$resultscats  = $wpdb->get_results("SELECT * FROM ".$search_table2."");	
	$resultscats = $wpdb->get_results( "SELECT * FROM $search_table2 LIMIT $offset, $limit" );

 
echo <<<HTML
 		<script>
 
			jQuery(document).ready(function($){
				$('.close-butt').click(function(){
 
					$(this).parent().fadeOut();

				});
				$('.right-button.new-cat-button').click(function(){
 
					$(".modal-new-cat").fadeIn();

				});
			 
			  
				// end redy function
			});

		</script>
	<div class="wrap wrap-spec clearfix">
	
	
 			<div class='content'>

 				<div class='modal-new-cat'>	
 					<a href='#' class='close-butt'>×</a>
 					<!-- form create new category -->
		 			<form method="post"  class='form-create-cat'   >
		 				<h1>Create New Category</h1>
		 				<div  class='border-form'>

			 				<label>Name Category</label>

			 				<input type="text" name="namecat" required />

		 				</div>
						<div  class='border-form'>

			 				<label>Slug Category</label>

			 				<input type="text" name="slugcat" required/>

		 				</div>

						<div>

		 					<input type="submit"  class='submit-create newcat'  value="Create" />



		 				</div>
		 				
		 			</form>

		 			<!-- end create new category-->

				</div>

			<form method="post">		
		 

 				<div class='top-content clearfix'>
 					<h1 class='content-title'>
HTML;
						_e( 'Categories List' , 'light'); 
echo <<<HTML
					</h1>
 					<input type="submit" class='right-button save-cat-button' value='Save'>
HTML;
 				 		
echo <<<HTML
 					</a>
 					<a href='#' class='right-button new-cat-button'>
HTML;
 				 		_e( 'Add New' , 'light'); 
echo <<<HTML
 					</a>

 				</div>

 				<table class='content-list special-inputs'>
HTML;

 				echo "	<tr>
		 					<th>
		 						".__( 'ID' , 'light')." 
		 					</th>
		 					<th>
		 						".__( 'Name' , 'light')." 
		 					</th>
		 					<th>
		 						".__( 'Slug' , 'light')." 
		 					</th>
		 				 

 						</tr>";
 					 


				foreach ($resultscats as $key => $resultscat) {
					 
					 echo   "
							<tr>
							 	<td>
							 	".$resultscat->id."
								</td>

								<td>
							 		<input  type='text' name='editnamecat[".$resultscat->id."]' value='".$resultscat->name_cat."'/>
								</td>

								<td>
							 		<input  type='text' name='editslugcat[".$resultscat->id."]' value='".$resultscat->slug_cat."'/>
								</td>

								 

							</tr>
							";
				
				}


echo <<<HTML
					 
 				</table>
HTML;
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
				    echo '<div class="tablenav"><div class="tablenav-pages clearfix" style="margin: 1em 0">' . $page_links . '</div></div>';
				};


echo "				
			</div>

	
	</div>
	</form>

"
;


	//print_r($_POST);
	// save edit category
	if(isset($_POST["editnamecat"])){

		foreach ($_POST["editnamecat"] as $key => $value) {
				 
				$wpdb->query("UPDATE $search_table2 SET `name_cat` = '".$value."', `slug_cat` = '".$_POST['editslugcat'][$key]."'  WHERE id = '".$key."'");
		}



	echo "
 		<script>
 
 

			function locreload() {
  
 				location.reload();

			}
 
			setTimeout(locreload, 400) ;

		</script>

	";


	}

	// save category 
	if(isset($_POST["namecat"])){

				 isset($_POST["slugcat"]) ? $redyslug = trim($_POST["slugcat"]) : $redyslug ="" ;

				 

				

			$wpdb->query( $wpdb->prepare(
			    "INSERT INTO `".$search_table2."` (`id`, `slug_cat`, `name_cat` ) VALUES ( %d, %s,%s )",
			    array(
			        0,
			        trim($_POST["namecat"]),
			        $redyslug 
			    )
			));

	echo "
 		<script>
 
 

			function locreload() {
  
 				location.reload();

			}
 
			setTimeout(locreload, 400) ;

		</script>

	";


	}

 


// end function page category
 }



/*------------------------------------
** Add Menu Products
-------------------------------------*/

 add_action('admin_menu', 'mt_add_pages');

// action function for above hook
function mt_add_pages() {
 add_menu_page('Products', 'Products',  "admin_options", "products_page", 'products_page_function');
}
 function products_page_function(){

 ?>
<script>
jQuery(document).ready(function($){
	"use strict";
 
//-------------------------
// Load list items Products
//-------------------------

// get url
var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};   


 /*
 * Load list product
 */

						var pagenum = getUrlParameter('pagenum');

						if (pagenum == "undefined"){

								pagenum = "";

						}

                         var data = {

                            action: 'load_product',
                          	dataType: 'json',
                            getpagnum: pagenum 
 
                          
                          }; 
                          
                    jQuery.post( '/wp-admin/admin-ajax.php', data, function(response) {
                    	$(".loader-items").fadeOut();
                    	var data = $.parseJSON(response);

                    	 
 
                    	$.each( data, function () {
 
                    		var files = this.file.split(',');


                    	var arraytegsimg=""; 
                    	$.each( files, function () {
                    		if (this.length){
 
                    		arraytegsimg += "<img src='" + this+"'>";
 

							}

                    	});

                    	  
 
                    		$(".special-inputs-products").append("<tr><td>"+this.id +"</td><td class='product-image'> "+ 	arraytegsimg +" </td><td>"+ this.price +"</td><td>"+ this.name +"</td><td>"+ this.price_descount +"</td> <td><a href='#' class=' edit-product'   data-id='"+this.id+"'> Edit</a></td> </tr>");
 

                    	})


                    	editproductfunction(); // load edit form event


                    });  // end ajax 




$('#filer_input').filer({
    changeInput: '<div class="jFiler-input-dragDrop"><div class="jFiler-input-inner"><div class="jFiler-input-icon"><i class="icon-jfi-folder"></i></div><div class="jFiler-input-text"><h3>Click on this box</h3> <span style="display:inline-block; margin: 15px 0">or</span></div><a class="jFiler-input-choose-btn blue">Browse Files</a></div></div>',
    showThumbs: true,
    theme: "dragdropbox",
    templates: {
        box: '<ul class="jFiler-items-list jFiler-items-grid"></ul>',
        item: '<li class="jFiler-item">\
                    <div class="jFiler-item-container">\
                        <div class="jFiler-item-inner">\
                            <div class="jFiler-item-thumb">\
                                <div class="jFiler-item-status"></div>\
                                <div class="jFiler-item-info">\
                                    <span class="jFiler-item-title"><b title="{{fi-name}}">{{fi-name | limitTo: 25}}</b></span>\
                                    <span class="jFiler-item-others">{{fi-size2}}</span>\
                                </div>\
                                {{fi-image}}\
                            </div>\
                            <div class="jFiler-item-assets jFiler-row">\
                                <ul class="list-inline pull-left"></ul>\
                                <ul class="list-inline pull-right">\
                                    <li><a class="icon-jfi-trash jFiler-item-trash-action"></a></li>\
                                </ul>\
                            </div>\
                        </div>\
                    </div>\
                </li>',
        itemAppend: '<li class="jFiler-item">\
                        <div class="jFiler-item-container">\
                            <div class="jFiler-item-inner">\
                                <div class="jFiler-item-thumb">\
                                    <div class="jFiler-item-status"></div>\
                                    <div class="jFiler-item-info">\
                                        <span class="jFiler-item-title"><b title="{{fi-name}}">{{fi-name | limitTo: 25}}</b></span>\
                                        <span class="jFiler-item-others">{{fi-size2}}</span>\
                                    </div>\
                                    {{fi-image}}\
                                </div>\
                                <div class="jFiler-item-assets jFiler-row">\
                                    <ul class="list-inline pull-left">\
                                        <li><span class="jFiler-item-others">{{fi-icon}}</span></li>\
                                    </ul>\
                                    <ul class="list-inline pull-right">\
                                        <li><a class="icon-jfi-trash jFiler-item-trash-action"></a></li>\
                                    </ul>\
                                </div>\
                            </div>\
                        </div>\
                    </li>',
        itemAppendToEnd: false,
        removeConfirmation: true,
        _selectors: {
            list: '.jFiler-items-list',
            item: '.jFiler-item',
            remove: '.jFiler-item-trash-action'
        }
    }





	}); 

function  editproductfunction(){

				//----------------------------------
				// Edit Form
				//----------------------------------

				$('.edit-product').click(function(){
 						 
					 var dataid = $(this).attr('data-id');
				 		 	
                          var data = {

                            action: 'get_product',
                          	dataType: 'json',
                            dataid: dataid 
 
                          
                          }; 
                          
                    jQuery.post( '/wp-admin/admin-ajax.php', data, function(response) {
                    	console.log(response);
                    	data = $.parseJSON(response);
                    		 
                    	$('.edit-field-name-product').val(data[0].name);
                    	$('.edit-field-price-product').val(data[0].price);
                    	$('.edit-field-description-product').text(data[0].description);
                    	$('.edit-field-colors-product').val(data[0].colors);
                    	$('.edit-field-sizs-product').val(data[0].sizs);
                    	$('.ideditproduct').val(data[0].id);

 


                    	// cat
                    	var stringcat = data['cat'].split(',');
					 
						$.each(stringcat, function (i, item) {

							 
						    // $('.edit-cat-product option[value='+item+']').attr('selected', 'true').text(item);
						    $('.edit-cat-product option[value="'+item+'"]').attr('selected', 'selected'); 



						});
 
							$('.selectcat-product').select2({
							  tags: true
							})	
							$('.checkcolor-edit, .checksize-edit').prop( "checked", true );			
							$('.edit-field-sizs-product, .edit-field-colors-product').selectize({
							    plugins: ['restore_on_backspace'],
							    delimiter: ',',
							    persist: false,
							    create: function(input) {
							        return {
							            value: input,
							            text: input
							        }
							    }
							});

						 

							$('.block-add-colors, .block-add-size').show();



                    	$('.modal-edit-product').fadeIn();
                    	/*
                    	*  Load files
                    	*/
						 var $arrayfils = [];
						 jQuery.each(data['file'], function () {  
						 
							 var itemfile = {};

							 itemfile ["name"] = this.name;
							 itemfile ["size"] = this.size;
							 itemfile ["type"] = this.type;
							 itemfile ["file"] = this.url;

							 $arrayfils.push(itemfile);

						 });
 console.log($arrayfils);

$(' .filer_input_edit').filer({

	files: $arrayfils,
    changeInput: '<div class="jFiler-input-dragDrop"><div class="jFiler-input-inner"><div class="jFiler-input-icon"><i class="icon-jfi-folder"></i></div><div class="jFiler-input-text"><h3>Click on this box</h3> <span style="display:inline-block; margin: 15px 0">or</span></div><a class="jFiler-input-choose-btn blue">Browse Files</a></div></div>',
    showThumbs: true,
    theme: "dragdropbox",
    templates: {
        box: '<ul class="jFiler-items-list jFiler-items-grid"></ul>',
        item: '<li class="jFiler-item">\
                    <div class="jFiler-item-container">\
                        <div class="jFiler-item-inner">\
                            <div class="jFiler-item-thumb">\
                                <div class="jFiler-item-status"></div>\
                                <div class="jFiler-item-info">\
                                    <span class="jFiler-item-title"><b title="{{fi-name}}">{{fi-name | limitTo: 25}}</b></span>\
                                    <span class="jFiler-item-others">{{fi-size2}}</span>\
                                </div>\
                                {{fi-image}}\
                            </div>\
                            <div class="jFiler-item-assets jFiler-row">\
                                <ul class="list-inline pull-left"></ul>\
                                <ul class="list-inline pull-right">\
                                    <li><a class="icon-jfi-trash jFiler-item-trash-action"></a></li>\
                                </ul>\
                            </div>\
                        </div>\
                    </div>\
                </li>',
        itemAppend: '<li class="jFiler-item">\
                        <div class="jFiler-item-container">\
                            <div class="jFiler-item-inner">\
                                <div class="jFiler-item-thumb">\
                                    <div class="jFiler-item-status"></div>\
                                    <div class="jFiler-item-info">\
                                        <span class="jFiler-item-title"><b title="{{fi-name}}">{{fi-name | limitTo: 25}}</b></span>\
                                        <span class="jFiler-item-others">{{fi-size2}}</span>\
                                    </div>\
                                    {{fi-image}}\
                                </div>\
                                <div class="jFiler-item-assets jFiler-row">\
                                    <ul class="list-inline pull-left">\
                                        <li><span class="jFiler-item-others">{{fi-icon}}</span></li>\
                                    </ul>\
                                    <ul class="list-inline pull-right">\
                                        <li><a class="icon-jfi-trash jFiler-item-trash-action"></a></li>\
                                    </ul>\
                                </div>\
                            </div>\
                        </div>\
                    </li>',
        itemAppendToEnd: false,
        removeConfirmation: true,
        _selectors: {
            list: '.jFiler-items-list',
            item: '.jFiler-item',
            remove: '.jFiler-item-trash-action'
        }
    }





	}); 

 }); // end ajax query 


}); // end click end edit product 
} // function edit 

			 
			  // show and hide modal window
				$('.close-butt').click(function(){
 
					$(this).parent().fadeOut();

				});
				$('.right-button.new-product-button').click(function(){
 			 
					$('.modal-new-product').fadeIn();

				});

				 // fields togle color and size also special select
				 
				$(' #idsize, #idcolor').selectize({
				    plugins: ['restore_on_backspace'],
				    delimiter: ',',
				    persist: false,
				    create: function(input) {
				        return {
				            value: input,
				            text: input
				        }
				    }
				});
								 
			 	 
								 
			 
				$('.checkcolor').change(function() {
					var varcheckcolor = $('.checkcolor');
				  		if(varcheckcolor.hasClass('show-color')){
							
							$('.block-add-colors').fadeOut();
							varcheckcolor.removeClass('show-color');

				  		}else{
				  			varcheckcolor.addClass('show-color');
							$('.block-add-colors').fadeIn();

				  		}
					

				});
				$('.checksize').change(function() {
					var varchecksize = $('.checksize');
				  		if(varchecksize.hasClass('show-size')){
							
							$('.block-add-size').fadeOut();
							varchecksize.removeClass('show-size');

				  		}else{
				  			varchecksize.addClass('show-size');
							$('.block-add-size').fadeIn();

				  		}
					

				});
					// select category
				$('.selectcat-product').select2({
				  tags: true
				})				










	// end redy function 
});  
</script>
 <?php
	global $wpdb; 
	$search_table = $wpdb->prefix . "products";
	$search_table2 = $wpdb->prefix . "products_cat";
	$search_table3 = $wpdb->prefix . "products_addfield";

 
	$resultsallcategories = $wpdb->get_results( "SELECT * FROM $search_table2  " ); 


 	echo  " 
 		<div class='wrap  wrap-spec'>
				<div class='modal-new-product'   >

				

				<a href='#' class='close-butt'>×</a>		

					<!-- start for mcreate new form -->

					<form id='form-create-product'   method='post' enctype='multipart/form-data' >
						<h1>".__( 'Create New Product' , 'light')."</h1>
						<div class='border-form'>
							<label>".__( 'Name Product' , 'light')."</label>
							<input type='text' name='nameproduct'>
						</div>
						<div class='border-form'>
							<label>".__( 'Price' , 'light')."</label>
							<input type='text' name='price'>
						</div>
						<div class='border-form'>
							<label>".__( 'Choose Categies' , 'light')."</label>

							<select  class='selectcat-product' multiple name='categoryproduct[]' >";

							foreach ($resultsallcategories as $key => $resultsallcategory) {
								echo "<option value='".$resultsallcategory->name_cat."'>".$resultsallcategory->name_cat."</option>";
							}
							

							echo  "
							</select>
						</div>

						<div class='border-form'>
							<label>".__( 'Description' , 'light')."</label>
							<textarea  name='description'></textarea>
						</div>

						<div class='border-form'>

							<!--toggle color-->

							<label>".__( 'Color' , 'light')."</label>

							<div class='slideThree'>
								
								<input type='checkbox' id='slideThree'  class='checkcolor' name='selectcolor' >
								<label for='slideThree'></label>
							</div>

							<!--array colors-->

							<div  class='block-add-colors'>
								<label>".__( 'Entry needed colors' , 'light')."</label>
								<input type='text' name='arraycolors' id='idcolor' />
							</div>

						</div>

						<div class='border-form'>

							<!--toggle size-->

							<label>".__( 'Size' , 'light')."</label>

							<div class='slideThree'>
								
								<input type='checkbox' id='slideTwo'  class='checksize' name='selectsize' >
								<label for='slideTwo'></label>
							</div>

							<!--array sizs-->

							<div  class='block-add-size'>
								<label>".__( 'Entry needed Sizs' , 'light')."</label>
								<input type='text' name='arraysizs' id='idsize' />
							</div>

						</div>

						<div class='border-form clearfix'>
							<label>".__( 'Uploads Images' , 'light')."</label>
							<input type='file' name='files[]' id='filer_input' multiple='multiple'>

						</div>

						<!--<a href='#' id='submit-create-product' class='submit-create' >".__( 'Create' , 'light')."</a>-->
						<input type='submit' id='submit-create-product' class='submit-create' value=".__( 'Create' , 'light').">

					</form>

					<!-- end form create new product -->




				</div>



					<!-- start edit form  product -->

				<div class='modal-edit-product'>

				

				<a href='#' class='close-butt'>×</a>	
					


					<form  class='form-edit-product'   method='post' enctype='multipart/form-data' >
						<h1>".__( 'Edit  Product' , 'light')."</h1>
						<div class='border-form'>
							<label>".__( 'Name Product' , 'light')."</label>
							<input type='text' name='nameproductedit'  class='edit-field-name-product'>
							<input type='hidden' name='ideditproduct'  class='ideditproduct'>
						</div>
						<div class='border-form'>
							<label>".__( 'Price' , 'light')."</label>
							<input type='text' name='price'  class='edit-field-price-product'>
						</div>
						<div class='border-form'>
							<label>".__( 'Choose Categies' , 'light')."</label>

							<select  class='selectcat-product edit-cat-product' multiple name='categoryproduct[]' >";

							foreach ($resultsallcategories as $key => $resultsallcategory) {
								echo "<option value='".$resultsallcategory->name_cat."'>".$resultsallcategory->name_cat."</option>";
							}
							

							echo  "
							</select>
						</div>

						<div class='border-form'>
							<label>".__( 'Description' , 'light')."</label>
							<textarea  name='description'  class='edit-field-description-product'></textarea>
						</div>

						<div class='border-form'>

							<!--toggle color-->

							<label>".__( 'Color' , 'light')."</label>

							<div class='slideThree'>
								
								<input type='checkbox' id='slideThree'  class='checkcolor checkcolor-edit' name='selectcolor'>

								<label for='slideThree'></label>

							</div>
							

							<!--array colors--> 

							<div  class='block-add-colors'>
								<label>".__( 'Entry needed colors' , 'light')."</label>
								<input type='text' name='arraycolors' class='edit-field-colors-product'  id='idcolor' />
							</div>

						</div>

						<div class='border-form'>

							<!--toggle size-->

							<label>".__( 'Size' , 'light')."</label>

							<div class='slideThree'>
								
								<input type='checkbox' id='slideTwo'  class='checksize checksize-edit' name='selectsize'>
								<label for='slideTwo'></label>
							</div>

							<!--array sizs-->

							<div  class='block-add-size'>
								<label>".__( 'Entry needed Sizs' , 'light')."</label>
								<input type='text' name='arraysizs' class='edit-field-sizs-product' id='idsize' />
							</div>

						</div>

						<div class='border-form clearfix'>
							<label>".__( 'Uploads Images' , 'light')."</label>
							<input type='file' name='filesedit[]' id='filer_input' class='filer_input_edit' multiple='multiple'>

						</div>

						<input type='submit' class='submit-create' value=".__( 'Save' , 'light').">
						
					</form>
					<!-- end form edit product -->


				</div>
 			<div class='content'> 
 				<div class='top-content clearfix'>
 					<h1 class='content-title'>".__( 'Products List' , 'light')."</h1>
 					
 
 					<a href='#' class='right-button new-product-button'>
 					".__( 'Add New' , 'light')."
 					</a>

 				</div>

 				
					<table class='content-list special-inputs-products'>
		 				<tr>
		 					<th >".__( 'ID' , 'light')."</th> 
		 					<th >".__( 'Image' , 'light')."</th> 
		 					<th >".__( 'Name' , 'light')."</th> 
		 					<th>".__( 'Price' , 'light')."</th>  
		 					<th>".__( 'Price Descount' , 'light')."</th>  
		 					<th>".__( 'Action' , 'light')."</th>  
		 				</tr>
		 				";






		 	echo "</table>";
			
			echo "<div class='loader-items'> <img src='".get_template_directory_uri()."/images/loader.gif'/> 
						<span style='    display: block; text-transform: uppercase; padding-top: 9px;'>".__( 'Loading Items' , 'light')."</span></div>	
 			</div>";
 ?>	
 			<div class="block-pagination"></div>	
 <?php	

echo "
 		</div>
 	";

 	global $wpdb;
	$search_table = $wpdb->prefix . "products";
	$search_table2 = $wpdb->prefix . "products_cat";
	$search_table3 = $wpdb->prefix . "products_addfield";

 	/*
 	* Save new Product 
 	*/
 	if (isset($_POST["nameproduct"])){


 		
 			$datenew = Date("Y-m-d"); 
 				 



 			 isset($_POST['nameproduct']) ? $name = trim($_POST['nameproduct']) : $name = "";

 			 isset($_POST['price']) ? $price = trim($_POST['price']) : $price = "";

 			 isset($_POST['description']) ? $description = $_POST['description'] : $description = "";

 			 isset($_POST['arraycolors']) ? $arraycolors = trim($_POST['arraycolors']) : $arraycolors = "";

 			 isset($_POST['arraysizs']) ? $arraysizs = trim($_POST['arraysizs']) : $arraysizs = "";



 			$uniqid = uniqid();

 			 

			$wpdb->query( $wpdb->prepare(
			    "INSERT INTO `".$search_table."` (`id`, `date`, `name` ,`description`,`sizs`,`colors`,`price`, `uniqid`) VALUES ( %d, %s,%s,%s,%s, %s, %s, %s )",
			    array(
			        0,
			        $datenew,
			        $name, 
			        $description,
			        $arraysizs,
			        $arraycolors,
			        $price,
			        $uniqid
			    )
			));
				 

				$resultIdProduct = $wpdb->get_results( "SELECT id FROM $search_table WHERE uniqid='".$uniqid."'" );
  


				if (isset($resultIdProduct)) {

					$idproduct = $resultIdProduct[0];

				}else{
					
					$idproduct = "";

				}

				isset($_POST["categoryproduct"]) ? $redyarraycatrow = implode( ",",$_POST["categoryproduct"]) : $redyarraycatrow = "";

			$wpdb->query( $wpdb->prepare(
			    "INSERT INTO `".$search_table3."` (`id`, `id_product`, `field_option` ,`meta` ) VALUES ( %d, %s,%s,%s )",
			    array(
			        0,
			        $idproduct->id,
			        $redyarraycatrow,   
			        "category"
			    )
			));



 			 if (isset($_FILES["files"])){


				    $uploader = new Uploader();
				    $data = $uploader->upload($_FILES['files'], array(
				        'limit' => 10, //Maximum Limit of files. {null, Number}
				        'maxSize' => 10, //Maximum Size of files {null, Number(in MB's)}
				        'extensions' => null, //Whitelist for file extension. {null, Array(ex: array('jpg', 'png'))}
				        'required' => false, //Minimum one file is required for upload {Boolean}
				        'uploadDir' => '../uploads/', //Upload directory {String}
				        'title' => array('name'), //New file name {null, String, Array} *please read documentation in README.md
				        'removeFiles' => true, //Enable file exclusion {Boolean(extra for jQuery.filer), String($_POST field name containing json data with file names)}
				        'perms' => null, //Uploaded file permisions {null, Number}
				        'onCheck' => null, //A callback function name to be called by checking a file for errors (must return an array) | ($file) | Callback
				        'onError' => null, //A callback function name to be called if an error occured (must return an array) | ($errors, $file) | Callback
				        'onSuccess' => null, //A callback function name to be called if all files were successfully uploaded | ($files, $metas) | Callback
				        'onUpload' => null, //A callback function name to be called if all files were successfully uploaded (must return an array) | ($file) | Callback
				        'onComplete' => null, //A callback function name to be called when upload is complete | ($file) | Callback
				        'onRemove' => 'onFilesRemoveCallback' //A callback function name to be called by removing files (must return an array) | ($removed_files) | Callback
				    ));
				    if($data['isComplete']){
				        $files = $data['data'];
 			 

 		

				        	for ($f=0;$f<=count($files["files"]);$f++) {

  							
  							if(!empty($files["files"][$f])){
							 
								$wpdb->query( $wpdb->prepare(
								    "INSERT INTO `".$search_table3."` (`id`, `id_product`, `field_option` ,`meta` ) VALUES ( %d, %s,%s,%s )",
								    array(
								        0,
								        $idproduct->id,
								        $files["files"][$f], 
								        "file"
								    )
								));
							}
  
				        	}


				    }

				    if($data['hasErrors']){
				        $errors = $data['errors'];
				        print_r($errors);
				    } 



 			 }			
			

	}




 	/*
 	* Save EDit  Product 
 	*/
 	if (isset($_POST["nameproductedit"])){


 		
 			$datenew = Date("Y-m-d"); 
 				 



 			 isset($_POST['nameproductedit']) ? $name = trim($_POST['nameproductedit']) : $name = "";

 			 isset($_POST['price']) ? $price = trim($_POST['price']) : $price = "";

 			 isset($_POST['description']) ? $description = $_POST['description'] : $description = "";

 			 isset($_POST['arraycolors']) ? $arraycolors = trim($_POST['arraycolors']) : $arraycolors = "";

 			 isset($_POST['arraysizs']) ? $arraysizs = trim($_POST['arraysizs']) : $arraysizs = "";



 			 

			$wpdb->query( $wpdb->prepare(
			    "INSERT INTO `".$search_table."` (`id`, `date`, `name` ,`description`,`sizs`,`colors`,`price`, `uniqid`) VALUES ( %d, %s,%s,%s,%s, %s, %s, %s )",
			    array(
			        0,
			        $datenew,
			        $name, 
			        $description,
			        $arraysizs,
			        $arraycolors,
			        $price,
			        $uniqid
			    )
			));
			$wpdb->query("UPDATE $table_name SET `name` = '".$name."', `description` = '".$description."', `price` = '".$price."', `colors` = '".$arraycolors."', `sizs` = '".$arraysizs."' WHERE id = '".$_POST['ideditproduct']."'");

				 
 

				isset($_POST["categoryproduct"]) ? $redyarraycatrow = implode( ",",$_POST["categoryproduct"]) : $redyarraycatrow = "";

			$wpdb->query("UPDATE $table_name3 SET `field_option` = '".$redyarraycatrow."'  WHERE id_product = '".$_POST['ideditproduct']."' AND meta='category'");

		 



 			 if (isset($_FILES["files"])){


				    $uploader = new Uploader();
				    $data = $uploader->upload($_FILES['files'], array(
				        'limit' => 10, //Maximum Limit of files. {null, Number}
				        'maxSize' => 10, //Maximum Size of files {null, Number(in MB's)}
				        'extensions' => null, //Whitelist for file extension. {null, Array(ex: array('jpg', 'png'))}
				        'required' => false, //Minimum one file is required for upload {Boolean}
				        'uploadDir' => '../uploads/', //Upload directory {String}
				        'title' => array('name'), //New file name {null, String, Array} *please read documentation in README.md
				        'removeFiles' => true, //Enable file exclusion {Boolean(extra for jQuery.filer), String($_POST field name containing json data with file names)}
				        'perms' => null, //Uploaded file permisions {null, Number}
				        'onCheck' => null, //A callback function name to be called by checking a file for errors (must return an array) | ($file) | Callback
				        'onError' => null, //A callback function name to be called if an error occured (must return an array) | ($errors, $file) | Callback
				        'onSuccess' => null, //A callback function name to be called if all files were successfully uploaded | ($files, $metas) | Callback
				        'onUpload' => null, //A callback function name to be called if all files were successfully uploaded (must return an array) | ($file) | Callback
				        'onComplete' => null, //A callback function name to be called when upload is complete | ($file) | Callback
				        'onRemove' => 'onFilesRemoveCallback' //A callback function name to be called by removing files (must return an array) | ($removed_files) | Callback
				    ));
				    if($data['isComplete']){
				        $files = $data['data'];
 			 

 		

				        	for ($f=0;$f<=count($files["files"]);$f++) {

  							
  							if(!empty($files["files"][$f])){
							 
								$wpdb->query( $wpdb->prepare(
								    "INSERT INTO `".$search_table3."` (`id`, `id_product`, `field_option` ,`meta` ) VALUES ( %d, %s,%s,%s )",
								    array(
								        0,
								        $idproduct->id,
								        $files["files"][$f], 
								        "file"
								    )
								));
							}
  
				        	}


				    }

				    if($data['hasErrors']){
				        $errors = $data['errors'];
				        print_r($errors);
				    } 



 			 }			
			

	}








// end  product page function 
 }
 /*
 *  For Edit form
 */
 add_action('wp_ajax_get_product', 'get_product_callback');

function get_product_callback() {
 	global $wpdb; 
	$search_table = $wpdb->prefix . "products";
	#$search_table2 = $wpdb->prefix . "products_cat";
	$search_table3 = $wpdb->prefix . "products_addfield";
	$id_product = $_POST["dataid"];
 
	$resultproduct = $wpdb->get_results( "SELECT * FROM $search_table  WHERE  id='$id_product'" );
	$resultproductcat= $wpdb->get_results( "SELECT field_option FROM $search_table3  WHERE  id_product='$id_product'  AND  meta='category'" ); 
	$resultfilesproducts= $wpdb->get_results( "SELECT field_option FROM $search_table3  WHERE  id_product='$id_product'  AND  meta='file'" ); 

    $resultproduct["cat"] = $resultproductcat[0]->field_option;
  #  $resultproduct["file"] = $resultfilesproducts[0]->field_option;


 	$subarray =  array();

 	for($i = 0 ; $i<= count($resultfilesproducts); $i++) {
 		if(!empty($resultfilesproducts[$i]->field_option)){
		 	$filesize 	=  filesize($resultfilesproducts[$i]->field_option);
		 	$path_parts =  pathinfo($resultfilesproducts[$i]->field_option);
		 	$namefile 	=  $path_parts['basename'];
			$finfo 		=  finfo_open(FILEINFO_MIME_TYPE);
			$typeimage 	=  finfo_file($finfo, $resultfilesproducts[$i]->field_option); 	 	
	 		array_push($subarray , array(
	 			"id"   => $id_product,
		    	"size" => $filesize , 
		     	"type" => $typeimage, 
		     	"url" => $resultfilesproducts[$i]->field_option, 
		     	"name" => $namefile

	    	));
    	}
 	
 	}




    $resultproduct["file"] = $subarray;
    

 
	if($resultproduct){
		echo json_encode($resultproduct);
 	}else {

 		echo "error get data";
 	} 
wp_die();
} 
/*
* Load  Product Items  * List
*/
 add_action('wp_ajax_load_product', 'load_product_callback');

function load_product_callback() {

 
	global $wpdb; 
	$search_table = $wpdb->prefix . "products";
	$search_table2 = $wpdb->prefix . "products_cat";
	$search_table3 = $wpdb->prefix . "products_addfield";


	$pagenum = isset( $_POST['getpagnum'] ) ? absint( $_POST['getpagnum'] ) : 1;
 
	$limit = 10; // number of rows in page
	$offset = ( $pagenum - 1 ) * $limit;
	$total = $wpdb->get_var( "SELECT COUNT(`id`) FROM $search_table" );


	$num_of_pages = ceil( $total / $limit );

	#$resultscats  = $wpdb->get_results("SELECT * FROM ".$search_table2."");	
	$resultsproducts = $wpdb->get_results( "SELECT * FROM $search_table LIMIT $offset, $limit" );
	$resultsallfiles = $wpdb->get_results( "SELECT * FROM $search_table3  WHERE  meta='file'" ); 
 

 	$arayitems = array(); 

	foreach ($resultsproducts as $key => $resultsproduct) {
		$files = "";
		$i = 0;
		foreach ($resultsallfiles as $key => $resultsallfile) {
			

			if($resultsproduct->id ==  $resultsallfile->id_product){
			 
				if($i==0){

					$files = $resultsallfile->field_option;

				}else{
 
					$files .= ",".$resultsallfile->field_option;

				}
 
			$i++;

			}

		}
			
			
 
				#array_merge($arayitemsredy,$arayitems );

				 array_push($arayitems,  array(

				 		"file" => $files,
				 		"id"   => $resultsproduct->id,
				 		"name" => $resultsproduct->name,
				 		"price"=> $resultsproduct->price,
				 		"price_descount"=> $resultsproduct->price_descount

				 	));
				 
			 


		
	}

	$response = $arayitems;

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

				    $response["pagination"] =  '<div class="tablenav"><div class="tablenav-pages clearfix" style="margin: 1em 0">' . $page_links . '</div></div>';

				} 

	echo json_encode($response);



wp_die();
} 	
