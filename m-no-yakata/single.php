<?php get_header(); ?>

<main class="mno-container">
  <?php
  if (have_posts()) :
    while (have_posts()) : the_post(); ?>

      <article <?php post_class('mno-single'); ?>>
        <h1 class="mno-single__title"><?php the_title(); ?></h1>

        <?php if (has_post_thumbnail()) : ?>
          <div class="mno-single__thumb">
            <?php the_post_thumbnail('large'); ?>
          </div>
        <?php endif; ?>

        <div class="mno-single__content">
          <?php the_content(); ?>
        </div>

      </article>

    <?php endwhile;
  else : ?>
    <p>記事が見つかりませんでした。</p>
  <?php endif; ?>
</main>

<?php get_footer(); ?>