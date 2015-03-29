<?php

namespace Formal;

use PHPHtmlParser\Dom;
use Formal\Element\Form;
use Formal\Element;

class Formal {

  private static $forms = array();
  private static $submits = array();
  private static $validates = array();
  private static $config = array();

  // private static $askedForErrors = FALSE;

  public static function init(){
    if (session_status() == PHP_SESSION_NONE) {
      // We need a session.
      session_start();
    }
  }

  /**
   * Start collecting output of the form.
   **/
  public static function start() {
    ob_start();
  }

  /**
   * End collecting output and parse it.
   **/
  public static function end() {
    $contents = ob_get_contents();
    ob_end_clean();
    echo self::parse($contents);
  }

  /**
   * Parse HTML, build form, register event handlers.
   **/
  public static function parse($html) {
    $dom = new Dom();
    $dom->load($html);

    // Create form.
    $form = $dom->find('form', 0);
    $form = new Form($form);
    $token = $form->getToken();
    self::$forms[$token] = $form;

    // Flag to return the HTML of the form.
    $outputform = TRUE;

    // Setup config if not already available,
    // to ensure availability of default values.
    if (empty(self::$config[$token])) {
      self::config($token);
    }
    $config = self::$config[$token];

    // HTML to show.
    $output = '';

    // Check for a post event and fire the post callback, if provided.
    if ($postdata = self::isPost($form)) {
      // var_dump($postdata);exit;
      if (!$config['validateOnSubmit'] || self::validate($form, $postdata)) {
        if (isset(self::$submits[$token])) {
          $post_callback = self::$submits[$token];
          $user_output = call_user_func_array($post_callback, array($postdata));
          if (!empty($user_output)) {
            return $user_output;
          }
        }
        if ($config['hideOnSubmit']) {
          $outputform = FALSE;
        }
      }

      // Check if their are any errors set on the form.
      $errors = $form->getErrors();
      if (count($errors)) {

        // We need an HTML element to show the errors to the user.
        // $errorContainer = $dom->getElementById($config['errorContainer']);
        // if ($errorContainer) {
        //   $errorContainer = new Element($errorContainer);
        //   foreach ($errors as $fieldname => $errs) {
        //     if (is_array($errs)) {
        //       foreach ($errs as $message) {
        //         $errorContainer->append('<div>'. $message .'</div>');
        //       }
        //     }
        //   }
        //   $output .= $errorContainer->getContent();
        // }

        // Repopulate form with posted data.
        $form->populate($postdata);
      }
    }

    if ($outputform) {
      // Add form to output.
      $output .= $form->getOutput();
    }

    return $output;
  }

  public static function startErrors($formname) {

  }

  public static function endErrors() {

  }

  public static function getErrors($form_name) {
    $errors = array();
    if (!empty(self::$forms[$form_name])) {
      $errors = self::$forms[$form_name]->getErrors();
    }
    return $errors;
  }

  /**
   * Register an event handler for a specific form.
   **/
  public static function on($event, $form_token, $closure) {
    if ($event == 'submit') {
      self::addEvent(self::$submits, $form_token, $closure);
    }
    if ($event == 'validate') {
      self::addEvent(self::$validates, $form_token, $closure);
    }
  }

  /**
   * Get all posted data.
   **/
  public static function getFormData($data) {
    unset($data['form_token']);
    return $data;
  }

  /**
   * Setup user defined config for a specific form.
   **/
  public static function config($form_token, $settings = array()) {
    $defaults = array(
      'hideOnSubmit'        => FALSE,
      'validateOnSubmit'    => TRUE,
      'errorContainer'      => '#formalErrorContainer',
    );

    if (!empty($settings['post'])) {
      self::on('post', $form_token, $settings['post']);
      unset($settings['post']);
    }

    $settings = array_merge($defaults, $settings);
    self::$config[$form_token] = $settings;
  }

  /**
   * Validate posted data against a form.
   *
   * TODO: move this to the Form class.
   **/
  public static function validate($form, &$data) {
    $token = $form->getToken();

    // var_dump($data['form_protect'], $form->csrf);exit;

    // Check CSRF.
    if (empty($data['form_protect']) || $data['form_protect'] != $form->csrf) {
      die('CSRF attack');
      return FALSE;
    }

    // Anti-spam check.
    // Only a bot can fill in the 'secret' field.
    if (!empty($data['secret'])) {
      // die('antispam');
      return FALSE;
    }
    unset($data['secret']);

    self::validateFields($form, $data);
    if (isset(self::$validates[$token])) {
      call_user_func_array(self::$validates[$token], array($form, $data));
    }
    return count($form->getErrors()) === 0;
  }

  /***********************/
  /*** PRIVATE METHODS ***/
  /***********************/

  /**
   * Validate each field of a form aginast its attributes.
   **/
  private static function validateFields($form, $data) {
    foreach ($form->fields as $field) {
      if (is_array($field)) {
        if (count($field)) {
          foreach ($field as $f) {
            self::checkRequired($form, $f, $data);
          }
        }
      } else {
        self::checkRequired($form, $field, $data);
      }
    }
  }

  private static function checkRequired($form, $field, $data) {
    $fieldname = str_replace('[]', '', $field->name);
    if ($field->required && empty($data[$fieldname])) {
      $form->setError($field->name, $field->getLabel() . ' is required.');
    }
  }

  /**
   * Get form by token.
   **/
  private static function fetchForm($form_token) {
    if (array_key_exists($form_token, self::$forms)) {
      return self::$forms[$form_token];
    }
  }

  /**
   * Add event handler to an array.
   **/
  private static function addEvent(&$holder, $target, $closure) {
    $holder[$target] = $closure;
  }

  /**
   * Finds out if a specific form was submitted.
   **/
  private static function isPost($form) {
    $bag = $form->getMethod() == 'post' ? $_POST : $_GET;

    if (!empty($bag) && isset($bag['form_token']) && $bag['form_token'] == $form->getToken()) {
      return self::getFormData($bag);
    }

    return FALSE;
  }

}
