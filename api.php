<?php
  require_once('Instagram.php');

  # Declare the InstagramFeed Class
  $instagram = new InstagramFeed(array(
    'accessToken' => 'YOUR_ACCESS_TOKEN'
  ));

  # Download the Photos
  $instagram->downloadPhotos(array(
    'count' => 20
  ));
?>
