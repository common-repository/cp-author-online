<?php
/*
Plugin Name: CP Author Online
Plugin URI: http://www.easycpmods.com
Description: CP Author Online is a lightweight plugin that will show you if the ad author is online or not. There is also an animated popup when you hover over image. No JavaScript, all CSS. Classipress theme is required for this plugin.
Author: EasyCPMods
Version: 1.8.0
Author URI: http://www.easycpmods.com
Text Domain: ecpm-cao
*/

define('ECPM_CAO', 'ecpm-cao');
define('ECPM_CAO_NAME', 'CP Author Online');
define('ECPM_CAO_VERSION', '1.8.0');

register_activation_hook( __FILE__, 'ecpm_cao_activate');
//register_deactivation_hook( __FILE__, 'ecpm_cao_deactivate');
register_uninstall_hook( __FILE__, 'ecpm_cao_uninstall');

add_action('plugins_loaded', 'ecpm_cao_plugins_loaded');
add_action('admin_init', 'ecpm_cao_requires_version');
  
add_action('admin_menu', 'ecpm_cao_create_menu_set', 11);
add_action('wp_enqueue_scripts', 'ecpm_cao_enqueuescripts');

//add_action( 'after_setup_theme', 'ecpm_replace_after_title_func', 1000 );
if (ecpm_cao_is_cp4())
  add_action('cp_listing_item_meta', 'ecpm_cao_user_active');
else  
  add_action(ecpm_cao_get_settings('icon_vertical_pos'), 'ecpm_cao_user_active' );

add_action('wp', 'ecpm_cao_update_user_status');
add_action('wp_logout', 'ecpm_cao_user_logout');

add_action( 'show_user_profile', 'ecpm_cao_user_profile');
add_action( 'edit_user_profile', 'ecpm_cao_user_profile');
add_action( 'personal_options_update', 'ecpm_cao_user_profile_save');
add_action( 'edit_user_profile_update', 'ecpm_cao_user_profile_save'); 

if (!ecpm_cao_is_cp4() && ecpm_cao_get_settings('replace_app_stats') == 'on') {
  add_action( 'appthemes_after_post_content', 'ecpm_do_loop_stats' );
}

function ecpm_cao_is_cp4() {
  if ( defined("CP_VERSION") )
    $cp_version = CP_VERSION;
  else   
    $cp_version = get_option('cp_version');
    
  if (version_compare($cp_version, '4.0.0') >= 0) {
    return true;
  }
  
  return false;
}

function ecpm_cao_get_settings($ret_value){
  $cao_settings = get_option('ecpm_cao_settings');
  return $cao_settings[$ret_value];
}
 
function ecpm_cao_requires_version() {
  $allowed_apps = array('classipress');
  
  if ( defined(APP_TD) && !in_array(APP_TD, $allowed_apps ) ) { 
	  $plugin = plugin_basename( __FILE__ );
    $plugin_data = get_plugin_data( __FILE__, false );
		
    if( is_plugin_active($plugin) ) {
			deactivate_plugins( $plugin );
			wp_die( "<strong>".$plugin_data['Name']."</strong> requires a AppThemes Classipress theme to be installed. Your Wordpress installation does not appear to have that installed. The plugin has been deactivated!<br />If this is a mistake, please contact plugin developer!<br /><br />Back to the WordPress <a href='".get_admin_url(null, 'plugins.php')."'>Plugins page</a>." );
		}
	}
}

/*
function ecpm_cao_deactivate() {
} 
*/

function ecpm_cao_activate() {
  $ecpm_cao_settings = get_option('ecpm_cao_settings');
  if ( empty($ecpm_cao_settings) ) {
    $ecpm_cao_settings = array(
      'installed_version' => ECPM_CAO_VERSION,
      'active_threshold' => '10',
      'animation_type' => 'expand',
      'active_color' => 'green',
      'pulse_icon' => '',
      'inactive_color' => 'dimgrey',
      'hidden_color' => 'lightgrey',
      'icon_type' => 'normal',
      'icon_size' => '16',
      'font_size' => '12',
      'icon_position' => 'left',
      'icon_vertical_pos' => 'appthemes_after_post_title',
      'replace_app_stats' => '',
      'icon_margin' => array('8', '7', '0', '0'),
      'online_text' => '',
      'hidden_text' => '',
      'exclude_label' => '',
      'exclude_text' => '',
      'opacity' => '100'
    );
    update_option( 'ecpm_cao_settings', $ecpm_cao_settings );
  }
}

function ecpm_cao_uninstall() {                                   
  delete_option( 'ecpm_cao_settings' );
}

function ecpm_cao_plugins_loaded() {
  $dir = dirname(plugin_basename(__FILE__)).DIRECTORY_SEPARATOR.'languages'.DIRECTORY_SEPARATOR;
	load_plugin_textdomain(ECPM_CAO, false, $dir);
}

