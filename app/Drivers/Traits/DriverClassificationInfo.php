<?php

namespace App\Drivers\Traits;

trait DriverClassificationInfo {
   public function getDriverMessageFormat()
    {
        return $this->messageFormat ?? "undefined";
    }

    public function getDriverProtocol()
    {
        return $this->driverProtocol ?? "undefined";
    }
}
