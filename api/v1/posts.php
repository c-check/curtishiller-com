<?php

  //ini_set('display_errors', 1);
  //ini_set('display_startup_errors', 1);
  //error_reporting(E_ALL);

  //-connect to db
  $dbc = mysqli_connect(); // masked
	mysqli_set_charset( $dbc, 'utf8' );

  $query = 
		"SELECT * 
		FROM `_levels` 
		WHERE 
			`_levels`.`table` != 'lastfm' 
		ORDER BY `_levels`.`timestamp` DESC
		LIMIT 40;";
	$results = mysqli_query( $dbc, $query );
  $response = array();
  while( $row = mysqli_fetch_assoc($results) )
  {
    $query = "SELECT * FROM `{$row['table']}` WHERE `id` = '{$row['resource_id']}' LIMIT 1;";
		$individual_result = mysqli_query( $dbc, $query );
    $full_result = array_merge( $row, mysqli_fetch_assoc($individual_result) );
    array_push( $response, prepare_result($full_result) );
  }
  
  echo json_encode($response);
  die();

  function prepare_result($row)
  {  
    $new_row = array();

    $new_row['date'] = prepare_date( $row['timestamp'] );
    $new_row['data'] = [
      'type' => parse_type( $row ),
      'data' => parse_data( $row )
    ];

    return $new_row;
  }

  function parse_data($row)
  {
    switch($row['table'])
    {
      case 'checkins':
        $response = [
          'checkin' => [
            'location' => $row['name'],
            'text'     => prepare_checkin_text( $row )
          ]
        ];
        if( $row['checkin_photos'] != 'NULL' )
        {
          $response['images'] = prepare_checkin_images( $row );
        }
        return $response;
        break;

      case 'mentions':
        $response['user'] = prepare_mention_user( $row );
      case 'twats':
        $response['text']  = prepare_tweet_text( $row );
        $response['media'] = prepare_tweet_media( $row );
        return $response;
        break;

      case 'grams':
        if( $row['type'] == 'video' )
        {
          return prepare_instagram_video_data($row);
        }
        elseif( $row['type'] == 'image' )
        {
          return prepare_instagram_image_data($row);
        }
        else
        {
          return prepare_instagram_carousel_data($row);
        }
        break;

      default:
        return $row;
        break;
    }
  }

  function prepare_mention_user( $row )
  {
    return [
      'name'      => $row['user_screen_name'],
      'full_name' => $row['user_name'],
      'link'      => 'http://twitter.com/' . $row['user_screen_name']
    ];
  }

  function prepare_tweet_text( $row )
  {
    //-fix embedded links
    $output = preg_replace(
      '/http([s]?):\/\/([^\ \)$]*)/',
      "<a rel='nofollow' href='http$1://$2' target='blank'>http$1://$2</a>",
      $row['text']
    );
    //-fix twitter handles
		$output = preg_replace(
      '/(^|\s|-)@((?!_@)\w+)/',
      "$1<a href='http://www.twitter.com/$2' class='twitter-handle' title='@$2' target='blank'>@$2</a>",
      $output
    );
    //-fix hashtags
	  $output = preg_replace(
      '/(^|\s)#(\w+)/',
      "$1<a href='http://twitter.com/search/%23$2' class='twitter-hash' title='#$2' target='blank'>#$2</a>",
      $output
    );
    return $output;
  }

  function prepare_tweet_media( $row )
  {
    $row = json_decode( $row['full_object'], true );
    $entities = $row['entities'];
    
    if( !$entities || $entities == 'NULL' || !array_key_exists('media', $entities) || count($entities['media']) == 0 )
    {
      return '';
    }

    return [
      'url'   => $entities['media'][0]['expanded_url'],
      'image' => $entities['media'][0]['media_url']
    ];
  }

  function prepare_checkin_text( $row )
  {
    if( $row['shout'] != 'NULL' )
    {
      return $row['shout'];
    }
    return '';
  }

  function prepare_checkin_images( $row )
  {
    $images = json_decode( $row['checkin_photos'], true );
    $response = [];
    foreach( $images['items'] as $image )
    {
      array_push( $response, $image['prefix'] . 'original' . $image['suffix'] );
    }
    return $response;
  }

  function prepare_instagram_video_data( $row )
  {
    //-add saved media later

    $video = json_decode( $row['videos'], true );
    return ['videos' => array($video['standard_resolution']['url'])];
  }

  function prepare_instagram_carousel_data($row)
  {
    $saved_media = json_decode( $row['saved_media'], true );
    if( $saved_media['images'] )
    {
      prepare_instagram_image_data($row);
    }

    $images = json_decode( $row['images'], true );
    $data = array();
    foreach( $images as $image )
    {
      array_push( $data, $image['images']['standard_resolution']['url'] );
    }
    return ['images' => $data];
  }

  function prepare_instagram_image_data($row)
  {
    $saved_media = json_decode( $row['saved_media'], true );
    if( $saved_media['images'] )
    {
      return ['images' => $saved_media['images']];
    }

    $images = json_decode( $row['images'], true );
    return ['images' => array($images['standard_resolution']['url'])];
  }

  function prepare_date( $timestamp )
  {
    $timestamp += 7200;
    return date( 'Y-M-d g:ia', $timestamp );
  }

  function parse_type( $row )
  {
    switch( $row['table'] )
    {
      case 'grams':
        if( $row['type'] == 'video' )
        {
          return 'insta-video';
        }
        return 'insta-image';
        break;
      
      case 'mentions':      
      case 'twats':
        return 'tweet';
        break;

      case 'checkins':
        return 'swarm';
        break;
      
      default:
        return $row['table'] . '-' . $row['type'];
        break;
    }
  }

?>