function ecpm_cao_enqueuescripts()	{
  if (is_single())
    return;
  
  $ecpm_cao_settings = get_option('ecpm_cao_settings');
  
  $icon_type = $ecpm_cao_settings['icon_type'];
  if ($icon_type == 'inverted')
    $icon_type = '-inv';
  else
    $icon_type = '';
    
  $icon_size = '-'.$ecpm_cao_settings['icon_size'];  
    
  wp_enqueue_style('ecpm_cao_style', plugins_url('css/ecpm-cao-min.css', __FILE__), array(), null);
  wp_enqueue_style('ecpm_cao_icons', plugins_url('css/cao-icons'.$icon_type.$icon_size.'-min.css', __FILE__), array(), null);
}

/*
function ecpm_replace_after_title_func(){
	remove_action( 'appthemes_after_registration', 'cp_ad_loop_meta', 10 );
	add_action( 'appthemes_after_registration', 'ecpm_ad_loop_meta', 10, 2 );
}
*/

function ecpm_cao_user_active() {
	global $post;

 	if ( !current_theme_supports( 'app-stats' ) ) {
		return;
	}

  if ( is_singular( APP_POST_TYPE ) ) {
		return;
	}
	
	$ecpm_cao_settings = get_option('ecpm_cao_settings');
	
  $anim_type = $ecpm_cao_settings['animation_type'];
	$icon_size = $ecpm_cao_settings['icon_size'];
	$icon_margin = $ecpm_cao_settings['icon_margin'];
	$icon_position = $ecpm_cao_settings['icon_position'];
	$replace_stats = $ecpm_cao_settings['replace_app_stats'];
	
	$font_size = $ecpm_cao_settings['font_size'];
	$cao_online_text = $ecpm_cao_settings['online_text'];
	$cao_hidden_text = $ecpm_cao_settings['hidden_text'];
  
  if ($ecpm_cao_settings['icon_type'] == 'normal')
    $ecpm_cao_icon_type = '';
  else    
    $ecpm_cao_icon_type = 'inv-';
    
  $ecpm_cao_opacity = $ecpm_cao_settings['opacity'];
  if ($ecpm_cao_opacity == '')
    $ecpm_cao_opacity = '1';
  else
    $ecpm_cao_opacity = $ecpm_cao_opacity / 100;     

	?>
  <div style="display:inline; float:<?php echo esc_html($icon_position);?>; margin:<?php echo esc_html($icon_margin[0]).'px '.esc_html($icon_margin[1]).'px '.esc_html($icon_margin[2]).'px '.esc_html($icon_margin[3]). 'px;';?>">
  
	<?php 
	$author_meta_id = get_the_author_meta('ID');
  $hide_status = get_the_author_meta( 'ecpm_cao_hide_status');

	
	if ( $hide_status == 'on' ) {
	  if (trim($cao_hidden_text) == '') {
      $cao_hidden_text = __( 'Unknown author status', ECPM_CAO );
    }
    ?> 
	  <span style="opacity:<?php echo $ecpm_cao_opacity;?>" class="cao-tooltip <?php echo esc_html($anim_type);?>" style="font-size:<?php echo esc_html($font_size);?>px" rel="cao-tooltip" data-placement="top" data-title="<?php echo esc_html($cao_hidden_text); ?>"><span class="cao-sprite cao-sprite-<?php echo $ecpm_cao_icon_type . $ecpm_cao_settings["hidden_color"] ."-". $icon_size;?>"></span></span>
	  <?php 

  } else {
    if ( ecpm_user_active($author_meta_id)) {
  
      if (trim($cao_online_text) != '') {
        $cao_online_text = str_replace('[user]', get_the_author_meta('display_name'), $cao_online_text);
      } else { 
        $cao_online_text = get_the_author_meta('display_name') .' '. __( 'is online', ECPM_CAO );
      }
      
      if ( isset($ecpm_cao_settings['pulse_icon']) && $ecpm_cao_settings['pulse_icon'] == 'on'){
        $pulse_icon = 'cao-pulse';
      }
      
      ?> 
  	  <span style="opacity:<?php echo $ecpm_cao_opacity;?>" class="cao-tooltip <?php echo esc_html($anim_type);?>" style="font-size:<?php echo esc_html($font_size);?>px" rel="cao-tooltip" data-placement="top" data-title="<?php echo esc_html($cao_online_text); ?>"><span class="<?php echo $pulse_icon;?>"><span class="cao-sprite cao-sprite-<?php echo $ecpm_cao_icon_type . $ecpm_cao_settings["active_color"] ."-". $icon_size;?>"></span></span></span>
  	  <?php 
  
    } else { 
  
      ?>
  		<span style="opacity:<?php echo $ecpm_cao_opacity;?>" class="cao-tooltip <?php echo esc_html($anim_type);?>" style="font-size:<?php echo esc_html($font_size);?>px" rel="cao-tooltip" data-placement="top" data-title="<?php _e( 'Last Login:', APP_TD ); ?> <?php echo appthemes_get_last_login( $author_meta_id ); ?>"><span class="cao-sprite cao-sprite-<?php echo $ecpm_cao_icon_type . $ecpm_cao_settings["inactive_color"] ."-". $icon_size;?>"></span></span>
  	  <?php 
    } 
  }  
  ?>
  </div>
  <?php
  if ($replace_stats == 'on') {
  ?>
    <div class="cao-stats"><?php appthemes_stats_counter( $post->ID ); ?></div>
  <?php
  }
  ?>

<?php
}

