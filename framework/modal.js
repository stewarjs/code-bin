$.fn.modal = function( options ) {
	
	options = $.extend({
		transition: 'fade',
        title: null,
        content: null,
		loadContent: null
	}, options);
	
	/* Map our transitions to the appropriate classes */
	var transitions = {
		'fade': 'modal__window--fade',
		'slideDown': 'modal__window--slide'
	};
	return this.each( function() {
		var $this = $(this);
		
			
			/* Only set the transition once */
			/* Fades on creation, transitions correctly each time it's called */
			/* Can't change transition once set */
			
			//$this.toggleClass(, true);
			//console.log(transitions[transition]);
			/* Toggle whether or not to show the modal window */
			$this.toggleClass('modal__window--active');
			
			/* Give functionality to the close (x) button */
			$this.find('.modal__close').off('click').on('click', function(e) {
				$this.toggleClass('modal__window--active');
				return false;
			});
			
			if( options.loadContent ) {
				$this.find('.modal__content').load(options.loadContent);
			}
	});

	
};
