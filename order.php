<?php
/**
Plugin Name: ORDER POSTS
Plugin URI: http://matgargano.com
Description: Drag and Drop Post Order for All of Your Post Types
Author: Mat Gargano
Version: 1.0
Author URI: http://matgargano.com
*/



class orderposts {
    

    const PAGE_PREFIX = 'edit_order_';

    public static function init(){
        add_action( 'admin_menu', array( __CLASS__, 'add_menus' ) );
        add_action( 'admin_init', array( __CLASS__, 'enqueue' ) );

    }

    public static function enqueue(){
        global $pagenow;
        $post_type = self::get_post_type();
        $page = self::get_page();
        if ( ( stripos( $page, self::PAGE_PREFIX ) !== false ) ) {
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_style( 'postorder', self::plugins_url( 'css/postorder.css', __FILE__ ) );
            wp_enqueue_script( 'postorder', self::plugins_url( 'js/postorder.js', __FILE__ ), array( 'jquery-ui-sortable' ), false, true );
        }
    }

    public static function get_post_type(){
        if ( !empty($_GET['post_type']) ) {
            return $_GET['post_type'];
        }
    }

    public static function get_page(){
        if ( !empty($_GET['page']) ) {
            return $_GET['page'];
        }
    }

    public static function add_menus(){
        $post_types = get_post_types();
        foreach($post_types as $post_type) {
            add_submenu_page('edit.php?post_type=' . $post_type, 'Order ' . $post_type, 'Order ' . $post_type, 'activate_plugins', self::PAGE_PREFIX . $post_type, array( __CLASS__, 'admin_page' ) );
        }
    }

    public static function admin_page(){
        $post_type = $_GET['post_type'];
        $posts = new WP_Query( array( 'post_type'=>$post_type, 'posts_per_page'=> -1 ) );
        ?>
        <div class="wrap">
            <h2>Hello</h2>
            <div class="post-list-wrap">
                <ul id="post-list">
                <?php
                    if ( $posts->have_posts() ) :
                        while ( $posts->have_posts() ) :
                            $posts->the_post();
                            ?>
                                 <li data-id="<?php the_ID(); ?>"><?php the_title(); ?></li>
                            <?php
                        endwhile;
                    endif;


                ?>                
                </ul>
            <input type="text" class="order">
        </div>
            

        </div>

        <?php
        
    }  

        public static function plugins_url( $relative_path, $plugin_path ) {
            $template_dir = get_template_directory();

            foreach (array( 'template_dir', 'plugin_path' ) as $var) {
            $$var = str_replace( '\\', '/', $$var ); // sanitize for Win32 installs
            $$var = preg_replace( '|/+|', '/', $$var );
            }
            if ( 0 === strpos( $plugin_path, $template_dir ) ) {
            $url = get_template_directory_uri();
            $folder = str_replace( $template_dir, '', dirname( $plugin_path ) );
            if ( '.' != $folder ) {
            $url .= '/' . ltrim( $folder, '/' );
            }
            if ( !empty( $relative_path ) && is_string( $relative_path ) && strpos( $relative_path, '..' ) === false ) {
            $url .= '/' . ltrim( $relative_path, '/' );
            }
            return $url;
            } else {
            return plugins_url( $relative_path, $plugin_path );
        }
}  

}

orderposts::init();
