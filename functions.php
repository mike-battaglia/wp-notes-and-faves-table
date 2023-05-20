<? php
  
function enqueue_hardware_store_scripts() {
    wp_enqueue_style('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
    wp_enqueue_script('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', array('jquery'), null, true);
    wp_enqueue_script('hardware-store', get_template_directory_uri() . '/js/hardware-store.js', array('jquery'), null, true);
    wp_localize_script('hardware-store', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'enqueue_hardware_store_scripts');

add_action('wp_ajax_handle_favorites', 'handle_favorites');
add_action('wp_ajax_nopriv_handle_favorites', 'handle_favorites');

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
        'letter' => 'A',
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
                'key' => 'attribute1acf',
            ),
            array(
                'key' => 'attribute2acf',
            ),
            array(
                'key' => 'attribute3acf',
            ),
        ),
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
                        <th>Category</th>
                        <th>Title</th>
                        <th>Excerpt</th>
                        <th>Content</th>
                        <th>Attribute 1</th>
                        <th>Attribute 2</th>
                        <th>Attribute 3</th>
                        <th>Favorites</th>
                        <th>Notes</th>
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

            $output .= '
                <tr>
                    <td>' . $category_list . '</td>
                    <td>' . get_the_title() . '</td>
                    <td>' . get_the_excerpt() . '</td>
                    <td>' . get_the_content() . '</td>
                    <td>' . get_field('attribute1acf') . '</td>
                    <td>' . get_field('attribute2acf') . '</td>
                    <td>' . get_field('attribute3acf') . '</td>
                    <td><input type="checkbox" data-item-id="' . get_the_ID() . '" class="favorite-checkbox" ' . ($is_favorite ? 'checked' : '') . '></td>
                    <td><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#item-modal-' . get_the_ID() . '">Add/Edit Notes</button></td>
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

    wp_reset_postdata();

    return $output;
}
add_shortcode('hardware-book', 'hardware_book_shortcode');
