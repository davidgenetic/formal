<?php

namespace Formal;

use PHPHtmlParser\Dom;

class Element {

  protected $element;
  protected $attributes = array();

  public function __construct(\PHPHtmlParser\Dom\HtmlNode &$element) {
    $this->element = $element;

    // Hold our own array of attributes.
    $this->fetchAttrs();
  }

  // Setter for attributes.
  public function __set($name, $value) {
    $this->setAttribute($name, $value);
  }

  // Getter for attributes.
  public function __get($name) {
    return $this->getAttribute($name);
  }

  protected function setAttribute($name, $value) {
    $this->attributes[$name] = $value;

    // Also set attribute on the PHPHtmlParser\Dom\Tag class
    // to make sure it's in our html output.
    $this->element->getTag()->setAttribute($name, array(
      'doubleQuote' => TRUE,
      'value' => $value,
    ));
  }

  protected function getAttribute($name) {
    if (isset($this->attributes[$name])) {
      return $this->attributes[$name];
    }
  }

  public function append($html) {
    $dom = new Dom();
    $dom->load($html);
    $node = $dom->root->firstChild();
    $this->element->addChild($node);
  }

  public function getContent($inner = FALSE) {
    return $inner ? $this->element->innerHtml : $this->element->outerHtml;
  }

  private function fetchAttrs(){
    $attrs = $this->element->getTag()->getAttributes();
    if (!empty($attrs)) {
      foreach ($attrs as $key => $value) {
        $this->attributes[$key] = $value['value'] ?: TRUE;
      }
    }
  }

}
