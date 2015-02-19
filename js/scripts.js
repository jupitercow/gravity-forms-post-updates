function gform_up_show_input( $obj )
{
	$obj.hide().css({'opacity':1,'height':'auto'}).slideDown();
}

jQuery(document).ready(function($) {

	$('.' + gform_up.prefix + '_addmore_link')
		.on('click', function(e) {
			e.preventDefault();
			gform_up_show_input( $(this).fadeOut(200).siblings('.ginput_container') )
		});

	$('.' + gform_up.prefix + '_delete_link')
		.each(function(){
			$(this).closest('div').siblings('.ginput_container').css({opacity:0,height:0,overflow:'hidden'});
		})
		.on('click', function(e) {
			e.preventDefault();

			var $this = $(this),
				url   = $this.siblings('p').children('a:first').attr('href');

			$.ajax({
				url:      gform_up.url,
				type:     'post',
				async:    true,
				cache:    false,
				dataType: 'html',
				data: {
					action:   gform_up.action,
					post_id:  $this.data('post_id'),
					form_id:  $this.data('form_id'),
					meta:     $this.data('meta'),
					file:     url,
					featured: $this.data('featured'),
					nonce:    gform_up.nonce
				},

				success: function( response )
				{
					//console.log(response);
					if ( '1' === response )
					{
						var $container = $this.closest('div'),
							$input     = $container.siblings('.ginput_container');

						$container.fadeOut();
						if (! $('div:first', $input).hasClass('gform_fileupload_multifile') ) {
							gform_up_show_input( $input );
						}
					}
				},

				error: function( xhr )
				{
					//console.log(xhr.responseText);
				}
			});
		});

});