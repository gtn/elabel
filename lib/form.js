$( document ).ready(function() {
	 
    $( "a[name='formnav']" ).click(function( event ) {
    	event.preventDefault();
        $("form[name='request']").attr('action', $(this).attr("href"));
        $("form[name='request']").submit();
    });

	var weight = $("#weightvalue").val();
    var value = $("#pagevalue").val();
	$( "#score" ).text("Punkte: " + Math.round(value * (weight/100)));
	$( "#pagevaluelabel" ).text("Erfüllungsgrad " + value + "%");
	
    var $slider3 = $("#slider").slider({ max: 100 , value: value, slide: 
    	function( event, ui ) {
        	$( "#pagevalue" ).val( ui.value );
        	$( "#score" ).text("Punkte: " + Math.round(ui.value * (weight/100)));
        	$( "#pagevaluelabel" ).text("Erfüllungsgrad " + ui.value + "%");
    	}
    });

    $slider3.slider("pips", {
        rest: "label",
        step: 10,
        suffix: "%"
    });
    $slider3.slider("float");
    generateMinMax();
    
    $("select").change(function() {
    	generateMinMax();
    });
    $("textarea").on('change keyup paste', function() {
        generateMinMax();
    });
    function generateMinMax(){
		countTrue = 0;
		countPartly = 0;
		countOthers = 0;
		countAll = 0;
		$('select option:selected').each(function (index,value) {
			if($(this).attr('value') == 3)
				countTrue++;
			if($(this).attr('value') == 1)
				countPartly++;
			
			countAll++;
		});
		
		$('textarea').each(function(index,value) {
			if($(this).val())
				countOthers++;
			
			countAll++;
		});
		
		console.log(countTrue*2);
		console.log(countPartly);
		console.log(countOthers*2);
		
		multiplier = $("#pagemultiplier").val();
		console.log(multiplier);
		sum = countTrue * 2 + countPartly + countOthers * 2;
		console.log(sum);
		result = sum * 100 / (countAll*2) * multiplier;
		console.log(result);
		min = Math.floor(result-15);
		console.log(min);
		max = Math.floor(result+15);
		console.log(max);
		if(min < 0) min = 0;
		if(max > 100) max = 100;
		$( "#min" ).text("min: " + min);
		$( "#max" ).text("max: " + max);
    }
});

