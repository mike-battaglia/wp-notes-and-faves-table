<?

function enqueue_hardware_store_scripts() {
    wp_enqueue_style('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
    wp_enqueue_script('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', array('jquery'), null, true);
    wp_enqueue_script('hardware-store', get_template_directory_uri() . '/js/hardware-store.js', array('jquery'), null, true);
    wp_localize_script('hardware-store', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'enqueue_hardware_store_scripts');

add_action('wp_ajax_handle_favorites', 'handle_favorites');
add_action('wp_ajax_nopriv_handle_favorites', 'handle_favorites');

add_action('wp_ajax_search_items', 'search_items');
add_action('wp_ajax_nopriv_search_items', 'search_items');

function handle_favorites() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_die('You are not allowed to perform this action.');
    }

    $item_id = intval($_POST['item_id']);
    $favorite = ($_POST['favorite'] === 'true') ? true : false;
    $item_title = get_the_title($item_id);

    if ($favorite) {
        add_user_meta($user_id, 'favorite_item', $item_id);
    } else {
        delete_user_meta($user_id, 'favorite_item', $item_id);
    }

    echo esc_html($item_title);
    wp_die();
}

add_action('wp_ajax_handle_notes', 'handle_notes');
add_action('wp_ajax_nopriv_handle_notes', 'handle_notes');

function handle_notes() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_die('You are not allowed to perform this action.');
    }

    $item_id = intval($_POST['item_id']);
    $note = sanitize_text_field($_POST['note']);
    $item_title = get_the_title($item_id);

    update_user_meta($user_id, 'note_item_' . $item_id, $note);

    echo esc_html($item_title);
    wp_die();
}

