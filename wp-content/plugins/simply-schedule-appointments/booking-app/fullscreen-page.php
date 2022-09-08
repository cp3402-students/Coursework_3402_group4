<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php the_title(); ?></title>
    <?php wp_head(); ?>
  </head>
  <body <?php body_class(); ?>>
    <?php while ( have_posts() ) : the_post(); ?>
      <?php the_content(); ?>
    <?php endwhile; // End of the loop. ?>
  </body>
  <?php wp_footer(); ?>
</html>
