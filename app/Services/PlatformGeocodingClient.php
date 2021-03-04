<?php

namespace App\Services;

use Ushahidi\Platform\Client;

/*
 * This class just extends the SDK client, to differentiate its use
 * for geocoding purposes.
 */
class PlatformGeocodingClient extends Client {

  // Replicate constructor for Laravel's IoC
  public function __construct(string $apiUrl, array $options = [], string $version = '5')
  {
    parent::__construct($apiUrl, $options, $version);
  }

}