function hardware_book_shortcode($atts) {
    $a = shortcode_atts(array(
        'letter' => '',
        'category' => '',
    ), $atts);

    $args = array(
        'post_type' => 'item',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'item-category',
                'field' => 'slug',
                'terms' => $a['category'],
            ),
        ),
        'meta_query' => array(
            array(
                'key' => 'ny_state_taxable',
            ),
            array(
                'key' => 'local_non_nyc',
            ),
            array(
                'key' => 'local_nyc',
            ),
        ),
    	'orderby' => 'title',
        'order' => 'ASC',
    );

    $query = new WP_Query($args);

	$output = '';
    $output .= '
		<div>
            <input type="text" id="search-box" placeholder="Search" />
            <button type="button" id="search-btn" class="btn btn-primary">Search</button>
        </div>
        <div id="search-results">';

    if ($query->have_posts()) {
        $output .= '
            <table class="table">
                <thead>
                    <tr>
						<th class="th-image">Image</th>
                        <th class="th-category">Category</th>
                        <th class="th-title">Title</th>
                        <th class="th-excerpt">Excerpt</th>
                        <th class="th-content">Content</th>
                        <th class="th-state">NY State</th>
                        <th class="th-local">Local (non-NYC)</th>
                        <th class="th-city">Local (NYC)</th>
                        <th class="th-favorites">Favorites</th>
                        <th class="th-notes">Notes</th>
                    </tr>
                </thead>
                <tbody>';
        while ($query->have_posts()) {
            $query->the_post();
            $item_title = strtolower(get_the_title());

            if ($item_title[0] !== strtolower($a['letter'])) {
                continue;
            }

            $categories = get_the_terms(get_the_ID(), 'item-category');
            $category_names = array();
            foreach ($categories as $category) {
                $category_names[] = $category->name;
            }
            $category_list = implode(', ', $category_names);

            $favorite = get_user_meta(get_current_user_id(), 'favorite_item', false);
            $is_favorite = in_array(get_the_ID(), $favorite);

            $user_note = get_user_meta(get_current_user_id(), 'note_item_' . get_the_ID(), true);

  			$button_text = empty($user_note) ? "Add" : "Edit";
			
			$ny_state_taxable = get_field('ny_state_taxable');
			$ny_state_taxable_class = '';

			if ($ny_state_taxable === 'taxable') {
				$ny_state_taxable_class = 'is-taxable';
			} elseif ($ny_state_taxable === 'exempt') {
				$ny_state_taxable_class = 'is-exempt';
			}

			$ny_state_taxable = ucfirst($ny_state_taxable);

			$local_non_nyc = get_field('local_non_nyc');
			$local_non_nyc_class = '';

			if ($local_non_nyc === 'taxable') {
				$local_non_nyc_class = 'is-taxable';
			} elseif ($local_non_nyc === 'exempt') {
				$local_non_nyc_class = 'is-exempt';
			}

			$local_non_nyc = ucfirst($local_non_nyc);
			
			$local_nyc = get_field('local_nyc');
			$local_nyc_class = '';

			if ($local_nyc === 'taxable') {
				$local_nyc_class = 'is-taxable';
			} elseif ($local_nyc === 'exempt') {
				$local_nyc_class = 'is-exempt';
			}

			$local_nyc = ucfirst($local_nyc);
			

			
            $output .= '
                <tr>
					<td>' . get_the_post_thumbnail(get_the_ID(), 'thumbnail') . '</td>
                    <td>' . $category_list . '</td>
                    <td>' . get_the_title() . '</td>
                    <td>' . get_the_excerpt() . '</td>
                    <td>' . get_the_content() . '</td>
                    <td class="ny-state ' . $ny_state_taxable_class . '">' . $ny_state_taxable . '</td>
                    <td class="non-nyc ' . $local_non_nyc_class . '">' . $local_non_nyc . '</td>
                    <td class="nyc ' . $local_nyc_class . '">' . $local_nyc . '</td>
                    <td><input type="checkbox" data-item-id="' . get_the_ID() . '" class="favorite-checkbox" ' . ($is_favorite ? 'checked' : '') . '></td>
                    <td><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#item-modal-' . get_the_ID() . '">' . $button_text . '</button></td>
                </tr>
                <div class="modal fade" id="item-modal-' . get_the_ID() . '" tabindex="-1" role="dialog" aria-labelledby="item-modal-label" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="item-modal-label">Notes for ' . get_the_title() . '</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <textarea rows="4" class="form-control" id="note-' . get_the_ID() . '">' . esc_textarea($user_note) . '</textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary save-note" data-item-id="' . get_the_ID() . '">Save changes</button>
                            </div>
                        </div>
                    </div>
                </div>
            ';
        }
        $output .= '</tbody></table>';
    } else {
        $output .= '<p>No items found in the given category and letter.</p>';
    }

    $output .= '</div>';

    wp_reset_postdata();

    return $output;
}
add_shortcode('hardware-book', 'hardware_book_shortcode');

