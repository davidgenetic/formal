<?php

namespace Formal\Element;

use Formal\Element;

class Form extends Element {

  private $token;
  private $content;
  private $events = array();

  public function __construct(\PHPHtmlParser\Dom\HtmlNode $formElement) {
    $this->token = $formElement->getAttribute('name');
    $this->content = $formElement->outerHtml;
  }

  public function output() {
    $html = $this->content;
    $html = str_replace('</form>', '<input type="hidden" name="form_token" value="'. $this->token .'" /></form>', $html);
    return $html;
  }

  public function getToken() {
    return $this->token;
  }

  // public function onPost($cb) {

  // }

}
