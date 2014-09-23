$( document ).ready(function() {
	 
    $( "a[name='formnav']" ).click(function( event ) {
    	event.preventDefault();
        $('#request').attr('action', $(this).attr("href"));
        $("#request").submit();
    });

    var $slider3 = $("#slider").slider({ max: 100 , value: 10 });

    $slider3.slider("pips");
    $slider3.slider("float");
});

