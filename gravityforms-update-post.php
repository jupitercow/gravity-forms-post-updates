<?php
/**
 * @link              https://github.com/jupitercow/gravity-forms-post-updates
 * @since             1.2.18
 * @package           gform_update_post
 *
 * @wordpress-plugin
 * Plugin Name:       Gravity Forms: Post Updates
 * Plugin URI:        https://wordpress.org/plugins/gravity-forms-post-updates/
 * Description:       Allow Gravity Forms to update post content and the meta data associated with a post.
 * Version:           1.2.18
 * Author:            jcow
 * Author URI:        http://jcow.com/
 * Contributer:       ekaj
 * Contributer:       jr00ck
 * Contributer:       p51labs
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gform_update_post
 * Domain Path:       /languages
 */

if (! class_exists('gform_update_post') ) :

add_action( 'init', array('gform_update_post', 'init') );

class gform_update_post
{
	/**
	 * Class prefix
	 *
	 * @since 	1.2
	 * @var 	string
	 */
	const PREFIX = __CLASS__;

	/**
	 * Current version of plugin
	 *
	 * @since 	1.2
	 * @var 	string
	 */
	const VERSION = '1.2.18';

	/**
	 * Settings
	 *
	 * @since 	0.6.1
	 * @var 	string
	 */
	public static $settings = array();

	/**
	 * Holds the post to update
	 *
	 * @since 	0.6.1
	 * @var 	string
	 */
	private static $post;

	/**
	 * Holds the form info
	 *
	 * @since 	0.6.1
	 * @var 	string
	 */
	private static $form;

	/**
	 * Initialize the Class
	 *
	 * Add filters and actions and set up the options.
	 *
	 * @author  ekaj
	 */
	public static function init()
	{
		self::setup();

		// actions
		add_action( 'admin_init',                                 array(__CLASS__, 'admin_init') );

		// filters
		add_filter( 'shortcode_atts_gravityforms',                array(__CLASS__, 'gf_shortcode_atts'), 10, 3 );
	}

	/**
	 * Admin init
	 *
	 * @author  ekaj
	 * @since	1.2
	 */
	public static function admin_init()
	{
		if (! self::test_requirements() )
		{
			$plugin_data = get_plugin_data(__FILE__);
			self::$settings['name'] = $plugin_data['Name'];
			add_action( 'admin_notices', array(__CLASS__, 'admin_warnings'), 20);
		}
	}

	/**
	 * Add support for the new update attribute in the shortcode
	 *
	 * @author  ekaj
	 * @author  jr00ck
	 * @since	1.2
	 */
	public static function gf_shortcode_atts( $out, $pairs, $atts )
	{
		if ( isset($atts['update']) )
		{
			if ( is_numeric($atts['update']) )
			{
				do_action( self::PREFIX . '/setup_form', array('form_id'=>$atts['id'], 'post_id'=>$atts['update']) );
			}
			elseif ( 'false' == $atts['update'] )
			{
				remove_filter( 'gform_form_tag', array(__CLASS__, 'gform_form_tag') );
				remove_filter( 'gform_pre_render_' . $atts['id'], array(__CLASS__, 'gform_pre_render') );
				remove_filter( 'gform_pre_render', array(__CLASS__, 'gform_pre_render') );
			}
		}
		elseif ( in_array('update', $atts) )
		{
			do_action( self::PREFIX . '/setup_form', array('form_id'=>$atts['id']) );
		}

		return $out;
	}

	/**
	 * Set up the Class
	 *
	 * Set up options and check if a URL variable is sent.
	 *
	 * @author  ekaj
	 */
	public static function setup()
	{
		if ( self::test_requirements() )
		{
			add_filter( self::PREFIX . '/settings/get_path', array(__CLASS__, 'helpers_get_path'), 1 );
			add_filter( self::PREFIX . '/settings/get_dir',  array(__CLASS__, 'helpers_get_dir'), 1 );

			self::$settings = array(
				'request_id'     => apply_filters( self::PREFIX . '/request_id', 'gform_post_id' ),
				'nonce_delete'   => self::PREFIX . '_delete_upload',
				'nonce_update'   => self::PREFIX . '_update_post',
				'file_width'     => 46,
				'file_height'    => 60,
				'path'           => apply_filters( self::PREFIX . '/settings/get_path', __FILE__ ),
				'dir'            => apply_filters( self::PREFIX . '/settings/get_dir',  __FILE__ ),
				'unique_field'   => 'field_unique_custom_meta_value'
			);

			self::$settings  = array_merge( self::$settings, apply_filters( self::PREFIX . '/settings', self::$settings ) );

			// Adds support for unique custom fields
			add_action( 'gform_field_standard_settings', array(__CLASS__, 'gform_field_standard_settings'), 10, 2 );
			add_action( 'gform_editor_js',               array(__CLASS__, 'gform_editor_js') );
			add_filter( 'gform_tooltips',                array(__CLASS__, 'gform_tooltips') );

			// Custom post types plugin doesn't update taxonomies, it just adds to them, so you have to delete first
			add_filter( 'gform_after_submission',         array(__CLASS__, 'delete_custom_taxonomy_save'), 1, 2 );

			// Update validation for file/image upload
			add_filter( 'gform_field_validation',        array(__CLASS__, 'required_upload_field_validation'), 10, 4 );

			// Adds a really basic shortcode to set the plugin in action
			add_shortcode( self::PREFIX,                 array(__CLASS__, 'shortcode') );


			// Add an action to set up the form
			add_action( self::PREFIX . '/setup_form',    array(__CLASS__, 'setup_form') );


			// Add a filter to get an edit url
			add_filter( self::PREFIX . '/edit_url',      array(__CLASS__, 'get_edit_url'), 10, 2 );

			// Add a filter to get an edit link
			add_filter( self::PREFIX . '/get_edit_link', array(__CLASS__, 'get_edit_link'), 99 );

			// Adds a really basic shortcode to set the plugin in action
			add_shortcode( self::PREFIX . '_edit_link',  array(__CLASS__, 'shortcode_edit_link') );

			// Add an action to create a link
			add_action( self::PREFIX . '/edit_link',     array(__CLASS__, 'edit_link') );


			// Ajax file delete
			add_action( 'wp_ajax_' . self::PREFIX . '_delete_upload', array(__CLASS__, 'ajax_delete_upload') );
			if ( apply_filters( self::PREFIX . '/public_file_delete', true ) )
				add_action( 'wp_ajax_nopriv_' . self::PREFIX . '_delete_upload', array(__CLASS__, 'ajax_delete_upload') );

			// Set up from url query vars and process submitted forms.
			self::process_request();
		}
	}

