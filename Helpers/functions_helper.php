<?php

    /**
     * Truncate a slug.
     *
     * @see utf8_uri_encode()
     *
     * @param string $slug   The slug to truncate.
     * @param int    $length Optional. Max length of the slug. Default 200 (characters).
     * @return string The truncated slug.
     */
    function _truncate_slug( $slug, $length = 200 ) {

        $last = substr( $slug, -1 , strlen($slug));
        if(is_numeric($last)){
            $slug = substr( $slug, 0 , strlen($slug)-1);
        }
          if ( strlen( $slug ) > $length ) {
              $decoded_slug = urldecode( $slug );
              if ( $decoded_slug === $slug ) {
                  $slug = substr( $slug, 0, $length );
              } else {
                  $slug = utf8_uri_encode( $decoded_slug, $length );
              }
          }
          return rtrim( $slug, '-' );
      }