=== Plugin Name ===
Contributors: jcow, ekaj, jr00ck, p51labs
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=jacobsnyder%40gmail%2ecom&lc=US&item_name=Jacob%20Snyder&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: gravity forms, update posts, frontend, front end
Requires at least: 3.6.1
Tested up to: 4.1.1
Stable tag: 1.2.17
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to use Gravity Forms to update any post on the front end.


== Description ==

**Requires PHP 5.3 or greater**

Allows you to use Gravity Forms to update any post on the front end. If you use the "Gravity Forms + Custom Post Types", you can even update custom post types and use custom taxonomies.

= Features =

* Supports custom field file uploading and deletion with thumbnails or mime type icons for existing items.
* Fixed a bugs on multi selects and checkboxes.
* Fixed bug on Categories.
* Completely removed the ability to delete posts.
* There are some filters to customize things now.
* Adds non-query-var template method to setup a form.
* Adds a really basic shortcode to setup a form (UPDATE: This is still supported, but it is better to use the addition, below, to the gravityform shortcode).
* Adds an additional attribute to the gravityform shortcode: "update"

= SHORTCODE =

`[gravityform id="1" update] // Loads current post for editing`

`[gravityform id="1" update="34"] // Loads post where ID=34 for editing`

We worked with Rocketgenius, makers of Gravity Forms, to get a small upgrade added that allows us to extend their shortcode, so now you can simply add the "update" attribute to the normal "gravityform" shortcode. If you only add "update", it will load the current post in to update. If you add an integer to the update attribute, it will use that to load a post by its ID.


= URL QUERY VARIABLE =

1. At the heart, it is pretty similar to how it was, but now there is a nonce required to activate it.
2. So you should use the action to create your links.

`do_action('gform_update_post/edit_link');`

`do_action('gform_update_post/edit_link', array(
	'post_id' => $post->ID,
	'url'     => home_url('/edit_post/'),
) );`

**Arguments (query string or array)**

* `post_id` (int) (optional) The id of the post you want to edit. Default: global $post->ID
* `url` (string|int) (optional) Either the full url of the page where your edit form resides, or an id for the page/post where the edit form resides. Default: get_permalink()
* `text` (string) (optional) The link text. Default: "Edit Post"
* `title` (string) (optional) The title attribute of the anchor tag. Default: (text) parameter

### Get just the URL

This will return a basic edit url

`apply_filters('gform_update_post/edit_url', '');`

Specify post to edit (post_id) and post that holds the edit form (url)

`apply_filters('gform_update_post/edit_url', 1, home_url('/edit_post/'));`

### Shortcode to show the edit link

`[gform_update_post_edit_link]`

Specify post to edit (post_id) and post that holds the edit form (url)
`[gform_update_post_edit_link post_id=1 url=6]`

= IN TEMPLATE =

You can use the action to force a form show a specific post:

`do_action('gform_update_post/setup_form');`

`do_action('gform_update_post/setup_form', $post->ID);`

`do_action('gform_update_post/setup_form', array('post_id' => $post->ID, 'form_id' => $form_id));`

**Parameters**

* `post_id` (int|array) (optional) The id of the post you want to edit or an array with post id and/or form id. This allows you to specify the form id, so that update functionality does not get applied to other forms on the page. Default: global $post->ID


== Installation ==

* Install plugin either via the WordPress.org plugin directory, or by uploading the files to your server.
* Activate the plugin via the Plugins admin page.


== Frequently Asked Questions ==

= Tags aren't working in a _____. =

Tags really only work with a single line text field, checkbox and multiselect currently won't show the selected items when loaded for editing, but they will select the items. This might get changed in the future, but isn't pressing. Categories support those other methods, and the text field seems more appropriate, over all, for the tags.

= Why can't I add a post image that is not "featured"? =

Image fields are only supported if they are the "Featured Image". Otherwise you have to use a Custom Field and choose "File Upload" under File Type. If it is not one of these two types, we can't track it for updating.

= What does this error mean: syntax error, unexpected T_FUNCTION?! =

It usually means you are running PHP 5.2.x on your server. I know WordPress supports 5.2.4, but it is highly recommended to run at least 5.4, and this plugin simply doesn't support older than 5.3. We tried a little bit, but we could not get anonymous functions to work in a format that was compatible with 5.2, and, honestly, we really don't want to spend a lot more time on it, because 5.2 is so old, and you should upgrade. We did try though...


== Screenshots ==

1. A form on the front end. This just demonstrates the image/file capabilities.


== Changelog ==

## 1.2.17 - 2015-02-24

- Should wrap up 1.9 support and fix issues with custom post types. Helped along by jr00ck!

## 1.2.16 - 2015-02-18

