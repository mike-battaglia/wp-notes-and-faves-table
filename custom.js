jQuery(document).ready(function($) {
    
 'use strict';

    function search_filter_items() {
        var search_term = $('#search_box').val();
        var item_category = $('#item_category_dropdown').val();
        var first_letter = $('#first_letter_dropdown').val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'search_and_filter_items',
                search_term: search_term,
                item_category: item_category,
                first_letter: first_letter
            },
            success: function(response) {
                $('#items_table tbody').html(response);
            }
        });
    }

    $('#search_box').on('input', function() {
        search_filter_items();
    });

    $('#item_category_dropdown, #first_letter_dropdown').on('change', function() {
        search_filter_items();
    });

    function toggle_favorite(item_id, is_favorite) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'toggle_favorite_item',
                item_id: item_id,
                is_favorite: is_favorite
            },
            success: function(response) {
                var title = $('#item_title_' + item_id).text();
                var message = is_favorite ? ' added to ' : ' removed from ';
                alert(title + message + 'favorites');
            }
        });
    }

    $('body').on('change', '.favorite-checkbox', function() {
        var item_id = $(this).data('item-id');
        var is_favorite = $(this).prop('checked');
        toggle_favorite(item_id, is_favorite);
    });

    $('body').on('click', '.notes-btn', function() {
        var item_id = $(this).data('item-id');
        var note = $('#item_note_' + item_id).val();
        $('#note_item_id').val(item_id);
        $('#note_editor').val(note);
        $('#note_modal').modal('show');
    });

    $('#save_note').on('click', function() {
        var item_id = $('#note_item_id').val();
        var note = $('#note_editor').val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'update_item_note',
                item_id: item_id,
                note: note
            },
            success: function() {
                var updated_note = note.trim() != '' ? 'Edit' : 'Add';
                $('#item_note_' + item_id).val(note);
                $('#note_btn_' + item_id).text(updated_note);
                $('#note_modal').modal('hide');
            }
        });
    });
})(jQuery);
    
});

search_filter_items();