function ecpm_do_loop_stats() {
	global $post, $cp_options;

	if ( ! is_singular( array( 'post', APP_POST_TYPE ) ) ) {
		return;
	} 
	
  if ( !current_theme_supports( 'app-stats' ) ) {
		return;
	}
?>
	<div class="cao-prdetails">
		<p class="dashicons-before stats"><?php appthemes_stats_counter( $post->ID ); ?></p>
	</div>
 
<?php
} 
 
function ecpm_user_active($user_id) {
	global $wpdb;
  $ecpm_cao_settings = get_option('ecpm_cao_settings');  
  $time_passed = $ecpm_cao_settings['active_threshold'] * 60;
  $users_online = get_transient('ecpm_cao_authors_online');
 
  if ( isset($users_online[$user_id]) && ($users_online[$user_id] > (current_time('timestamp') - $time_passed ) ) ) 
    return true;
  else
    return false;  
} 

function ecpm_cao_update_user_status(){
  if(is_user_logged_in()){
    $ecpm_cao_settings = get_option('ecpm_cao_settings');
    
    if(($users_online = get_transient('ecpm_cao_authors_online')) === false) $users_online = array();

    $current_user = wp_get_current_user();
    $current_user = $current_user->ID;  
    $current_time = current_time('timestamp');

    if(!isset($users_online[$current_user]) || ($users_online[$current_user] < ($current_time - ($ecpm_cao_settings['active_threshold'] * 60)))){
      $users_online[$current_user] = $current_time;
      set_transient('ecpm_cao_authors_online', $users_online, $ecpm_cao_settings['active_threshold'] * 2 * 60);
    }
  }
} 

function ecpm_cao_user_logout(){
  if(is_user_logged_in()){
    $ecpm_cao_settings = get_option('ecpm_cao_settings');
    
    if(($users_online = get_transient('ecpm_cao_authors_online')) === false) return;

    $current_user = wp_get_current_user();
    $current_user = $current_user->ID;  

    unset($users_online[$current_user]);
    set_transient('ecpm_cao_authors_online', $users_online, $ecpm_cao_settings['active_threshold'] * 2 * 60);
  }
} 


function ecpm_cao_user_profile( $user ) {
  $ecpm_cao_settings = get_option('ecpm_cao_settings');
  $cao_exclude_label = $ecpm_cao_settings['exclude_label'];
  $cao_exclude_text = $ecpm_cao_settings['exclude_text'];
  
  ?>
  <table class="form-table">
  	<tbody>
  		<tr>
  			<th>
  				<label for="ecpm_cao_hide_status"><?php 
          if (trim($cao_exclude_label) != '')
            echo $cao_exclude_label;  
          else
            _e('Hide online status', ECPM_CAO);
          ?></label>
  			</th>
  			<td style="vertical-align:middle;">
  				<?php
/*
  				if ( current_user_can( 'edit_users' ) ) {
  				  $check_disable = '';
          } else {
            $check_disable = ' disabled';
          }
*/          
          ?>
            <Input <?php //echo $check_disable;?> type="checkbox" id="ecpm_cao_hide_status" Name="ecpm_cao_hide_status" <?php if ( get_the_author_meta( 'ecpm_cao_hide_status', $user->ID ) == 'on') echo 'checked';?>>
  				  <span class="description">
            <?php 
            if (trim($cao_exclude_text) != '')
              echo $cao_exclude_text;
            else  
              _e( 'Select this to hide your online status' , ECPM_CAO);
            ?></span>
  			</td>
  		</tr>
  	<tbody>
  </table>
  <?php
}

function ecpm_cao_user_profile_save( $user_id ) {

//	  if ( !current_user_can( 'edit_users' ) )
//  return;

	if ( $_POST['ecpm_cao_hide_status'] == 'on' ) {
		$hide_status = $_POST['ecpm_cao_hide_status'];
	} else {
		$hide_status = '';
	}
 
	update_user_meta( $user_id, 'ecpm_cao_hide_status', $hide_status );
}

