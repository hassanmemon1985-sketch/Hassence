<?php get_header(); ?>
<main class="wrap page">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<article><h1><?php the_title(); ?></h1><div class="ct"><?php the_content(); ?></div></article>
<?php endwhile; else : ?><p>Nothing found.</p><?php endif; ?>
</main>
<?php get_footer(); ?>
