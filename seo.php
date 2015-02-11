<?php
/*
Plugin Name: Bubble SEO
Plugin URI: 
Description: It's time to have a good and fast SEO (Pure SEO)
Version: 2.1
Author: iLen
Author URI:  
*/
if ( !class_exists('ilen_seo') ) {

require_once 'assets/ilenframework/assets/lib/utils.php'; // get utils
require_once 'assets/functions/options.php'; // get options plugins

class ilen_seo extends ilen_seo_make{

  function __construct(){
    global $if_utils;
    parent::__construct(); // configuration general

    global $ilen_seo;

 
    if( is_admin() ){

      add_action( 'admin_enqueue_scripts', array( &$this,'script_and_style_admin' ) );
 
    }elseif( ! is_admin() ) {

      if( TRUE ){

        if ( ! has_filter( 'wp_title', array( &$this,'getRealTitle')) ){
          add_filter( 'wp_title', array( &$this,'getRealTitle'), 10 );
        }

        // add meta tags
        add_action('wp_head', array( &$this , 'getMetaTags'), 0 );

        // remove Wordpress generator (as options)
        if( isset($ilen_seo->remove_link_wp_generator) && $ilen_seo->remove_link_wp_generator ){
          add_filter('the_generator', array( &$this,'wp_generator') );
        }

        // remove canonical links
        if( isset($ilen_seo->remove_link_canonical) && $ilen_seo->remove_link_canonical ){
          remove_action('wp_head', 'rel_canonical');
        }


      }

      // add scripts & styles
      add_action( 'wp_enqueue_scripts', array( &$this,'script_and_style_front' ) );
    }




  }


  function wp_generator() {
    return '';
  }

 


  //  SEO
  function getRealTitle( $title ){
 
    if ( is_feed() )
      return $title;
    
    $title = '';
    $title = self::getFormatTitle();
    return apply_filters( 'getRealTitle', $title );

  }


  function getMetaTags(){

    global $ilen_seo, $post, $authordata, $if_utils;

    $meta_keyword        = null;
    $meta_description    = null;
    $tags_to_metakeyword = null;
    $meta_facebook       = null;
    $meta_twitter        = null;
    $meta_google         = null;

    if( (isset($ilen_seo->meta_keywork) && $ilen_seo->meta_keywork) || ( is_singular() &&  isset($ilen_seo->tag_keyword) && $ilen_seo->tag_keyword ) ){

      if( is_singular() && isset($ilen_seo->tag_keyword) && $ilen_seo->tag_keyword ){

        $t = wp_get_post_tags($post->ID);
        if( $t ){
          $tags = array();
          foreach ($t as $tag) {
            $tags[] = $tag->name;
          }

          $tags_to_metakeyword = implode(",",$tags);
        }

        $meta_keyword = '<meta name="keywords" content="'.$tags_to_metakeyword.'" />
        ';

      }else{

        $meta_keyword = '<meta name="keywords" content="'.$ilen_seo->meta_keywork.'" />
        ';

      }

      

    }

    if( get_query_var('paged') ){

      $meta_description = "";

    }elseif( is_home() ){

      $meta_description = mb_substr($ilen_seo->meta_description,0,155,'utf-8');

      if( isset( $ilen_seo->facebook_open_graph ) && $ilen_seo->facebook_open_graph ){

$meta_facebook = '
<!-- open Graph data -->
<meta property="og:title" content="'.get_bloginfo('name').'" />
<meta property="og:description" content="'.$meta_description.'" />
<meta property="og:url" content="'.get_bloginfo('url').'" />
<meta property="og:type" content="website" />
<meta property="og:locale" content="'.get_locale().'" />
<meta property="og:site_name" content="'.get_bloginfo( 'name' ).'" />
';

      }

      if( isset( $ilen_seo->twitter_user ) && $ilen_seo->twitter_user ){
      $meta_twitter= '<!-- twitter Card data -->
<meta name="twitter:card" content="summary" />
<meta name="twitter:site" content=@"'.$ilen_seo->twitter_user.'" />
<meta name="twitter:title" content="'.get_bloginfo('name').'" />
<meta name="twitter:description" content="'.$meta_description.'" />
';
      }
    }elseif( is_tag() ){
  
      if( isset( $ilen_seo->facebook_open_graph ) && $ilen_seo->facebook_open_graph ){

        $tag = ucfirst(single_tag_title("", false));
        $tag_id = get_query_var('tag_id');
        $meta_facebook = '
<!-- open Graph data -->
<meta property="og:title" content="'.($tag).'" />
<meta property="og:url" content="'.get_tag_link( $tag_id ).'" />
<meta property="og:type" content="website" />
<meta property="og:locale" content="'.get_locale().'" />
<meta property="article:section" content="'.($tag).'" />
<meta property="og:site_name" content="'.get_bloginfo( 'name' ).'" />
';

      }

      if( isset( $ilen_seo->twitter_user ) && $ilen_seo->twitter_user ){
      $meta_twitter= '<!-- twitter Card data -->
<meta name="twitter:card" content="summary" />
<meta name="twitter:site" content="@'.$ilen_seo->twitter_user.'" />
<meta name="twitter:title" content="'.$tag.'" />
';
      }

      $meta_description = "";

    }elseif( is_singular() ){

      $excert  = strip_shortcodes(strip_tags(trim( $post->post_content  )));
      $excert1 = preg_replace('/\s\s+/', ' ', $excert);  
      $excert2 = $if_utils->IF_removeShortCode( $excert1 );
      $content = mb_substr(trim( $excert2 ),0,155,'utf-8')."...";
      $meta_description = $content;

      $tags_string = "";
      $categories_string = "";

      if( isset( $ilen_seo->facebook_open_graph ) && $ilen_seo->facebook_open_graph ){

        if(   isset( $ilen_seo->facebook_open_graph_tag ) && $ilen_seo->facebook_open_graph_tag   ){
            $t = wp_get_post_tags($post->ID);
            if( $t ){
              $tags = array();
              foreach ($t as $tag) {
                $tag_link = get_tag_link($tag->term_id);
                $tags[] = $tag->name;
              }
              if( is_array($tags) ){
                foreach($tags as $tt){
                    $tags_string .='
<meta property="article:tag" content="$tt" />';
                }
                $tags_string = "\n{$tags_string}\n";
              }
            }
        }

        $c = get_the_category(); 
        $array_cat = array();
        if( $c ){

          foreach ($c as $category) {
            $array_cat[] = $category->cat_name;
          }

          if( is_array( $array_cat ) ){
            $categories_string = implode(",",$array_cat);
          }

        }

        $image_post = $if_utils->IF_get_image('medium',null,$post->ID);
        $meta_facebook = '
<!-- open graph data -->
<meta property="og:title" content="'.get_the_title().'" />
<meta property="og:description" content="'.$content.'" />
<meta property="og:url" content="'.get_permalink().'" />
<meta property="og:type" content="website" />
<meta property="og:locale" content="'.get_locale().'" />
<meta property="og:image" content="'.$image_post['src'].'" />
<meta property="article:section" content="'.$categories_string.'" />
<meta property="og:site_name" content="'.get_bloginfo( 'name' ).'" />
';

      }

      if( isset( $ilen_seo->twitter_user ) && $ilen_seo->twitter_user ){
      $meta_twitter= '<!-- twitter card data -->
<meta name="twitter:card" content="summary" />
<meta name="twitter:site" content="@'.$ilen_seo->twitter_user.'" />
<meta name="twitter:title" content="'.get_the_title().'" />
<meta name="twitter:description" content="'.$content.'" />
';
      }

    }elseif( is_category() ){

      $category      = get_the_category();
      $category_desc = $category[0]->description;
      $category_name = ucfirst($category[0]->cat_name);
      $category_id   = $category[0]->cat_ID;

      if( isset( $ilen_seo->facebook_open_graph ) && $ilen_seo->facebook_open_graph ){

        $meta_facebook = '
<!-- open graph data -->
<meta property="og:title" content="'.($category_name).'" />
<meta property="og:description" content="'.$category_desc.'" />
<meta property="og:url" content="'.get_category_link( $category_id ).'" />
<meta property="og:type" content="website" />
<meta property="og:locale" content="'.get_locale().'" />
<meta property="article:section" content="'.($category_name).'" />
<meta property="og:site_name" content="'.get_bloginfo( 'name' ).'" />
';

      }

      if( isset( $ilen_seo->twitter_user ) && $ilen_seo->twitter_user ){
      $meta_twitter= '<!-- twitter card data -->
<meta name="twitter:card" content="summary" />
<meta name="twitter:site" content="@'.$ilen_seo->twitter_user.'" />
<meta name="twitter:title" content="'.$category_name.'" />
<meta name="twitter:description" content="'.$category_desc.'" />
';
      }

    }elseif( is_search() ){

      $meta_description = "";

    }elseif( is_day() ){

      $meta_description = "";

    }elseif( is_month() ){

      $meta_description = "";

    }elseif( is_year() ){

      $meta_description = "";

    }elseif( is_author() ){

      if( $des_aut = get_the_author_meta( 'description', $authordata->ID ) ){
        $meta_description = $des_aut;  
      }

      if( isset( $ilen_seo->facebook_open_graph ) && $ilen_seo->facebook_open_graph ){

        $meta_facebook = '
<!-- open graph data -->
<meta property="og:title" content="'.($authordata->display_name).'" />
<meta property="og:description" content="'.$meta_description.'" />
<meta property="og:url" content="'.get_author_posts_url( $authordata->ID ).'" />
<meta property="og:type" content="website" />
<meta property="og:locale" content="'.get_locale().'" />
<meta property="og:site_name" content="'.get_bloginfo( 'name' ).'" />
';

      }

      if( isset( $ilen_seo->twitter_user ) && $ilen_seo->twitter_user ){

      $meta_twitter= '<!-- twitter card data -->
<meta name="twitter:card" content="summary" />
<meta name="twitter:site" content="@'.$ilen_seo->twitter_user.'" />
<meta name="twitter:title" content="'.($authordata->display_name).'" />
<meta name="twitter:description" content="'.$meta_description.'" />
';
      }
      

    }elseif( is_404() ){

      $meta_description = "";

    }



    if( $meta_description ){

      $meta_description = '
<meta name="description" content="'.$meta_description.'" />';

    }

    if( isset($ilen_seo->google_publisher) && $ilen_seo->google_publisher ){

        $meta_google = '<!-- google publisher -->
<link href="'.$ilen_seo->google_publisher.'" rel="publisher" />
';

    }
 
echo "\n<!-- This site is optimized with the WordPress Bubble SEO  plugin v". 
$this->parameter['version'] .
"- https://wordpress.org/plugins/bubble-seo/  -->"
.$meta_description.$meta_keyword.$meta_facebook.$meta_twitter.$meta_google."<!-- /Bubble SEO -->\n\n";

  }




  function getFormatTitle(){

    global $ilen_seo, $authordata;

    $title_format = null;
    $blog         = get_bloginfo('name');
    $description  = get_bloginfo('description');
    $post         = "";
    $category     = "";
    $tag          = "";
    $day          = null;
    $monthnum     = null;
    $year         = null;
    $author       = "";
    $query        = "";
    $num          = "";


    if( get_query_var('page') || get_query_var('paged') ){

      $title_format = $ilen_seo->pagination_title_format;
      $num = get_query_var('page')?get_query_var('page'):get_query_var('paged');

    }elseif( is_home() ){

      $title_format = $ilen_seo->home_title;

    }elseif( is_singular() ){

      $post = get_the_title();
      $title_format = $ilen_seo->post_title_format;

    }elseif( is_category() ){

      $category = get_the_category();
      $category = $category[0]->cat_name;
      $title_format = $ilen_seo->category_title_format;

    }elseif( is_search() ){

      $title_format = $ilen_seo->search_title_format;

    }elseif( is_tag() ){

      $tag = ucfirst(single_tag_title("", false));
      $title_format = $ilen_seo->tag_title_format;

    }elseif( is_day() ){

      $year     = get_query_var('year');
      $monthnum = get_query_var('monthnum');
      $day      = get_query_var('day');
      $title_format = $ilen_seo->day_title_format;

    }elseif( is_month() ){

      $year     = get_query_var('year');
      $monthnum = get_query_var('monthnum');
      $day      = get_query_var('day');
      $title_format = $ilen_seo->month_title_format;

    }elseif( is_year() ){

      $year     = get_query_var('year');
      $monthnum = get_query_var('monthnum');
      $day      = get_query_var('day');
      $title_format = $ilen_seo->year_title_format;

    }elseif( is_author() ){

      $author = $authordata->display_name;
      $title_format = $ilen_seo->author_title_format;

    }elseif( is_404() ){

      $title_format = $ilen_seo->no_404_title_format;

    }

    $variables = array(
        '{blog}'                   => $blog
        , '{description}'          => $description
        , '{post}'                 => $post
        , '{category}'             => $category
        , '{tag}'                  => $tag
        , '{month}'                => $monthnum
        , '{year}'                 => $year
        , '{day}'                  => $day
        , '{author}'               => $author
        , '{query}'                => (get_search_query())
        , '{num}'                  => $num
    ); 
    
    $new_title = str_replace(array_keys($variables), array_values($variables), htmlspecialchars($title_format));
    return $new_title;

  }




  
  function script_and_style_admin(){
    if( isset($_GET["page"]) &&  $_GET["page"] == $this->parameter["id_menu"] ){
        wp_enqueue_script( 'admin-js-'.$this->parameter["name_option"], plugins_url('/assets/js/admin.js',__FILE__), array( 'jquery' ), $this->parameter['version'], true );
    }
  }


  function script_and_style_front(){
    // Register styles
    //wp_register_style( 'front-css-'.$this->parameter["name_option"], plugins_url('/assets/css/style.css',__FILE__),'all',$this->parameter['version'] );
    // Enqueue styles
    //wp_enqueue_style( 'front-css-'.$this->parameter["name_option"] );
    //wp_enqueue_script( 'front-js-'.$this->parameter["name_option"], plugins_url('/assets/js/jquery.equalizer.js',__FILE__), array( 'jquery' ), '1.2.5', true );

  }

 
 


} // end class
} // end if

 
global $IF_CONFIG;
unset($IF_CONFIG);
$IF_CONFIG = null;
$IF_CONFIG = new ilen_seo;

require_once "assets/ilenframework/core.php";
?>