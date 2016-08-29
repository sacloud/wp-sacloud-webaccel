<?php

/**
 * Plugin Name: SakuraCloud Web Accelerator Plugin
 * Plugin URI: https://github.com/yamamoto-febc/wp-sacloud-webaccel
 * Description: WordPressとさくらのクラウド ウェブアクセラレータを連携させるためのプラグイン
 * Author: Kazumichi Yamamoto
 * Author URI: https://github.com/yamamoto-febc
 * Text Domain: wp-sacloud-webaccel
 * Version: 0.0.1
 * License: GPLv3
*/

// -------------------- Register boot functions ---------------------
register_deactivation_hook( __FILE__, 'sacloud_webaccel_deactivate' );
add_action('init' , 'sacloud_webaccel_start');
// ------------------------------------------------------------------
function sacloud_webaccel_start(){
    // Text Domain
    load_plugin_textdomain('wp-sacloud-webaccel', false, basename(dirname(__FILE__)). DIRECTORY_SEPARATOR . 'lang');

    // for admin WebUI
    add_action('admin_menu', 'sacloud_webaccel_add_pages');
    add_action('admin_init', 'sacloud_webaccel_options' );
    add_action('wp_ajax_sacloud_webaccel_connect_test', 'sacloud_webaccel_connect_test');

    if (sacloud_webaccel_auth()){
        add_action('admin_bar_menu', 'sacloud_webaccel_toolbar_purge_item', 100 );

        // for media(attachment)
        if (sacloud_webaccel_get_option("use-subdomain") == 1) {
            add_filter('wp_get_attachment_url', 'sacloud_webaccel_subdomain_url');
        }
        add_action('add_attachment', 'sacloud_webaccel_delete_cache_by_id');
        add_action('edit_attachment', 'sacloud_webaccel_delete_cache_by_id');
        add_action('delete_attachment', 'sacloud_webaccel_delete_cache_by_id');
        add_filter('wp_delete_file', 'sacloud_webaccel_delete_cache_by_path');
        add_filter('wp_update_attachment_metadata', 'sacloud_webaccel_thumb_upload');

        // post + page
        add_action('post_updated', 'sacloud_webaccel_purge_post_on_edit', 10, 3);
        add_action('set_object_terms', 'sacloud_webaccel_set_object_terms', 10, 6); // for term when update post.

        // post comment
        add_action('wp_insert_comment', 'sacloud_webaccel_purge_post_on_comment', 200, 2);
        add_action('transition_comment_status', 'sacloud_webaccel_purge_post_on_comment_change', 200, 3);

        // category / tag / custom taxonomy
        add_action('edit_terms', 'sacloud_webaccel_purge_on_term_taxonomy_edited', 20, 2);
        add_action('pre_delete_term', 'sacloud_webaccel_purge_on_term_taxonomy_edited', 20, 2);


        // purge link in menubar
        add_action('admin_init', 'sacloud_webaccel_purge_all');

        // expose action to allow other plugins to purge the cache
        add_action('sacloud_webaccel_purge_all', 'sacloud_webaccel_true_purge_all');

        // Load WP-CLI command
        if (defined('WP_CLI') && WP_CLI) {
            require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wp-cli.php';
            \WP_CLI::add_command('sacloud-webaccel', 'Sacloud_WebAccel_WP_CLI_Command');
        }
        // ------------ for Public --------

        // add HTTP header
        add_action('wp' , 'sacloud_webaccel_send_cache_header');
    }
    // write .htaccess for Mediafile
    add_action('add_option_sacloud-webaccel-options', 'sacloud_webaccel_options_handle_add' , 10 , 2);
    add_action('update_option_sacloud-webaccel-options', 'sacloud_webaccel_options_handle_update' , 10 , 2);
    add_action('delete_option_sacloud-webaccel-options', 'sacloud_webaccel_options_handle_delete');



}

function sacloud_webaccel_add_pages() {
    $r = add_options_page(__("WebAccelerator" , 'wp-sacloud-webaccel'), __("WebAccelerator" , 'wp-sacloud-webaccel'), 'manage_options', __FILE__, 'sacloud_webaccel_option_page');
}

function sacloud_webaccel_option_page() {
    wp_enqueue_script('sacloud-webaccel-script', plugins_url( '/script/sacloud-webaccel.js' , __FILE__ ), array( 'jquery' ), '0.0.1',true);
    wp_enqueue_style('sacloud-webaccel-style', plugins_url('style/sacloud-webaccel.css', __FILE__));

    $sacloud_webaccel_options = sacloud_webaccel_get_options();
    $messages = array();
    include "tpl/setting.php";
}