	/**
	 * Get the plugin path
	 *
	 * Calculates the path (works for plugin / theme folders). These functions are from Elliot Condon's ACF plugin.
	 *
	 * @since: 0.6
	 */
	public static function helpers_get_path( $file )
	{
	    return trailingslashit( dirname($file) );
	}

	/**
	 * Get the plugin directory
	 *
	 * Calculates the directory (works for plugin / theme folders). These functions are from Elliot Condon's ACF plugin.
	 *
	 * @since: 0.6
	 */
	public static function helpers_get_dir( $file )
	{
        $dir = trailingslashit( dirname($file) );
        $count = 0;

        // sanitize for Win32 installs
        $dir = str_replace('\\' ,'/', $dir);

        // if file is in plugins folder
        $wp_plugin_dir = str_replace('\\' ,'/', WP_PLUGIN_DIR); 
        $dir = str_replace($wp_plugin_dir, plugins_url(), $dir, $count);

        if ( $count < 1 )
        {
	        // if file is in wp-content folder
	        $wp_content_dir = str_replace('\\' ,'/', WP_CONTENT_DIR); 
	        $dir = str_replace($wp_content_dir, content_url(), $dir, $count);
        }

        if ( $count < 1 )
        {
	        // if file is in ??? folder
	        $wp_dir = str_replace('\\' ,'/', ABSPATH); 
	        $dir = str_replace($wp_dir, site_url('/'), $dir);
        }

        return $dir;
    }

	/**
	 * Just returns the request_id
	 *
	 * @author  ekaj
	 */
	public static function request_id()
	{
		return apply_filters( self::PREFIX . '/request_id', self::$settings['request_id'] );
	}

	/**
	 * Make sure that any neccessary dependancies exist
	 *
	 * @author  ekaj
	 * @return	bool
	 */
	public static function test_requirements()
	{
		// Look for GF
		if (! class_exists('RGForms') )      return false;
		// Make sure the Form Model is there also
		if (! class_exists('GFFormsModel') ) return false;
		// Look for the GFCommon object
		if (! class_exists('GFCommon') )     return false;

		return true;
	}

	/**
	 * If Gravity Forms isn't installed, add an error to let user know this won't be useable.
	 *
	 * @author  ekaj
	 * @return	void
	 */
    public static function admin_warnings()
    {
		$message = sprintf( __('<strong>%s</strong> requires Gravity Forms to be installed. Please <a href="http://www.gravityforms.com/">download the latest version</a> to use this plugin.', self::PREFIX), self::$settings['name'] );
		?>
		<div class="error">
			<p>
				<?php echo $message; ?>
			</p>
		</div>
		<?php
    }

	public static function scripts_and_styles()
	{
		// register acf scripts
		wp_register_script( self::PREFIX, plugins_url( 'js/scripts.js', __FILE__ ), array('jquery'), self::VERSION );
		$args = array(
			'url'                  => admin_url( 'admin-ajax.php' ),
			'action'               => self::PREFIX . '_delete_upload',
			'prefix'               => self::PREFIX,
			'spinner'              => admin_url( 'images/loading.gif' ),
			'nonce'                => wp_create_nonce( self::$settings['nonce_delete'] )
		);
		wp_localize_script( self::PREFIX, 'gform_up', $args );
		wp_enqueue_script( array(
			self::PREFIX
		) );
	}

