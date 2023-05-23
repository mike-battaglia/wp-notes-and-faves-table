function enqueue_custom_styles_scripts() {
    wp_enqueue_style('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
    wp_enqueue_script('jquery');
    wp_enqueue_script('bootstrap-js', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', array('jquery'));
    wp_enqueue_script('custom-script', get_stylesheet_directory_uri() . '/js/custom-scripts.js', array('jquery'), false, true);
}
add_action('wp_enqueue_scripts', 'enqueue_custom_styles_scripts');

// AJAX handler for searching and filtering items
add_action('wp_ajax_search_and_filter_items', 'search_and_filter_items');
add_action('wp_ajax_nopriv_search_and_filter_items', 'search_and_filter_items');

function search_and_filter_items() {
    $search_term = $_POST['search_term'];
    $item_category = $_POST['item_category'];
    $first_letter = $_POST['first_letter'];

    $args = array(
        'post_type'      => 'item',
        'orderby'        => 'title',
        'order'          => 'ASC',
        'posts_per_page' => -1,
        's'              => $search_term,
        'tax_query'      => array(),
        'meta_query'     => array(),
    );

    if ($item_category != 'all') {
        $args['tax_query'][] = array(
        'taxonomy' => 'item-category',
        'field'    => 'term_id',
        'terms'    => $item_category,
    );
}

if ($first_letter != 'all') {
    $args['meta_query'][] = array(
        'key'     => 'title_first_letter',
        'value'   => $first_letter,
        'compare' => '=',
    );
}

$query = new WP_Query($args);

if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post();
        $item_id = get_the_ID();
        get_template_part('template-parts/content', 'item-row');
    }
}

wp_reset_postdata();
        
    die;
}

// AJAX handler for mark item as favorite
add_action('wp_ajax_toggle_favorite_item', 'toggle_favorite_item');
add_action('wp_ajax_nopriv_toggle_favorite_item', 'toggle_favorite_item');

function toggle_favorite_item() {
    $item_id = $_POST['item_id'];
    $is_favorite = $_POST['is_favorite'];
    $user_id = get_current_user_id();

    if ($is_favorite == 'true') {
        add_user_meta($user_id, 'favorite_items', $item_id, false);
    } else {
        delete_user_meta($user_id, 'favorite_items', $item_id);
    }

    die;
}

// AJAX handler for adding or updating notes
add_action('wp_ajax_update_item_note', 'update_item_note');
add_action('wp_ajax_nopriv_update_item_note', 'update_item_note');

function update_item_note() {
    $item_id = $_POST['item_id'];
    $note = $_POST['note'];
    $user_id = get_current_user_id();

    update_user_meta($user_id, 'item_note_' . $item_id, $note);

    die;
}

function items_table_shortcode($atts) {
ob_start(); ?><!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>

<input type="text" id="search_box" placeholder="Search items...">
<select id="item_category_dropdown"><?php
$item_categories = get_terms(array(
    'taxonomy'   => 'item-category',
    'hide_empty' => false
));
?>  <option value="all">All Categories</option><?php
foreach ($item_categories as $item_category) {
?>  <option value="<?php echo $item_category->term_id; ?>"><?php echo $item_category->name; ?></option><?php
}
?></select>
<select id="first_letter_dropdown">
    <option value="all">All</option><?php
foreach (range('A', 'Z') as $letter) {
?>  <option value="<?php echo $letter; ?>"><?php echo $letter; ?></option><?php
}
?></select>

<table id="items_table">
    <thead>
        <tr>
            <th class="featured-image">Featured Image</th>
            <th class="category">Category</th>
            <th class="title">Title</th>
            <th class="excerpt">Excerpt</th>
            <th class="content">Content</th>
            <th class="ny-state-taxable">NY State Taxable</th>
            <th class="favorites">Favorites</th>
            <th class="notes">Notes</th>
        </tr>
    </thead>
    <tbody>

    </tbody>
</table>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        jQuery('#search_box').trigger('input');
    });
</script>

<div class="modal" tabindex="-1" role="dialog" id="note_modal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add/Edit Note</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="note_item_id">
        <textarea id="note_editor"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="save_note">Save Note</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

</body>
</html><?php
return ob_get_clean();
}
add_shortcode('items_table', 'items_table_shortcode');

function favorites_table_shortcode($atts) {
    ob_start();
    if (is_user_logged_in()) {
        echo do_shortcode('[items_table favorites_only="1"]');
    } else {
        echo 'You must be logged in to view your favorite items.';
    }
    return ob_get_clean();
}
add_shortcode('favorites_table', 'favorites_table_shortcode');