function search_items()
{
    $search_term = sanitize_text_field($_POST['search_term']);

    // Query all items with the search term
    $args = array(
        'post_type' => 'item',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        's' => $search_term,
	);
	
	$query = new WP_Query($args);

    $output = '';
    if ($query->have_posts()) {
        $output .= '
            <table class="table">
                <thead>
                    <tr>
						<th class="th-image">Image</th>
                        <th class="th-category">Category</th>
                        <th class="th-title">Title</th>
                        <th class="th-excerpt">Excerpt</th>
                        <th class="th-content">Content</th>
                        <th class="th-state">NY State</th>
                        <th class="th-local">Local (non-NYC)</th>
                        <th class="th-city">Local (NYC)</th>
                        <th class="th-favorites">Favorites</th>
                        <th class="th-notes">Notes</th>
                    </tr>
                </thead>
                <tbody>';
        while ($query->have_posts()) {
            $query->the_post();
 
            $categories = get_the_terms(get_the_ID(), 'item-category');
            $category_names = array();
            foreach ($categories as $category) {
                $category_names[] = $category->name;
            }
            $category_list = implode(', ', $category_names);

            $favorite = get_user_meta(get_current_user_id(), 'favorite_item', false);
            $is_favorite = in_array(get_the_ID(), $favorite);

            $user_note = get_user_meta(get_current_user_id(), 'note_item_' . get_the_ID(), true);

  			$button_text = empty($user_note) ? "Add" : "Edit";
			
			$ny_state_taxable = get_field('ny_state_taxable');
			$ny_state_taxable_class = '';

			if ($ny_state_taxable === 'taxable') {
				$ny_state_taxable_class = 'is-taxable';
			} elseif ($ny_state_taxable === 'exempt') {
				$ny_state_taxable_class = 'is-exempt';
			}

			$ny_state_taxable = ucfirst($ny_state_taxable);

			$local_non_nyc = get_field('local_non_nyc');
			$local_non_nyc_class = '';

			if ($local_non_nyc === 'taxable') {
				$local_non_nyc_class = 'is-taxable';
			} elseif ($local_non_nyc === 'exempt') {
				$local_non_nyc_class = 'is-exempt';
			}

			$local_non_nyc = ucfirst($local_non_nyc);
			
			$local_nyc = get_field('local_nyc');
			$local_nyc_class = '';

			if ($local_nyc === 'taxable') {
				$local_nyc_class = 'is-taxable';
			} elseif ($local_nyc === 'exempt') {
				$local_nyc_class = 'is-exempt';
			}

			$local_nyc = ucfirst($local_nyc);
			
            $output .= '
                <tr>
					<td>' . get_the_post_thumbnail(get_the_ID(), 'thumbnail') . '</td>
                    <td>' . $category_list . '</td>
                    <td>' . get_the_title() . '</td>
                    <td>' . get_the_excerpt() . '</td>
                    <td>' . get_the_content() . '</td>
                    <td class="ny-state ' . $ny_state_taxable_class . '">' . $ny_state_taxable . '</td>
                    <td class="non-nyc ' . $local_non_nyc_class . '">' . $local_non_nyc . '</td>
                    <td class="nyc ' . $local_nyc_class . '">' . $local_nyc . '</td>
                    <td><input type="checkbox" data-item-id="' . get_the_ID() . '" class="favorite-checkbox" ' . ($is_favorite ? 'checked' : '') . '></td>
                    <td><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#item-modal-' . get_the_ID() . '">' . $button_text . '</button></td>
                </tr>
                <div class="modal fade" id="item-modal-' . get_the_ID() . '" tabindex="-1" role="dialog" aria-labelledby="item-modal-label" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="item-modal-label">Notes for ' . get_the_title() . '</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <textarea rows="4" class="form-control" id="note-' . get_the_ID() . '">' . esc_textarea($user_note) . '</textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary save-note" data-item-id="' . get_the_ID() . '">Save changes</button>
                            </div>
                        </div>
                    </div>
                </div>
            ';
        }
        $output .= '</tbody></table>';
    } else {
        $output .= '<p>No items found for your search query.</p>';
    }

    wp_reset_postdata();

    echo $output;
    wp_die();
}	

