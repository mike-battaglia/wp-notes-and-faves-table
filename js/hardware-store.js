jQuery(document).ready(function($) {
    
        $("#search-btn").on("click", function () {
            var searchTerm = $("#search-box").val();

            $.ajax({
                url: ajax_object.ajax_url,
                method: "POST",
                data: {
                    action: "search_items",
                    search_term: searchTerm,
                },
                success: function (response) {
                    $("#search-results").html(response);
                },
            });
        });

    
    function handleFavorites(itemId, favorite) {
        $.ajax({
            url: ajax_object.ajax_url,
            method: 'POST',
            data: {
                action: 'handle_favorites',
                item_id: itemId,
                favorite: favorite
            },
            success: function(response) {
                if (favorite) {
                    alert(response + ' added to your favorites.');
                } else {
                    alert(response + ' removed from your favorites.');
                }
            }
        });
    }

    function handleNotes(itemId, note) {
        $.ajax({
            url: ajax_object.ajax_url,
            method: 'POST',
            data: {
                action: 'handle_notes',
                item_id: itemId,
                note: note
            },
            success: function(response) {
                alert('Note updated successfully for ' + response + '.');
            }
        });
    }

    $(document).on('click', '.favorite-checkbox', function() {
        var itemId = $(this).data('item-id');
        var favorite = $(this).is(':checked');
        handleFavorites(itemId, favorite);
    });

    $(document).on('click', '.save-note', function() {
        var itemId = $(this).data('item-id');
        var note = $('#note-' + itemId).val();
        handleNotes(itemId, note);
    });
});
