<?php
  // set the appropriate header since we are saving out images
  header("Content-Type: image/jpeg");

  // require our settings which contain the API information
  require('settings.php');

  // function to save the external images so we have a copy
  function save_image($inPath,$outPath) {
      $in  =  fopen($inPath, "rb");
      $out =  fopen($outPath, "wb");
      while ($chunk = fread($in,8192)) {
        fwrite($out, $chunk, 8192);
      }
      fclose($in);
      fclose($out);
  }

  // create our empty arrays
  $feed_recent_array = array();
  $json_feed_data = array();
  $i = 0;

  function add_images($data) {
    global $feed_recent_array;
    global $i;

    // loop through the feed and put the items in an array
    foreach($data->data as $item) :
      // only add images, not videos
      if($item->type == "image") :
        $i++;
        $feed_recent_array['full'][$i]['full_img'] = $item->images->standard_resolution->url;
        $feed_recent_array['full'][$i]['thumbnail_img'] = $item->images->thumbnail->url;
        $feed_recent_array['full'][$i]['base_img'] = basename($item->images->standard_resolution->url);
        $feed_recent_array['full'][$i]['description'] = ($item->caption) ? $item->caption->text : "";
      endif;
    endforeach;

    // loop through this current function again with the next batch of photos
    if($data->pagination->next_url) :
      $func = __FUNCTION__;
      $next_url = json_decode(file_get_contents($data->pagination->next_url, true));
      $func($next_url);
    endif;
  }

  // grab the instagram feed
  $feed_url = "https://api.instagram.com/v1/users/".INSTAGRAM_ID."/media/recent/?count=".PHOTO_COUNT."&access_token=".ACCESS_TOKEN."";
  $data = json_decode(file_get_contents($feed_url, true));

  if(!$data) :
    die('Could not fetch feed URL. Double check your settings file and make sure your keys are correct.');
  endif;

  // begin script
  echo "\nBeginning to download instagram images\n\n";

  add_images($data);

  // loop through our array
  foreach($feed_recent_array['full'] as $item) :

    $json_feed_data[] = $item;

    // if there's a new image, let's save it to the recent folder
    if(!glob(FULL_IMAGE_PATH."/*".basename($item['full_img']))) {
      $file_name = basename($item['full_img']);

      // Save full image
      save_image($item['full_img'], FULL_IMAGE_PATH."/".$file_name);

      // Save thumbnail
      save_image($item['thumbnail_img'], THUMBS_IMAGE_PATH."/".$file_name);

      echo "New image ".basename($item['full_img'])." created!\n";
    } else {
      echo basename($item['full_img'])." exists. Skipping.\n";
    }
  endforeach;

  // if everything was successful, update the json file
  if($json_feed_data) :
    file_put_contents("instagram.json", json_encode($json_feed_data));
    echo "\nSaved contents to a local JSON file\n";
  endif;
?>