function sacloud_webaccel_toolbar_purge_item( $admin_bar ) {
    if ( !current_user_can( 'manage_options' ) ) {
        return;
    }
    $purge_url = add_query_arg( array( 'sacloud_webaccel_action' => 'purge', 'sacloud_webaccel_url' => 'all' ) );
    $nonced_url = wp_nonce_url( $purge_url, 'sacloud_webaccel-purge_all' );

    $admin_bar->add_menu( array(
        'id' => 'sacloud-webaccel-purge-all',
        'title' => __( 'Purge Cache', 'wp-sacloud-webaccel' ),
        'href' => $nonced_url,
        'meta' => array(
            'title' => __( 'Purge Cache', 'wp-sacloud-webaccel' ),
            'onclick' => "return confirm(\"" .__( 'Are you sure you want to delete the WebAccelerator cache?', 'wp-sacloud-webaccel' ). "\")"
        ))
    );
}


function sacloud_webaccel_options()
{
    register_setting('sacloud-webaccel-options', 'sacloud-webaccel-options' , 'sacloud_webaccel_validate_options');
}

function sacloud_webaccel_validate_options($values){

    $default_values = array (
        'api-key' => '',
        'api-secret' => '',
        'api-zone'  => 'tk1a',
        'use-subdomain' => 0,
        'subdomain-name' => '',
        'subdomain-ssl' => 0,
        'maxage' => 60 * 60 * 24,
        'enable-page' => 0,
        'enable-post' => 0,
        'enable-media' => 0,
        'enable-archive' => 0,
        'enable-log' => 0,
    );

    if ( !is_array( $values ) ) {
        return $default_values;
    }


    $maxage_max = 60 * 60 * 24 * 7;
    $maxage_min = 0;
    $api_keys_maxlen  = 128;
    $subdomain_maxlen =32;

    $ignore_keys = array('api-zone');
    $checkboxs = array(
        'use-subdomain' , 'subdomain-ssl' , 'enable-page' , 'enable-post' , 'enable-media' , 'enable-archive' , 'enable-log'
    );

    $out = array ();
    foreach ( $default_values as $key => $value )
    {
        if ( empty ( $values[ $key ] ) )
        {
            $out[ $key ] = $value;

            if ($key === 'api-key' && function_exists("add_settings_error")){
                add_settings_error(
                    'sacloud-webaccel-options',
                    $key,
                    sprintf(__("%s is required.","wp-sacloud-webaccel") , __('api-key' , 'wp-sacloud-webaccel') )
                );
            }else if ($key === 'api-secret' && function_exists("add_settings_error")){
                add_settings_error(
                    'sacloud-webaccel-options',
                    $key,
                    sprintf(__("%s is required.","wp-sacloud-webaccel") , __('api-secret' , 'wp-sacloud-webaccel') )
                );
            }
        }
        else
        {
            if ( in_array($key, $ignore_keys) ) {
                // ignore keys
                $out [ $key ] = $value;
            }else if ( in_array($key, $checkboxs) ) {
                // if $key is in $checkboxes , set value to 1(ignore posted value)
                $out [ $key ] = '1';
            }else{

                if ( 'api-key' === $key ){
                    $value_len = strlen($values[ $key ]);
                    if ($value_len > $api_keys_maxlen){
                        add_settings_error(
                            'sacloud-webaccel-options',
                            $key,
                            sprintf(__("%s is too long.","wp-sacloud-webaccel") , __('api-key' , 'wp-sacloud-webaccel') )
                        );
                        $out [ $key ] = $value;
                    }else{
                        $out[ $key ] = sanitize_text_field($values[ $key ]);
                    }

                } else if ('api-secret' === $key ){
                    $value_len = strlen($values[ $key ]);
                    if ($value_len > $api_keys_maxlen){
                        add_settings_error(
                            'sacloud-webaccel-options',
                            $key,
                            sprintf(__("%s is too long.","wp-sacloud-webaccel") , __('api-secret' , 'wp-sacloud-webaccel') )
                        );
                        $out [ $key ] = $value;
                    }else{
                        $out[ $key ] = sanitize_text_field($values[ $key ]);
                    }
                } else if ( 'subdomain-name' === $key){
                    $value_len = strlen($values[ $key ]);
                    if ($value_len > $subdomain_maxlen){
                        add_settings_error(
                            'sacloud-webaccel-options',
                            $key,
                            sprintf(__("%s is too long.","wp-sacloud-webaccel") , __('subdomain-name' , 'wp-sacloud-webaccel') )
                        );
                        $out [ $key ] = $value;
                    }else{
                        $out[ $key ] = sanitize_text_field($values[ $key ]);
                    }

                } else if ( 'maxage' === $key){
                    if (!(bool)preg_match('/\A[1-9][0-9]*\z/', $values[ $key ])){
                        add_settings_error(
                            'sacloud-webaccel-options',
                            $key,
                            sprintf(__("%s is allowed number only.","wp-sacloud-webaccel") , __('maxage' , 'wp-sacloud-webaccel') )
                        );
                        $out [ $key ] = $value;
                    }else if ($values[$key] > $maxage_max || $values[$key] < $maxage_min){
                        add_settings_error(
                            'sacloud-webaccel-options',
                            $key,
                            sprintf(__("%s is neet between %d and %d.","wp-sacloud-webaccel") , __('maxage' , 'wp-sacloud-webaccel') , $maxage_min , $maxage_max )
                        );
                        $out [ $key ] = $value;
                    }else{
                        $out[ $key ] = $values[ $key ];
                    }
                } else {
                    $out[ $key ] = $values[ $key ];
                }
            }

        }
    }

    //
    if ($out['use-subdomain'] === 1 && strlen($out['subdomain-name']) === 0) {
        add_settings_error(
            'sacloud-webaccel-options',
            'subdomain-name',
            __("subdomina-name is required when use-subdomain","wp-sacloud-webaccel")
        );
        $out['use-subdomain'] = 0;
    }


    return $out;
}

