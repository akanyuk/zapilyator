var CKEDITOR_BASEPATH = '/assets/ckeditor/';

$(document).ready(function(){
	/* CKEditor function
	 * Available options:
	 * height		textarea height 
	 * save_button	Add 'Save' button in toolbar. Enabled by default 
	 * toolbar		Toolbar type: 'Full' for extended toolbar 
	 * media		`owner_class` for media uploading
	 * media_owner	`owner_id` for media uploading
	 * css			path to stylesheet
	 */
	$.fn.CKEDIT = function(options) {
		var oTextarea = $(this);
		if (!options) options = {};

		// Select toolbar
    	if (options.toolbar == 'Full') {
	    	var ckToolbar = [
				{ name: 'document', items: [ 'Save' ]},
				{ name: 'basicstyles', items: [ 'Format', 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', 'TextColor' ]},
				{ name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'HorizontalRule', 'Blockquote', 'CreateDiv' ]},
				{ name: 'justify', items: [ 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ]},
				{ name: 'insert', items: [ 'Link', 'Unlink', 'Anchor', '-', 'Image', 'Table', 'Iframe' ]},
				{ name: 'document2', items: [ 'Source', '-', 'Maximize' ]}
			];
	    }
	    else {
	    	var ckToolbar = [
 		   		{ name: 'document', items: [ 'Save' ]},
 				{ name: 'basicstyles', items: [ 'Format', 'Bold', 'Italic', '-', 'NumberedList', 'BulletedList' ]},
 				{ name: 'justify', items: [ 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ]},
 				{ name: 'insert', items: [ 'Link', 'Unlink', '-', 'Image', 'Table' ]},
 				{ name: 'document2', items: [ 'Source', 'Maximize' ]}
 			];
    	}
		
		var cfg = {
			language: $('html').attr('lang') == 'ru' ? 'ru' : 'en',
			toolbar: ckToolbar,
			height: options.height ? options.height : 400,
			removePlugins: 'find,flash,font,forms,newpage,removeformat,smiley,specialchar,stylescombo,templates',
			extraPlugins: 'image2',
			format_tags: 'p;h1;h2;h3;pre',
			on: {
				configLoaded: function() {
					// Allow HTML
					this.config.protectedSource.push( /<script[\s\S]*?script>/g ); /* script tags */
					this.config.allowedContent = true; /* all tags */
		    	}
		    }
		};
		
		if (options.css) {
			cfg.contentsCss = options.css;
		}
		
		if (options.media) {
			cfg.filebrowserImageWindowWidth = '1000';
			cfg.filebrowserImageWindowHeight = '500';					

			var owner_str = options.media_owner ? '&owner_class=' + options.media + '&owner_id=' + options.media_owner : '&owner_class=' + options.media;
			cfg.filebrowserUploadUrl = '/media.php?action=CKE_upload' + owner_str;
			cfg.filebrowserBrowseUrl = '/media.php?action=CKE_list' + owner_str;
			cfg.filebrowserImageBrowseUrl = '/media.php?action=CKE_list&type=images' + owner_str;
		}
		
		oTextarea.ckeditor(cfg);
	}
});