<?php
  // define some settings
  define("INSTAGRAM_ID", "xxxxxxx");
  define("ACCESS_TOKEN", "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx");
  define("PHOTO_COUNT", 100);
  define("IMAGE_PATH", "images");

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
  // function to improve strstr()
  function strstr_after($haystack, $needle, $case_insensitive = false) {
    $strpos = ($case_insensitive) ? 'stripos' : 'strpos';
    $pos = $strpos($haystack, $needle);
    if (is_int($pos)) {
        return substr($haystack, $pos + strlen($needle));
    }
    return $pos;
  }
  // function to save thumbnails from the new images we just saved
  function createThumbnail($filename, $pathToImages, $pathToThumbs) {
      $source_image = imagecreatefromjpeg($pathToImages . $filename);
      $source_imagex = imagesx($source_image);
      $source_imagey = imagesy($source_image);
      $dest_imagex = 60;
      $dest_imagey = 60;
      $dest_image = imagecreatetruecolor($dest_imagex, $dest_imagey);
      imagecopyresampled($dest_image, $source_image, 0, 0, 0, 0, $dest_imagex, $dest_imagey, $source_imagex, $source_imagey);
      header("Content-Type: image/jpeg");
      imagejpeg($dest_image,$pathToThumbs . $filename,80);
  }

  // create our empty arrays
  $feed_recent_array = array();
  $json_feed_data = array();

  // grab the instagram feed
  $feed_url = "https://api.instagram.com/v1/users/".INSTAGRAM_ID."/media/recent/?count=".PHOTO_COUNT."&access_token=".ACCESS_TOKEN."";
  $data = json_decode(file_get_contents($feed_url, true));

  // begin script
  echo "\nBeginning to download instagram images\n\n";

  // loop through the feed and put the items in an array
  $i = 0;
  foreach($data->data as $item) :
    $i++;
    $feed_recent_array['full'][$i]['full_img'] = $item->images->standard_resolution->url;
    $feed_recent_array['full'][$i]['base_img'] = basename($item->images->standard_resolution->url);
    $feed_recent_array['full'][$i]['description'] = ($item->caption) ? $item->caption->text : "";
  endforeach;

  // loop through our array
  foreach($feed_recent_array['full'] as $item) :

    $json_feed_data[] = $item;

    // if there's a new image, let's save it to the recent folder
    if(!glob(IMAGE_PATH."/*".basename($item['full_img']))) {
      $file_name = basename($item['full_img']);

      save_image($item['full_img'],IMAGE_PATH."/full/".$file_name);
      createThumbnail($file_name, IMAGE_PATH."/full/", IMAGE_PATH."/thumbs/");
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
