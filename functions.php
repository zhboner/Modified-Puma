<?php
define('PUMA_VERSION','2.0.5');
if ( version_compare( $GLOBALS['wp_version'], '4.4-alpha', '<' ) ) {
    require get_template_directory() . '/inc/back-compat.php';
}

function puma_get_images($contents)
{

    $matches = array();
    $r = "#(<img.*?>)#";
    if (preg_match_all($r, $contents, $matches)) {
        foreach ($matches[0] as $num => $title) {
            $content .= '<div class="puma-image"><div class="puma-image-overlay"></div>' . $title . '</div>';
        }
    }
    // var_dump($matches);
    return $content;
}

function recover_comment_fields($comment_fields){
    $comment = array_shift($comment_fields);
    $comment_fields =  array_merge($comment_fields ,array('comment' => $comment));
    return $comment_fields;
}
add_filter('comment_form_fields','recover_comment_fields');

function wp_term_like( $preifx = null){
    global $wp_query;
    if(!is_tax() && !is_category() && !is_tag()) return ;
    $tax = $wp_query->get_queried_object();
    $id = $tax->term_id;
    $num = get_term_meta($id,'_term_like',true) ? get_term_meta($id,'_term_like',true) : 0;
    $active = isset($_COOKIE['_term_like_'.$id]) ? ' is-active' : '';
    $output = '<button class="button termlike' . $active . '" data-action="termlike" data-action-id="' . $id . '">' . $prefix . '<span class="count">' . $num . '</span></button>';
    return $output;
}

add_action('wp_ajax_nopriv_termlike','wp_term_like_callback');
add_action('wp_ajax_termlike','wp_term_like_callback');
function wp_term_like_callback(){
    $id = $_POST['actionId'];
    $num = get_term_meta($id,'_term_like',true) ? get_term_meta($id,'_term_like',true) : 0;
    $domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false; // make cookies work with localhost
    setcookie('_term_like_'.$id,$id,$expire,'/',$domain,false);
    update_term_meta($id,'_term_like',$num + 1);
    echo json_encode(array(
        'status'=>200,
        'data'=> $num + 1,
    ));
    die;
}


function puma_setup() {

    register_nav_menu( 'angela', __( 'Primary Menu', 'Puma' ) );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array(
        'search-form', 'comment-form', 'comment-list', 'gallery', 'caption'
    ) );
    add_filter( 'pre_option_link_manager_enabled', '__return_true' );
    load_theme_textdomain( 'puma', get_template_directory() . '/languages' );
    add_theme_support( 'post-formats', array(
        'status',
        'image',
    ) );
}

add_action( 'after_setup_theme', 'puma_setup' );

function puma_load_static_files(){
    $dir = get_template_directory_uri() . '/static/';
    wp_enqueue_style('puma', $dir . 'css/main.css' , array(), PUMA_VERSION , 'screen');
    wp_enqueue_script( 'puma', $dir . 'js/main.min.js' , array( 'jquery' ), PUMA_VERSION, true );
    wp_localize_script( 'puma', 'PUMA', array(
        'ajax_url'   => admin_url('admin-ajax.php'),
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
        if( get_user_meta(1,$social,true) != '' ) { $output .= '<span class="social-link"><a href="' . get_user_meta(1,$social,true) .'" target="_blank"><span class="icon-' . $social . '"></span></a></span>';
        }
    }
    $output .= '<span class="social-link"><a href="' . get_bloginfo('rss2_url'). '" target="_blank"><span class="icon-rss"></span></a></span>';
    return $output;
}

require get_template_directory() . '/inc/comment-action.php';

function get_the_link_items($id = null){
    $bookmarks = get_bookmarks('orderby=date&category=' .$id );
    $output = '';
    if ( !empty($bookmarks) ) {
        $output .= '<ul class="link-items fontSmooth">';
        foreach ($bookmarks as $bookmark) {
            $output .=  '<li class="link-item"><a class="link-item-inner effect-apollo" href="' . $bookmark->link_url . '" title="' . $bookmark->link_description . '" target="_blank" >'. get_avatar($bookmark->link_notes,64) . '<span class="sitename">'. $bookmark->link_name .'<br>' . $bookmark->link_description . '</span></a></li>';
        }
        $output .= '</ul>';
    } else {
        $output = '暂无链接。';
    }
    return $output;
}

function get_link_items(){
    $linkcats = get_terms( 'link_category' );
    if ( !empty($linkcats) ) {
        foreach( $linkcats as $linkcat){
            $result .=  '<h3 class="link-title">'.$linkcat->name.'</h3>';
            if( $linkcat->description ) $result .= '<div class="link-description">' . $linkcat->description . '</div>';
            $result .=  get_the_link_items($linkcat->term_id);
        }
    } else {
        $result = get_the_link_items();
    }
    return $result;
}

function disable_emojis() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    add_filter( 'tiny_mce_plugins', 'disable_emojis_tinymce' );
}
add_action( 'init', 'disable_emojis' );
/**
 * Filter function used to remove the tinymce emoji plugin.
 *
 * @param    array  $plugins
 * @return   array             Difference betwen the two arrays
 */
function disable_emojis_tinymce( $plugins ) {
    return array_diff( $plugins, array( 'wpemoji' ) );
}


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
    
    wp_clear_scheduled_hook('do_this_everyday');
}

function zhb_change_comment_form($input = array()){
    $input['fields']['url'] = ' ';
    return $input;
}
add_filter('comment_form_defaults', 'zhb_change_comment_form');

function zhb_check_referrer_comment(){
// filter rubbish comments
    if (!isset($_SERVER['HTTP_REFERER']) || $_SERVER['HTTP_REFERER'] == '') {
        wp_die();
    }
}
add_action('check_comment_flood', 'zhb_check_referrer_comment');

wp_schedule_event(time(), 'daily', 'do_this_everyday');
add_action('do_this_everyday', 'zhb_update_banner');