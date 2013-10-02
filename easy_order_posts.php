<?php
/**
Plugin Name: Easy Order Posts
Plugin URI: http://matgargano.com
Description: Drag and Drop Post Order for All of Your Post Types
Author: Mat Gargano
Version: 0.1
Author URI: http://matgargano.com
*/

/*
 * Filters:
 * easy_order_posts_post_types - modify the post types to apply this plugin to 
 * easy_order_posts_capability - modify the capability of user to be able to update post_type order
 *
 * Domain:
 * easy-order-posts
 *
 */

class easy_order_posts {
    

    const PAGE_PREFIX = 'edit_order_';
    const TRANSLATE_DOMAIN = 'easy-order-posts';
    
    /**
     * Set up the plugin action hooks
     *
     * @method init
     * @return void
     */

    public static function init(){
        add_action( 'admin_menu', array( __CLASS__, 'add_menus' ) );
        add_action( 'admin_init', array( __CLASS__, 'enqueue' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

    }

    /**
     * Allow this plugin to live either in the plugins directory or inside
     * the themes directory.
     *
     * @method plugins_url
     * @param type $relative_path
     * @param type $plugin_path
     * @return string
     */

    public static function register_settings(){
        $post_types = self::get_post_types();
        if ( is_array( $post_types ) ) {
            foreach( $post_types as $post_type ){
                register_setting( $post_type . '_order', $post_type . '_order', array(__CLASS__, 'post_type_order_save' ) );
            }
        }
    }

    /**
     * Helper function to get post types and apply the easy_order_posts_post_types filter
     *
     * @method get_post_types
     * @return void
     */    

    public static function get_post_types(){
        $post_types = get_post_types( '', 'names' );
        return apply_filters( 'easy_order_posts_post_types', $post_types );
    }

    /**
     * Sanitize callback for the option pages, updates the posts' menu_order in the posts table
     *
     * @method post_type_order_save
     * @param array $option
     * @return array
     */    

    public static function post_type_order_save( $option ){
        $menu_order = json_decode( $option['order'] );
        $counter = 0;
        foreach( $menu_order as $post_id ) {
            self::set_menu_order( $post_id, $counter );
            $counter++;
        }
        return $option;
    }

    /**
     * Helper function to update the menu_order on the posts table
     *
     * @method set_menu_order
     * @param int $post_id
     * @param int $counter
     * @return void
     */    


    public static function set_menu_order( $post_id, $counter ) {
        global $wpdb;   
        $wpdb->update( $wpdb->posts, array( 'menu_order' => $counter ), array( 'ID' => $post_id ) );
    }

    /**
     * Enqueue stylesheets and javascripts on the post order pages
     *
     * @method set_menu_order
     * @param int $post_id
     * @param int $counter
     * @return void
     */   

    public static function enqueue(){
        global $pagenow;
        $post_type = self::get_post_type();
        $page = self::get_page();
        if ( ( stripos( $page, self::PAGE_PREFIX ) !== false ) ) {
            wp_enqueue_style( 'easy_order_posts', self::plugins_url( 'css/easy_order_posts.css', __FILE__ ) );
            wp_enqueue_script( 'easy_order_posts', self::plugins_url( 'js/easy_order_posts.js', __FILE__ ), array( 'jquery-ui-sortable' ), false, true );
        }
    }

    /**
     * Helper function to get the post_type from the $_GET superglobal
     *
     * @method get_post_type
     * @return string post_type
     */   


    public static function get_post_type(){
        if ( !empty( $_GET['post_type'] ) ) {
            return $_GET['post_type'];
        }
    }

    /**
     * Helper function to get the page from the $_GET superglobal
     *
     * @method get_page
     * @return string page
     */   

    public static function get_page(){
        if ( !empty( $_GET['page'] ) ) {
            return $_GET['page'];
        }
    }

    /**
     * Function to add the 'Order Posts' menu item to each of the post types
     *
     * @method add_menus
     * @return void
     */   

    public static function add_menus(){
        $post_types = self::get_post_types();
        $capability = apply_filters( 'easy_order_posts_capability', 'activate_plugins' ) ;
        if ( is_array( $post_types ) ) {
            foreach( $post_types as $post_type ) {
                $title_name = get_post_type_object( $post_type )->labels->name;
                if ( $post_type === 'post' ) { 
                    add_submenu_page( 'edit.php', __( 'Order ' . $title_name, self::TRANSLATE_DOMAIN ), __( 'Order ' . $title_name, self::TRANSLATE_DOMAIN ), $capability, self::PAGE_PREFIX . $post_type, array( __CLASS__, 'admin_page' ) );
                } else {
                    add_submenu_page( 'edit.php?post_type=' . $post_type, __( 'Order ' . $title_name, self::TRANSLATE_DOMAIN ), __( 'Order ' . $post_type, self::TRANSLATE_DOMAIN ), $capability, self::PAGE_PREFIX . $post_type, array( __CLASS__, 'admin_page' ) );
                }
            }
        }
    }

    /**
     * Function to generate the 'Order Posts' pages for each post type
     *
     * @method add_menus
     * @return void
     */   

    public static function admin_page(){
        ( isset( $_GET['post_type'] ) ) ? $post_type = $_GET['post_type'] : $post_type = 'post';

        $posts = new WP_Query( array( 'post_type'=>$post_type, 'posts_per_page'=> -1, 'orderby' => 'menu_order', 'order' => 'asc' ) );
        $title_name = get_post_type_object( $post_type )->labels->name;
        
        ?>
        <div class="wrap">
            <h2>Order <?php echo $title_name; ?></h2>
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
                <form method="post" action="options.php">
                <?php settings_fields( $post_type . '_order' ); ?>
                <?php $options = get_option( $post_type . '_order' ); ?>
                <?php $order=false;?>
                <?php if ( !empty( $options ) && isset( $options['order'] ) ) {
                    $order=$options['order'];
                }?>
                    <input type="hidden" name="<?php echo $post_type?>_order[post_type]" value="<?php echo $post_type; ?>" />
                    <input type="hidden" class="order" name="<?php echo $post_type?>_order[order]" value="<?php echo $order; ?>" />
                    <input type="submit" class="button-primary" value="Save Sort">
                </form>
            </div>
        </div>

        <?php
        
    }  

    /**
     * Allow this plugin to live either in the plugins directory or inside
     * the themes directory.
     *
     * @method plugins_url
     * @param type $relative_path
     * @param type $plugin_path
     * @return string
     */


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


easy_order_posts::init();
