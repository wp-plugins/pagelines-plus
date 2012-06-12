<?php
/*
Plugin Name: Pagelines Plus
Plugin URI: http://highergroundstudio.com
Description: Enhancements to Pagelines Wordpress framework
Author: Kyle King
Author URI: http://highergroundstudio.com
Version: 1.1.1
Tags: upload, install, section, pagelines
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// constant definition
if( !defined( 'IS_ADMIN' ) )
    define( 'IS_ADMIN', is_admin() );

define( 'PAGELINES_PLUS_VERSION', '1.0.0' );
define( 'PAGELINES_PLUS_PREFIX', '_iti_su_' );
define( 'PAGELINES_PLUS_DIR', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) );
define( 'PAGELINES_PLUS_URL', rtrim( plugin_dir_url( __FILE__ ), '/' ) );

// Change to true to debug
define( 'PAGELINES_PLUS_DEBUG', false );

// WordPress actions
if( IS_ADMIN )
{
    add_action( 'admin_init', array( 'pagelinesPlus', 'environment_check' ) );
	add_action( 'admin_menu', array( 'pagelinesPlus', 'add_menu' ) );
	//Add assets
	add_action( 'init',       array( 'pagelinesPlus', 'assets_public' ) );
}
/**
 * Section uploader
 *
 * @package WordPress
 * @author Kyle King
 **/
class pagelinesPlus{
	/**
     * Constructor
     * Setup everything
     *
     * @author Kyle King
     */
    function __construct()
	{
		
    }

	function add_menu()
	{
		add_menu_page(
			'PageLines Plus', // Page title
			'PageLines Plus', // Menu Title
			'administrator', // Capability
			'pagelines-plus', // Menu Slug
			'', // Function
			( PAGELINES_PLUS_URL . '/icon.png'), // Icon url
			4 // Position
		);
		add_submenu_page( 
			'pagelines-plus', // parent_slug
			'Upload Section', // page_title
			'Upload Section', // menu_title
			'install_plugins', // capability
			'section-uploader', // menu_slug
			array( __CLASS__, 'upload_form' ) // function
		);
		remove_submenu_page( 'pagelines-plus', 'pagelines-plus' );
	}	
	
