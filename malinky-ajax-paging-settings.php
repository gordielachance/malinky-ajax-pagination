<?php

class Malinky_Ajax_Paging_Settings
{
    public function __construct()
    {
        // Add page.
        add_action( 'admin_menu', array( $this, 'malinky_ajax_paging_settings_add_page' ) );
        // Set up sections and fields.
        add_action( 'admin_init', array( $this, 'malinky_ajax_paging_settings_init' ) );
    }

    /**
     * Return the number of settings pages saved in the database.
     *
     * @return int
     */
    public function malinky_ajax_paging_settings_count_settings()
    {
        for ( $x = 1; ; $x++ ) {
            if ( ! get_option( '_malinky_ajax_paging_settings_' . $x ) ) return --$x;
        }
    }

    /**
     * Get the current settings page number.
     *
     * @return int
     */
    public function malinky_ajax_paging_settings_current_page_number()
    {
        return substr( strrchr( $_GET['page'], '-' ), 1 );
    }  

    /**
     * Get the next settings page number.
     *
     * @return int
     */
    public function malinky_ajax_paging_settings_new_page_number()
    {
        $total_pages = $this->malinky_ajax_paging_settings_count_settings();
        return ++$total_pages;
    }
    
    /**
     * Delete a settings page.
     *
     * @return void
     */
    private function malinky_ajax_paging_settings_delete( $page_number )
    {
        $all_settings = array();
        $total_settings = $this->malinky_ajax_paging_settings_count_settings();
        for ( $x = 1; $x <= $total_settings; $x++ ) {
            // Add remaining settings to a new array.
            // Delete each option.
            if ( $x != $page_number ) { 
                $all_settings[ $x ] = get_option( '_malinky_ajax_paging_settings_' . $x );
            }
            delete_option( '_malinky_ajax_paging_settings_' . $x );
        }

        // Renumber array keys.
        $all_settings = array_combine( range( 1, count( $all_settings ) ), array_values( $all_settings ) );
        
        // Save new settings.
        foreach ( $all_settings as $key => $value ) {
            add_option( '_malinky_ajax_paging_settings_' . $key, $value );
        }

        //Redirect.
        wp_redirect('options-general.php?page=malinky-ajax-paging-settings-1');
    }

    /**
     * Add an options page. Called from admin_menu action in __construct.
     *
     * @return void
     */
    public function malinky_ajax_paging_settings_add_page()
    {
        for ( $x = 1; $x <= $this->malinky_ajax_paging_settings_new_page_number(); $x++ ) {

            /**
             * add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
             * 
             * @link http://codex.wordpress.org/Function_Reference/add_submenu_page
             */
            
            // Only show first page in menu.
            $show_in_menu = $x == 1 ? 'options-general.php' : null;

            add_submenu_page(
                $show_in_menu,
                'AJAX Pagination Setting',
                'AJAX Pagination Settings',
                'manage_options',
                'malinky-ajax-paging-settings-' . $x,
                array( $this, 'malinky_ajax_paging_settings_add_page_output_callback' )
            );
        }
    }

