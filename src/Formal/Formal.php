<?php

namespace Formal;

use PHPHtmlParser\Dom;
use Formal\Element\Form;

class Formal {

  private static $forms = array();
  private static $posts = array();
  private static $config = array();

  public static function start() {
    ob_start();
  }

  public static function end() {
    $contents = ob_get_contents();
    ob_end_clean();
    echo self::build($contents);
  }

  public static function build($html) {
    $dom = new Dom();
    $dom->load($html);

    // Create form.
    $form = new Form($dom->find('form', 0));
    self::$forms[$form->getToken()] = $form;

    // Check for a post event and fire the post callback, if provided.
    if (!empty($_POST) && isset($_POST['form_token'])) {
      if ($_POST['form_token'] == $form->getToken()) {
        if (isset(self::$posts[$form->getToken()])) {
          $post_callback = self::$posts[$form->getToken()];
          call_user_func_array($post_callback, array(self::getFormData($_POST)));
        }
      }
    }

    // Return HTML.
    return $form->output();
  }

  public static function on($event, $target, $closure) {
    if ($event == 'post') {
      self::addEvent(self::$posts, $target, $closure);
    }
  }

  public static function post($target, $closure) {
    self::on('post', $target, $closure);
  }


  public static function getFormData($post) {
    unset($post['form_token']);
    return $post;
  }

  public static function config($target, $settings) {
    $defaults = array(
      'hideOnPost' => FALSE
    );

    if (!empty($settings['post'])) {
      self::on('post', $target, $settings['post']);
      unset($settings['post']);
    }

    $settings = array_merge($defaults, $settings);
    self::$config[$target] = $settings;
  }

  /*** PRIVATE ***/

  private static function fetchForm($token) {
    if (array_key_exists($token, self::$forms)) {
      return self::$forms[$token];
    }
  }

  private static function addEvent(&$holder, $target, $closure) {
    $holder[$target] = $closure;
  }

}