function sacloud_webaccel_deactivate(){
    delete_option("sacloud-webaccel-options");

    $upload_dir = wp_upload_dir();
    if (!wp_mkdir_p($upload_dir['basedir'])) { return -1;}
    $htaccess = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . '.htaccess';

    //clean up old rules first
    if (sacloud_webaccel_cleanup_htaccess($htaccess) == -1)
    {
        return -1; //unable to write to the file
    }

}

function sacloud_webaccel_options_handle_add($name , $values){
    sacloud_webaccel_handle_htaccess_file($values);
}

function sacloud_webaccel_options_handle_update($old , $new){
    sacloud_webaccel_handle_htaccess_file($new);
}

function sacloud_webaccel_options_handle_delete($name){
    sacloud_webaccel_handle_htaccess_file();
}

// -------------------- purge cache functions -----------------

function sacloud_webaccel_purge_post_on_edit($post_id, $post_after, $post_before){

    $purge_status = array( 'publish', 'future' );

    $purge = false;
    if( in_array( $post_before->post_status, $purge_status ) || in_array( $post_after->post_status, $purge_status ) ) {
        $purge = true;
    }

    if ( ! $purge ) { return; }

    //purge target URLs
    $targetURLs = array();

    // ===== permalink =====
    if ( sacloud_webaccel_get_option('enable-post') == '1' ) {
        $before_permalink = str_replace('__trashed' , '', get_permalink($post_before));
        $after_permalink = str_replace('__trashed' , '',get_permalink($post_after));

        if ($before_permalink != $after_permalink){
            $targetURLs[] = $before_permalink;
        }
        $targetURLs[] = $after_permalink;
    }

    // ===== archive(date) =====
    if ( sacloud_webaccel_get_option('enable-archive') == '1' ) {

        foreach( array($post_before , $post_after) as $post) {
            $day = get_the_time('d', $post);
            $month = get_the_time('m', $post);
            $year = get_the_time('Y', $post);

            if ($year) {
                $targetURLs[] = get_year_link($year);
                if ($month) {
                    $targetURLs[] = get_month_link($year, $month);
                    if ($day)
                        $targetURLs[] = get_day_link($year, $month, $day);
                }
            }
        }
    }

    // ===== home(top) =====
    if ( sacloud_webaccel_get_option('enable-page') == '1' ) {
        $targetURLs[] = sacloud_webaccel_get_homepage_url();
    }

    if (! empty($targetURLs)){
        sacloud_webaccel_purge_url(array_values(array_unique($targetURLs)));
    }
}

function sacloud_webaccel_purge_post_on_comment( $comment_id, $comment ) {
    $oldstatus = '';
    $approved = $comment->comment_approved;

    if ( $approved == null )
        $newstatus = false;
    elseif ( $approved == '1' )
        $newstatus = 'approved';
    elseif ( $approved == '0' )
        $newstatus = 'unapproved';
    elseif ( $approved == 'spam' )
        $newstatus = 'spam';
    elseif ( $approved == 'trash' )
        $newstatus = 'trash';
    else
        $newstatus = false;

    sacloud_webaccel_purge_post_on_comment_change( $newstatus, $oldstatus, $comment );
}

function sacloud_webaccel_purge_post_on_comment_change( $newstatus, $oldstatus, $comment ) {

    if ( sacloud_webaccel_get_option('enable-post') != '1' ) {
        return;
    }

    $_post_id = $comment->comment_post_ID;
    $_comment_id = $comment->comment_ID;
    switch ( $newstatus ) {
        case 'approved':
            sacloud_webaccel_purge_post( $_post_id );
            break;
        case 'spam':
        case 'unapproved':
        case 'trash':
            if ( $oldstatus == 'approve' ) {
                sacloud_webaccel_purge_post( $_post_id );
            }
            break;
    }
}