    /**
     * Form handler
     *
     * @return string $output Formatted HTML to be used in the theme
     * @author Kyle King
     */
    function upload_form()
    {
		//Setup message
		$messageHolder = '';
		if (PAGELINES_PLUS_DEBUG)
		{
			$debugHolder = '';
		}
		//Check if there was a file uploaded to process else just echo the html
		if (isset($_FILES['sectionupload']['name']))
		{
			if (PAGELINES_PLUS_DEBUG){
				// $_FILES array print out
				$debugFileHolder = print_r($_FILES, true);
				$debugHolder .= '<div class="alert alert-info"><h4>$_FILES Array</h4><pre>' . $debugFileHolder . '</pre></div>';
			}
			
			//Var to pageslines-sections folder
			$pl_section_dir = ABSPATH . 'wp-content/plugins/pagelines-sections/';
			
			//Var to file
			$uploadfile = $pl_section_dir . basename($_FILES['sectionupload']['name']);
			//remove ".zip" from end of the name 
			$uploadfile = str_replace('.zip', '', $uploadfile);
		
			//Error holder
			$ok=true; 
			
			//Check if pagelines-sections folder exists
			if (!file_exists($pl_section_dir)){$messageHolder .= '<div class="alert alert-error"><strong>ERROR! </strong>Pagelines Sections Plugin not installed</div>';$ok=false;}
			
			//Check if section exists
			if (file_exists($uploadfile)){$messageHolder .= '<div class="alert alert-error"><strong>ERROR! </strong>Section already exists</div>';$ok=false;}
			
			//Check the size
			if ($_FILES['sectionupload']['size'] > 350000)
			{$messageHolder .= '<div class="alert alert-error"><strong>ERROR! </strong>Your file is too large.</div>'; $ok=false;}
			
			//Check if it is a zip
			$uploaded_type = $_FILES['sectionupload']['type'];
			if (($uploaded_type=="application/x-zip-compressed") || 
				($uploaded_type=="application/zip") || 
				($uploaded_type=="multipart/x-zip") || 
				($uploaded_type=="application/s-compressed")) {
			} else{
				$messageHolder .= '<div class="alert alert-error"><strong>ERROR! </strong>You may only upload ZIP files.</div>';
				$ok=false;
			}
						
			//Here we check that everything is $ok 
			if ($ok==false){ 
				if (PAGELINES_PLUS_DEBUG){$messageHolder .= '<div class="alert" id="ERROR-1.1">File not uploaded</div>';}
			}else{
				$messageHolder .= '<pre>';
				//Unzip the file to the pagelines-sections dir
				$zip = new ZipArchive;
				$res = $zip->open($_FILES['sectionupload']['tmp_name']);
				if (PAGELINES_PLUS_DEBUG){
					//Get filenames
					$debugHolder .= '<div class="alert alert-info">Contents unzipped:<br>';
					for($i = 0; $i < $zip->numFiles; $i++) 
					{   
						$debugHolder .= 'Filename: ' . $zip->getNameIndex($i) . '<br />';
					}
					$debugHolder .= '</div>';
				}
				if ($res === TRUE) {
					$zip->extractTo($pl_section_dir);
					$zip->close();
					
					$messageHolder .= '<div class="alert alert-success"><strong>SUCCESS! </strong>You section has been installed successfully.</div>';
					if (PAGELINES_PLUS_DEBUG){$debugHolder .= '<div class="alert alert-info">File upload location: ' . $uploadfile . '</div>';}
					unlink($_FILES['sectionupload']['tmp_name']);  
				} else {
					$messageHolder .= '<div class="alert alert-error" id="ERROR-1.2">Sorry your file was not uploaded</div>';
				}
				
				/*
				if (move_uploaded_file($_FILES['sectionupload']['tmp_name'], $uploadfile)) {
					echo "File is valid, and was successfully uploaded to: {$uploadfile}\n";
				} else {
					echo "Sorry your file was not uploaded (E2) <br>";
				}
				*/

				$messageHolder .= "</pre>";
			}
		}
		
		echo '<div class="messages">';
		echo $messageHolder;
		if (PAGELINES_PLUS_DEBUG)
		{
		echo $debugHolder;
		}
		echo '</div>';
  
		$html = '<div class="wrap">'; 
		$html .= '<form method="post" enctype="multipart/form-data" action="' . self_admin_url( 'admin.php?page=section-uploader') . '">';
		$html .= wp_nonce_field('section-upload');
        $html .= '<h4>Install a Section in zip format</h4>';  
		$html .= '<p class="install-help">If you have a section in a .zip format, you may install it by uploading it here.</p>';
		$html .= '<label class="screen-reader-text" for="sectionupload">Plugin zip file</label>';  
		$html .= '<input type="file" id="sectionupload" name="sectionupload" value="" size="25">';
		$html .= '<input type="submit" class="button" value="Install Now">';
		$html .= '</form></div>';
  
		echo $html;  
    }
	
	/**
     * Enqueue the assets
     *
     * @return void
     * @author Kyle King
     */
    function assets_public()
    {
		wp_enqueue_style(
            'section-uploader'
            ,PAGELINES_PLUS_URL . '/style.css'
            ,PAGELINES_PLUS_VERSION
        );
	}

	
	
	 /**
     * Checks to ensure we have proper WordPress and PHP versions
     *
     * @return void
     * @author Kyle King
     */
    function environment_check()
    {
        $wp_version = get_bloginfo( 'version' );
        if( !version_compare( PHP_VERSION, '5.2', '>=' ) || !version_compare( $wp_version, '3.2', '>=' ) )
        {
            if( IS_ADMIN && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) )
            {
                require_once ABSPATH.'/wp-admin/includes/plugin.php';
                deactivate_plugins( __FILE__ );
                wp_die( __('Section Uploader requires WordPress 3.2 or higher, it has been automatically deactivated.') );
            }
            else
            {
                return;
            }
        }
		
		//Check if uploads 
		if ( ( !is_writable( WP_PLUGIN_DIR ) ) ? true : false )
			echo 'Sorry uploads do not work with this server config, please use FTP!';

		if ( EXTEND_NETWORK )
			echo 'Only network admins can upload sections!';
    }

}

$pagelinesPlus = new pagelinesPlus();
