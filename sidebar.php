<?php
/**
 * The sidebar containing the main widget area.
 *
 * @package Blogus
 */

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
	return;
}

if(is_front_page()){ ?>
	<div class="bs-widget widget_block filter">
		<label class="wp-block-search__label">Filter</label>

		<?php echo do_shortcode( '[ajax_filter]' ); ?>
	</div>
<?php } ?>

	<?php $blogus_sidebar_stickey = get_theme_mod('blogus_sidebar_stickey',true); ?>
	<div id="sidebar-right" class="bs-sidebar <?php if($blogus_sidebar_stickey == true) { ?> bs-sticky <?php } ?>">
		
		<?php dynamic_sidebar( 'sidebar-1' ); ?>
	</div>