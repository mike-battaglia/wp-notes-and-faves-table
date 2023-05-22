jQuery(document).ready(function($) {
	
    /* Function to fetch items based on filters and pagination */
    function fetchItems() {
		var searchTerm = $('.search-input').val();
        var categoryId = $('.item-category-filter').val();
		var letterFilter = $('#letter-dropdown').val();
        var currentPage = $('.pagination .active .page-link').data('page-number') || 1;
        var userId = $('.catalog-user-id').val();
        var favoritesOnly = $('.favorites-only').val() === '1';
	
        $.ajax({
            url: ajax_object.ajaxurl,
            method: 'POST',
            data: {
                action: 'fetch_items',
				search_term: searchTerm,
                category_filter: categoryId,
                letter_filter: letterFilter,
                current_page: currentPage,
                user_id: userId,
                favorites_only: favoritesOnly
            },
            beforeSend: function() {
                $('.item-results').html('<tr><td colspan="10">Loading...</td></tr>');
            },
            success: function(response) {
                if (response) {
        // Update table rows
        $('.item-results').html(response);

        // Update pagination
        var totalPages = Math.ceil($('.item-results tr').length / 10); // You can set your desired number of items per page
        var paginationHtml = '';

        if (totalPages > 1) {
            for (var i = 1; i <= totalPages; i++) {
                var activeClass = (i === currentPage) ? 'active' : '';
                paginationHtml += '<li class="page-item ' + activeClass + '"><a class="page-link" href="#" data-page-number="' + i + '">' + i + '</a></li>'
            }
        }

        $('.pagination').html(paginationHtml);
		} else {
			$('.item-results').html('<tr><td colspan="10">No items found.</td></tr>');
		}
	},
            error: function() {
                $('.item-results').html('<tr><td colspan="10">Error fetching items. Please try again.</td></tr>');
            }
        });
    }

    /* Fetch items initially */
    fetchItems();

    /* Apply filters */
    $('.apply-filters').on('click', function() {
        fetchItems();
    });

    /* Pagination */
    $(document).on('click', '.pagination .page-link', function(e) {
        e.preventDefault();
        if (!$(this).parent().hasClass('active')) {
            $('.pagination .page-item').removeClass('active');
            $(this).parent().addClass('active');
            fetchItems();
        }
    });

    /* Favorite checkbox handling */
    $(document).on('change', '.favorite-checkbox', function() {
        var itemId = $(this).data('item-id');
        var actionType = $(this).is(':checked') ? 'add' : 'remove';

        if (itemId) {
            $.ajax({
                url: ajax_object.ajaxurl,
                method: 'POST',
                data: {
                    action: 'update_favorites',
                    item_id: itemId,
                    action_type: actionType
                },
                success: function(response) {
					if (response === 'success') {
						// Update the favorite status
						var message = actionType === 'add' ? ' added to' : ' removed from';
						message += ' your favorites.';
						var favoriteMessage = itemTitle + message;

						// Show the modal with the message
						$('#favoriteModal .modal-body').html('<p>' + favoriteMessage + '</p>');
						$('#favoriteModal').modal('show');
					}
				},
				error: function() {
					alert('An error occurred. Please try again.');
				},
            });
        }
    });

    /* Notes modal handling */
    $(document).on('click', '.notes-button', function() {
        var itemId = $(this).data('item-id');
        var itemTitle = $(this).data('item-title');
        var itemNotes = $(this).data('notes');

        // Set notes modal data
        $('#notesModalLabel').text('Notes for ' + itemTitle);
        $('#notes-item-id').val(itemId);
        $('#item-notes-textarea').val(itemNotes);

        // Show the modal
        $('#notesModal').modal('show');
    });

    /* Save notes */
    $('.save-notes').on('click', function() {
        var itemId = $('#notes-item-id').val();
        var newNotes = $('#item-notes-textarea').val();

        if (itemId) {
            $.ajax({
                url: ajax_object.ajaxurl,
                method: 'POST',
                data: {
                    action: 'update_notes',
                    item_id: itemId,
                    notes: newNotes
                },
			success: function(response) {
				if (response === 'success') {
					// Update the notes data on the notes button
					var notesBtn = $('.notes-button[data-item-id="' + itemId + '"]');
					notesBtn.data('notes', newNotes);

					// Close the modal
					$('#notesModal').modal('hide');
				}
			},
			error: function() {
				alert('An error occurred. Please try again.');
			},
            });
        }
    });

	$('.search-input').on('input', function() {
    fetchItems();
	});
	
});

