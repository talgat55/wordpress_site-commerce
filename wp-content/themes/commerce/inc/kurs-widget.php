<?php


	
add_action( 'widgets_init', function(){
     register_widget( 'My_Widget' );
});	
/**
 * Adds My_Widget widget.
 */
class My_Widget extends WP_Widget {
	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'My_Widget', // Base ID
			__('My Widget', 'text_domain'), // Name
			array( 'description' => __( 'My first widget!', 'text_domain' ), ) // Args
		);
	}
	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
	function light_scripts() {  

	// Theme stylesheet.
	wp_enqueue_style( 'light-style', get_stylesheet_uri() );

 
	wp_enqueue_script( 'light-script',  '//cdnjs.cloudflare.com/ajax/libs/jquery/1.11.2/jquery.min.js', array( 'jquery' ), '20160412', true );

  
}
add_action( 'wp_enqueue_scripts', 'light_scripts' );
     	echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}
		function get_data($url) {

			$ch = curl_init();
			$timeout = 5;
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			$data = curl_exec($ch);
			curl_close($ch);
			return $data;

		}
		$date = date("Y-m-d");
		?>
		<script>
		jQuery(document).ready(function($){
				$.ajax({ url: 'http://finance.tut.by/kurs/', success: function(data) { console.log(data); } });

		});
		</script>
		<?php
		//$data = get_data("http://www.nbrb.by/API/ExRates/Rates?onDate=".$date."&Periodicity=0");
		$data = get_data("http://finance.tut.by/kurs");

		print_r (json_decode($data));
		echo $args['after_widget'];
 


	}
	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'New title', 'text_domain' );
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php 
	}
	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
}
?>