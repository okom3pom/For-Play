$(document).on('change', '#hamon_order', function(e){
	
	e.preventDefault();
	
		$.post($("#hamon_frm").attr("action")+'?ajax=true', $("#hamon_frm").serialize()).done(function(data) {
			$('.hamon_waiting').hide();
			var result = jQuery.parseJSON(data);
			if (result.success == false) {

				$("#products").html('<p class="errors">'+result.errors+'</p>');				
				$("#return_message").slideUp();
				$("#hamon_form_error").hide();			
				
				
			} else {
				$("#products").html(result.success);
				$("#return_message").slideDown();
				$("#hamon_form_error").hide();

			}
		});
		
		
		
});

$('document').ready(function(){
	

	$('#hamon_submit').click(function(){
		$('.hamon_submit_waiting').show();
		
		
		$.post($("#hamon_frm").attr("action"), $("#hamon_frm").serialize()).done(function(data) {
			$('.hamon_waiting').hide();
			var result = jQuery.parseJSON(data);
			if (result.success == false) {
				var errors = '';
				for (var i = 0; i < result.errors.length; i++) {
					errors = errors + '<li>' + result.errors[i] + '</li>';
				}
				$("#hamon_form_error").html('<ol class="errors">'+errors+'</ol>').show();
				$('body').scrollTo('#hamon_form_error');
			} else {
				$("#hamon_form_error").html('<p class="success">'+result.success+'</p>');
				$("#hamon_frm").hide();
			}
		});
		return false;
	});
	
	
});


