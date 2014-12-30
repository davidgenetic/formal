<?php

namespace Formal\Element;

use Formal\Element;
use Formal\Element\Input;

class Form extends Element {

  private $token;
  private $method;
  private $formElement;
  private $events = array();
  private $errors = array();
  public $fields = array();

  public function __construct(\PHPHtmlParser\Dom\HtmlNode $formElement) {
    $this->formElement = $formElement;
    $this->token = $formElement->getAttribute('name');
    $this->method = strtolower($formElement->getAttribute('method')) ?: 'get';

    $this->getFields();
  }

  public function getOutput() {
    $html = $this->formElement->outerHtml;
    $this->addAntiSpamField($html);
    $this->addTokenField($html);
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
  }

  public function getErrors() {
    return $this->errors;
  }

  public function populate($data) {
    if (!empty($data)) {
      $fields = $this->getFields();
      foreach ($data as $fieldname => $value) {
        if (array_key_exists($fieldname, $this->fields)) {
          if (is_array($this->fields[$fieldname])) {
            // Field is radiobutton.
            // Set 'checked' attribute to correct field.
            foreach ($this->fields[$fieldname] as $radio) {
              $radio->checked = $radio->value == $value;
            }
          } else {
            $this->fields[$fieldname]->value = $value;
          }
        }
      }
    }
  }

  private function getFields() {
    $inputs = $this->formElement->find('input, select, textarea');
    foreach ($inputs as $el) {
      $name = $el->getAttribute('name');
      $inp = new Input($el);
      if ($inp->type == 'radio') {
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
