<?php

namespace Formal\Element;

use Formal\Element;
use PHPHtmlParser\Dom\TextNode;

class Input extends Element {

  public function __construct(\PHPHtmlParser\Dom\HtmlNode &$element) {
    parent::__construct($element);

    // Try to get label from label tag referencing this input.
    $arr = $element->ancestorByTag('form')->find('label[for="inpName"]')->toArray();
    if (count($arr)) {
      $this->label = $arr[0]->text;
    }
  }

  public function getLabel() {
    if ($this->label) {
      return $this->label;
    }
    if ($this->placeholder) {
      return $this->placeholder;
    }
    return $this->name;
  }

  public function setError($message) {
    $this->class = $this->class . ' error';
  }

  protected function setAttribute($name, $value) {
    parent::setAttribute($name, $value);

    if ($name == 'value') {

      // TEXTAREA
      if ($this->element->getTag()->name() == 'textarea') {
        if ($this->element->hasChildren()) {
          $textnode = $this->element->firstChild();
          if (!is_null($textnode)) {
            $this->element->removeChild($textnode->id());
          }
        }
        $textnode = new TextNode($value);
        $this->element->addChild($textnode);
      }

      // CHECKBOX
      if ($this->type == 'checkbox') {
        $this->element->getTag()->setAttribute('checked', !!$value);
      }

      // SELECT
      if ($this->element->getTag()->name() == 'select') {
        $options = $this->element->find('option');
        if (count($options)) {
          foreach($options as $opt) {
            $opt->getTag()->selected = array(
              'doubleQuote' => TRUE,
              'value' => $opt->getTag()->value['value'] == $value ? '1' : '0',
            );
          }
        }
        // var_dump($this->element);exit;
      }
    }
  }

}