function sacloud_webaccel_purge_post($post_id){
    $targetURLs = sacloud_webaccel_get_purge_url_by_post($post_id);

    if (! empty($targetURLs)){
        sacloud_webaccel_purge_url(array_values(array_unique($targetURLs)));
    }
}

function sacloud_webaccel_get_purge_url_by_post($post_id){
    //purge target URLs
    $targetURLs = array();

    // ===== permalink =====
    if ( sacloud_webaccel_get_option('enable-post') == '1' ) {
        $permalink = str_replace('__trashed' , '', get_permalink($post_id));
        $targetURLs[] = $permalink;
    }

    // ===== archive(date) =====
    if ( sacloud_webaccel_get_option('enable-archive') == '1' ) {

        $day = get_the_time('d', $post_id);
        $month = get_the_time('m', $post_id);
        $year = get_the_time('Y', $post_id);

        if ($year) {
            $targetURLs[] = get_year_link($year);
            if ($month) {
                $targetURLs[] = get_month_link($year, $month);
                if ($day)
                    $targetURLs[] = get_day_link($year, $month, $day);
            }
        }
    }

    // ===== home(top) =====
    if ( sacloud_webaccel_get_option('enable-page') == '1' ) {
        $targetURLs[] = sacloud_webaccel_get_homepage_url();
    }

    return $targetURLs;
}

function sacloud_webaccel_get_homepage_url() {
    //  WPML installetd?
    if ( function_exists('icl_get_home_url') )
    {
        $homepage_url = trailingslashit( icl_get_home_url() );
    }
    else
    {
        $homepage_url = trailingslashit( home_url() );
    }

    return $homepage_url;
}

function sacloud_webaccel_purge_on_term_taxonomy_edited( $term_id , $taxon) {

    // purge tag/category/custom-taxonomy
    $targetURLs = array();

    if ( sacloud_webaccel_get_option('enable-archive') == '1' ) {
        $targetURLs[] = sacloud_webaccel_get_term_link($term_id , $taxon);

        //object_idsのパージ(記事)
        $args = array(
            'tax_query' => array(
                array(
                    'taxonomy' => $taxon,
                    'field' => 'term_id',
                    'terms' => $term_id
                )
            )
        );

        $posts = get_posts($args);
        if (!empty($posts)) {
            foreach ($posts as $post) {
                $targetURLs = array_merge($targetURLs, sacloud_webaccel_get_purge_url_by_post($post->ID));
            }
        }
    }

    if (! empty($targetURLs)){
        sacloud_webaccel_purge_url(array_values(array_unique($targetURLs)));
    }

    return true;
}

function sacloud_webaccel_set_object_terms($object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids){

    $targetIDs = array_values(array_unique(array_merge($tt_ids , $old_tt_ids)));
    $targetURLs = array();

    if ( sacloud_webaccel_get_option('enable-archive') == '1' ) {
        foreach($targetIDs as $termID){
            $targetURL = sacloud_webaccel_get_term_link($termID , $taxonomy);
            if ($targetURL != "") {
                $targetURLs[] = $targetURL;
            }
        }
    }

    if (! empty($targetURLs)){
        sacloud_webaccel_purge_url($targetURLs);
    }

}

function sacloud_webaccel_get_term_link($termID , $taxonomy){
    $ret = "";
    switch ($taxonomy){
        case "category":
            $ret = get_category_link($termID);
            break;
        case "post_tag":
            $ret = get_tag_link($termID);
            break;
        case "link_category":
        case "post_format":
            // nop
            break;
        default:
            // custom taxonomy
            $ret = get_term_link((int)$termID , $taxonomy);
            if (is_wp_error($ret)){
                sacloud_webaccel_log(sprintf("[ERROR] get_term_link returns error. termID=[%s],taxonomy=[%s]" , $termID, $taxonomy));
                $ret = "";
            }
            break;
    }

    return $ret;
}

function sacloud_webaccel_purge_url( $urls, $feed = true ) {

    if (!is_array($urls)){
        $urls = array($urls);
    }
    $targetURLs = array();

    foreach ($urls as $url){

        $url = trailingslashit( $url );

        $parse = parse_url( $url );

        // サブドメインの場合、http[s]://[your-host].user.webaccel.jpにする
        $host_url = $parse[ 'scheme' ] . '://' . $parse[ 'host' ] ;
        $isSubdomain = sacloud_webaccel_get_option('use-subdomain') == '1';
        if ($isSubdomain){
            $protocol = sacloud_webaccel_get_option('subdomain-ssl') == '1' ? "https://" : "http://";
            $subdomain = sacloud_webaccel_get_option('subdomain-name') . ".user.webaccel.jp";
            $host_url = $protocol . $subdomain;
        }

        $_url_purge_base = $host_url . $parse[ 'path' ];
        $_url_purge = $_url_purge_base;

        if ( isset( $parse[ 'query' ] ) && $parse[ 'query' ] != '' ) {
            $_url_purge .= '?' . $parse[ 'query' ];
        }

        $targetURLs[] = $_url_purge;

        if ( $feed ) {
            $feed_url = rtrim( $_url_purge_base, '/' ) . '/feed/';
            $targetURLs[] =  $feed_url;
            $targetURLs[] =  $feed_url . 'atom/';
            $targetURLs[] =  $feed_url . 'rdf/';
        }
    }

    if (! empty($targetURLs)){
        sacloud_webaccel_delete_cache($targetURLs);
    }

}

