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

	setNumSelection : function( postid, el ) {
		var sel, elWidth = $('#subjectarea-sel-width-' + postid), elHeight = $('#subjectarea-sel-height-' + postid),
			width = this.intval( elWidth.val() ), height = this.intval( elHeight.val() ),
			elX = $('#subjectarea-sel-x-' + postid), elY = $('#subjectarea-sel-y-' + postid),
			x = this.intval( elX.val() ), y = this.intval( elY.val() ),
			img = $('#image-subjectarea-preview-' + postid), imgh = img.height(), imgw = img.width(),
			sizer = this.hold.sizer, x1, y1, x2, y2, ias = this.iasapi;

		if ( false === this.validateNumeric( el ) ) {
			return;
		}

		if ( width < 1 ) {
			elWidth.val('');
			return false;
		}

		if ( height < 1 ) {
			elHeight.val('');
			return false;
		}

		if ( x < 0 ) {
			elX.val( 0 );
			return false;
		}

		if ( y < 0 ) {
			elY.val( 0 );
			return false;
		}

		if ( width && height && x && y && ( sel = ias.getSelection() ) ) {
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
			ias.update();
			this.setSubjectAreaSelection(postid, ias.getSelection());
		}
	},

	imgLoaded : function(postid) {
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
		this.setSubjectAreaSelection(postid, 0);
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

		console.log($(image));
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

			onSelectEnd: function(img, c) {
				rokkaSubjectAreaEdit.setSubjectAreaSelection(postid, c);
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

	setSubjectAreaSelection : function(postid, c) {
		var sel;

		c = c || 0;

		if ( !c || ( c.width < 3 && c.height < 3 ) ) {
			$('#subjectarea-sel-width-' + postid).val('');
			$('#subjectarea-sel-height-' + postid).val('');
			$('#subjectarea-sel-x-' + postid).val(0);
			$('#subjectarea-sel-y-' + postid).val(0);
			$('#subjectarea-selection-' + postid).val('');
			return false;
		}

		sel = { 'x': c.x1, 'y': c.y1, 'w': c.width, 'h': c.height };
		$('#subjectarea-selection-' + postid).val( JSON.stringify(sel) );
	},

	validateNumeric: function( el ) {
		if ( ! this.intval( $( el ).val() ) ) {
			$( el ).val( '' );
			return false;
		}
	}
};
})(jQuery);
