<?php
if ( have_posts() ) : while ( have_posts() ) : the_post();
	?>

	<h1><?php the_title(); ?></h1>

	<h2>wp_get_attachment_url()</h2>
	<?php echo wp_get_attachment_url($post->ID); ?>
	<br>
	<h2>wp_get_attachment_image()</h2>
	<?php echo wp_get_attachment_image($post->ID, 'ratio-2-to-1-2000w'); ?>
	<br>
	<h2>wp_get_attachment_link()</h2>
	<?php echo wp_get_attachment_link($post->ID, 'medium'); ?>
	<br>
	<h2>wp_get_attachment_image_src('ratio-2-to-1-2000w')</h2>
	<?php print_r( wp_get_attachment_image_src($post->ID, 'ratio-2-to-1-2000w') ); ?>
	<br>
	<h2>wp_get_attachment_thumb_file()</h2>
	This function is kinda deprecated (see: https://core.trac.wordpress.org/ticket/17262)
	<?php echo wp_get_attachment_thumb_file($post->ID); ?>
	<br>
	<h2>wp_get_attachment_thumb_url()</h2>
	<?php echo wp_get_attachment_thumb_url($post->ID); ?>
	<br>
	<h2>get_attached_file()</h2>
	<?php echo get_attached_file($post->ID); ?>
	<br>
	<h2>wp_get_attachment_image_srcset()</h2>
	<?php echo wp_get_attachment_image_srcset($post->ID, 'full'); ?>
	<br>
	<h2>wp_get_attachment_image_sizes()</h2>
	<?php echo wp_get_attachment_image_sizes($post->ID, 'full'); ?>

	<h2>wp_get_attachment_image_srcset()</h2>
	<?php echo wp_get_attachment_image_srcset($post->ID, 'ratio-2-to-1-2000w'); ?>

	<h2>wp_get_attachment_metadata()</h2>
	<?php print_r(wp_get_attachment_metadata($post->ID)); ?>
<?php endwhile; ?>

<?php endif; ?>
