<?php
/**
Plugin Name: Easy Order Posts
Plugin URI: http://matgargano.com
Description: Drag and Drop Post Order for All of Your Post Types
Author: Mat Gargano
Version: 1.0
Author URI: http://matgargano.com
*/



class orderposts {
    

    const PAGE_PREFIX = 'edit_order_';
    const TRANSLATE_DOMAIN = 'easy-order-posts';

    public static function init(){
        add_action( 'admin_menu', array( __CLASS__, 'add_menus' ) );
        add_action( 'admin_init', array( __CLASS__, 'enqueue' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

    }
    public static function register_settings(){
        $post_types = get_post_types();
        foreach($post_types as $post_type){
            register_setting( $post_type . '_order', $post_type . '_order', array(__CLASS__, 'post_type_order_save' ) );
        }
    }

    public static function post_type_order_save($option){
        $menu_order = json_decode($option['order']);
        $counter = 0;
        foreach($menu_order as $post_id) {
            self::set_menu_order($post_id, $counter);
            $counter++;
        }
        return $option;
    }

    public static function set_menu_order($post_id, $counter) {
        global $wpdb;   
        error_log('updating ' . $post_id . ' to ' . $counter);
        $wpdb->update( $wpdb->posts, array('menu_order' => $counter), array('ID' => $post_id ) );
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
            $title_name = get_post_type_object( $post_type )->labels->name;

            if ($post_type == 'post') { 
                add_submenu_page('edit.php', __( 'Order ' . $title_name, self::TRANSLATE_DOMAIN ), __( 'Order ' . $title_name, self::TRANSLATE_DOMAIN ), 'activate_plugins', self::PAGE_PREFIX . $post_type, array( __CLASS__, 'admin_page' ) );
            } else {
                add_submenu_page('edit.php?post_type=' . $post_type, __( 'Order ' . $title_name, self::TRANSLATE_DOMAIN ), __( 'Order ' . $post_type, self::TRANSLATE_DOMAIN ), 'activate_plugins', self::PAGE_PREFIX . $post_type, array( __CLASS__, 'admin_page' ) );
            }
        }
    }

    public static function admin_page(){
        (isset($_GET['post_type']) ) ? $post_type = $_GET['post_type'] : $post_type = 'post';

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
            <?php settings_fields($post_type . '_order'); ?>
            <?php $options = get_option($post_type . '_order'); ?>
            <?php $order=false;?>
            <?php if (!empty($options) && isset($options['order'])) {
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