function ecpm_cao_create_menu_set() {
  if ( is_plugin_active('easycpmods-toolbox/ecpm-toolbox.php') ) {
    $ecpm_etb_settings = get_option('ecpm_etb_settings');
    if ($ecpm_etb_settings['group_settings'] == 'on') {
      add_submenu_page( 'ecpm-menu', ECPM_CAO_NAME, ECPM_CAO_NAME, 'manage_options', 'ecpm_cao_settings_page', 'ecpm_cao_settings_page_callback' );
      return;
    }
  }
  add_options_page(ECPM_CAO_NAME, ECPM_CAO_NAME, 'manage_options', 'ecpm_cao_settings_page', 'ecpm_cao_settings_page_callback');
}    
  
function ecpm_cao_settings_page_callback() {
  global $cp_options;
  
  $ecpm_cao_settings = get_option('ecpm_cao_settings');
  
  $avail_animations = array('fade', 'expand', 'swing');
  $avail_icon_types = array('normal', 'inverted');
  $avail_icon_pos = array('left', 'right');
  $avail_icon_vertical_pos = array('appthemes_before_post_title', 'appthemes_after_post_title', 'appthemes_before_post_content', 'appthemes_after_post_content');
	
  if( isset( $_POST['ecpm_cao_submit'] ) )
	{
    
    if ( isset($_POST[ 'ecpm_cao_active_threshold' ]) && is_numeric (intval($_POST[ 'ecpm_cao_active_threshold' ])) )
      $ecpm_cao_settings['active_threshold'] = $_POST[ 'ecpm_cao_active_threshold' ];
      
    if ( isset($_POST[ 'ecpm_cao_animation_type' ]) && in_array($_POST[ 'ecpm_cao_animation_type' ], $avail_animations) )
      $ecpm_cao_settings['animation_type'] = $_POST[ 'ecpm_cao_animation_type' ];

    if ( isset($_POST[ 'ecpm_cao_active_color' ]) && is_numeric (intval($_POST[ 'ecpm_cao_active_color' ])) )
      $ecpm_cao_settings['active_color'] = $_POST[ 'ecpm_cao_active_color' ];
      
    if ( isset($_POST[ 'ecpm_cao_inactive_color' ]) && is_numeric (intval($_POST[ 'ecpm_cao_inactive_color' ])) )
      $ecpm_cao_settings['inactive_color'] = $_POST[ 'ecpm_cao_inactive_color' ];
    
    if ( isset($_POST[ 'ecpm_cao_hidden_color' ]) && is_numeric (intval($_POST[ 'ecpm_cao_hidden_color' ])) )
      $ecpm_cao_settings['hidden_color'] = $_POST[ 'ecpm_cao_hidden_color' ];  
    
    if ( isset($_POST[ 'ecpm_cao_icon_type' ]) && in_array($_POST[ 'ecpm_cao_icon_type' ], $avail_icon_types) )
      $ecpm_cao_settings['icon_type'] = $_POST[ 'ecpm_cao_icon_type' ];
      
    if ( isset($_POST[ 'ecpm_cao_icon_position' ]) && in_array($_POST[ 'ecpm_cao_icon_position' ], $avail_icon_pos) )
      $ecpm_cao_settings['icon_position'] = $_POST[ 'ecpm_cao_icon_position' ];
    
    if ( isset($_POST[ 'ecpm_cao_icon_vertical_pos' ]) && in_array($_POST[ 'ecpm_cao_icon_vertical_pos' ], $avail_icon_vertical_pos) )
      $ecpm_cao_settings['icon_vertical_pos'] = $_POST[ 'ecpm_cao_icon_vertical_pos' ];  
    
    for ($i = 0; $i <= 3; $i++) {
      $ecpm_cao_settings['icon_margin'][$i] = sanitize_text_field( $_POST[ 'ecpm_cao_icon_margin_'.$i ] );
    }
    
    if ( isset($_POST[ 'ecpm_cao_pulse_icon' ]) && $_POST[ 'ecpm_cao_pulse_icon' ] == 'on' )
      $ecpm_cao_settings['pulse_icon'] = sanitize_text_field($_POST[ 'ecpm_cao_pulse_icon' ]);
    else  
      $ecpm_cao_settings['pulse_icon'] = '';
    
    if ( ecpm_cao_is_cp4() && isset($_POST[ 'ecpm_cao_replace_app_stats' ]) && $_POST[ 'ecpm_cao_replace_app_stats' ] == 'on' )
      $ecpm_cao_settings['replace_app_stats'] = sanitize_text_field($_POST[ 'ecpm_cao_replace_app_stats' ]);
    else  
      $ecpm_cao_settings['replace_app_stats'] = '';
    
    if ( isset($_POST[ 'ecpm_cao_icon_size' ]) && is_numeric (intval( $_POST[ 'ecpm_cao_icon_size' ] ) ) ) {
      $ecpm_cao_settings['icon_size'] = sanitize_text_field($_POST[ 'ecpm_cao_icon_size' ]);
      
      if ($ecpm_cao_settings['icon_size'] > 32 )
        $ecpm_cao_settings['icon_size'] = '32';
    }    
    
    if ( isset($_POST[ 'ecpm_cao_font_size' ]) && is_numeric (intval( $_POST[ 'ecpm_cao_font_size' ] ) ) )
      $ecpm_cao_settings['font_size'] =  sanitize_text_field($_POST[ 'ecpm_cao_font_size' ]);
      
    if ( isset($_POST[ 'ecpm_cao_opacity' ]) && is_numeric(intval( $_POST[ 'ecpm_cao_opacity' ] ) ) ) {
      $ecpm_cao_settings['opacity'] = $_POST[ 'ecpm_cao_opacity' ];
      if ( intval($ecpm_cao_settings['opacity']) > 100 || intval($ecpm_cao_settings['opacity']) < 0 || $ecpm_cao_settings['opacity'] == '' )
        $ecpm_cao_settings['opacity'] = '100';   
    }
      
    $ecpm_cao_settings['online_text']   = sanitize_text_field( $_POST[ 'ecpm_cao_online_text' ] );
    $ecpm_cao_settings['hidden_text']   = sanitize_text_field( $_POST[ 'ecpm_cao_hidden_text' ] );
    $ecpm_cao_settings['exclude_label'] = sanitize_text_field( $_POST[ 'ecpm_cao_exclude_label' ] );
    $ecpm_cao_settings['exclude_text']  = sanitize_text_field( $_POST[ 'ecpm_cao_exclude_text' ] );
    
    update_option( 'ecpm_cao_settings', $ecpm_cao_settings );
    
    echo scb_admin_notice( __( 'Settings saved.', APP_TD ), 'updated' );  
	}
	
	if ($ecpm_cao_settings['replace_app_stats'] == 'on' &&  $cp_options->ad_stats_all) {
    echo scb_admin_notice( __( 'You should disable AppThemes setting - View Counter.', APP_TD ), 'error' );
  }
	
	$cao_colors = array('black', 'dimgrey', 'lightgrey', 'darkgreen', 'green', 'lightseagreen', 'darkred', 'crimson', 'lightcoral', 'midnightblue', 'royalblue', 'lightblue');
  
  ?>
  
		<div id="caosetting">
		  <div class="wrap">
			<h1><?php echo _e('CP Author Online', ECPM_CAO); ?></h1>
      <?php
        echo "<i>Plugin version: <u>".ECPM_CAO_VERSION."</u>";
        echo "<br>Plugin language file: <u>ecpm-cao-".get_locale().".mo</u></i>";
        ?>
  			<hr>
        <div id='cao-container-left' style='float: left; margin-right: 285px;'>
        <form id='caosettingform' method="post" action="">
  				<table width="100%" cellspacing="0" cellpadding="10" border="0">
            <tr>
    					<th align="left">
    						<label for="ecpm_cao_active_threshold"><?php echo _e('Minutes threshold', ECPM_CAO); ?></label>
    					</th>
    					<td>
    						<Input type='text' size='2' id='ecpm_cao_active_threshold' Name ='ecpm_cao_active_threshold' value='<?php echo esc_html($ecpm_cao_settings['active_threshold']);?>'>
    				  </td>
              <td>		
                <span class="description"><?php _e( 'Specify how long the user will be shown as active since the last activity.' , ECPM_CAO ); ?></span>
    					</td>
    				</tr>

            <tr>
    					<th align="left">
    						<label for="ecpm_cao_animation_type"><?php echo _e('Animation type', ECPM_CAO); ?></label>
    					</th>
    					<td>
                <select id="ecpm_cao_animation_type" name="ecpm_cao_animation_type" >
	 							  <option value="fade" <?php echo ($ecpm_cao_settings['animation_type'] == 'fade' ? 'selected':'') ;?>><?php echo _e('Fade', ECPM_CAO); ?></option>
                  <option value="expand" <?php echo ($ecpm_cao_settings['animation_type'] == 'expand' ? 'selected':'') ;?>><?php echo _e('Expand', ECPM_CAO); ?></option>
                  <option value="swing" <?php echo ($ecpm_cao_settings['animation_type'] == 'swing' ? 'selected':'') ;?>><?php echo _e('Swing', ECPM_CAO); ?></option>
                </select>
              </td>
              <td>
                <span class="description"><?php _e( 'Select the animation type' , ECPM_CAO ); ?></span>
    					</td>
    				</tr>
    				
    				<tr>
    					<th align="left">
    						<label for="ecpm_cao_icon_type"><?php echo _e('Author icon type', ECPM_CAO); ?></label>
    					</th>
    					<td valign="middle">
                <input type="radio" id="ecpm_cao_icon_type" name="ecpm_cao_icon_type" value="normal" <?php echo (esc_html($ecpm_cao_settings['icon_type']) == 'normal' ? 'checked':'') ;?>>
                <img src="<?php echo plugins_url('images/cao-black-24.png', __FILE__);?>" align="middle">
                &nbsp;&nbsp;
                <input type="radio" id="ecpm_cao_icon_type" name="ecpm_cao_icon_type" value="inverted" <?php echo (esc_html($ecpm_cao_settings['icon_type']) == 'inverted' ? 'checked':'') ;?>>
                <img src="<?php echo plugins_url('images/cao-inv-black-24.png', __FILE__);?>" align="middle">
              </td>
              <td>  
                <span class="description"><?php _e( 'Type of icon to show' , ECPM_CAO ); ?></span>
    					</td>
    				</tr>
    				
    				<tr>
    					<th align="left">
    						<label for="ecpm_cao_icon_position"><?php echo _e('Icon horizontal position', ECPM_CAO); ?></label>
    					</th>
    					<td valign="middle">
                <input type="radio" id="ecpm_cao_icon_position" name="ecpm_cao_icon_position" value="left" <?php echo ( esc_html($ecpm_cao_settings['icon_position']) == 'left' ? 'checked':'') ;?>><?php echo _e('Left', ECPM_CAO); ?>
                &nbsp;&nbsp;
                <input type="radio" id="ecpm_cao_icon_position" name="ecpm_cao_icon_position" value="right" <?php echo ( esc_html($ecpm_cao_settings['icon_position']) == 'right' ? 'checked':'') ;?>><?php echo _e('Right', ECPM_CAO); ?>
              </td>
              <td>  
                <span class="description"><?php _e( 'Where to show the icon' , ECPM_CAO ); ?></span>
    					</td>
    				</tr>
    				
    				<?php 
            if (!ecpm_cao_is_cp4()) {
            ?>
            <tr>
    					<th align="left">
    						<label for="ecpm_cao_icon_vertical_pos"><?php echo _e('Icon vertical position', ECPM_CAO); ?></label>
    					</th>
    					<td valign="middle">
    					  <select id='ecpm_cao_icon_vertical_pos' name="ecpm_cao_icon_vertical_pos">
                  <option value="appthemes_before_post_title" <?php echo ($ecpm_cao_settings['icon_vertical_pos'] == 'appthemes_before_post_title' ? 'selected':'') ;?>><?php _e('Before Post Title', ECPM_CAO);?></option>
                  <option value="appthemes_after_post_title" <?php echo ($ecpm_cao_settings['icon_vertical_pos'] == 'appthemes_after_post_title' ? 'selected':'') ;?>><?php _e('After Post Title', ECPM_CAO);?></option>
                  <option value="appthemes_before_post_content" <?php echo ($ecpm_cao_settings['icon_vertical_pos'] == 'appthemes_before_post_content' ? 'selected':'') ;?>><?php _e('Before Post Content', ECPM_CAO);?></option>
                  <option value="appthemes_after_post_content" <?php echo ($ecpm_cao_settings['icon_vertical_pos'] == 'appthemes_after_post_content' ? 'selected':'') ;?>><?php _e('After Post Content', ECPM_CAO);?></option>
                </select> 
              </td>
              <td>  
                <span class="description"><?php _e( 'Where to show the icon' , ECPM_CAO ); ?></span>
    					</td>
    				</tr>
    				
    				<tr>
    					<th align="left">
    						<label for="ecpm_cao_replace_app_stats"><?php echo _e('Replace App stats', ECPM_CAO); ?></label>
    					</th>
    					<td valign="left">
                <Input type="checkbox" Name="ecpm_cao_replace_app_stats" <?php if ($ecpm_cao_settings['replace_app_stats'] == 'on') echo 'checked';?>>
              </td>
              <td>  
                <span class="description"><?php _e( 'Enable this if you want the icon to appear on the same line as ad stats.' , ECPM_CAO ); ?></span>
    					</td>
    				</tr>
            <?php
            }
            ?>
    				
    				<tr>
    					<th align="left">
    						<label for="ecpm_cao_icon_margin"><?php echo _e('Icon margin', ECPM_CAO); ?></label>
    					</th>
    					<td>
    						<table>
                  <tr>
                    <td><?php echo _e('Top', ECPM_CAO); ?></td>
                    <td><?php echo _e('Right', ECPM_CAO); ?></td>
                    <td><?php echo _e('Bottom', ECPM_CAO); ?></td>
                    <td><?php echo _e('Left', ECPM_CAO); ?></td>
                  </tr>
                  <tr>
                  <?php 
      						for ($mi = 0; $mi <= 3; $mi++){
      						?>
                   <td><Input type='text' size='1' id='ecpm_cao_icon_margin_<?php echo $mi;?>' Name ='ecpm_cao_icon_margin_<?php echo $mi;?>' value='<?php echo esc_html($ecpm_cao_settings['icon_margin'][$mi]);?>'>px</td>
      						<?php
                  }
                  ?>
                  </tr>
                </table>
    				  </td>
              <td>		
                <span class="description"><?php _e( 'Margin in pixels around the icon' , ECPM_CAO ); ?></span>
    					</td>
    				</tr>
    				
    				<tr>
    					<th align="left">
    						<label for="ecpm_cao_active_color"><?php echo _e('Author online color', ECPM_CAO); ?></label>
    					</th>
    					<td>
							  <table cellspacing="0" cellpadding="3" border="0">
                  <tr height="16px">
                    <?php
                    foreach($cao_colors as $cao_color) {
                    ?>
                    <td align="center" width="16px" bgcolor="<?php echo esc_html($cao_color);?>"></td>
                    <?php 
                    }                    
                    ?>
                    
                  </tr><tr>
                    <?php
                    foreach($cao_colors as $cao_color) {
                    ?>
                    <td align="center"><input type="radio" id="ecpm_cao_active_color" name="ecpm_cao_active_color" value="<?php echo esc_html($cao_color);?>" <?php echo (esc_html($ecpm_cao_settings['active_color']) == $cao_color ? 'checked':'') ;?>></td>
                    <?php 
                    } 
                    ?>
                  </tr>
                </table>
              </td>
              <td valign="top">
                <span class="description"><?php _e( 'Color to show when author is online' , ECPM_CAO ); ?></span>
    					</td>
    				</tr>
            
            <tr>
    					<th align="left">
    						<label for="ecpm_cao_pulse_icon"><?php echo _e('Pulse online image', ECPM_CAO); ?></label>
    					</th>
    					<td valign="left">
                <Input type="checkbox" Name="ecpm_cao_pulse_icon" <?php if ($ecpm_cao_settings['pulse_icon'] == 'on') echo 'checked';?>>
              </td>
              <td>  
                <span class="description"><?php _e( 'Enable this if you want the online icon to blink.' , ECPM_CAO ); ?></span>
    					</td>
    				</tr>
    				
            <tr>
    					<th align="left">
    						<label for="ecpm_cao_inactive_color"><?php echo _e('Author offline color', ECPM_CAO); ?></label>
    					</th>
    					<td>
							  <table cellspacing="0" cellpadding="3" border="0">
                  <tr height="16px">
                    <?php
                    foreach($cao_colors as $cao_color) {
                    ?>
                    <td align="center" width="16px" bgcolor="<?php echo esc_html($cao_color);?>"></td>
                    <?php 
                    }                    
                    ?>
                    
                  </tr><tr>
                    <?php
                    foreach($cao_colors as $cao_color) {
                    ?>
                    <td align="center"><input type="radio" id="ecpm_cao_inactive_color" name="ecpm_cao_inactive_color" value="<?php echo esc_html($cao_color);?>" <?php echo (esc_html($ecpm_cao_settings['inactive_color']) == $cao_color ? 'checked':'') ;?>></td>
                    <?php 
                    } 
                    ?>
                  </tr>
                </table>
              </td>
              <td valign="top">
                <span class="description"><?php _e( 'Color to show when author is offline' , ECPM_CAO ); ?></span>
    					</td>
    				</tr>
    				
    				<tr>
    					<th align="left">
    						<label for="ecpm_cao_hidden_color"><?php echo _e('Author hidden color', ECPM_CAO); ?></label>
    					</th>
    					<td>
							  <table cellspacing="0" cellpadding="3" border="0">
                  <tr height="16px">
                    <?php
                    foreach($cao_colors as $cao_color) {
                    ?>
                    <td align="center" width="16px" bgcolor="<?php echo esc_html($cao_color);?>"></td>
                    <?php 
                    }                    
                    ?>
                    
                  </tr><tr>
                    <?php
                    foreach($cao_colors as $cao_color) {
                    ?>
                    <td align="center"><input type="radio" id="ecpm_cao_hidden_color" name="ecpm_cao_hidden_color" value="<?php echo esc_html($cao_color);?>" <?php echo (esc_html($ecpm_cao_settings['hidden_color']) == $cao_color ? 'checked':'') ;?>></td>
                    <?php 
                    } 
                    ?>
                  </tr>
                </table>
              </td>
              <td valign="top">
                <span class="description"><?php _e( 'Color to show when author is hiding his/her online status' , ECPM_CAO ); ?></span>
    					</td>
    				</tr>
    				
    				<tr>
    					<th align="left" valign="top">
    						<label for="ecpm_cao_opacity"><?php echo _e('Opacity', ECPM_CAO ); ?></label>
    					</th>
    					<td>
			          <Input type='text' size='2' id='ecpm_cao_opacity' Name='ecpm_cao_opacity' value='<?php echo esc_html($ecpm_cao_settings['opacity']);?>'>%
    				  </td>
              <td>		
                <span class="description"><?php _e( 'Transparency for the shown data and icons' , ECPM_CAO ); ?></span>
    					</td>
    				</tr> 
    				
    				<tr>
    					<th align="left">
    						<label for="ecpm_cao_icon_size"><?php echo _e('Icon size', ECPM_CAO); ?></label>
    					</th>
    					<td>
    						<select id="ecpm_cao_icon_size" name="ecpm_cao_icon_size" >
                  <option value="12" <?php echo ($ecpm_cao_settings['icon_size'] == '12' ? 'selected':'') ;?>>12px</option>
                  <option value="16" <?php echo ($ecpm_cao_settings['icon_size'] == '16' ? 'selected':'') ;?>>16px</option>
                  <option value="20" <?php echo ($ecpm_cao_settings['icon_size'] == '20' ? 'selected':'') ;?>>20px</option>
                  <option value="24" <?php echo ($ecpm_cao_settings['icon_size'] == '24' ? 'selected':'') ;?>>24px</option>
                  <option value="32" <?php echo ($ecpm_cao_settings['icon_size'] == '32' ? 'selected':'') ;?>>32px</option>
                </select>
    				  </td>
              <td>		
                <span class="description"><?php _e( 'Specify the size of icons in pixels (max: 32px)' , ECPM_CAO ); ?></span>
    					</td>
    				</tr>
    				
    				<tr>
    					<th align="left">
    						<label for="ecpm_cao_font_size"><?php echo _e('Font size', ECPM_CAO); ?></label>
    					</th>
    					<td>
    						<Input type='text' size='2' id='ecpm_cao_font_size' Name ='ecpm_cao_font_size' value='<?php echo esc_html($ecpm_cao_settings['font_size']);?>'>px
    				  </td>
              <td>		
                <span class="description"><?php _e( 'Specify the size of text on popup in pixels' , ECPM_CAO ); ?></span>
    					</td>
    				</tr>
    				
    				<tr>
    					<th align="left" valign="top">
    						<label for="ecpm_cao_online_text"><?php echo _e('Online text', ECPM_CAO); ?></label>
    					</th>
    					<td>
    						<textarea rows="2" cols="38" id='ecpm_cao_online_text' Name='ecpm_cao_online_text'><?php echo esc_html($ecpm_cao_settings['online_text']);?></textarea><br>
    						<i><?php _e( 'Use [user] for username.' , ECPM_CAO ); ?></i>
    				  </td>
              <td valign="top">		
                <span class="description"><?php _e( 'Text to display when user is online. If empty, default value will be used.' , ECPM_CAO ); ?></span>
    					</td>
    				</tr>
    				
    				<tr>
    					<th align="left" valign="top">
    						<label for="ecpm_cao_hidden_text"><?php echo _e('Hidden text', ECPM_CAO); ?></label>
    					</th>
    					<td>
    						<textarea rows="2" cols="38" id='ecpm_cao_hidden_text' Name='ecpm_cao_hidden_text'><?php echo esc_html($ecpm_cao_settings['hidden_text']);?></textarea>
    				  </td>
              <td valign="top">		
                <span class="description"><?php _e( 'Text to display when user is hiding his/her online status. If empty, default value will be used.' , ECPM_CAO ); ?></span>
    					</td>
    				</tr>
    				
    				<tr>
    					<th align="left">
    						<label for="ecpm_cao_exclude_label"><?php echo _e('Exclude label', ECPM_CAO); ?></label>
    					</th>
    					<td>
    						<input class="text regular-text" type="text" id='ecpm_cao_exclude_label' Name='ecpm_cao_exclude_label' value='<?php echo esc_html($ecpm_cao_settings['exclude_label']);?>'>
    				  </td>
              <td>		
                <span class="description"><?php _e( 'Label to display on user account settings' , ECPM_CAO ); ?></span>
    					</td>
    				</tr>
    				
    				<tr>
    					<th align="left">
    						<label for="ecpm_cao_exclude_text"><?php echo _e('Exclude text', ECPM_CAO); ?></label>
    					</th>
    					<td>
    						<input class="text regular-text" type=text id='ecpm_cao_exclude_text' Name='ecpm_cao_exclude_text' value='<?php echo esc_html($ecpm_cao_settings['exclude_text']);?>'>
    				  </td>
              <td>		
                <span class="description"><?php _e( 'Text to display on user account settings' , ECPM_CAO ); ?></span>
    					</td>
    				</tr>
    				
          </table>
          <hr>
          <p>  
  				<input type="submit" id="ecpm_cao_submit" name="ecpm_cao_submit" class="button-primary" value="<?php _e('Save settings', ECPM_CAO); ?>" />
  				</p>
  			</form>
        </div>
        
        <div id='cao-container-right' class='nocloud' style='border: 1px solid #e5e5e5; float: right; margin-left: -275px; padding: 0em 1.5em 1em; background-color: #fff; box-shadow: 10px 10px 5px #888888; display: inline-block; width: 234px;'>
          <h3>Thank you for using</h3>
          <h2><?php echo ECPM_CAO_NAME;?></h2>
          <hr>
          <?php include_once( plugin_dir_path(__FILE__) ."/image_sidebar.php" );?>
        </div>
		</div>
	</div>
<?php
}
?>