// -------------------- purge all -----------------------------
function sacloud_webaccel_purge_all(){
    if ( !isset( $_REQUEST['sacloud_webaccel_action'] ) )
        return;

    if ( !current_user_can( 'manage_options' ) )
        wp_die( 'Sorry, you do not have the necessary privileges to edit these options.' );

    $action = $_REQUEST['sacloud_webaccel_action'];

    if ( $action == 'done' ) {
        add_action( 'admin_notices',  'sacloud_webaccel_show_notice'  );
        add_action( 'network_admin_notices', 'sacloud_webaccel_show_notice' );
        return;
    }

    check_admin_referer( 'sacloud_webaccel-purge_all' );

    switch ( $action ) {
        case 'purge':
            sacloud_webaccel_true_purge_all();
            break;
    }
    wp_redirect( esc_url_raw( add_query_arg( array( 'sacloud_webaccel_action' => 'done' ) ) ) );
}

function sacloud_webaccel_true_purge_all(){
    sacloud_webaccel_log(  __( "Start purge_all", "wp-sacloud-webaccel" ) );

    $targetURLs = array();

    $targetURLs = array_filter(array_merge($targetURLs ,
        array(sacloud_webaccel_get_homepage_url()) ,
        sacloud_webaccel_get_all_posts_url(),    // posts
        sacloud_webaccel_get_all_taxonomies() ,  // tag/category/taxonomy
        sacloud_webaccel_get_all_date_archives() // date
    ));

    if (! empty($targetURLs)){
        sacloud_webaccel_purge_url(array_values(array_unique($targetURLs)));
    }

    sacloud_webaccel_log( __( "Finish purge_all", "wp-sacloud-webaccel" ) );

    return true;

}

function sacloud_webaccel_get_all_posts_url(){

    $targetURLs = array();
    $args = array(
        'numberposts' => 0,
        'post_type' => 'any',
        'post_status' => 'publish' );

    if ( $_posts = get_posts( $args ) ) {
        foreach ( $_posts as $p ) {
            $targetURLs[] = get_permalink( $p->ID );
        }
    }

    return $targetURLs;
}

function sacloud_webaccel_get_all_taxonomies(){
    $targetURLs = array();
    $targetURLs = array_merge($targetURLs,
        sacloud_webaccel_get_all_categories_url(),
        sacloud_webaccel_get_all_posttags_url(),
        sacloud_webaccel_get_all_customtaxa_url());

    return $targetURLs;
}

function sacloud_webaccel_get_all_categories_url(){

    $targetURLs = array();
    if ( $_categories = get_categories() ) {

        foreach ( $_categories as $c ) {
            $targetURLs[] =  get_category_link( $c->term_id ) ;
        }
    }

    return $targetURLs;
}

function sacloud_webaccel_get_all_posttags_url(){

    $targetURLs = array();
    if ( $_posttags = get_tags() ) {
        foreach ( $_posttags as $t ) {
            $targetURLs[] = get_tag_link( $t->term_id ) ;
        }
    }

    return $targetURLs;
}

function sacloud_webaccel_get_all_customtaxa_url(){

    $targetURLs = array();
    if ( $custom_taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => false ) ) ) {

        foreach ( $custom_taxonomies as $taxon ) {
            if ( ! in_array( $taxon, array( 'category', 'post_tag', 'link_category' , 'post_format' ) ) ) {

                if ( $terms = get_terms( $taxon ) ) {
                    foreach ( $terms as $term ) {
                        $targetURLs[] = get_term_link( $term, $taxon );
                    }
                }
            }
        }
    }

    return $targetURLs;
}

function sacloud_webaccel_get_all_date_archives(){
    $targetURLs = array();
    $targetURLs = array_merge($targetURLs,
        sacloud_webaccel_get_all_daily_archives(),
        sacloud_webaccel_get_all_monthly_archives(),
        sacloud_webaccel_get_all_yearly_archives());

    return $targetURLs;
}

