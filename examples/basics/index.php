<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('../../vendor/autoload.php');
use Formal\Formal;

Formal::init();

// Configurate form with own settings.
Formal::config('frmContact', array(
  'hideOnSubmit' => TRUE
));

$vars = array();

// Submit handler.
Formal::on('submit', 'frmContact', function($data) use (&$vars){
  $vars = $data;
});

?>

<!DOCTYPE html>
<html>
  <head>
    <title>Formal - Example - Basics</title>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body>

    <div class="container">

      <div class="row">
        <div class="col-sm-6 col-sm-offset-3">

          <h1>Register</h1>

          <?php Formal::start(); ?>
            <form method="POST" name="frmContact" novalidate >
              <div class="form-group">
                <label class="form-label" for="inpName">Name</label>
                <input class="form-control" type="text" id="inpName" name="name" required />
              </div>

              <div class="form-group">
                <label class="form-label" for="inpEmail">Email</label>
                <input class="form-control" type="email" id="inpEmail" name="email" required />
              </div>

              <input type="submit" value="Submit" class="btn btn-primary" />
            </form>
          <?php Formal::end(); ?>

          <?php if (!empty($vars)): ?>
            Thanks <?php echo $vars['name'] ?>, a mail was sent to <?php echo $vars['email'] ?>.
          <?php endif ?>

        </div>
      </div>

    </div>

  </body>
</html>
