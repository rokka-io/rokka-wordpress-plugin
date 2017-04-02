jQuery(document).ready( function () {
});

(function($) {
var rokkaSubjectAreaEdit = window.rokkaSubjectAreaEdit = {
	iasapi : {},
	hold : {},
	postid : '',

	intval : function(f) {
		/*
		 * Bitwise OR operator: one of the obscure ways to truncate floating point figures,
		 * worth reminding JavaScript doesn't have a distinct "integer" type.
		 */
		return f | 0;
	},

	round : function(num) {
		var s;
		num = Math.round(num);

		if ( this.hold.sizer > 0.6 ) {
			return num;
		}

		s = num.toString().slice(-1);

		if ( '1' === s ) {
			return num - 1;
		} else if ( '9' === s ) {
			return num + 1;
		}

		return num;
	},

	validateNumeric: function( num ) {
		if ( ! this.intval( num ) ) {
			return false;
		}
	},

	init : function(postid) {
		var t = this,
			x = t.intval( $('#subjectarea-original-width-' + postid).val() ),
			y = t.intval( $('#subjectarea-original-height-' + postid).val() );

		t.hold.w = t.hold.ow = x;
		t.hold.h = t.hold.oh = y;
		t.hold.xy_ratio = x / y;
		t.hold.sizer = parseFloat( $('#subjectarea-sizer-' + postid).val() );
		t.postid = postid;

		var img = $('#image-subjectarea-preview-' + postid), parent = $('#subjectarea-' + postid);

		this.initSubjectArea(postid, img, parent);
		// set initial selection (from database)
		this.setNumSelection(postid, $('#subjectarea-sel-x-' + postid));
		// Editor is ready, move focus to the first focusable element.
		$( '.subjectarea-wrap .imgedit-help-toggle' ).eq( 0 ).focus();
	},

	initSubjectArea : function(postid, image, parent) {
		var t = this,
			selW = $('#subjectarea-sel-width-' + postid),
			selH = $('#subjectarea-sel-height-' + postid),
			selX = $('#subjectarea-sel-x-' + postid),
			selY = $('#subjectarea-sel-y-' + postid),
			$img;

		t.iasapi = $(image).imgAreaSelect({
			parent: parent,
			instance: true,
			handles: true,
			keys: true,
			minWidth: 3,
			minHeight: 3,

			onInit: function( img ) {
				// Ensure that the imgareaselect wrapper elements are position:absolute
				// (even if we're in a position:fixed modal)
				$img = $( img );
				$img.next().css( 'position', 'absolute' )
					.nextAll( '.imgareaselect-outer' ).css( 'position', 'absolute' );

				parent.children().mousedown(function(e){
					var ratio = false, sel, defRatio;

					if ( e.shiftKey ) {
						sel = t.iasapi.getSelection();
						defRatio = t.getSelRatio(postid);
						ratio = ( sel && sel.width && sel.height ) ? sel.width + ':' + sel.height : defRatio;
					}

					t.iasapi.setOptions({
						aspectRatio: ratio
					});
				});
			},

			onSelectChange: function(img, c) {
				var sizer = rokkaSubjectAreaEdit.hold.sizer;

				selW.val( rokkaSubjectAreaEdit.round(c.width / sizer) );
				selH.val( rokkaSubjectAreaEdit.round(c.height / sizer) );
				selX.val( rokkaSubjectAreaEdit.round(c.x1 / sizer) );
				selY.val( rokkaSubjectAreaEdit.round(c.y1 / sizer) );
			}
		});
	},


	setNumSelection : function( postid, el ) {
		var sel, elWidth = $('#subjectarea-sel-width-' + postid), elHeight = $('#subjectarea-sel-height-' + postid),
			width = this.intval( elWidth.val() ), height = this.intval( elHeight.val() ),
			elX = $('#subjectarea-sel-x-' + postid), elY = $('#subjectarea-sel-y-' + postid),
			x = this.intval( elX.val() ), y = this.intval( elY.val() ),
			img = $('#image-subjectarea-preview-' + postid), imgh = img.height(), imgw = img.width(),
			sizer = this.hold.sizer, x1, y1, x2, y2, ias = this.iasapi;

		if ( false === this.validateNumeric( width ) || width < 3 ) {
			elWidth.val( 0 );
			width = 0
		}

		if ( false === this.validateNumeric( height ) || height < 3 ) {
			elHeight.val( 0 );
			height = 0;
		}

		if ( false === this.validateNumeric( x ) || x < 0 ) {
			elX.val( 0 );
			x = 0;
		}

		if ( false === this.validateNumeric( y ) || y < 0 ) {
			elY.val( 0 );
			y = 0;
		}

		// remove selection if width or height are smaller than minimum
		if ( width < 3 || height < 3 ) {
			ias.cancelSelection();
			ias.update();
			return
		}

		x1 = Math.round( x * sizer );
		y1 = Math.round( y * sizer );
		x2 = x1 + Math.round( width * sizer );
		y2 = y1 + Math.round( height * sizer );

		if ( x2 > imgw ) {
			x1 = 0;
			x2 = imgw;
			elWidth.val( Math.round( x2 / sizer ) );
			elX.val( 0 );
		}

		if ( y2 > imgh ) {
			y1 = 0;
			y2 = imgh;
			elHeight.val( Math.round( y2 / sizer ) );
			elY.val( 0 );
		}

		ias.setSelection( x1, y1, x2, y2 );
		ias.setOptions({ show: true });
		ias.update();
	},

	removeSelection : function( postid ) {
		var t = this,
			selW = $('#subjectarea-sel-width-' + postid),
			selH = $('#subjectarea-sel-height-' + postid),
			selX = $('#subjectarea-sel-x-' + postid),
			selY = $('#subjectarea-sel-y-' + postid),
			ias = this.iasapi;

		selW.val( 0 );
		selH.val( 0 );
		selX.val( 0 );
		selY.val( 0 );
		ias.cancelSelection();
		ias.update();
	}
};
})(jQuery);
