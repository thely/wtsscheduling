<?php
/*
 * Template Name: Student Signup
 * Description: Currently, the page for student signup stuff. 
 */
get_header();
?>
<?php while (have_posts()) :
		the_post();?>
	<?php get_template_part('content', 'page'); ?>
<?php endwhile;

get_footer(); ?>
