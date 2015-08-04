if (typeof(functionsDB) == 'undefined') { functionsDB = {}; }

functionsDB.editTemplateHandlers = function(dialog) {

	// attach TinyMCE
	// prevent jQuery UI dialog from blocking focusin
	// TAG_TODO перенести в основной движок для полей соответственного типа, но
	// TAG_TODO только после выяснения причины, почему второй раз не показывается без перезагрузки страницы
	$(document).on('focusin', function(e) {
		if ($(e.target).closest(".mce-window, .moxman-window").length) {
			e.stopImmediatePropagation();
		}
	});
	
	$('.apply_tinymce').tinymce({
		//inline : true,
		plugins: ["advlist autolink lists link image charmap searchreplace visualblocks code insertdatetime media table contextmenu paste minigallery"],
		toolbar: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link minigallery"
	});
					
	// get comment list into special container
	commentListTarget = $(dialog).find('div[data-meaning="comments-dynamic-box"]');
	if (commentListTarget.length == 1) {
		callAPI('comments_dialog', {
			rowId     : $(dialog).find('input[name="row_id"]').val(),
			container : commentListTarget
		}, {
			after: function() {
				functionsDB.modifyCommentBoxInTemplates($(dialog).find('.commentbox-outer-wrapper'));
			}
		});
	}
}

// modify comments box - remove some elements and restyle others
functionsDB.modifyCommentBoxInTemplates = function(target) {

	target.css('font-size', 'inherit');
	target.find('.commentbox-comment-list').css('height', 'auto');
	target.find('.commentbox p').css('display', 'inline-block');
	target.find('[data-form-container="commentbox-adder-form"]').removeClass('form-vertical').addClass('form-inline');
	target.find('[data-form-container="commentbox-adder-form"]').append( $('<input type="hidden" name="respond-type" value="integrated" />') );
	target.find('.commentbox-add-file').css('vertical-align', 'baseline');
	target.find('input[data-button-action="form-submit"]').css('vertical-align', 'baseline');
	target.find('.comments-dialog-buttons').find('[data-button-action="form-submit"]').val('Загрузить');
	
	target.find('div[data-edit-target="add-comment-files"]').append(target.find('input[data-button-action="form-submit"]').detach());
	target.find('input[data-button-action="form-submit"]').removeClass('btn-default').addClass('btn-info');
	target.find('input[data-button-action="form-cancel"]').remove();
	
	target.find('div[data-edit-target="add-comment-text"]').css('display', 'none');
	target.find('.commentbox-single-text').css('display', 'none');
	target.find('.commentbox-print-icon').css('display', 'none');
	target.find('.commentbox-single-header-user').css('display', 'none');
	target.find('.commentbox-single-header-stamp').css('display', 'none');
	target.find('.commentbox-single-attached-info img').css('display', 'none');
	target.find('.comments_no_comments').css('display', 'none');
	
	target.find('input[type="file"]').on('change', function() { target.find('[data-button-action="form-submit"]').click(); });
	

}