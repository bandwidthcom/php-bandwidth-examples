<?php
require_once(__DIR__."/../config.php");
require_once(__DIR__."/config.php");

// Application 005 initiation. This will
// look at the applicationNumbers and initiate
// the call foreach member. It should be accessible
// from the web interface

// !important make sure the callback url for calls
// is properly setup as this will impact where
// conferences are responded to.

Catapult\Credentials::setPath(__DIR__."/..");
$client = new Catapult\Client;
// First we start our primary
// call. This should be using ourselves
// and call to the conferenceFromNumber
$call = new Catapult\Call(array(
  "from" => $application->conferenceInitiateNumber,
  "to" => $application->conferenceFromNumber
));

sleep(5);
route(sprintf("./?message=%s", "Initiated conference, waiting for your attendees, you can also dial in"));

?>
