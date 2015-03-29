Currently in development. Not ready for production.

##Introduction

Todo

##Getting Started

Todo

##Quick Example

Create your form in HTML like you're used to. Then just wrap it with Formal functions to enhance it.

```phtml
<?php Formal::start(); ?>
  <form method="POST" name="example">
    <label>Name</label>
    <input type="text" name="name" required />

    <label>Email</label>
    <input type="email" name="email" required />

    <input type="submit" value="Submit" />
  </form>
<?php Formal::end(); ?>
```

Handle submits easily.

```php
// Use the form name attribute as selector, 'example' in this case.
Formal::on('submit', 'example', function($data) {
  // Do something with the posted values in $data.
});
```

##Caveats

####1.

Do not close a tag right after an attribute without a value. Put a space between.

These are ok:
```html
<form method="POST" name="frmContact">
```
```html
<form method="POST" name="frmContact" novalidate >
```

This is not ok and will render incorrect:
```html
<form method="POST" name="frmContact" novalidate>
```

####2.
