<?php
define('PUMA_VERSION','1.1.5');

function puma_setup() {
    register_nav_menu( 'angela', '主题菜单' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array(
        'search-form', 'comment-form', 'comment-list', 'gallery', 'caption'
    ) );
}

add_action( 'after_setup_theme', 'puma_setup' );

function puma_load_static_files(){
    $dir = get_template_directory_uri() . '/static/';
    wp_enqueue_style('puma', $dir . 'css/main.css' , array(), PUMA_VERSION , 'screen');
    wp_enqueue_script( 'puma', $dir . 'js/main.js' , array( 'jquery' ), PUMA_VERSION, true );
    wp_localize_script( 'puma', 'PUMA', array(
        'ajax_url'   => admin_url('admin-ajax.php')
    ) );
}

add_action( 'wp_enqueue_scripts', 'puma_load_static_files' );

function puma_wp_title( $title, $sep ) {
    global $paged, $page;

    if ( is_feed() )
        return $title;

    // Add the site name.
    $title .= get_bloginfo( 'name', 'display' );

    // Add the site description for the home/front page.
    $site_description = get_bloginfo( 'description', 'display' );
    if ( $site_description && ( is_home() || is_front_page() ) )
        $title = "$title $sep $site_description";

    // Add a page number if necessary.
    if ( ( $paged >= 2 || $page >= 2 ) && ! is_404() )
        $title = "$title $sep " . sprintf( 'Page %s', max( $paged, $page ) );

    return $title;
}
add_filter( 'wp_title', 'puma_wp_title', 10, 2 );

function puma_get_ssl_avatar($avatar) {
    $avatar = str_replace(array("www.gravatar.com", "0.gravatar.com", "1.gravatar.com", "2.gravatar.com"), "cn.gravatar.com", $avatar);
    return $avatar;
}
add_filter('get_avatar', 'puma_get_ssl_avatar');

function link_to_menu_editor( $args )
{
    if ( ! current_user_can( 'manage_options' ) )
    {
        return;
    }

    extract( $args );

    $link = $link_before
        . '<a href="' .admin_url( 'nav-menus.php' ) . '">' . $before . 'Add a menu' . $after . '</a>'
        . $link_after;

    if ( FALSE !== stripos( $items_wrap, '<ul' )
        or FALSE !== stripos( $items_wrap, '<ol' )
    )
    {
        $link = "<li>$link</li>";
    }

    $output = sprintf( $items_wrap, $menu_id, $menu_class, $link );
    if ( ! empty ( $container ) )
    {
        $output  = "<$container class='$container_class' id='$container_id'>$output</$container>";
    }

    if ( $echo )
    {
        echo $output;
    }

    return $output;
}

function puma_get_the_term_list( $id, $taxonomy ) {
    $terms = get_the_terms( $id, $taxonomy );
    $term_links = "";
    if ( is_wp_error( $terms ) )
        return $terms;

    if ( empty( $terms ) )
        return false;

    foreach ( $terms as $term ) {
        $link = get_term_link( $term, $taxonomy );
        if ( is_wp_error( $link ) )
            return $link;
        $term_links .= '<a href="' . esc_url( $link ) . '" class="post--keyword" data-title="' . $term->name . '" data-type="'. $taxonomy .'" data-term-id="' . $term->term_id . '">' . $term->name . '<sup>['. $term->count .']</sup></a>';
    }

    return $term_links;
}

function puma_contactmethods( $contactmethods ) {
    $contactmethods['twitter'] = 'Twitter';
    $contactmethods['sina-weibo'] = 'Weibo';
    $contactmethods['location'] = '位置';
    $contactmethods['instagram'] = 'Instagram';
    unset($contactmethods['aim']);
    unset($contactmethods['yim']);
    unset($contactmethods['jabber']);
    return $contactmethods;
}
add_filter('user_contactmethods','puma_contactmethods',10,1);


function header_social_link(){
    $socials = array('twitter','sina-weibo','instagram');
    $output = '';
    foreach ($socials as $key => $social) {
        if( get_user_meta(1,$social,true) != '' ) { $output .= '<span class="social-link"><a href="' . get_user_meta(1,$social,true) .'" target="_blank"><svg class="icon icon-' . $social . '" height="16" width="16" viewBox="0 0 16 16"><use xlink:href="' . get_template_directory_uri() . '/static/img/svgdefs.svg#icon-' . $social . '"></use></svg></a></span>';
        }
    }
    $output .= '<span class="social-link"><a href="' . get_bloginfo('rss2_url'). '" target="_blank"><svg class="icon icon-feed2" height="16" width="16" viewBox="0 0 16 16"><use xlink:href="' . get_template_directory_uri() . '/static/img/svgdefs.svg#icon-feed2"></use></svg></a></span>';
    return $output;
}

require get_template_directory() . '/inc/comment-action.php';

///////////////////
//
function zhb_update_banner(){
    // Get the bing picture as the head banner
    $position = get_template_directory() . '/static/img/banner.jpg';
    if (file_exists($position)) {
        $lastModifiedTime = filemtime($position);
        $currentTime = date("Y-m-d H:i:s", time());
        // echo $currentTime;
        // echo date("Y-m-d H:i:s", $lastModifiedTime);
        if (strtotime($currentTime) <= strtotime(date("Y-m-d", $lastModifiedTime))) {
            return;
        }
    }
    $bingtext=file_get_contents('http://www.bing.com/');
    //获取g_img={url:'与'之间的内容
    preg_match( "/g_img={url:'(.*)'/Uis ",$bingtext,$match);
    //去掉多余的
    $bingtarStr = str_replace("","",$match);
    //提取数组里第二个值
    $bingurlcontents = "http://www.bing.com".$bingtarStr[1];
    $url = preg_replace( '/(?:^[\'"]+|[\'"\/]+$)/', '', $bingurlcontents);//去除URL连接上面可能的引号
    $hander = curl_init();

    $fp = fopen($position,'wb');
    curl_setopt($hander,CURLOPT_URL,$url);
    curl_setopt($hander,CURLOPT_FILE,$fp);
    curl_setopt($hander,CURLOPT_HEADER,0);
    curl_setopt($hander,CURLOPT_FOLLOWLOCATION,1);
    //curl_setopt($hander,CURLOPT_RETURNTRANSFER,false);//以数据流的方式返回数据,当为false是直接显示出来
    curl_setopt($hander,CURLOPT_TIMEOUT,60);
    curl_exec($hander);
    curl_close($hander);
    fclose($fp);
}

function zhb_change_comment_form($input = array()){
    $input['fields']['email'] = ' ';
    $input['fields']['url'] = ' ';
    return $input;
}
add_filter('comment_form_defaults', 'zhb_change_comment_form');