	/**
	 * Manage URL Query
	 *
	 * Check if a url variable has been submitted correctly, and then trigger.
	 *
	 * @author  ekaj
	 * @return	void	
	 */
	public static function process_request()
	{
		$post_id = false;
		$request_key = apply_filters(self::PREFIX . '/request_id', self::$settings['request_id']);
		if (! empty($_REQUEST[$request_key]) )
			$post_id = $_REQUEST[$request_key];

		if ( $post_id && is_numeric($post_id) )
		{
			if ( 'POST' == $_SERVER['REQUEST_METHOD'] || (! empty($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], self::$settings['nonce_update'])) )
			{
				do_action( self::PREFIX . '/setup_form', $post_id );
			}
		}
	}

	/**
	 * Get the Post Object
	 *
	 * Used to get the post from id along with taxonomies.
	 *
	 * @author  p51labs
	 * @author  ekaj
	 * @param	int $post_id
	 * @return	void
	 */
	public static function get_post_object( $post_id )
	{
		self::$post = get_post($post_id);

		self::$post->taxonomies = array();

		self::get_post_taxonomies();
	}

	/**
	 * Add taxonomies to post object
	 *
	 * @author  ekaj
	 * @return	void
	 */
	public static function get_post_taxonomies()
	{
		if (! is_object(self::$post) ) return;

		$taxonomies = get_object_taxonomies(self::$post->post_type);
		foreach ( $taxonomies as $taxonomy )
		{
			$key = $taxonomy;
			if ( 'post_tag' == $taxonomy ) $key = 'post_tags';
			if ( 'category' == $taxonomy ) $key = 'post_category';
			self::$post->taxonomies[$key] = wp_get_object_terms(self::$post->ID, $taxonomy);
		}
	}

	/**
	 * Build Edit URI
	 *
	 * Create a url with GET variables to the form for editing.
	 *
	 * @author  ekaj
	 * @param	int		$post_id ID of the post you want to edit
	 * @param	string	$url By default the permalink of the post that you want to edit is used, use this to send to a different page to edit the post whose id is provided
	 * @return	void
	 */
	public static function get_edit_url( $post_id=false, $url=false )
	{
		if (! $post_id && ! empty($GLOBALS['post']) ) $post_id = $GLOBALS['post']->ID;

		// If the url parameter is a post_id, get the url to that post
		if ( is_numeric($url) ) $url = get_permalink($url);
		// If no url, use the post_id to get the url to the post being edited
		if (! $url ) $url = get_permalink($post_id);

		$request_id = apply_filters(self::PREFIX . '/request_id', self::$settings['request_id']);
		return add_query_arg( array($request_id => $post_id, 'nonce' => wp_create_nonce(self::$settings['nonce_update'])), $url );
	}

	/**
	 * Build Edit Link and return
	 *
	 * Create anchor link with the edit URI. Uses self::edit_url to create the URI.
	 *
	 * Arguments:
	 *	post_id (int) is the id of the post you want to edit
	 *	url (string|int) is either the full url of the page where your edit form resides, or an id for the page where the edit form resides
	 *	test (string) is the link text
	 *	title (string) is the title attribute of the anchor tag
	 *
	 * @author  ekaj
	 * @since   1.2.7
	 * @param	array|string $args The arguments to use when creating a link
	 * @return	void
	 */
	public static function get_edit_link( $args=array() )
	{
		$defaults = array(
			'post_id' => false,
			'url'     => false,
			'text'    => __("Edit Post", self::PREFIX),
			'title'   => false,
			'class'   => '',
		);
		$args = wp_parse_args( $args, $defaults );

		$output = '';

		// Get the current post id, if none is provided
		if (! $args['post_id'] && ! empty($GLOBALS['post']->ID) ) $args['post_id'] = $GLOBALS['post']->ID;

		if ( self::current_user_can( $args['post_id'] ) )
		{
			// Add the link text to the title if no link title is specified
			if (! $args['title'] ) $args['title'] = $args['text'];
			$output .= '<a class="' . esc_attr(self::PREFIX) . '_link' . ($args['class'] ? ' ' . esc_attr($args['class']) : '') . '" href="' . esc_attr( apply_filters(self::PREFIX.'/edit_url', $args['post_id'], $args['url']) ) . '" title="' . esc_attr($args['title']) . '">' . esc_html($args['text']) . '</a>';
		}

		return $output;
	}

	/**
	 * Build Edit Link
	 *
	 * Create anchor link with the edit URI. Uses self::edit_url to create the URI.
	 *
	 * Arguments:
	 *	post_id (int) is the id of the post you want to edit
	 *	url (string|int) is either the full url of the page where your edit form resides, or an id for the page where the edit form resides
	 *	test (string) is the link text
	 *	title (string) is the title attribute of the anchor tag
	 *
	 * @author  ekaj
	 * @param	array|string $args The arguments to use when creating a link
	 * @return	void
	 */
	public static function edit_link( $args=array() )
	{
		echo apply_filters( self::PREFIX . '/get_edit_link', $args );
	}

	/**
	 * Create a link to edit a post
	 *
	 * @author  ekaj
	 * @since   1.2.6
	 * @type	shortcode
	 * @return	void	
	 */
	public static function shortcode_edit_link( $atts )
	{
		$args = shortcode_atts( array(
			'post_id' => false,
			'url' => false
		), $atts );

		return apply_filters( self::PREFIX . '/get_edit_link', $args );
	}

	/**
	 * Create a simple short code to setup a form
	 *
	 * Set up a form on a post or page to be editable.
	 *
	 * @author  ekaj
	 * @type	shortcode
	 * @return	void	
	 */
	public static function shortcode( $atts )
	{
		extract( shortcode_atts( array(
			'post_id' => false,
		), $atts ) );

		do_action( self::PREFIX . '/setup_form', $post_id );
	}

	/**
	 * Set Up Form
	 *
	 * Sets up a form from post id for editing.
	 *
	 * @author  ekaj
	 * @param	int		$post_id id of the post you want to edit
	 * @return	void
	 */
	public static function setup_form( $args=array() )
	{
		if ( is_numeric($args) )
		{
			$post_id = $args;
			$form_id = false;
		}
		elseif ( is_array($args) )
		{
			$defaults = array(
				'post_id' => 0,
				'form_id' => 0,
			);
			$args = wp_parse_args( $args, $defaults );
			extract($args);
		}
		else
		{
			return false;
		}

		if (! $post_id && ! empty($GLOBALS['post']->ID) ) $post_id = $GLOBALS['post']->ID;

		self::get_post_object($post_id);

		if ( is_object(self::$post) )
		{
			// Make sure taxonomies get set up
			add_action( 'wp',                      array(__CLASS__, 'get_post_taxonomies') );

			// Load scripts and styles
			add_action( 'gform_enqueue_scripts',   array(__CLASS__, 'scripts_and_styles') );

			// Add the request_id to the form as a hidden field. This triggers our post data addition that will update the post
			add_filter( 'gform_form_tag',          array(__CLASS__, 'gform_form_tag'), 50, 2 );

			if ( $form_id )
			{
				// Add the existing information to the form
				add_filter( 'gform_pre_render_' . $form_id,        array(__CLASS__, 'gform_pre_render') );
			}
			else
			{
				// Add the existing information to the form
				add_filter( 'gform_pre_render',        array(__CLASS__, 'gform_pre_render') );
			}

			// Updates the post data with post id, so post gets updated instead of creating a new one
			add_action( 'gform_post_data',         array(__CLASS__, 'gform_post_data'), 10, 2 );

			// Update file field
			add_filter( 'gform_field_content',     array(__CLASS__, 'gform_field_content'), 10, 5 );
		}
	}

	/**
	 * AJAX Delete Upload Wrapper
	 *
	 * @author  ekaj
	 * @return	void
	 */
	public static function ajax_delete_upload()
	{
   		// vars
		$options = array(
			'nonce' => '',
			'post_id' => 0,
			'form_id' => 0,
			'file' => false,
			'featured' => 0,
			'meta' => ''
		);

		// load post options
		$options = array_merge($options, $_POST);

		// test options
		if (! $options['post_id'] || ! $options['form_id'] || (! $options['featured'] && ! $options['meta']) ) die('Missing information');

		// verify nonce
		if (! wp_verify_nonce($options['nonce'], self::$settings['nonce_delete']) ) die('Are you sure?');

		if ( $options['featured'] )
		{
			// Delete the attachment, if it works, remove the featured meta from post
			if ( wp_delete_attachment( $options['featured'] ) )
				delete_post_meta( $options['post_id'], '_thumbnail_id' );
		}
		elseif ( $options['meta'] )
		{
			self::delete_upload( $options );
		}
		else
		{
			die(0);
		}

		die('1');
	}

	/**
	 * Delete Upload
	 *
	 * @author  ekaj
	 * @return	void
	 */
	public static function delete_upload( $options )
	{
		$file     = ( $options['file'] ) ? $options['file'] : get_post_meta( $options['post_id'], $options['meta'], true );
		$filetype = wp_check_filetype( $file );

		// get the thumbnail name
		$path_to_file = GFFormsModel::get_upload_path($options['form_id']);
		$url_to_file  = GFFormsModel::get_upload_url($options['form_id']);
		$file_path    = str_replace($url_to_file, $path_to_file, $file);

		// Delete the file
		@unlink($file_path);

		// Attempt to delete the thumbnail if an image
		if ( 'image/' == substr($filetype['type'], 0, 6) )
		{
			$width       = apply_filters( self::PREFIX . '/image/width', apply_filters(self::PREFIX . '/file/width', self::$settings['file_width']) );
			$height      = apply_filters( self::PREFIX . '/image/height', apply_filters(self::PREFIX . '/file/height', self::$settings['file_height']) );

			// get the thumbnail name
			$old_ext     = '.' . $filetype['ext'];
			$new_ext     = '-thumb.' . $filetype['ext'];
			$resized     = str_replace($old_ext, $new_ext, $file_path);
			@unlink($resized);
		}

		// Remove the meta from the post
		if ( $options['file'] ) {
			return delete_post_meta( $options['post_id'], $options['meta'], $options['file'] );
		} else {
			return delete_post_meta( $options['post_id'], $options['meta'] );
		}
	}

	/**
	 * Update File Fields
	 *
	 * Add the existing file to file field in form.
	 *
	 * @author  ekaj
	 * @type	filter
	 */
	public static function gform_field_content( $content, $field, $value, $lead_id, $form_id )
	{
		if (! empty($field['type']) && 'post_image' == $field['type'] && ! empty($field['postFeaturedImage']) )
		{
			$thumb_id  = get_post_thumbnail_id(self::$post->ID);
			if ( is_numeric($thumb_id) )
			{
				$thumb_url = wp_get_attachment_image_src($thumb_id, 'thumbnail', true);
				$full_url  = wp_get_attachment_image_src($thumb_id, 'full', true);
				$file      = $full_url[0];
				$filename  = basename($file);
	
				$image = '<span style="display:inline-block; width:' . esc_attr($thumb_url[1]) . 'px; height:' . esc_attr($thumb_url[2]) . 'px; overflow:hidden;"><img src="' . esc_url($thumb_url[0]) . '" /></span>';
	
				ob_start();
				?>
				<div class="<?php echo esc_attr(self::PREFIX); ?>_upload_container">
					<p class="<?php echo esc_attr(self::PREFIX); ?>_upload_link" style="margin:1em 0 0 0;">
						<a target="_blank" href="<?php echo esc_url($file); ?>" style="border:none; margin:0 1em 0 0;">
							<?php echo $image; ?>
						</a>
						<a target="_blank" href="<?php echo esc_url($file); ?>">
							<strong><?php echo esc_html( apply_filters(self::PREFIX . '/file/name', $filename) ); ?></strong>
						</a>
					</p>
					<?php if ( apply_filters( self::PREFIX . '/public_file_delete', true ) && apply_filters( self::PREFIX . '/public_file_delete/featured', true ) ) : ?>
						<a class="<?php echo esc_attr(self::PREFIX); ?>_delete_link" data-post_id="<?php echo esc_attr(self::$post->ID); ?>" data-form_id="<?php echo esc_attr($form_id); ?>" data-meta="0" data-featured="<?php echo $thumb_id; ?>" href="#<?php _e("delete_requires_javascript", self::PREFIX); ?>" title="<?php _e("Delete Upload", self::PREFIX); ?>">
							<?php _e("Delete", self::PREFIX); ?>
						</a>
					<?php endif; ?>
				</div>
				<?php
				$content .= ob_get_clean();
			}
		}
		elseif (! empty($field['inputType']) && 'fileupload' == $field['inputType'] && ! empty($field['defaultValue']) )
		{
			if (! empty($field['multipleFiles']) )
			{
				$content .= '<a class="' . esc_attr(self::PREFIX) . '_addmore_link" href="#' . __("requires_javascript", self::PREFIX) . '" title="' . __("Add more uploads", self::PREFIX) . '">' . __("Add more", self::PREFIX) . '</a>';
				$file_array = explode(', ', $field['defaultValue']);
				if ( $file_array )
				{
					foreach ( $file_array as $file )
					{
						$content .= self::create_uploaded_file( $file, $field, $form_id );
					}
				}
			}
			else
			{
				$content .= self::create_uploaded_file( $field['defaultValue'], $field, $form_id );
			}
		}
		return $content;
	}

	/**
	 * Create an upload.
	 *
	 * @author  ekaj
	 * @return	void
	 */
	public static function create_uploaded_file( $file, $field, $form_id )
	{
		$basename    = basename($file);
		$filetype    = wp_check_filetype( $basename );
		$mime        = $filetype['type'];

		$width       = apply_filters(self::PREFIX . '/file/width', self::$settings['file_width']);
		$height      = apply_filters(self::PREFIX . '/file/height', self::$settings['file_height']);

		// If this is an image, set up and create a thumbnail
		if ( 'image/' == substr($mime, 0, 6) )
		{
			$image_url = '';
			if ( apply_filters(self::PREFIX . '/image/resize', true) )
			{
				// Get settings for image thumb
				$width       = apply_filters(self::PREFIX . '/image/width', $width);
				$height      = apply_filters(self::PREFIX . '/image/height', $height);
				$crop        = apply_filters(self::PREFIX . '/image/crop', true);

				// Create the file local path
				$basedir	= GFFormsModel::get_upload_path($form_id);
				$baseurl	= GFFormsModel::get_upload_url($form_id);
				$filename   = str_replace($baseurl, $basedir, $file);

				if ( is_file($filename) )
				{
					// Make sure the server supports resize and save
					$img_editor_test = wp_image_editor_supports( array(
					    'methods' => array(
					        'resize',
					        'save'
					    )
					) );
					if ( true === $img_editor_test && is_writable($basedir) )
					{
						// Get the image editor
						$image_editor = wp_get_image_editor( $filename );
						if (! is_wp_error($image_editor) )
						{
							// Create thumbnail filename
							$thumbname = $image_editor->generate_filename( 'thumb' );
							// Test if thumbnail exists
							$thumb_exists = file_exists($thumbname);
							if ( $thumb_exists ) $thumbsize = getimagesize( $thumbname );

							// If no thumbnail, or the size has changed, generate a new one
							if (! $thumb_exists || $thumbsize[0] != $width || $thumbsize[1] != $height )
							{
								$image_editor->resize( $width, $height, $crop );
								$resized = $image_editor->save($thumbname);
								if (! is_wp_error($resized) )
								{
									$pathinfo  = pathinfo($file);
									$image_url = $pathinfo['dirname'] . '/' . $resized['file'];
								}
							}
							// Otherwise use the existing file
							else
							{
								$image_url = str_replace($basedir, $baseurl, $thumbname);
							}
						}
					}
				}

			}

			// If there is no thumbnail at this point, use the file itself
			if (! $image_url ) $image_url = $file;
		}
		// Not a file then get a mimetype icon from WP
		else
		{
			$image_url = wp_mime_type_icon( wp_ext2type($filetype['ext']) );
		}

		$image = '<span style="display:inline-block; width:' . esc_attr($width) . 'px; height:' . esc_attr($height) . 'px; overflow:hidden;"><img src="' . esc_url($image_url) . '" /></span>';

		ob_start();
		?>
		<div class="<?php echo esc_attr(self::PREFIX); ?>_upload_container">
			<p class="<?php echo esc_attr(self::PREFIX); ?>_upload_link" style="margin:1em 0 0 0;">
				<a target="_blank" href="<?php echo esc_url($file); ?>" style="border:none; margin:0 1em 0 0;">
					<?php echo $image; ?>
				</a>
				<a target="_blank" href="<?php echo esc_url($file); ?>">
					<strong><?php echo esc_html( apply_filters(self::PREFIX . '/file/name', $basename) ); ?></strong>
				</a>
			</p>
			<?php if ( apply_filters( self::PREFIX . '/public_file_delete', true ) ) : ?>
				<a class="<?php echo esc_attr(self::PREFIX); ?>_delete_link" data-post_id="<?php echo esc_attr(self::$post->ID); ?>" data-form_id="<?php echo esc_attr($form_id); ?>" data-meta="<?php echo esc_attr($field['postCustomFieldName']); ?>" data-featured="0" href="#<?php _e("delete_requires_javascript", self::PREFIX); ?>" title="<?php _e("Delete Upload", self::PREFIX); ?>">
					<?php _e("Delete", self::PREFIX); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Add Request Field to Form
	 *
	 * This field will trigger the post data updates when their isn't a GET variable.
	 *
	 * @author  p51labs
	 * @author  ekaj
	 * @type	filter
	 */
	public static function gform_form_tag( $form_tag, $form )
	{
		$form_tag .= '<input type="hidden" name="' . apply_filters(self::PREFIX . '/request_id', self::$settings['request_id']) . '" value="' . self::$post->ID . '" class="gform_hidden" />';
		return $form_tag;
	}

	/**
	 * Populate Form Fields
	 *
	 * Add any exisiting info to the form fields from our edit post.
	 *
	 * @author  p51labs
	 * @type	filter
	 */
	public static function gform_pre_render( $form )
	{
		if ( self::current_user_can() )
		{
			$meta = get_post_custom( self::$post->ID );

			foreach ( $form['fields'] as &$field )
			{
				$field_type = $field['type'];

				if ( isset(self::$post->$field_type) && is_string(self::$post->$field_type) )
				{
					$field = self::populate_element($field, $field_type, self::$post->$field_type);
				}
				elseif ( 'post_custom_field' == $field_type && isset($meta[$field['postCustomFieldName']]) )
				{
					$multi_fields = array('multiselect', 'checkbox', 'list');
					if ( in_array($field['inputType'], $multi_fields) || ! empty($field['multipleFiles']) ) {
						$value = apply_filters( self::PREFIX . '_multi_fields', $meta[ $field['postCustomFieldName'] ] );
					} else {
						$value = end($meta[ $field['postCustomFieldName'] ]);
					}
					$field = self::populate_element($field, $field['inputType'], $value);
				}
				elseif ( isset(self::$post->taxonomies[$field_type]) )
				{
					$value = array();
					foreach ( self::$post->taxonomies[$field_type] as $object )
					{
						if ( 'post_tags' == $field_type ) {
							$value[] = $object->name;
						} else {
							$value[] = $object->term_id;
						}
					}

					$field = self::populate_element($field, $field_type, $value);
				}
				elseif (! empty($field['populateTaxonomy']) && isset(self::$post->taxonomies[$field['populateTaxonomy']]) )
				{
					$value = array();
					if ( self::$post->taxonomies[$field['populateTaxonomy']] )
					{
						foreach ( self::$post->taxonomies[$field['populateTaxonomy']] as $object )
						{
							$value[] = $object->term_id;
						}
					}
					$field = self::populate_element($field, 'populateTaxonomy', $value);
				}

				if (! empty($field['defaultValue']) && ! empty($field['conditionalLogic']) && is_array($field['conditionalLogic']) )
				{
					foreach ( $field['conditionalLogic']['rules'] as $rule )
					{
						if (! $form['conditional'] ) {
							$form['conditional'] = array();
						}
						$form['conditional'][] = $rule['fieldId'];
					}
				}
			}
		}

		return $form;
	}

	/**
	 * Populate Field Elements
	 *
	 * Populate specific form fields based on type.
	 *
	 * @author  p51labs
	 * @author  ekaj
	 * @param	array	$field
	 * @param	string	$field_type
	 * @param	mixed	$value
	 * @return	array	$field Modified $field array
	 */
	public static function populate_element( $field, $field_type, $value )
	{
		$value = maybe_unserialize($value);

		switch ( $field_type )
		{
			case 'post_category':

				$field['allowsPrepopulate'] = true;
				$field['inputName'] = $field_type;

				self::$settings['cat_value'] = $value;
				add_filter( 'gform_field_value_' . $field['inputName'], array(__CLASS__, 'return_category_field_value'), 10, 2 );
				#add_filter( 'gform_field_value_' . $field['inputName'], function($value) use($value) { return $value; } );
				break;

			case 'populateTaxonomy':

				$field['allowsPrepopulate'] = true;
				$field['inputName'] = $field['populateTaxonomy'];

				self::$settings['tax_value'][$field['inputName']] = $value;
				add_filter( 'gform_field_value_' . $field['inputName'], array(__CLASS__, 'return_taxonomy_field_value') , 10, 2 );

				$value = (! is_array($value) ) ? array($value) : $value;

				if ( version_compare(GFCommon::$version, '1.9') >= 0 )
				{
					if ( isset($field->choices) )
					{
						foreach ( $field->choices as &$choice ) {
							$choice['isSelected'] = ( in_array($choice['value'], $value) ) ? true : '';
						}
					}
				}
				else
				{
					if ( isset($field['choices']) )
					{
						foreach ( $field['choices'] as &$choice ) {
							$choice['isSelected'] = ( in_array($choice['value'], $value) ) ? true : '';
						}
					}
				}

				#add_filter( 'gform_field_value_' . $field['inputName'], function($value) use($value) { return $value; } );
				break;

			case 'list':

				if ( is_array($value) )
				{
					$new_value = array();
					foreach ( $value as $row )
					{
						$row_array = explode('|', $row);
						$new_value = array_merge($new_value, $row_array);
					}
					$field['allowsPrepopulate'] = true;
					$field['inputName'] = $field['postCustomFieldName'];

					$value = $new_value;
					add_filter( 'gform_field_value_' . $field['inputName'], function($value) use($value) { return $value; } );
				}
				break;

			#case 'select':
			case 'multiselect':
			case 'checkbox':
			#case 'radio':

				$value = (! is_array($value) ) ? array($value) : $value;

				if ( version_compare(GFCommon::$version, '1.9') >= 0 )
				{
					if ( isset($field->choices) )
					{
						foreach ( $field->choices as &$choice ) {
							$choice['isSelected'] = ( in_array($choice['value'], $value) ) ? true : '';
						}
					}
				}
				else
				{
					if ( isset($field['choices']) )
					{
						foreach ( $field['choices'] as &$choice ) {
							$choice['isSelected'] = ( in_array($choice['value'], $value) ) ? true : '';
						}
					}
				}

				break;

			default:

				if ( is_array($value) )
				{
					$value = implode(', ', $value);
				}

				$field['defaultValue'] = $value;
				break;
		}
		return $field;
	}

	/**
	 * Return value for taxonomy fields
	 *
	 * @author  ekaj
	 * @return	value
	 */
	public static function return_category_field_value( $value, $field )
	{
		return (! empty(self::$settings['cat_value']) ) ? self::$settings['cat_value'] : $value;
	}
	public static function return_taxonomy_field_value( $value, $field )
	{
		return (! empty(self::$settings['tax_value'][$field['inputName']]) ) ? self::$settings['tax_value'][$field['inputName']] : $value;
	}

	/**
	 * Remove Custom Taxonomies
	 *
	 * While nice, custom post types plugin only adds taxonomies on intead of updating the whole set, so you can't ever remove them. This wipes them out and then they can be added through the custom post types plugin.
	 *
	 * @author  ekaj
	 * @return	void
	 */
	public static function delete_custom_taxonomy_save( $entry, $form )
	{
		// Check if the submission contains a WordPress post
		if (! empty($entry['post_id']) )
		{
			foreach( $form['fields'] as &$field )
			{
				$taxonomy = false;
				if ( array_key_exists('populateTaxonomy', $field) )
					$taxonomy = $field['populateTaxonomy'];

				if ( $taxonomy ) wp_set_object_terms( $entry['post_id'], NULL, $taxonomy );
			}
		}
	}

	/**
	 * Update Post Data
	 *
	 * Adds post id to cause post to be udpated instead of inserted.
	 *
	 * Adds the support for unique custom fields which clean up the database. This does force a new post_meta entry every save though. 
	 * This is forced because GF uses "add_post_meta" instead of "update_post_meta".
	 *
	 * @author  p51labs
	 * @author  ekaj
	 * @type	action
	 */
	public static function gform_post_data( $post_data, $form )
	{
		if ( self::current_user_can() )
		{
			// Always make these unique, they are multiple choice fields and they become a mess otherwise
			$always_unique = array('multiselect','checkbox','list','fileupload');
			// If a custom field is unique, delete the old value(s) before we proceed
			foreach ( $form['fields'] as $field )
			{
				// Make sure we are dealing with a custom field
				if ( 'post_custom_field' != $field['type'] ) {
					continue;
				}

				// if the field is specifically not unique and is not part of the unique array
				if ( isset( $field['postCustomFieldUnique'] ) && false === $field['postCustomFieldUnique'] && (! in_array($field['inputType'], $always_unique) || ! empty($field['multipleFiles'])) ) {
					continue;
				}

				if ( 'fileupload' == $field['inputType'] && empty( $_FILES['input_' . $field['id']]['tmp_name'] ) ) {
					continue;
				}

				delete_post_meta(self::$post->ID, $field['postCustomFieldName']);
			}

			$post_data['ID']             = self::$post->ID;
			$post_data['post_author']    = self::$post->post_author;
			$post_data['post_status']    = self::$post->post_status;
			$post_data['post_date']      = self::$post->post_date;
			$post_data['post_date_gmt']  = self::$post->post_date_gmt;
			$post_data['comment_status'] = self::$post->comment_status;
			$post_data['ping_status']    = self::$post->ping_status;
			$post_data['post_password']  = self::$post->post_password;
			$post_data['post_name']      = self::$post->post_name;
			$post_data['post_parent']    = self::$post->post_parent;
			$post_data['menu_order']     = self::$post->menu_order;
			$post_data['post_type']      = self::$post->post_type;
		}

		return $post_data;
	}

	/**
	 * Test if an image already exists, for validation on required fields
	 *
	 * @author  ekaj
	 * @since	0.6.4
	 * @return	void
	 */
	public static function required_upload_field_validation( $result, $value, $form, $field )
	{
		if ( ('post_image' == $field['type'] || 'fileupload' == $field['inputType']) && $field['isRequired'] && ! $result['is_valid'] ) // || 'post_image' == $field['type']
		{
			if ( ('post_image' == $field['type'] && has_post_thumbnail(self::$post->ID)) || ('fileupload' == $field['inputType'] && get_post_meta( self::$post->ID, $field['postCustomFieldName'], true )) )
			{
				$result['is_valid'] = true;
			}
		}
		return $result;
	}


	/**
	 * Check User Permissions
	 *
	 * Check permissions for current user that they are allowed to edit the post/page.
	 *
	 * @author  ekaj
	 * @return	void
	 */
	public static function current_user_can( $post_id=false )
	{
		$public_edit = apply_filters( self::PREFIX . '/public_edit', false );
		if ( true === $public_edit )
		{
			return true;
		}
		elseif ( 'loggedin' === $public_edit && is_user_logged_in() )
		{
			return true;
		}
		elseif ( $public_edit && is_user_logged_in() )
		{
			if ( is_array($public_edit) )
			{
				foreach ( $public_edit as $cap )
				{
					if ( current_user_can($public_edit) ) return true;
				}
				return false;
			}
			else
			{
				return current_user_can($public_edit);
			}
		}

		if ( $post_id && is_numeric($post_id) )
		{
			$post_type = get_post_type( $post_id );
		}
		elseif ( is_object(self::$post) )
		{
			$post_id   = self::$post->ID;
			$post_type = self::$post->post_type;
		}
		else
		{
			return false;
		}

		$capability = ( 'page' == $post_type ) ? 'edit_pages' : 'edit_posts';

		if ( current_user_can($capability, $post_id) ) return true;

		return false;
	}

	/**
	 * Add support for unique custom fields
	 *
	 * This is a great feature from original plugin that cleans up your database by updating post meta instead of creating new meta every time.
	 *
	 * This function adds the checkbox.
	 *
	 * @author  p51labs
	 * @author  ekaj
	 * @type	action
	 * @return	void
	 */
	public static function gform_field_standard_settings( $position, $form_id )
	{
		if ( 700 == $position ) :
		?>
			<li class="post_custom_field_unique field_setting">

				<input type="checkbox" id="<?php echo esc_attr(self::$settings['unique_field']); ?>" onclick="SetFieldProperty('postCustomFieldUnique', this.checked);" />
				<label for="<?php echo esc_attr(self::$settings['unique_field']); ?>" class="inline">
					<?php _e("Unique Custom Field?", self::PREFIX); ?>
					<?php gform_tooltip('form_' . self::$settings['unique_field']) ?>
				</label>

			</li>
		<?php
		endif;
	}

	/**
	 * Add support for unique custom fields
	 *
	 * Adds the js for the checkbox.
	 *
	 * @author  p51labs
	 * @author  ekaj
	 * @type	action
	 * @return	void
	 */
	public static function gform_editor_js()
	{
		?>
		<script type="text/javascript">
			var fieldTypes   = ['post_custom_field'];
			var excludeTypes = ['checkbox','multiselect','list','fileupload'];
			for ( var i=0; i<fieldTypes.length; i++ )
			{
				fieldSettings[fieldTypes[i]] += ', .post_custom_field_unique';
			}

			jQuery(document).bind('gform_load_field_settings', function(event, field, form) {
				var $input = jQuery('#<?php echo esc_js(self::$settings['unique_field']); ?>');
				// Hides for multiple choice fields
				if ( -1 === jQuery.inArray(field['inputType'], excludeTypes) )
				{
					//console.log('started');
					if (! field.hasOwnProperty('postCustomFieldUnique') )
					{
						field['postCustomFieldUnique'] = true;
					}
					$input.attr('checked', field['postCustomFieldUnique'] == true);
				}
				else
				{
					$input.closest('.field_setting').hide();
				}
			});
		</script>
		<?php
	}

	/**
	 * Add support for unique custom fields
	 *
	 * This function adds the tooltip for the checkbox.
	 *
	 * @author  p51labs
	 * @author  ekaj
	 * @type	filter
	 * @return	void
	 */
	public static function gform_tooltips($tooltips)
	{
		$tooltips['form_' . self::$settings['unique_field']] = __("<h6>Unique Meta Field</h6>Check this box to ensure this meta field is saved as unique.", self::PREFIX);
		return $tooltips;
	}
}

endif;