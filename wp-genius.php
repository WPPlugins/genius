<?php defined('ABSPATH') or die('No direct script access');

/*
 * Plugin Name: Genius Annotations
 * Plugin URL: http://genius.com
 * Description: Easily make your posts annotatable
 * Version: 0.0.4
 */

class WPGenius {

  public function __construct() {
    add_action('wp_head', array($this, 'add_injection_header'));
    add_action('save_post', array($this, 'save_genius_meta_box_data'));
    add_action('add_meta_boxes', array($this, 'add_meta_box'));
    add_shortcode('genius-callout', array($this, 'callout_shortcode'));
  }

  public function add_injection_header() {
    global $post;
    if (!is_home() && $post && $post->post_type === 'post' || $post->post_type === 'page') {
      $value = get_post_meta($post->ID, '_genius_injection_enabled', true) === '1';
      if ($value) {
        echo '<script src="//genius.codes/" async></script>';
        return;
      }
    }
    echo '<style>genius-callout { display: none !important; }</style>';
  }

  public function save_genius_meta_box_data($id) {
    if (!isset($_POST['genius_meta_box_nonce'])) { return; }
    if (!wp_verify_nonce($_POST['genius_meta_box_nonce'], 'save_genius_meta_box_data')) { return; }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }

    if ('page' == $_POST['post_type'] && !current_user_can('edit_page', $id)) { return; }
    if ('post' == $_POST['post_type'] && !current_user_can('edit_post', $id)) { return; }

    $value = $_POST['genius_injection_enabled'] === 'on';
    update_post_meta($id, '_genius_injection_enabled', $value);
  }

  public function add_meta_box() {
    $screens = array('post', 'page');
    foreach ($screens as $screen) {
      add_meta_box('genius_section', 'Genius Settings', array($this, 'meta_box_callback'), $screen);
    }
  }

  public function meta_box_callback($post) {
    wp_nonce_field('save_genius_meta_box_data', 'genius_meta_box_nonce');
    $value = get_post_meta($post->ID, '_genius_injection_enabled', true) === '1';
    echo '<label><input type="checkbox" name="genius_injection_enabled" '. ($value ? 'checked' : '')  .'/> Enable Genius Annotations</label>';
  }

  public function callout_shortcode($atts) {
    $atts = shortcode_atts(array(
      'intro' => 'Want to add your own comment?',
    ), $atts);

    $intro = $atts['intro'];

    return '<genius-callout>' .
      "<genius-callout-intro>$intro</genius-callout-intro> " .
      '<genius-callout-highlight>Highlight text</genius-callout-highlight> ' .
      'and click <genius-callout-prompt></genius-callout-prompt> to annotate using ' .
      '<a href="http://www.genius.com/beta" target="_blank">Genius</a></genius-callout>';
  }

}

new WPGenius();
