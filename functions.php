<?php

function child_theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css'); 
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style'), wp_get_theme()->get('Version') );
	
	wp_enqueue_script('books-script', get_stylesheet_directory_uri() . '/script.js', array('jquery', ) );
}
add_action('wp_enqueue_scripts', 'child_theme_enqueue_styles');


/*
 * Register new Books post type
 */

add_action( 'init', 'register_books_post_type' );

function register_books_post_type() {

	register_taxonomy( 'genres', [ 'books' ], [
		'label'             => 'Genres',
		'labels'            => [
			'name'              => 'Genres',
			'singular_name'     => 'Genre',
			'search_items'      => 'Search Genre',
			'all_items'         => 'All Genres',
			'parent_item'       => 'Parenthesis section of the Genres',
			'parent_item_colon' => 'Parent section of the Genres:',
			'edit_item'         => 'Edit Genre',
			'update_item'       => 'Update Genre',
			'add_new_item'      => 'Add Genre',
			'new_item_name'     => 'New Genre',
			'menu_name'         => 'Genres',
		],
		'description'       => 'Genres for Books',
		'public'            => true,
		'show_in_nav_menus' => false,   
		'show_ui'           => true,    
		'show_tagcloud'     => false,   
		'hierarchical'      => true,
		'rewrite'           => [ 'slug' => 'genres', 'hierarchical' => false, 'with_front' => false, 'feed' => false ],
		'show_admin_column' => true,
	] );

	register_post_type( 'books', [
		'label'               => 'Books',
		'labels'              => [
			'name'          => 'Books',
			'singular_name' => 'Book',
			'menu_name'     => 'Books',
			'all_items'     => 'All Books',
			'add_new'       => 'Add Book',
			'add_new_item'  => 'Add a new book',
			'edit'          => 'Edit Book',
			'edit_item'     => 'Edit Book',
			'new_item'      => 'New Book',
		],
		'description'         => '',
		'public'              => true,
		'publicly_queryable'  => true,
		'show_ui'             => true,
		'show_in_rest'        => true,
		'rest_base'           => '',
		'show_in_menu'        => true,
		'exclude_from_search' => false,
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
		'hierarchical'        => false,
		'rewrite'             => [
			'slug'       => 'books',
			'with_front' => false,
			'pages'      => false,
			'feeds'      => false,
			'feed'       => false,
		],
		'has_archive'         => 'books',
		'query_var'           => true,
		'supports'            => [ 'title', 'editor' ],
		'taxonomies'          => [ 'genres' ],
	] );
}

/*
 * The function returns information about the book by ISBN. 
 * If it is in the cache, it returns from the cache, if not, we get it from the API, cache it and return it.
 */
function get_book_info( $isbn ) {
	
	if ( get_transient( $isbn ) ) {
		
		$bookInfo = get_transient( $isbn );
		
	} else {
		$url = 'https://www.googleapis.com/books/v1/volumes?q=isbn:'.$isbn;
		
		$jsonString = file_get_contents($url);      
		$getcontent = json_decode( $jsonString );

		$bookInfo = $getcontent->items[0]->volumeInfo;

		set_transient( $isbn, $bookInfo, DAY_IN_SECONDS );
	}
	
	return $bookInfo;
}


/*
 * Filter function
 * 
 */
add_action('wp_ajax_myfilter', 'books_filter_function');
add_action('wp_ajax_nopriv_myfilter', 'books_filter_function');

function books_filter_function(){
	$args = array(
		'orderby' => 'date', // sort posts by date
		'order'	=> $_POST['date'], // ASC or DESC
		'post_type'    => 'books',
	);
 
     
	// for taxonomies / genres
	if( isset( $_POST['genresfilter'] ) && !empty( $_POST['genresfilter'] ) ) {

		$args['tax_query'][] = array(
				'taxonomy' => 'genres',
				'field' => 'id',
				'terms' => $_POST['genresfilter']
			); }
	
	$query = new WP_Query( $args );

    ob_start();
	if( $query->have_posts() ) :
		while( $query->have_posts() ): $query->the_post();
	
		$book_id = 	$query->post->ID;
	
		$isbn = get_post_meta( $book_id, 'isbn', true );
			
		$title = $query->post->post_title;
		$genres = get_the_terms( $book_id, 'genres' );
		$link = get_the_permalink($book_id);
		
		$getcontent = get_book_info( $isbn );
		
		$imgUrl = $getcontent->imageLinks->thumbnail;
		$authors = $getcontent->authors;
		$publisher = $getcontent->publisher;
		$date = $getcontent->publishedDate;
	?>
		
		<div class="row" style="margin: 30px">
		<div class="col-lg-3"><a href="<?php echo $link; ?>"><img src="<?php echo $imgUrl ?>"  width="128" height="202"></a></div>
		<div class="col-lg-9"><b>Title:</b> <?php echo $title ?><br>
		
		<?php if ( count( $authors ) <= 1 ) { ?>
			<b>Author: </b>
		<?php } else { ?>
			<b>Authors: </b>
		<?php } ?>
		
		<?php foreach ($authors as $author) { ?>
			<?php echo $author; ?><br>
		<?php } ?>
		
		<b>Publisher:</b> <?php echo $publisher ?><br>
		<b>Year:</b><?php echo mb_strimwidth( $date, 0, 4 ) ?><br>
		
		<?php if ( count( $genres ) <= 1 ) { ?>
			<b>Genre: </b>
		<?php } else { ?>
			<b>Genres: </b>
		<?php } ?>
		
		<?php foreach ($genres as $genre) { ?>
			<?php echo $genre->name ?><br>
		<?php } ?>
		
		<br><a href="<?php echo $link ?>"><b>Read more >></b></a>
		</div></div>
	

		<?php
		endwhile;
		wp_reset_postdata();
	else :
		echo 'No posts found';
	endif;

    $posts = ob_get_clean();

    $return = ['posts' => $posts ];
    wp_send_json($return);
	
}


/*
 * Register [ajax_filter] shortcode 
 */

add_shortcode( 'ajax_filter', 'ajax_filter_shortcode' );
function ajax_filter_shortcode() {

    ?>
    <div id="filter">
        
    <?php
        if( $terms = get_terms( array(
            'taxonomy' => 'genres', 
            'orderby' => 'name'
        ) ) ) : ?>
            
            Genre: <select name="genresfilter"><option value="0">View All</option>
            <?php foreach ( $terms as $term ) : ?>
                <option value="<?php echo $term->term_id ?>"><?php echo $term->name ?></option>'; 
            <?php endforeach; ?>
            </select>
        <?php endif; ?>

		<button id="filerButton">Apply filter</button>
    </div>

    <?php
}