function user_favorites_shortcode()
{
    $user_id = get_current_user_id();
    if (!$user_id) {
        return 'Please log in to view your favorite items.';
    }

    // Get the favorite items from user meta
    $favorite_items = get_user_meta($user_id, 'favorite_item', false);

    // If there are no favorite items
    if (empty($favorite_items)) {
        return '<p>No items have been marked as favorites.</p>';
    }

    // Query the favorite items
    $args = array(
        'post_type' => 'item',
        'post__in' => $favorite_items,
        'orderby' => 'title',
        'order' => 'ASC',
    );
	
	 $query = new WP_Query($args);

    $output = '';
    if ($query->have_posts()) {
        $output .= '
            <table class="table">
                <thead>
                    <tr>
						<th class="th-image">Image</th>
                        <th class="th-category">Category</th>
                        <th class="th-title">Title</th>
                        <th class="th-excerpt">Excerpt</th>
                        <th class="th-content">Content</th>
                        <th class="th-state">NY State</th>
                        <th class="th-local">Local (non-NYC)</th>
                        <th class="th-city">Local (NYC)</th>
                        <th class="th-favorites">Favorites</th>
                        <th class="th-notes">Notes</th>
                    </tr>
                </thead>
                <tbody>';
        while ($query->have_posts()) {
            $query->the_post();
			$categories = get_the_terms(get_the_ID(), 'item-category');
            $category_names = array();
            foreach ($categories as $category) {
                $category_names[] = $category->name;
            }
            $category_list = implode(', ', $category_names);

            $favorite = get_user_meta(get_current_user_id(), 'favorite_item', false);
            $is_favorite = in_array(get_the_ID(), $favorite);

            $user_note = get_user_meta(get_current_user_id(), 'note_item_' . get_the_ID(), true);

  			$button_text = empty($user_note) ? "Add" : "Edit";
			
			$ny_state_taxable = get_field('ny_state_taxable');
			$ny_state_taxable_class = '';

			if ($ny_state_taxable === 'taxable') {
				$ny_state_taxable_class = 'is-taxable';
			} elseif ($ny_state_taxable === 'exempt') {
				$ny_state_taxable_class = 'is-exempt';
			}

			$ny_state_taxable = ucfirst($ny_state_taxable);

			$local_non_nyc = get_field('local_non_nyc');
			$local_non_nyc_class = '';

			if ($local_non_nyc === 'taxable') {
				$local_non_nyc_class = 'is-taxable';
			} elseif ($local_non_nyc === 'exempt') {
				$local_non_nyc_class = 'is-exempt';
			}

			$local_non_nyc = ucfirst($local_non_nyc);
			
			$local_nyc = get_field('local_nyc');
			$local_nyc_class = '';

			if ($local_nyc === 'taxable') {
				$local_nyc_class = 'is-taxable';
			} elseif ($local_nyc === 'exempt') {
				$local_nyc_class = 'is-exempt';
			}

			$local_nyc = ucfirst($local_nyc);
			
            $output .= '
                <tr>
					<td>' . get_the_post_thumbnail(get_the_ID(), 'thumbnail') . '</td>
                    <td>' . $category_list . '</td>
                    <td>' . get_the_title() . '</td>
                    <td>' . get_the_excerpt() . '</td>
                    <td>' . get_the_content() . '</td>
                    <td class="ny-state ' . $ny_state_taxable_class . '">' . $ny_state_taxable . '</td>
                    <td class="non-nyc ' . $local_non_nyc_class . '">' . $local_non_nyc . '</td>
                    <td class="nyc ' . $local_nyc_class . '">' . $local_nyc . '</td>
                    <td><input type="checkbox" data-item-id="' . get_the_ID() . '" class="favorite-checkbox" ' . ($is_favorite ? 'checked' : '') . '></td>
                    <td><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#item-modal-' . get_the_ID() . '">' . $button_text . '</button></td>
                </tr>
                <div class="modal fade" id="item-modal-' . get_the_ID() . '" tabindex="-1" role="dialog" aria-labelledby="item-modal-label" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="item-modal-label">Notes for ' . get_the_title() . '</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <textarea rows="4" class="form-control" id="note-' . get_the_ID() . '">' . esc_textarea($user_note) . '</textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary save-note" data-item-id="' . get_the_ID() . '">Save changes</button>
                            </div>
                        </div>
                    </div>
                </div>
            ';
        }
        $output .= '</tbody></table>';
    } else {
        $output .= '<p>No items found in your favorites.</p>';
    }
	
	 wp_reset_postdata();

    return $output;
}

add_shortcode('user-favorites', 'user_favorites_shortcode');
