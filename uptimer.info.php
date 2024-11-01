<?php
/*
  Plugin Name: UpTimer
  Plugin URI: https://uptimer.info
  Description: The UpTimer plugin for WordPress lets you see and analyse your website uptime and pagespeed on your dashboard and in details in the plugin's pages 
  Version: 0.0.1
  Author: Pavel Petrov
  Author URI: http://www.webbamboo.net
  License: GPL V3
 */
class UpTimer_Info {

    private static $instance = null;
    private $plugin_path;
    private $plugin_url;
    private $text_domain = 'uptimer';
    private $options;

    /**
     * Creates or returns an instance of this class.
     */
    public static function get_instance() {
        // If an instance hasn't been created and set to $instance create an instance and set it to $instance.
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Initializes the plugin by setting localization, hooks, filters, and administrative functions.
     */
    private function __construct() {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);
        load_plugin_textdomain($this->text_domain, false, $this->plugin_path . '\lang');
        add_action('admin_enqueue_scripts', array($this, 'register_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'register_styles'));
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'register_styles'));
        //Register the Dashboard Widget
        add_action( 'wp_dashboard_setup', array($this, 'uptimer_dashboard_widgets') );
        //Menu page
        add_action( 'admin_menu', array($this, 'uptimer_menu') );
        //Settings
        add_action( 'admin_init', array( $this, 'uptimer_init' ) );
          


        register_activation_hook(__FILE__, array($this, 'activation'));
        register_deactivation_hook(__FILE__, array($this, 'deactivation'));
        $this->run_plugin();
    }
    public function uptimer_init()
    {  
        register_setting(
            'uptimer_option_group', // Option group
            'uptimer_info_options', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'General Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'uptimer-info' // Page
        );  

        add_settings_field(
            'api_key', // ID
            'API Key', // Title 
            array( $this, 'api_key_callback' ), // Callback
            'uptimer-info', // Page
            'setting_section_id' // Section           
        );    
    }

    public function get_plugin_url() {
        return $this->plugin_url;
    }

    public function get_plugin_path() {
        return $this->plugin_path;
    }

    /**
     * Place code that runs at plugin activation here.
     */
    public function activation() {
        
    }

    /**
     * Place code that runs at plugin deactivation here.
     */
    public function deactivation() {
        
    }

    /**
     * Enqueue and register JavaScript files here.
     */
    public function register_scripts() {
        
    }

    /**
     * Enqueue and register CSS files here.
     */
    public function register_styles() {
        
    }

    /**
     * Place code for your plugin's functionality here.
     */
    private function run_plugin() {
        
    }
    
    public function uptimer_dashboard_widgets() {

        wp_add_dashboard_widget(
                'uptimer_dashboard_widget', // Widget slug.
                'UpTimer.info', // Title.
                array($this, 'uptimer_widget_function') // Display function.
        );
        // Globalize the metaboxes array, this holds all the widgets for wp-admin
 
 	global $wp_meta_boxes;

        // Get the regular dashboard widgets array 
        // (which has our new widget already but at the end)

        $normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];

        // Backup and delete our new dashboard widget from the end of the array

        $example_widget_backup = array('uptimer_dashboard_widget' => $normal_dashboard['uptimer_dashboard_widget']);
        unset($normal_dashboard['uptimer_dashboard_widget']);

        // Merge the two arrays together so our widget is at the beginning

        $sorted_dashboard = array_merge($example_widget_backup, $normal_dashboard);

        // Save the sorted array back into the original metaboxes 

