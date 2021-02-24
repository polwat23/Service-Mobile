<?php

require_once(__DIR__.'/../extension/vendor/autoload.php');

// import the Intervention Image Manager Class
use Intervention\Image\ImageManager;

// create an image manager instance with favored driver
$manager = new ImageManager(array('driver' => 'imagick'));

// to finally create image instances
$image = $manager->make('00012330.jpg')->resize(300, 200);
?>