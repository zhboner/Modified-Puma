<?php get_header();?>
    <main class="main-content">
        <section class="blockGroup">
            <?php if (have_posts()):
            	query_posts($query_string .'&cat=-184');

                while (have_posts()): the_post();
                    get_template_part('template-parts/content', get_post_format());
                endwhile;
            endif;?>
        </section>
        <div class="u-textAlignCenter postsFooterNav">
            <div class="posts-nav">
                <?php echo paginate_links( array(
                    'prev_next'          => 0,
                    'before_page_number' => '',
                    'mid_size' => 2
                ) );?>
            </div>
            </div>
    </main>
<?php get_footer();?>