function sacloud_webaccel_get_all_daily_archives(){
    global $wpdb;

    $targetURLs = array();
    $_query_daily_archives =
        "SELECT YEAR(post_date) AS 'year', MONTH(post_date) AS 'month', DAYOFMONTH(post_date) AS 'dayofmonth', count(ID) as posts
                FROM $wpdb->posts
                WHERE post_type = 'post' AND post_status = 'publish'
                GROUP BY YEAR(post_date), MONTH(post_date), DAYOFMONTH(post_date)
                ORDER BY post_date DESC";


    if ( $_daily_archives = $wpdb->get_results( $_query_daily_archives ) ) {
        foreach ( $_daily_archives as $_da ) {
            $targetURLs[] = get_day_link( $_da->year, $_da->month, $_da->dayofmonth ) ;
        }
    }
    return $targetURLs;
}

function sacloud_webaccel_get_all_monthly_archives(){
    global $wpdb;

    $targetURLs = array();
    $_query_monthly_archives =
        "SELECT YEAR(post_date) AS 'year', MONTH(post_date) AS 'month', count(ID) as posts
                FROM $wpdb->posts
                WHERE post_type = 'post' AND post_status = 'publish'
                GROUP BY YEAR(post_date), MONTH(post_date)
                ORDER BY post_date DESC";

    if ( $_monthly_archives = $wpdb->get_results( $_query_monthly_archives ) ) {

        foreach ( $_monthly_archives as $_ma ) {
            $targetURLs[] = get_month_link( $_ma->year, $_ma->month ) ;
        }
    }
    return $targetURLs;
}

function sacloud_webaccel_get_all_yearly_archives(){
    global $wpdb;

    $targetURLs = array();

    $_query_yearly_archives =
        "SELECT YEAR(post_date) AS 'year', count(ID) as posts
                FROM $wpdb->posts
                WHERE post_type = 'post' AND post_status = 'publish'
                GROUP BY YEAR(post_date)
                ORDER BY post_date DESC";

    if ( $_yearly_archives = $wpdb->get_results( $_query_yearly_archives ) ) {
        foreach ( $_yearly_archives as $_ya ) {
            $targetURLs[] =  get_year_link( $_ya->year ) ;
        }
    }
    return $targetURLs;
}


function sacloud_webaccel_show_notice(){
    echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Purge initiated', 'wp-sacloud-webaccel' ) . '</p></div>';
}

// -------------------- internal functions --------------------

// *********** utilities ************

// Connection test
function sacloud_webaccel_connect_test()
{
    $accessKey = '';
    if(isset($_POST['key'])) {
        $accessKey = sanitize_text_field($_POST['key']);
    }

    $secret = '';
    if(isset($_POST['secret'])) {
        $secret = sanitize_text_field($_POST['secret']);
    }

    try {
        $client = createApiClient($accessKey , $secret , null , true);

        $client->Auth();
        echo json_encode(array(
            'message' => __("Connection was Successfully" , 'wp-sacloud-webaccel'),
            'is_error' => false,
        ));
        exit;

    } catch(Exception $ex) {
        echo json_encode(array(
            'message' => __("Connection Error" , 'wp-sacloud-webaccel') . ":" . $ex->getMessage(),
            'is_error' => true,
        ));
        exit;
    }
}

function sacloud_webaccel_auth($force = false){
    try {
        $client = createApiClient(null , null , null , $force);
        $client->Auth();
        return true;
    } catch(Exception $ex) {
        return false;
    }
}

// Load optionss
function sacloud_webaccel_get_options($force = false){
    static $options;
    if ($force || $options == null) {
        $options = get_option("sacloud-webaccel-options");
    }
    return sacloud_webaccel_validate_options($options);
}

// Get option by name
function sacloud_webaccel_get_option($key , $force = false){
    $options = sacloud_webaccel_get_options($force);
    return $options[$key];
}

// Logging
function sacloud_webaccel_log( $msg ) {

    if ( ! WP_DEBUG || sacloud_webaccel_get_option('enable-log') != 1 ) {
        return;
    }
    error_log("[sacloud-webaccel] " . $msg );
    return true;
}

// ********** for cache mediafile by .htaccess ************

function sacloud_webaccel_is_supported_app_server(){
    //figure out what server they're using
    if (strstr(strtolower(filter_var($_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING)), 'apache'))
    {
        $aiowps_server = 'apache';
    }
    else if (strstr(strtolower(filter_var($_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING)), 'nginx'))
    {
        $aiowps_server = 'nginx';
    }
    else
    { //unsupported server
        return -1;
    }
    return 1;
}

