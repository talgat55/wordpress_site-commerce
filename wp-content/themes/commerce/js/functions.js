 //-------------------------
 //  Sidebar
 //------------------------
 /*
 * Price range
 */
 ( function($) {


 	var minvalslider = $("#amount").data("min");
 	var maxvalslider = $("#amount").data("max");
    $( "#slider-range" ).slider({
      range: true,
      min: minvalslider,
      max: maxvalslider,
      values: [ minvalslider, maxvalslider ],
      slide: function( event, ui ) {
        $( "#amount" ).val( "$" + ui.values[ 0 ] + " - $" + ui.values[ 1 ] );
      }
    });
    $( "#amount" ).val( "$" + $( "#slider-range" ).slider( "values", 0 ) +
      " - $" + $( "#slider-range" ).slider( "values", 1 ) );
  } )(jQuery);