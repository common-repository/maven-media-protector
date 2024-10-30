<?php 
/*

**************************************************************************

Plugin Name:  Maven Media Protector
Plugin URI:   
Description:  Protect files with user and password
Version:      0.2 
Author:       Emiliano Jankowski
Author URI:   

**************************************************************************/

class maven_media_protector {
	var $menu_id;

	function __construct() {
		
		register_activation_hook(__FILE__, array( &$this,'mavenmp_activate'));
		register_deactivation_hook( __FILE__, array( &$this,'mavenmp_deactivate') );// anda
		
		
		add_action( 'admin_enqueue_scripts',                   array( &$this, 'admin_enqueues' ) );
		add_action( 'wp_ajax_mvn_m_protector_makeprivate',	   array( &$this, 'ajax_make_private' ) );
		add_filter( 'media_row_actions',                       array( &$this, 'add_media_row_action' ), 10, 2 );

		add_filter('manage_media_columns',  array( &$this,'add_tag_column'));
		add_action('manage_media_custom_column',  array( &$this,'manage_attachment_tag_column'), 10, 2);
		
		
		add_filter('mod_rewrite_rules','mavenmp_activate');
	}
	function mavenmp_activate($nada){
		mmp_htaccess_file_init();
		return $nada;
	}
	function mavenmp_deactivate(){ //anda
		mmp_htaccess_file_unistall();
	}
	

	function add_tag_column($posts_columns) {
			
		$posts_columns['att_private'] = _x('Private', 'column name');
		return $posts_columns;
	}
	
	function manage_attachment_tag_column($column_name, $id) {
		switch($column_name) {
		case 'att_private':
			
			$image = plugin_dir_url(__FILE__);
			
			if ( $this->is_file_protected( $id ) )
				$image .= '/lock.png';
			else
				$image .= '/unlock.png';
			
			echo "<img alt='' src='{$image}' />";
			
			break;
		default:
			break;
		}

	}

	
	private function is_file_protected( $id ){
		
		$protected_files = get_option('mvn_media_protect_files',array());
			
		$filename = basename ( get_attached_file( $id ) );
		
		return isset( $protected_files[$filename] ) ;
		
	}
	
	function ajax_make_private(){
		
		$file_id = intval( isset( $_POST['file_id'] )?$_POST['file_id']:0 );
		
		if ( $file_id ){
			
			$protected_files = get_option('mvn_media_protect_files',array());
			
			$filename = basename ( get_attached_file( $file_id ) );
			
			if ( $this->is_file_protected( $file_id ) )
				unset($protected_files[$filename]);
			else
				$protected_files[$filename] = 1;
			
			update_option('mvn_media_protect_files',$protected_files);
			
			die('updated');
		}
		
		die('File not exists');
	}



	function admin_enqueues( $hook_suffix ) {
		
		global $pagenow;
		if ( $pagenow == 'upload.php' ){
			
			wp_enqueue_script( 'maven-media-protector', plugins_url( 'maven-media-protector.js', __FILE__ ), array( 'jquery','jquery-ui-core' ) );
		}
		
	}


	function add_media_row_action( $actions, $post ) {

		$label  = $this->is_file_protected( $post->ID )?"Make public":"Make private";
		
		$script = 'mvn_m_protector_make_private( '. $post->ID .');';
		$actions['make_private'] = '<a href="javascript:void(0);" onclick="'.$script.'" title="' . esc_attr( __( $label, 'make-private' ) ) . '">' . __( $label, 'make-private' ) . '</a>';

		return $actions;
	}

}
/*******************************/

register_activation_hook(__FILE__, 'mavenmp_activate');
function mavenmp_activate(){
		mmp_htaccess_file_init();
		
}

// Start up this plugin
add_action( 'init', 'MavenMediaProtect' );
function MavenMediaProtect() {
	global $MavenMediaProtect;
	$MavenMediaProtect = new maven_media_protector();

}

add_action('admin_menu', 'my_plugin_menu');
	
function my_plugin_menu() {
	add_options_page( 'Maven MP Options', 'Maven MP', 'manage_options', 'maven-media-protector', 'mmp_plugin_page' );
}

function mmp_htaccess_file_init(){
	
	
	$file = get_home_path().'.htaccess';
	if ( !is_readable( $file ) || !is_writable( $file ) ) {
		$errors = new WP_Error();
		$errors->add( 'UnWritable', __( '<strong>ERROR</strong>: .Htaccess no Writable' ) );
		return false;
	}
		$mmppro = $wpg = $s = false;
		
		$ot = array();
		$ot[] = '# +Maven Media Protect';
		$ot[] = '# - - - - - - - - - - -';
		$ot[] = "RewriteCond %{REQUEST_FILENAME} -s";
		$ot[] = "RewriteRule ^wp-content/uploads/(.*)$ /wp-content/plugins/maven-media-protector/maven-media-protector-proxy.php?file=$1 [QSA,L]";
		$ot[] = '# - - - - - - - - - - -';
		$ot[] = '# -Maven Media Protect';
		
		$markerdata = ( is_writable( dirname( $file ) ) && touch( $file ) ) ? @explode( "\n", @implode( '', @file( $file ) ) ) : false;
		
		if ( $markerdata )
		{
			foreach ( $markerdata as $line )
			{
				if ( strpos( $line, '# BEGIN WordPress' ) !== false )
				{
					$s = $wpg = true;
					$wordp[] = "";
				}
				if ( $s === true ) $wordp[] = $line;
				if ( strpos( $line, '# END WordPress' ) !== false )
				{
					$s = false;
					continue;
				}

				if ( !$s ) $new[] = $line;

				if ( strpos( $line, '# +Maven Media Protect' ) !== false ) $mmppro = true;
			}
		}

		@chmod( $file, 0644 );

		if ( !$mmppro )
		{
			$jot = ( $wpg ) ? array_merge( $new, $ot, $wordp ) : array_merge( $markerdata, $ot );

			if ( !$f = @fopen( $file, 'w' ) ) 
				return new WP_Error( 'fopen-failed', __( "mmp_htaccess_file_init couldnt fopen {$file}" ) );
			$pr = join( "\n", $jot );
			if ( !@fwrite( $f, $pr, strlen( $pr ) ) ) 
				return new WP_Error( 'mmp_htaccess_file_init', __( "Maven_MP_insert_mark couldnt fwrite {$file}" ) );
			if ( !@fclose( $f ) ) 
				return new WP_Error( 'fclose-failed', __( "Couldnt fclose {$file}" ) );
		}
		
		return true;
	
}


