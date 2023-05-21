console.log("HW BOOK2 JS CALLED");

jQuery(function($) {
    // Handle favorite checkbox change
    $('.favorite-item').change(function() {
        $.post(ajax_object.ajax_url, {
            action: 'toggle_favorite',
            post_id: $(this).data('postId'),
            checked: $(this).prop('checked'),
        });
    });

    // Handle add note button click
    $('.add-note').click(function() {
        var modal = $('.note-modal[data-post-id="' + $(this).data('postId') + '"]');
        modal.show();
    });

    // Handle save note button click
    $('.save-note').click(function() {
        var modal = $(this).closest('.note-modal');
        $.post(ajax_object.ajax_url, {
            action: 'save_note',
            post_id: modal.data('postId'),
            note: modal.find('textarea').val(),
        });
        modal.hide();
    });
});
