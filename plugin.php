<?php
/*
 * Plugin Name: Replica Demo Basico
 */
add_action('wp_footer', function () {
    $http = new WP_Http();
    /** @var array<string,mixed>|WP_Error $apiResponse */
    $apiResponse = $http->get('https://fernandafamiliar.soy/wp-json/wp/v2/posts');

    if (is_wp_error($apiResponse)) {
        error_log('could not load remote site contents ' . $apiResponse->get_error_code(), 0);
        error_log(var_export($apiResponse->get_error_messages(), true), 0);
        return;
    }

    if(isset($apiResponse['body'])) {
        $parsed = json_decode($apiResponse['body'], true);
        $contents = array_reduce($parsed, 'reduceApiResponse');
    }

    [
        'media' => $mediaIdsDictionary,
        'posts' => $postsDictionary,
    ] = $contents;

    $mediaDictionary = getMediaForPosts($mediaIdsDictionary);

    $integratedContents = [];
    foreach($postsDictionary as $postId => $postData) {
        $hasFeaturedMedia = isset($postData['featured']);
        if (!$hasFeaturedMedia) {
            $integratedContents = $postData;
        };

        $wantedMedia = $postData['featured'];

        if (!isset($mediaDictionary->{$wantedMedia})) {
            $integratedContents[$postId] = $postData + [
                'media' => null,
            ];
        }

        $integratedContents[$postId] = $postData + [
            'media' => $mediaDictionary->{$wantedMedia},
        ];
    }

   ?><!-- hello replica -->
<?php echo var_export($postsDictionary, true); ?>
<?php echo var_export($integratedContents, true); ?>
<?php
});

function parseDateApiUTC(
    string $dateRaw,
    string $format = \DateTime::RFC7231,
    \DateTimeZone $originTZ = null,
    \DateTimeZone $targetTZ = null
): string {
    $fromTimezone = is_null($originTZ) ? new \DateTimeZone(date_default_timezone_get()) : $originTZ;

    $parsedDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $dateRaw, $fromTimezone);

    $toTimezone = is_null($targetTZ) ? new \DateTimeZone(date_default_timezone_get()) : $targetTZ;

    $parsedDate->setTimezone($toTimezone);
    return $parsedDate->format($format);
}

function reduceApiResponse(
    $accum = ['posts' => [], 'media' => []],
    array $apiResponseItem
) {
    $UTCTimeZone = new \DateTimeZone('UTC');
    ['posts' => $posts, 'media' => $media] = $accum;

    [
      'id' => $remoteId,
      'date_gmt' => $publishedDateRaw,
      'modified_gmt' => $updatedDateRaw,
      'link' => $remoteUrl,
      'title' => $titleRaw,
      'excerpt' => $excerptRaw,
    ] = $apiResponseItem;

    $postData = [
      'title' => $titleRaw['rendered'],
      'excerpt' => $excerptRaw['rendered'],
      'link' => $remoteUrl,
      'published' => parseDateApiUTC($publishedDateRaw, 'j \d\e F \d\e Y, g:i a', $UTCTimeZone),
      'modified' => parseDateApiUTC($updatedDateRaw, 'j \d\e F \d\e Y, g:i a', $UTCTimeZone)
    ];

    if (isset($apiResponseItem['featured_media'])) {
        $mediaWanted = $apiResponseItem['featured_media'];
        $postData['featured'] = $mediaWanted;
        $media[] = $mediaWanted;
    }

    $posts[$remoteId] = $postData;

    return compact('posts', 'media');
}

function getMediaForPosts(array $mediaIds) {
    $mediaApiQuery = 'https://fernandafamiliar.soy/wp-json/wp/v2/media/?includes=' .
      implode(',', $mediaIds);

    $http = new WP_Http();
    /** @var array<string,mixed>|WP_Error $apiResponse */
    $apiResponse = $http->get($mediaApiQuery);

    $mediaResponseData = json_decode($apiResponse['body'], true);

    $sizeQuery = count($mediaIds);
    $sizeResponse = count($mediaResponseData);
    $response = array_map(function ($mediaResponseData) {
        return $mediaResponseData['id'];
    }, $mediaResponseData);
    asort($response);
    asort($mediaIds);
    var_export(compact('sizeQuery', 'sizeResponse', 'response', 'mediaIds'));

    $mediaDictionary = new \stdClass();

    foreach($mediaResponseData as $mediaResponseDatum) {
        [
          'id' => $mediaId,
          'media_details' => $mediaDetail,
        ] = $mediaResponseDatum;
        $mediaDictionary->$mediaId = $mediaResponseDatum;
    }

    return $mediaDictionary;
}