function mmp_htaccess_file_unistall( )
{
	
	$file = get_home_path().'.htaccess';

	$file = ( @is_readable( $file ) ) ? realpath( rtrim( $file, '/' ) ) : rtrim( $file, '/' );
	if ( !is_readable( $file ) || !is_writable( $file ) ) {
		$errors = new WP_Error();
		$errors->add( 'UnWritable', __( '<strong>ERROR</strong>: .Htaccess no Writable' ) );
		return false;
	}else{
		

			if ( $markerdata = @explode( "\n", @implode( '', @file( $file ) ) ) )
			{
				$state = false;
				if ( !$f = @fopen( $file, 'w' ) ) return new WP_Error( 'fopen-failed', __( "Maven_MP_deactivate couldnt fopen {$file}" ) );

				foreach ( $markerdata as $n => $line )
				{
					if ( strpos( $line, "# +Maven Media Protect" ) !== false ) $state = true;
					if ( !$state ) fwrite( $f, $line . "\n" );
					if ( strpos( $line, "# -Maven Media Protect" ) !== false ) $state = false;
				}
			}

			@$_POST['notice'] = "Successfully Deactivated Maven Media Protect";
			if ( !fclose( $f ) )return new WP_Error( 'fclose-failed', __( "fclose failed to close {$file} in Maven_MP_deactivate_sid" ) );
	
			return true;
	}// if writeable
	
		 
}


function mmp_plugin_page()
{ 
	$aok = '<strong style="color:#319F52;background-color:#319F52;">[  ]</strong> ';
	$fail = '<strong style="color:#CC0000;background-color:#CC0000;">[  ]</strong> ';
	
	if ( isset( $_POST['infile'] ) )
		{
			mmp_htaccess_file_init();
		}
	
	
?>
	
<div class="wrap" style="max-width:95%;">
<h2><?php _e('Warning!'); ?></h2>
<p>
	<?php _e('Hey there! We need to add some lines to your .htaccess in order to do the magic! We can check if you have enough rights on the file, and apply the changes for you'); ?></p>

<h2><?php _e('Rule'); ?></h2>
	<p><?php _e("These are the lines, we will add to your file. If the plugin can't do it, you will need to do it manually."); ?></p>
	<p>	# +Maven Media Protect<br/>
		# - - - - - - - - - - - - - - - - - - - - - - - - - - -<br/>
		RewriteCond %{REQUEST_FILENAME} -s<br/>
		RewriteRule ^wp-content/uploads/(.*)$ /wp-content/plugins/maven-media-protector/maven-media-protector-proxy.php?file=$1 [QSA,L]<br/>
		# - - - - - - - - - - - - - - - - - - - - - - - - - - -<br/>
		# -Maven Media Protect<br/>
	</p>


<h2><?php _e('Test result'); ?></h2>
<p><?php _e('You have the file .htaccess:'); ?></p>
<?php
	$file = get_home_path().'.htaccess';
	$htaccess_writable = ( @is_writable( $file ) || @touch( $file ) ) ? 1 : 0;
	if ( $htaccess_writable ) {
		echo $aok . " {$file } ". __("file writable");
	}
	else{
		echo $fail . " {$file } ". __("file NO writable");
	} 
	
?>
<p><?php _e('Rules added'); ?>:</p>

<?php
	$state_mod = false;
	$file = ( @is_readable( $file ) ) ? realpath( rtrim( $file, '/' ) ) : rtrim( $file, '/' );
	if ( !is_readable( $file ) || !is_writable( $file ) ) 
		{
			$msg =  __("not readable/writable by Maven Media Protect <br/>");
			echo $fail . $msg ;
		}else{
			
			if ( $markerdata = @explode( "\n", @implode( '', @file( $file ) ) ) )
			{
				$state = false;
				if ( !$f = @fopen( $file, 'r' ) ) {
						echo $fail .  __("deactivate sid couldnt fopen");
				}else{

						foreach ( $markerdata as $n => $line )
						{
							if ( strpos( $line, "# +Maven Media Protect" ) !== false ) $state_mod = true;
						}

				}// fin del else
			} // if
			
		}//fin else
	if($state_mod){
		echo $aok .  __("all OK ");
	}else{
		echo $fail .  __("We couldn't add the rules");
	}

if ($htaccess_writable){?>
<form action="options-general.php?page=maven-media-protector.php" method="post">
	<input type="hidden" name="infile" id="infile" value="infile" />
<p class="submit"><input name="sub" type="submit" id="sub" value="<?php _e('Add rules'); ?> &raquo;" /></p>
</form>
<?php 
	}// form if writable
}