function sacloud_webaccel_handle_htaccess_file($options = null){

    if ($options === null){
        $options = sacloud_webaccel_get_options();
    }

//    $htaccess = ABSPATH . '.htaccess';
    $upload_dir = wp_upload_dir();
    if (!wp_mkdir_p($upload_dir['basedir'])) { return -1;}
    $htaccess = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . '.htaccess';

    //clean up old rules first
    if (sacloud_webaccel_cleanup_htaccess($htaccess) == -1)
    {
        return -1; //unable to write to the file
    }

    if ($options['enable-media'] != '1'){
        return 1; //success
    }

    try {
        $client = createApiClient($options['api-key'] , $options['api-secret'] , $options['api-zone'] , true);
        $client->Auth();
    } catch(Exception $ex) {
        return -1;
    }

    //backup_a_file($htaccess); //should we back up htaccess file??

    @ini_set( 'auto_detect_line_endings', true );
    $ht = explode( PHP_EOL, implode( '', file( $htaccess ) ) ); //parse each line of file into array

    $rules = sacloud_webaccel_getrules($options);

    if ($rules == -1)
    {
        return -1;
    }

    $rulesarray = explode( PHP_EOL, $rules );
    $contents = array_merge( $rulesarray, $ht );

    if (!$f = @fopen($htaccess, 'w+'))
    {
        return -1; //we can't write to the file
    }

    $blank = false;

    //write each line to file
    foreach ( $contents as $insertline )
    {
        if ( trim( $insertline ) == '' )
        {
            if ( $blank == false )
            {
                fwrite( $f, PHP_EOL . trim( $insertline ) );
            }
            $blank = true;
        }
        else
        {
            $blank = false;
            fwrite( $f, PHP_EOL . trim( $insertline ) );
        }
    }
    @fclose( $f );
    return 1; //success
}

function sacloud_webaccel_getrules($options){
    @ini_set( 'auto_detect_line_endings', true );

    if (sacloud_webaccel_is_supported_app_server() == -1) {
        return -1;
    }

    $maxAge = $options['maxage'];
    if (strlen($maxAge) == 0 ){
        $maxAge = "0";
    }

    $rules = '';
    $rules .= '<IfModule mod_headers.c>' . PHP_EOL;
    $rules .= '    Header set Cache-Control "s-maxage='.$maxAge.', public"    ' . PHP_EOL;
    $rules .= '</IfModule>' . PHP_EOL;

    //Add outer markers if we have rules
    if ($rules != '')
    {
        $rules = "# BEGIN wp-sacloud-webaccel" . PHP_EOL . $rules . "# END wp-sacloud-webaccel" . PHP_EOL;
    }

    return $rules;
}

function sacloud_webaccel_cleanup_htaccess($htaccess){

    $section = "wp-sacloud-webaccel";

    @ini_set('auto_detect_line_endings', true);
    if (!file_exists($htaccess))
    {
        $ht = @fopen($htaccess, 'a+');
        @fclose($ht);
    }
    $ht_contents = explode(PHP_EOL, implode('', file($htaccess)));
    if ($ht_contents)
    {
        $isNeedWrite = true;
        if (!$f = @fopen($htaccess, 'w+'))
        {
            @chmod( $htaccess, 0644 );
            if (!$f = @fopen( $htaccess, 'w+'))
            {
                return -1;
            }
        }

        foreach ( $ht_contents as $n => $markerline )
        { //for each line in the file
            if (strpos($markerline, '# BEGIN ' . $section) !== false)
            { //if we're at the beginning of the section
                $isNeedWrite = false;
            }
            if ($isNeedWrite == true)
            { //as long as we're not in the section keep writing
                fwrite($f, trim($markerline) . PHP_EOL);
            }
            if (strpos($markerline, '# END ' . $section) !== false)
            { //see if we're at the end of the section
                $isNeedWrite = true;
            }
        }
        @fclose($f);
        return 1;
    }
    return 1;
}

// ********** for send Cache-Control header ************

function sacloud_webaccel_send_cache_header(){
    $send_header = false;
    if (!$send_header && sacloud_webaccel_get_option("enable-page") == "1"){
        $send_header = is_front_page() || is_home();
    }
    if (!$send_header && sacloud_webaccel_get_option("enable-post") == "1"){
        $send_header = !is_preview() && (is_single() || is_page() || is_404());
    }
    if (!$send_header && sacloud_webaccel_get_option("enable-media") == "1"){
        $send_header = is_attachment(); //画像への直リンクは.htaccessで対応する
    }
    if (!$send_header && sacloud_webaccel_get_option("enable-archive") == "1"){
        $send_header = is_archive();
    }

    if ($send_header){
        $maxAge = sacloud_webaccel_get_option("maxage");
        if (strlen($maxAge) == 0 ){
            $maxAge = "0";
        }

        header( 'Cache-Control: s-maxage='.$maxAge.', public' );
    }
}


// -------------------- for Web Accelerator API functions --------------------

// Return object URL

function sacloud_webaccel_subdomain_url($wpurl) {

    $isSubdomain = sacloud_webaccel_get_option('use-subdomain') == '1';
    if (!$isSubdomain){return $wpurl;}

    $homeURL = home_url();
    $path = str_replace($homeURL , '', $wpurl);
    $protocol = sacloud_webaccel_get_option('subdomain-ssl') == '1' ? "https://" : "http://";
    $subdomain = sacloud_webaccel_get_option('subdomain-name') . ".user.webaccel.jp";

    $webaccelURL = $protocol . $subdomain . $path;
    return $webaccelURL;
}