        $wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
    }
    
    public function uptimer_widget_function() {
        $this->options = get_option( 'uptimer_info_options', array('api_key' => '') );
        if(empty($this->options['api_key']))
        {
            // Display whatever it is you want to show.
            echo 'Please Enter your API key <a href="'.admin_url( 'admin.php?page=uptimer-settings' ).'">here</a>';
        }
        else
        {
            $url = 'https://www.uptimer.info/api/v1/websites';
            $response = $this->apiGet($url);
            ?>
            <h3>Monitors</h3>
            <table class="wp-list-table widefat fixed striped users">
                            <thead>
                                <tr>
                                    <th scope="col" class="manage-column column-primary">
                                        Website
                                    </th>
                                    <th scope="col" class="manage-column">
                                        Status
                                    </th>
                                    <th scope="col" class="manage-column">
                                        Up Since
                                    </th>	
                                </tr>
                            </thead>

                            <tbody id="the-list" data-wp-lists="list:user">
                                <?php foreach($response as $monitor):?>
                                <tr id="user-1">
                                    <td class="has-row-actions column-primary" data-colname="Website">
                                        <strong>
                                            <a href="<?php echo $monitor->name; ?>" target="_blank"><?php echo $monitor->name; ?></a>
                                        </strong><br>
                                        <div class="row-actions">
                                            <span class="view">
                                                <a href="<?php echo admin_url('admin.php?page=uptimer-websites&websiteid='.$monitor->id);?>">View</a>
                                            </span>
                                        </div>
                                    </td>
                                    <td><?php if($monitor->lateststatus == 'Online'):?><span class="dashicons dashicons-smiley"></span> Online<?php else: ?><span class="dashicons dashicons-thumbs-down"></span> Offline<?php endif; ?></td>
                                    <td><?php echo $this->transformDate($monitor->upsince); ?></td>
                                 
                                </tr>	
                                <?php endforeach;?>
                            </tbody>

                            <tfoot>
                                <th scope="col" class="manage-column column-primary">
                                        Website
                                    </th>
                                    <th scope="col" class="manage-column">
                                        Status
                                    </th>
                                    <th scope="col" class="manage-column">
                                        Up Since
                                    </th>
                            </tfoot>

                        </table>
            <?php
        }
    }
    
    private function transformDate($dateString){
        $ymd = str_replace(array('T'), array(' '), $dateString);
        $DT = \DateTime::createFromFormat('Y-m-d H:i:sT', $ymd);
        return $DT->format('d-m-Y H:i:s');
    }
    
    public function uptimer_menu() {
        add_menu_page( 'UpTimer.info Options', 'UpTimer.info', 'manage_options', 'uptimer-websites', array($this, 'uptimer_websites_page'), plugins_url( 'favicon.ico', __FILE__ ));
        add_submenu_page ( 'uptimer-websites', 'UpTimer Settings', 'Settings', 'manage_options', 'uptimer-settings', array($this, 'uptimer_settings_menu') );
    }
    
    public function uptimer_websites_page() {
        $this->options = get_option( 'uptimer_info_options', array('api_key' => '') );
        if(empty($this->options['api_key']))
        {
            // Display whatever it is you want to show.
            echo '<div id="wpwrap">';
            echo 'Please Enter your API key <a href="'.admin_url( 'admin.php?page=uptimer-settings' ).'">here</a>';
            echo '</div>';
        }
        else
        {
            if(!isset($_GET['websiteid']))
            {
                $this->viewAll();
            }
            else
            {
                $this->viewSingle($_GET['websiteid']);
            }
        }
        
    }
    
    public function viewSingle($id) {
        $url = 'https://www.uptimer.info/api/v1/websites/'.$id;
        $response = $this->apiGet($url);
        
        $pagespeeds = $this->apiGet('https://www.uptimer.info/api/v1/pagespeeds/'.$id, 50);
        
        $pings = $this->apiGet('https://www.uptimer.info/api/v1/pings/'.$id, 50);

        ?>
            <div id="wpwrap">
                <h3><?php echo $response->name; ?></h3>
                <div id="col-container">
                    <div id="col-right">
                        <div class="col-wrap">
                            <div class="card">
                            <h3>Google Pagespeed History ( Last <?php echo count($pagespeeds) < 50 ? count($pagespeeds) : 50; ?> )</h3>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th scope="col" class="manage-column column-primary">
                                            Desktop
                                        </th>
                                        <th scope="col" class="manage-column">
                                            Mobile
                                        </th>
                                        <th scope="col" class="manage-column">
                                            Check date
                                        </th>	
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach($pagespeeds as $pagespeed):?>
                                    <tr>
                                        <td class="has-row-actions column-primary" data-colname="Pagespeed">
                                            <?php echo $pagespeed->desktop; ?>
                                        </td>
                                        <td><?php echo $pagespeed->mobile; ?></td>
                                        <td><?php echo $this->transformDate($pagespeed->datetime); ?></td>

                                    </tr>	
                                    <?php endforeach;?>
                                </tbody>

                                <tfoot>
                                    <th scope="col" class="manage-column column-primary">
                                        Desktop
                                    </th>
                                    <th scope="col" class="manage-column">
                                        Mobile
                                    </th>
                                    <th scope="col" class="manage-column">
                                        Check date
                                    </th>	
                                </tfoot>

                            </table>
                            </div>
                        </div>
                    </div>
                    <div id="col-left">
                        <div class="col-wrap">
                            <div class="card">
                                <ul>
                                    <li>
                                        <span><strong>Status: </strong></span> 
                                        <?php if ($response->lateststatus == 'Online'): ?><span class="dashicons dashicons-smiley"></span> Online<?php else: ?><span class="dashicons dashicons-thumbs-down"></span> Offline<?php endif; ?>
                                    </li>
                                    <li>
                                        <span><strong>Up Since: </strong></span> 
                                        <?php echo $this->transformDate($response->upsince); ?>
                                    </li>
                                    <li>
                                        <span><strong>Check interval: </strong></span> 
                                        <?php echo $response->timeout; ?>
                                    </li>
                                    <?php if($response->loadtime): ?>
                                    <li>
                                        <span><strong>Latest loadtime: </strong></span> 
                                        <?php echo $response->latestloadtime; ?>
                                    </li>
                                    <?php endif;?>
                                </ul>
                                <a href="<?php echo admin_url("admin.php?page=uptimer-websites"); ?>" class="button-primary">Go back</a>
                            </div>
                            <div class="card">
                                <h3>Monitor Checks History ( Last <?php echo count($pings) < 50 ? count($pings) : 50; ?> )</h3>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="manage-column column-primary">
                                                HTTP Code
                                            </th>
                                            <th scope="col" class="manage-column">
                                                Status
                                            </th>
                                            <th scope="col" class="manage-column">
                                                TimeStamp
                                            </th>	
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach($pings as $ping):?>
                                        <tr>
                                            <td class="has-row-actions column-primary" data-colname="Pagespeed">
                                                <?php echo $ping->responsecode; ?>
                                            </td>
                                            <td><?php if ($ping->online): ?><span class="dashicons dashicons-smiley"></span> Online<?php else: ?><span class="dashicons dashicons-thumbs-down"></span> Offline<?php endif; ?></td>
                                            <td><?php echo $this->transformDate($ping->datetime); ?></td>

                                        </tr>	
                                        <?php endforeach;?>
                                    </tbody>

                                    <tfoot>
                                        <th scope="col" class="manage-column column-primary">
                                                HTTP Code
                                            </th>
                                            <th scope="col" class="manage-column">
                                                Status
                                            </th>
                                            <th scope="col" class="manage-column">
                                                TimeStamp
                                            </th>	
                                    </tfoot>

                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        <?php
    }
    
    public function viewAll() {
        $url = 'https://www.uptimer.info/api/v1/websites';
        $response = $this->apiGet($url);
            ?>
            <div id="wpwrap">
                <h3>Monitors</h3>
                <table class="wp-list-table widefat fixed striped users">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-primary">
                                Website
                            </th>
                            <th scope="col" class="manage-column">
                                Status
                            </th>
                            <th scope="col" class="manage-column">
                                Up Since
                            </th>	
                        </tr>
                    </thead>

                    <tbody id="the-list" data-wp-lists="list:user">
                        <?php foreach ($response as $monitor): ?>
                            <tr id="user-1">
                                <td class="has-row-actions column-primary" data-colname="Website">
                                    <strong>
                                        <a href="<?php echo $monitor->name; ?>" target="_blank"><?php echo $monitor->name; ?></a>
                                    </strong><br>
                                    <div class="row-actions">
                                        <span class="view">
                                            <a href="<?php echo admin_url('admin.php?page=uptimer-websites&websiteid='.$monitor->id);?>">View</a>
                                        </span>
                                    </div>
                                </td>
                                <td><?php if ($monitor->lateststatus == 'Online'): ?><span class="dashicons dashicons-smiley"></span> Online<?php else: ?><span class="dashicons dashicons-thumbs-down"></span> Offline<?php endif; ?></td>
                                <td><?php echo $this->transformDate($monitor->upsince); ?></td>

                            </tr>	
                        <?php endforeach; ?>
                    </tbody>

                    <tfoot>
                    <th scope="col" class="manage-column column-primary">
                        Website
                    </th>
                    <th scope="col" class="manage-column">
                        Status
                    </th>
                    <th scope="col" class="manage-column">
                        Up Since
                    </th>
                    </tfoot>

                </table>
            </div>

            <?php
    }
    
    private function apiGet($url, $limit=false){
        $limitArg = $limit ? '&limit='.$limit : '';
        $this->options = get_option( 'uptimer_info_options', array('api_key' => '') );
        $apiKey = $this->options['api_key'];
        if(empty($this->options['api_key']))
        {
            return false;
        }

        $curl = curl_init();
        // Set target URL
        curl_setopt($curl, CURLOPT_URL, $url."?apikey=".$apiKey.$limitArg);
        // Set the desired HTTP method (GET is default, see the documentation for each request)
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        return json_decode(curl_exec($curl));
    }
    
    public function uptimer_settings_menu() {
        $this->options = get_option( 'uptimer_info_options' );
        if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
        ?>
	<div class="wrap">
            <h2>UpTimer.info Options</h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'uptimer_option_group' );   
                do_settings_sections( 'uptimer-info' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }
    
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['api_key'] ) )
            $new_input['api_key'] = $input['api_key'];

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function api_key_callback()
    {
        printf(
            '<input type="text" id="api_key" name="uptimer_info_options[api_key]" value="%s" />',
            isset( $this->options['api_key'] ) ? esc_attr( $this->options['api_key']) : ''
        );
    }


}

UpTimer_Info::get_instance();