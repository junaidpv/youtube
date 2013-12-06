<?php

/**
 * @file youtube.php
 * @author Junaid P V <junu.pv+public@gmail.com>
 * @version 0.1
 */

/**
 * Class to get information from youtube page.
 */
class Youtube {
  /**
   * Youtube video URL.
   * @var string
   */
  private $url;
  /**
   * Array to store video data cached.
   * @var string
   */
  private $video_data_array;

  /**
   *
   * @param string $url
   */
  public function __construct($url) {
    // TODO: validate URL passed.
    $this->url = $url;
  }

  public function get_video_data() {
    if (isset($video_data_array)) {
      return $video_data_array;
    }
    //utf8 encode and convert "&"
    $html = utf8_encode($this->get_page_html($this->url));
    $html = str_replace("\u0026amp;", "&", $html);

    // Extract text containing video information.
    preg_match_all('/"url_encoded_fmt_stream_map".*?"(.*?)"/i', $html, $matches);

    if (isset($matches[1][0])) {
      // Get array of data for each video present.
      // Need to url decode first.
      $video_data_collection = explode(',', $matches[1][0]);
      //var_dump($video_url_data);

      $video_data_array = array();
      foreach($video_data_collection as $video_raw_data) {
        $video_data = array();
        $video_raw_data = unescapeUTF8EscapeSeq($video_raw_data);
        parse_str($video_raw_data, $video_data);
        preg_match('#(video\/([A-Za-z\-0-9]+))(;\\s*?codecs\\="(.+?)(,\\s*(.+?))?")?#', $video_data['type'], $matches1);
        if ($matches1) {
          $type_info = array();
          // Container format
          if (isset($matches1[2])) {
            $type_info['format'] = $matches1[2];
          }
          // Audio codec
          if (isset($matches1[4])) {
            $type_info['vcodec'] = $matches1[4];
          }
          // Video codec
          if (isset($matches1[6])) {
            $type_info['acodec'] = $matches1[6];
          }
          $video_data['type_info'] = $type_info;
        }

        $video_data['download_url'] = "{$video_data['url']}&signature={$video_data['sig']}";
        $video_data_array[] = $video_data;
      }

      $this->video_data_array = $video_data_array;
      return $video_data_array;
    }
    else {
      // Video information could not be found.
      return FALSE;
    }
  }

  /**
   * Function to load HTML content from URL.
   *
   * @param string $url
   * @return string
   * @throws Exception
   */
  private function get_page_html($url) {
    if (function_exists("curl_init")) {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      return curl_exec($ch);
    }
    else {
      throw new Exception("No cURL module available");
    }
  }

  /**
   * Function get the video data by quality. So it can be easy to get
   * videos by quality.
   */
  public function get_video_data_by_quality() {
    if ($video_data_array = $this->get_video_data()) {
      $video_data_by_quality = array();
      foreach($video_data_array as $video_data) {
        $video_data_by_quality[$video_data['quality']][] = $video_data;
      }
      return $video_data_by_quality;
    }
  }
}

/**
 * From: http://stackoverflow.com/questions/2443558/what-is-u002639n-and-how-do-i-decode-it
 * @param string $str
 * @return string
 */
function unescapeUTF8EscapeSeq($str) {
  return preg_replace_callback("/\\\u([0-9a-f]{4})/i",
    create_function(
      '$matches',
      'return html_entity_decode(\'&#x\'.$matches[1].\';\', ENT_QUOTES, \'UTF-8\');'
      ),
    $str
  );
}