// Delete web-accel cache.
function sacloud_webaccel_delete_cache_by_id($file_id) {

    $url =  wp_get_attachment_url($file_id);
    return sacloud_webaccel_delete_cache($url);
}

// Delete web-accel cache
function sacloud_webaccel_delete_cache_by_path($path) {
    $dir = wp_upload_dir();
    $filePath = str_replace($dir['basedir'] . DIRECTORY_SEPARATOR, '' , $path);
    sacloud_webaccel_delete_cache($dir['baseurl'] . "/" . $filePath);
    return $path;
}

// Upload thumbnails
function sacloud_webaccel_thumb_upload($metadatas) {
    if( ! isset($metadatas['sizes'])) {
        return $metadatas;
    }

    $dir = wp_upload_dir();
    $targetURLs = array();
    foreach($metadatas['sizes'] as $thumb) {
        $fileURL = $dir['url'] . "/" . $thumb['file'];
        $targetURLs[] = $fileURL;
    }
    if ( ! empty($targetURLs)){
        sacloud_webaccel_delete_cache($targetURLs);
    }

    return $metadatas;
}

// Delete an object
function sacloud_webaccel_delete_object($filepath) {
    return __delete_object($filepath);
}

function createApiClient($key = null , $secret = null , $zone = null ,$force = false){
    static $client;

    if( ! $client || $force) {
        if($key === null) {
            $key = sacloud_webaccel_get_option('api-key');
        }
        if($secret === null) {
            $secret = sacloud_webaccel_get_option('api-secret');
        }
        if($zone === null) {
            $zone = sacloud_webaccel_get_option('api-zone');
        }

        $client = new SacloudClient($key , $secret , $zone);
    }
    return $client;
}

function sacloud_webaccel_delete_cache($url){
    $client = createApiClient();
    $client->DeleteCache($url);
    return true;
}

class SacloudClient{
    private $apiKey = '';
    private $apiSecret = '';
    private $zone = '';

    const API_BASE_URL_FORAMT = 'https://secure.sakura.ad.jp/cloud/zone/%s/api/%s';

    const API_CLOUD_SUFFIX = "cloud/1.1/";
    const API_WEBACCEL_SUFFIX = "webaccel/1.0/";

    function __construct($key , $secret , $zone)
    {
        $this->apiKey = $key;
        $this->apiSecret = $secret;
        $this->zone  = $zone;
    }

    public function Auth(){
        $apiURL = $this->getAPIAuthURL();
        $res = $this->Get($apiURL);

        if(!$res ){
            throw new Exception("AuthError : Unknown error");
        }elseif(isset($res['is_fatal']) && $res['is_fatal'] === true){
            throw new Exception("AuthError : " . $res['error_msg']);
        }elseif (strpos($res['ExternalPermission'] , 'cdn') === false){
            throw new Exception("AuthError : Is not have CDN permission ");
        }
        return true;
    }

    public function DeleteCache($url){
        $data = array();
        if (is_array($url)){
            $data = array();
            $splited_urls = array_chunk($url,100);
            foreach($splited_urls as $chunk){
                $data[] = array("URL" => $chunk);
            }
        }else{
            $data = array(array("URL" => array($url)));
        }

        foreach($data as $d) {
            $apiURL = $this->getDeleteCacheURL();
            $res = $this->Post($apiURL, $d);

            if (!$res) {
                throw new Exception("AuthError : Unknown error");
            } elseif (isset($res['is_fatal']) && $res['is_fatal'] === true) {
                throw new Exception("AuthError : " . $res['error_msg']);
            }
            sacloud_webaccel_log(sprintf("Posted delete cache request to [%s]", print_r($d, true)));
        }
        return true;

    }

    private function getAPIAuthURL(){
        return sprintf(self::API_BASE_URL_FORAMT , $this->zone , self::API_CLOUD_SUFFIX) . "auth-status";
    }

    private function getDeleteCacheURL(){
        return sprintf(self::API_BASE_URL_FORAMT , $this->zone , self::API_WEBACCEL_SUFFIX) . "deletecache";
    }

    private function setupCurl($url){

        $curl = curl_init($url);
        curl_setopt($curl , CURLOPT_USERPWD, sprintf("%s:%s" , $this->apiKey , $this->apiSecret));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        return $curl;
    }

    private function Get($url) {
        //TODO use wp_remote_get
        $curl = $this->setupCurl($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET'); // get
        $response = curl_exec($curl);
        $result = json_decode($response, true);
        curl_close($curl);
        return $result;
    }

    private function Post($url , $data){
        //TODO use wp_remote_post
        $curl = $this->setupCurl($url);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST'); // post
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($curl);
        $result = json_decode($response, true);
        curl_close($curl);

        return $result;
    }
}