    /**
     * Callback which outputs the option page content.
     *
     * @return void   
     */
    public function malinky_ajax_paging_settings_add_page_output_callback()
    {   
        // Check if deleting.
        if ( isset( $_GET['delete'] ) ) {
            if ( is_numeric( $_GET['delete'] ) ) {
                $this->malinky_ajax_paging_settings_delete( $_GET['delete'] );
            } else {
                wp_redirect('options-general.php?page=malinky-ajax-paging-settings-1');
            }
        }

        $total_settings = $this->malinky_ajax_paging_settings_count_settings(); ?>

        <div class="wrap malinky-ajax-paging">
            <h2>AJAX Paging Settings</h2>
            <?php for ( $x = 1; $x <= $total_settings; $x++ ) {
                if ( $x == 1 ) { ?>
                    <hr />
                    <h3>Saved Settings</h3>
                <?php } ?>
                <a href="options-general.php?page=malinky-ajax-paging-settings-<?php echo $x ?>" class="malinky-ajax-paging-saved-settings<?php echo $_GET['page'] == 'malinky-ajax-paging-settings-' . $x ? ' active' : '';?>">Paging Settings <?php echo $x; ?></a>
                <?php if ( $x < $total_settings ) { ?> | <?php }
                if ( $x == $total_settings ) { ?>
                    <hr />
                <?php } ?>
            <?php } ?>
            <form action="options.php" method="post">
                <?php settings_fields( $_GET['page'] ); ?>
                <?php do_settings_sections( $_GET['page'] ); ?>
                <hr />
                <?php if ( $total_settings >= 1 ) { ?>
                    <a href="options-general.php?page=malinky-ajax-paging-settings-<?php echo $this->malinky_ajax_paging_settings_new_page_number(); ?>" class="malinky-ajax-paging-add-button button button-primary">Add New</a>
                <?php  } ?>
                <?php if ( $total_settings > 1 ) { ?>
                    <a href="options-general.php?page=malinky-ajax-paging-settings-1&delete=<?php echo $this->malinky_ajax_paging_settings_current_page_number(); ?>&noheader=true" class="malinky-ajax-paging-add-button button button-primary button-delete">Delete</a>
                <?php  } ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register settings and set up sections and fields. Called from admin_init action.
     *
     * register_setting()
     * add_settings_section()
     * add_settings_field()
     *
     * @return void   
     */
    public function malinky_ajax_paging_settings_init()
    {
        // Populate defaults.
        $malinky_ajax_paging_theme_defaults = malinky_ajax_paging_theme_defaults();

        $total_settings = $this->malinky_ajax_paging_settings_count_settings(); 

        for ( $x = 1; $x <= $this->malinky_ajax_paging_settings_new_page_number(); $x++ ) {

            register_setting(
                'malinky-ajax-paging-settings-' . $x,
                '_malinky_ajax_paging_settings_' . $x
                //'malinky_settings_validation_callback' 
            );

            /* ------------------------------------------------------------------------ *
             * Sections
             * ------------------------------------------------------------------------ */

            add_settings_section(
                'wrapper_settings',
                'Wrapper Settings',
                array( $this, 'malinky_ajax_paging_settings_wrapper_message' ),
                'malinky-ajax-paging-settings-' . $x
            );

            add_settings_section(
                'pagination_type_settings',
                'Pagination Type Settings',
                array( $this, 'malinky_ajax_paging_settings_pagination_type_message' ),
                'malinky-ajax-paging-settings-' . $x
            );

            add_settings_section(
                'loader_settings',
                'Loader Settings',
                array( $this, 'malinky_ajax_paging_settings_loader_message' ),
                'malinky-ajax-paging-settings-' . $x
            );

            add_settings_section(
                'load_more_button_settings',
                'Load More Button Settings',
                array( $this, 'malinky_ajax_paging_settings_load_more_button_message' ),
                'malinky-ajax-paging-settings-' . $x
            );

            /* ------------------------------------------------------------------------ *
             * Wrapper Settings
             * ------------------------------------------------------------------------ */

            add_settings_field(
                'theme_defaults',
                'Theme Defaults',
                array( $this, 'malinky_settings_select_field' ), 
                'malinky-ajax-paging-settings-' . $x,
                'wrapper_settings',
                array(
                    'option_name'               => '_malinky_ajax_paging_settings_' . $x,        
                    'option_id'                 => 'theme_defaults',
                    'option_default'            => 'Twenty Fifteen',
                    'option_field_type_options' => malinky_ajax_paging_theme_default_names(),
                    'option_small'              => 'Select from popular themes or overwrite the settings below yourself.'
                )
            );

            add_settings_field(
                'posts_wrapper',
                'Posts Selector',
                array( $this, 'malinky_settings_text_field' ),
                'malinky-ajax-paging-settings-' . $x,
                'wrapper_settings',
                array(
                    'option_name'       => '_malinky_ajax_paging_settings_' . $x,
                    'option_id'         => 'posts_wrapper',
                    'option_default'    => $malinky_ajax_paging_theme_defaults['Twenty Fifteen']['posts_wrapper'],
                    'option_small'      => 'The selector that wraps all of the posts/products.'
                )
            );

            add_settings_field(
                'post_wrapper',
                'Post Selector',
                array( $this, 'malinky_settings_text_field' ),
                'malinky-ajax-paging-settings-' . $x,
                'wrapper_settings',
                array(
                    'option_name'       => '_malinky_ajax_paging_settings_' . $x,
                    'option_id'         => 'post_wrapper',
                    'option_default'    => $malinky_ajax_paging_theme_defaults['Twenty Fifteen']['post_wrapper'],
                    'option_small'      => 'The selector of an individual post/product.'
                )
            );

            add_settings_field(
                'pagination_wrapper',
                'Navigation Selector',
                array( $this, 'malinky_settings_text_field' ),
                'malinky-ajax-paging-settings-' . $x,
                'wrapper_settings',
                array(
                    'option_name'       => '_malinky_ajax_paging_settings_' . $x,
                    'option_id'         => 'pagination_wrapper',
                    'option_default'    => $malinky_ajax_paging_theme_defaults['Twenty Fifteen']['pagination_wrapper'],
                    'option_small'      => 'The selector of the post/product navigation.'
                )
            );

            add_settings_field(
                'next_page_selector',
                'Next Selector',
                array( $this, 'malinky_settings_text_field' ),
                'malinky-ajax-paging-settings-' . $x,
                'wrapper_settings',
                array(
                    'option_name'       => '_malinky_ajax_paging_settings_' . $x,
                    'option_id'         => 'next_page_selector',
                    'option_default'    => $malinky_ajax_paging_theme_defaults['Twenty Fifteen']['next_page_selector'],
                    'option_small'      => 'The selector of the navigation next link.'
                )
            );

            /* ------------------------------------------------------------------------ *
             * Pagination Type Settings
             * ------------------------------------------------------------------------ */

            add_settings_field(
                'paging_type',
                'Paging Type',
                array( $this, 'malinky_settings_select_field' ),
                'malinky-ajax-paging-settings-' . $x,
                'pagination_type_settings',
                array(
                    'option_name'       => '_malinky_ajax_paging_settings_' . $x,
                    'option_array'      => $x,
                    'option_id'         => 'paging_type',
                    'option_default'    => 'load-more',
                    'option_field_type_options' => array(
                        'infinite-scroll'   => 'Infinite Scroll',
                        'load-more'         => 'Load More Button',
                        'pagination'        => 'Pagination'
                    ),
                    'option_small'      => 'Choose a pagination type.<br />Infinite Scroll automatically loads new posts/products as the user scrolls to the bottom of the screen.<br />Load More Button displays a single button at the bottom of the posts/products that when clicked loads new posts/products.<br />Pagination displays the themes standard pagination but new posts/products are loaded via ajax.'
                )
            );

            add_settings_field(
                'infinite_scroll_buffer',
                'Infinite Scroll Buffer (px)',
                array( $this, 'malinky_settings_text_field' ),
                'malinky-ajax-paging-settings-' . $x,
                'pagination_type_settings',
                array(
                    'option_name'       => '_malinky_ajax_paging_settings_' . $x,  
                    'option_id'         => 'infinite_scroll_buffer',
                    'option_default'    => '20',
                    'option_small'      => 'The higher the buffer the earlier, during scrolling, additional posts/products will be loaded.<br /><em>Only used when Infinite Scroll is selected as the paging type.</em>'
                )
            );

            /* ------------------------------------------------------------------------ *
             * Loader Settings
             * ------------------------------------------------------------------------ */

            add_settings_field(
                'ajax_loader',
                'AJAX Loader',
                array( $this, 'malinky_settings_ajax_loader_field' ),
                'malinky-ajax-paging-settings-' . $x,
                'loader_settings',
                array(
                    'option_name'       => '_malinky_ajax_paging_settings_' . $x,
                    'option_id'         => 'ajax_loader',
                    'option_default'    => 'default',
                    'option_small'      => ''
                )
            );

            /* ------------------------------------------------------------------------ *
             * Load More Button Settings
             * ------------------------------------------------------------------------ */

            add_settings_field(
                'load_more_button_text',
                'Load More Button Text',
                array( $this, 'malinky_settings_text_field' ),
                'malinky-ajax-paging-settings-' . $x,
                'load_more_button_settings',
                array(
                    'option_name'       => '_malinky_ajax_paging_settings_' . $x,
                    'option_id'         => 'load_more_button_text',
                    'option_default'    => 'Load More Posts',
                    'option_small'      => 'Change the button text.'
                )
            );

            add_settings_field(
                'loading_more_posts_text',
                'Loading More Posts Text',
                array( $this, 'malinky_settings_text_field' ),
                'malinky-ajax-paging-settings-' . $x,
                'load_more_button_settings',
                array(
                    'option_name'       => '_malinky_ajax_paging_settings_' . $x,    
                    'option_id'         => 'loading_more_posts_text',
                    'option_default'    => 'Loading...' ,
                    'option_small'      => 'Change the text that is displayed on the button while new posts/products are being loaded.'
                )
            );

        }
    }

    /* ------------------------------------------------------------------------ *
     * Section Messages
     * ------------------------------------------------------------------------ */

    public function malinky_ajax_paging_settings_wrapper_message()
    {
        echo 'These options allow you to set the selectors that contain your posts/products and pagination.<br />If you use a popular theme it may be listed in the Theme Defaults, if not just overwrite the settings.<br /><em>Include a leading . before the selector names.</em>';
    }

    public function malinky_ajax_paging_settings_pagination_type_message()
    {
    	echo 'These options control the type of pagination used.';
    }

    public function malinky_ajax_paging_settings_loader_message()
    {
        echo 'This option allows you to upload a new preloader .gif.';
    }

    public function malinky_ajax_paging_settings_load_more_button_message()
    {
        echo 'These options allow you to override the button text if Load More Button is selected as the paging type.';
    }

    /**
     * Display a select field.
     *
     * @param  arr $args    option_name,
     *                      option_id,
     *                      option_default,
     *                      option_field_type_options
     * 
     * @return  void
     */
    public function malinky_settings_select_field( $args )
    {
    	$html = '';
        $html .= '<select id="' . $args['option_id'] . '" name="' . $args['option_name'] . '[' . $args['option_id'] . ']">';
    	$options = get_option ( $args['option_name'] );
    	foreach ( $args['option_field_type_options'] as $key => $value ) {
    		//Set selected, if value is already set
    		if ( isset( $options[ $args['option_id'] ] ) && $options[ $args['option_id'] ] == $key ) {
    			$selected = 'selected';
    		} else {
    			$selected = '';
    		}
    		//Set default if value is not set
    		if ( ! isset( $options[ $args['option_id'] ] ) ) {
    			$selected = $args['option_default'] == $key ? 'selected' : '';
    		}
    		$html .= '<option id="' . $args['option_id'] . '" name="' . $args['option_name'] . '[' . $args['option_id'] . ']" value="' . esc_attr( $key ) . '"' . $selected . '/>' . esc_html( $value ) . '</option>';
    	}
    	$html .= '</select><br /><small>' . $args['option_small'] . '</small>';
    	echo $html;
    }

    /**
     * Output a text field.
     *
     * @param   arr $args   option_name,
     *                      option_id,
     *                      option_default
     * 
     * @return  void
     */
    public function malinky_settings_text_field( $args )
    {
    	$options = get_option( $args['option_name'] );
    	$html = '';
    	$html .= '<input type="text" id="' . $args['option_id'] . '" name="' . $args['option_name'] . '[' . $args['option_id'] . ']" value="' . ( isset( $options[ $args['option_id'] ] ) ? esc_attr( $options[ $args['option_id'] ] ) : $args['option_default'] )  . '" placeholder="" size="30" /><br /><small>' . $args['option_small'] . '</small>';
    	echo $html;
    }

    /**
     * Output the ajax loader upload field. Uses WP Media Uploader.
     * Check if a user has uploaded an ajax loader and it is a valid image.
     * If not then use the default loader in the plugin folder.    
     *
     * @param   arr $args   option_name,
     *                      option_id,
     *                      option_default
     * 
     * @return  void
     */
    public function malinky_settings_ajax_loader_field( $args )
    {
        $ajax_loader_img = '';
        $options = get_option( $args['option_name'] );
    
        if ( isset( $options[ $args['option_id'] ] ) && $options[ $args['option_id'] ] != $args['option_default'] && wp_get_attachment_image( esc_attr( $options[ $args['option_id'] ] ) ) != '' ) {
            $img_attr = array(
                'class' => 'malinky-ajax-paging-ajax-loader',
                'alt'   => 'AJAX Loader',
            );        
            $ajax_loader_img = wp_get_attachment_image( esc_attr( $options[ $args['option_id'] ] ), 'thumbnail', false, $img_attr );
        } else {
            $ajax_loader_img = '<img src="' . MALINKY_AJAX_PAGING_PLUGIN_URL . '/img/loader.gif" class="malinky-ajax-paging-ajax-loader" alt="AJAX Loader" />';
            $options[ $args['option_id'] ] = 'default';
        }
        
        $html = '';
        $html .= $ajax_loader_img;
        $html .= '<p><a href="javascript:;" id="' . $args['option_id'] . '_button' . '">Upload AJAX Loader</a> | ';
        $html .= '<a href="' . MALINKY_AJAX_PAGING_PLUGIN_URL . '/img/loader.gif" id="' . $args['option_id'] . '_remove' . '">Use Original AJAX Loader</a></p>';
        $html .= '<input type="hidden" id="' . $args['option_id'] . '" name="' . $args['option_name'] . '[' . $args['option_id'] . ']" value="' . ( isset( $options[ $args['option_id'] ] ) ? esc_attr( $options[ $args['option_id'] ] ) : $args['option_default'] )  . '" /><br /><small>' . $args['option_small'] . '</small>';  
        echo $html;
   }
}