- Fixed a few non-static function declarations.
- Updated image upload to hopefully shutdown when the image is moved.

## 1.2.15 - 2015-02-18

- Fixed field treatment to object from array for GF 1.9.x.

## 1.2.14 - 2015-02-18

- Fixing capabilites to plural.

## 1.2.13 - 2014-09-11

- Removed test...

## 1.2.12 - 2014-09-09

- Fixed bug in last update.

## 1.2.11 - 2014-09-09

- Allow updates to be locked to current form id instead of all forms on page.

## 1.2.10 - 2014-09-07

- Updated multi upload support to allow better control.

## 1.2.9 - 2014-09-07

- Added basic multi file upload support.

## 1.2.7 - 2014-07-19

- Fixed bug in new shortcode.

## 1.2.6 - 2014-07-19

- Added filter to get edit link and shortcode to show it in posts.

## 1.2.5 - 2014-06-19

- Added patch from Tanner Moushey to fix issue where custom fields could only be unique. This allows non-unique custom fields.

## 1.2.4 - 2014-06-04

- Update to allow adding classes to the edit button.

## 1.2.3 - 2014-05-17

- Updated taxonomies to allow for more than one custom taxonomy to have value retrieved when updating.

## 1.2.2 - 2014-05-17

- Fixed prefix property to contant slip.

## 1.2.1 - 2014-05-17

- Added a filter to allow multifield values to be adjusted.

## 1.2 - 2014-05-13

- Added the shortcode support to Gravity Forms main shortcode.

## 1.1 - 2014-02-14

- Finished the file upload upgrades with featured image deletion and hiding upload input when image exists until it is deleted.

## 1.0.1 - 2014-02-14

- Updated file upload fields to hide input when a file exists and show it if the file is deleted.

## 1.0.0 - 2014-02-13

- Acknowledging that this plugin doesn't really work with the original anymore, I distiguishing it by marking a new version: 1.0 from 0.x.
- This should fix the strange output when users directories aren't writable. There is a test to make sure the directory is writable before sending the image to Wordpress core to crop.

## 0.6.4 - 2014-02-13

- Images/Files can now be required on update.

## 0.6.3.3 - 2014-02-04

- Fixed bug in anonymous function replacement. There is still an anonymous function left... Not sure if I can get rid of it. Going to stop trying.
- Added support for multiple capabilities.
- Added ability to turn off image resizing for now to better support users with the crazy output from GD.

## 0.6.3.2 - 2014-02-04

- Now supports the list field, at least with the default text field(s).

## 0.6.3.1 - 2014-02-04

- Removed two anonymous functions in an effort to support php 5.2 users.

## 0.6.3.0 - 2013-10-21

- Small bug change when adding a conditional to the form for the first time.

## 0.6.2.9 - 2013-10-15

- process_request was moved to the 'wp' action to make sure the taxonomies were loaded for post set up. This cause problems with the actual form processing because the form wasn't getting the post_id soon enough and started creating new posts. It was tricky to put back and still get all of the taxonomy support, but this seems to fix it.

## 0.6.2.8 - 2013-10-15

- Added an empty test for fieldType and type on image field type tests in "gform_field_content" filter.

## 0.6.2.7 - 2013-10-15

- Fix for categories, tags, and taxonomies when editing on a separate page.

## 0.6.2.6 - 2013-10-15

- Updated categories, tags, and added initial support for custom taxonomies through the gf custom post type plugin.

## 0.6.2.5 - 2013-10-15

- Fixed getting post thumbnail for current page/post instead of post that is currently being edited.

## 0.6.2.4 - 2013-10-15

- Fileuploads were always getting duplicated in database, becuase the method used was a carrie over from a fix for the old update posts plugin. This simplified it and makes sure fileuploads are never duplicated in database now.

## 0.6.2.3 - 2013-10-03

- Fixed an issue from last update where nonce was required for $_POST. This broke actual form posting. I reverted that aspect.

## 0.6.2.2 - 2013-09-27

- Updated the query variable form setup to always require a nonce and to better support posting the post_id to set up a form. Though it is highly unrecommended and doesn't really seem to work.

## 0.6.2.1 - 2013-09-20

- Added error checking for thumbnail save. If it doesn't work, we just move on and use the full sized image. Need to figure out what is causing save problem.

## 0.6.2 - 2013-09-19

- Overhauled the image thumbnail generation to start using the newer WP_Image_Editor instead of image_make_intermediate_size.

## 0.6.1 - 2013-09-19

- Removed error output from unlink when a file doesn't exist. Stopped the default thumbnail on post images, so post image thumbs only show up when there is an actual image.


== Upgrade Notice ==

= 1.0 =
This is the first version in the Wordpress repository.
