<?php

namespace Formal\Element;

use Formal\Element;
use Formal\Element\Input;

class Form extends Element {

  private $token;
  private $method;
  private $events = array();
  private $errors = array();

  public $csrf;
  public $fields = array();

  public function __construct(\PHPHtmlParser\Dom\HtmlNode &$formElement) {
    parent::__construct($formElement);

    $this->token = $this->element->getAttribute('name');
    $this->method = strtolower($this->element->getAttribute('method')) ?: 'get';
    $this->csrf = $this->getCsrfToken();

    $this->getFields();
  }

  public function getOutput() {
    $html = $this->element->outerHtml;
    $this->addAntiSpamField($html);
    $this->addTokenField($html);
    $this->addCsrfField($html);
    $this->refactor($html);
    return $html;
  }

  public function getToken() {
    return $this->token;
  }

  public function getMethod() {
    return $this->method;
  }

  public function setError($fieldname, $message) {
    if (empty($this->errors[$fieldname])) {
      $this->errors[$fieldname] = array();
    }
    $this->errors[$fieldname][] = $message;

    if (!empty($this->fields[$fieldname])) {
      $this->fields[$fieldname]->setError($message);
    }
  }

  public function getErrors() {
    return $this->errors;
  }

  public function populate($data) {
    if (!empty($data)) {
      foreach ($data as $fieldname => $value) {
        if (is_array($value)) {
          $fieldname = $fieldname . '[]';
        }

        if (array_key_exists($fieldname, $this->fields)) {
          if (is_array($this->fields[$fieldname])) {
            // Field is radiobutton or checkboxlist.
            // Set 'checked' attribute to correct field.
            foreach ($this->fields[$fieldname] as $choice) {
              if (is_array($value)) {
                // Checkboxlist
                $choice->checked = in_array($choice->value, $value);
              } else {
                // Radiobutton
                $choice->checked = $choice->value == $value;
              }
            }
          } else {
            $this->fields[$fieldname]->value = $value;
          }
        }
      }
    }
  }

  public function findField($name) {
    if (!empty($this->fields[$name])) {
      return $this->fields[$name];
    }
  }

  private function getCsrfToken() {
    if (!empty($_SESSION['csrf_' . $this->token])) {
      $token = $_SESSION['csrf_' . $this->token];
    } else {
      $token = md5(__DIR__ . time() . $this->token);
      $_SESSION['csrf_' . $this->token] = $token;
    }
    return $token;
  }

  private function getFields() {
    $inputs = $this->element->find('input, select, textarea');
    foreach ($inputs as $el) {
      $name = $el->getAttribute('name');
      $inp = new Input($el);
      if ($inp->type == 'radio') {
        $this->fields[$name][] = $inp;
      } else if ($inp->type == 'checkbox' && strpos($name, '[]')) {
        $this->fields[$name][] = $inp;
      } else {
        $this->fields[$name] = $inp;
      }
    }
  }

  private function addTokenField(&$content) {
    $content = str_replace('</form>', '<input type="hidden" name="form_token" value="'. $this->token .'" /></form>', $content);
  }

  private function addAntiSpamField(&$content) {
    $content = str_replace('</form>', '<input type="text" style="position:absolute;overflow:hidden;height:0;width:0;left:-99999px;" name="secret" /></form>', $content);
  }

  private function addCsrfField(&$content) {
    $content = str_replace('</form>', '<input type="hidden" name="form_protect" value="' . $this->csrf . '" /></form>', $content);
  }

  private function refactor(&$content) {

    // Make sure the radio buttons are checked correctly.
    // Found no other way around this.
    $content = preg_replace('/checked=""/', '', $content);
    $content = preg_replace('/checked="0"/', '', $content);
    $content = preg_replace('/checked="1"/', 'checked', $content);

    // Do the same for selects.
    $content = preg_replace('/selected="0"/', '', $content);
    $content = preg_replace('/selected="1"/', 'selected', $content);
  }

}
