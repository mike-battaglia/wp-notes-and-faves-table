console.log("js/hardware-store.js called");

jQuery(document).ready(function($) {
    
        function populateCategoryDropdown() {
          $.ajax({
            url: ajax_object.ajax_url,
            method: "POST",
            data: {
              action: "get_item_categories",
            },
            success: function (response) {
              $("#category-dropdown").html(response);
              $("#category-dropdown").prepend("<option value='all'>All</option>");
            },
          });
        }
    
        function populateLetterDropdown() {
          var letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
          var options = "";

          for (var i = 0; i < letters.length; i++) {
            options += "<option value='" + letters[i] + "'>" + letters[i] + "</option>";
          }

          $("#letter-dropdown").html(options);
          $("#letter-dropdown").prepend("<option value='all'>All</option>");
        }

        populateCategoryDropdown();
        populateLetterDropdown();
    
        $("#category-dropdown, #letter-dropdown").on("change", function () {
            var selectedCategory = $("#category-dropdown").val();
            var selectedLetter = $("#letter-dropdown").val();

            $.ajax({
                url: ajax_object.ajax_url,
                method: "POST",
                data: {
                    action: "update_hardware_book_table",
                    letter: selectedLetter,
                    category: selectedCategory,
                },
                success: function (response) {
                    $("#search-results").html(response);
                },
            });
        });

        populateCategoryDropdown();
        populateLetterDropdown();
    
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
