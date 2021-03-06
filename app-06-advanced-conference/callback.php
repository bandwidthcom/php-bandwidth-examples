<?php
require_once(__DIR__."/../config.php");
require_once(__DIR__."/config.php");

// Advanced Conferences
// This will extend the basic conference by doing
// user authentication and saving to our SQLite database
//
// here is what we need:
//
// 1. Answer primary incoming call
// 2. Initiate a conference
// 3. For each conference member add a call
// 
// Listening:
// 1. Make sure each conference member enter his/her unique gather key  
// and sets a fullname
// 2. Display a message for the member it should tell him his nickname
// and alert everyone of his joining
// 


// Set up our client
Catapult\Credentials::setPath(__DIR__."/..");
$client = new Catapult\Client;
// Start our event this will 
// be multipurpose and used for Gathers
// Calls and Conferences

// comment this out if you are a using
// the auto version
$inboundCallEvent = new Catapult\IncomingCallEvent;
// if you have set auto incoming
// calls you will want to uncomment:
// $inboundCallEvent = new Catapult\AnswerCallEvent;
$hangupCallEvent = new Catapult\HangupCallEvent;
$gatherCallEvent = new Catapult\GatherCallEvent;
$errorCallEvent = new Catapult\ErrorCallEvent;
$timeoutCallEvent = new Catapult\TimeoutCallEvent;
$conferenceMemberEvent = new Catapult\ConferenceMemberEvent;

if ($inboundCallEvent->isActive()) {

  // Step 1.
  //
  // Handle all incoming calls to the
  // conference number
  $call = new Catapult\Call($inboundCallEvent->callId);


  // Section 1.
  // 
  // the conference must begin
  // this is event based and will require
  // one of the authenticated users "conferenceInitiateNumber"
  // calling in.
  if ($call->to == $application->conferenceFromNumber && $call->from == $application->conferenceInitiateNumber) {


    // Important
    //
    // when using the manual  
    // approach we will need to
    // accept the calls ourselves
    if ($call->state == Catapult\CALL_STATES::started) {
      $call->accept();
    }
    $call->speakSentence(array(
      "voice" => $application->conferenceVoice,
      "gender" => $application->conferenceVoiceGender,
      "sentence" => $application->conferenceInitiate
    ));

    // let the initiate speech begin. then we will start our conference
    
    // It's recommended
    // we wait for the welcome
    // message to be complete
    sleep($application->conferenceWelcomeTimeout);

    $conference = new Catapult\Conference;
    $conference->create(array(
      "from" => $call->to
    ));


    // Important
    //
    // in the advanced conference
    // we need to create a code for every
    // attendee this sequence will need to
    // be unique for everyone



    $created = array();
    foreach ($application->conferenceAttendees as $attendee) {
           
      // we will keep generating a 4 digit code
      // until we find a unique one
     $code = rand(1000, 9999);
     while (in_array($code, $created)) {
      $code = rand(1000, 9999);
     }


     // Important
     //
     // now we can assign this
     // code to our attendee
     addRecord($application->applicationDataTable, array($code,$call->to, $attendee, $conference->id,0), array("code", "call_from", "receiver_call_from", "conference_id","attended"));

     $created[] = $code;
    }


    // Recommended
    //
    // Add two rows in our database
    // one for the basic application's
    // data the other foer intermiediette data 
    // the application. The application
    // needs the second

    $date = new DateTime;
    addRecordBasic($application->applicationTable, array($call->from, $call->to, $conference->id, $date->format("Y-m-d")));

  } else {

     // Section 2 
     //
     // someone has called conference
     // 
     // For added security we check if there a conference
     // attendee. When they are prompt them for there access code


     // we should first retrieve our conference id
     // then call

     $last = getRow(sprintf("SELECT * FROM %s WHERE call_from = '%s'; ", $call->to));
     $conference = new Catapult\Conference($last['conference_id']);
     $call = new Catapult\Call($inboundCallEvent->callId);

    // Important 
    //
    // When using the manual
    // approach we will need to
    // accept calls ourselves
    if ($call->state == Catapult\CALL_STATES::started) {
      $call->accept();
    }


    // Recommended
    //
    // check whether our conference
    // is still active
    //
    if ($conference->state == Catapult\CONFERENCE_STATES::completed) {
      // Our conference has ended
      // we will display our ended
      // message
      $call->speakSentence(array(
        "sentence" => $application->conferenceEnded,
        "voice" => $application->conferenceVoice,
        "gender" => $application->conferenceVoiceGender
      ));

      sleep($application->conferenceEndedTimeout);
      exit(1);

    }

    if (in_array($call->from, $application->conferenceAttendees)) {

      $call->speakSentence(array(
        "gender" => $application->conferenceVoiceGender,
         "voice" => new Catapult\Voice($application->conferenceVoice),
        "sentence" => $application->conferencePreJoinMessage
      ));

     sleep($application->conferenceJoinTimeout);
     $call->playAudio($application->conferenceBeepFile);

     sleep($application->conferenceAudioTimeout);

     $last = getRow(sprintf(
      "SELECT * FROM %s WHERE call_from = '%s'",
      $call->to)); 
    
      // Important
      //
      // this application uses gathers
      // like applicaiton 003 we will use 
      // the Gather's prompt for best results
      $gather = new Catapult\Gather($call->id, array(
        "terminatingDigit" => $application->conferenceTerminatingDigit,
        "maxDigits" => $application->conferenceGatherMaxDigits, 
        "prompt" => array(
          "voice" => $applicaiton->conferenceVoice,
          "sentence" => $application->conferenceVoiceGender,
          "gender" => $application->conferenceEnterDigits
        )
      ));

    } else {

      // Optional
      //
      // we may or may not want to treat un invited
      // guests. In this application we will output
      // speech telling them they were not
      // on the guest list

      $call = new Catapult\Call($inboundCallEvent->callId);
      $call->speakSentence(array(
        "voice" => $application->conferenceVoice,
        "gender" => $applicastion->conferenceVoiceGender,
        "sentence" => $application->conferenceNotInvited
      )); }


  }

} 

if ($gatherCallEvent->isActive()) {

   
   
   // Step 2.
   //
   // lookup the unique digits
   // for this user when they match
   // allow entrance. When they don't 
   // display application->noEntryMessage
   // 
   // 
   // When allowed in create our conferenceMember
   // and alert the room of their presence

  $gather = new Catapult\Gather($dtmfCallEvent->callId, $dtmfCallEvent->gatherId);
  $call = new Catapult\Call($dtmfCallEvent->callId);


  // Recommended
  //
  // we should stop speaking
  // for this call
  $call->stopSpeaking();



  // recommended
  //
  // we should treat inter digit timeouts
  // when this happens we will recreate the
  // same gather with the initial code
  if ($gather->state == Catapult\GATHER_STATES::completed
  && $gather->reason == Catapult\GATHER_REASONS::interDigitTimeout) {

    // prompt the user
    // again for the same
    // input
    //
    $gather = new Catapult\Gather($call->id, array(
       "maxDigits" => $application->conferenceGatherMaxDigits,
        // adding a shorter or longer
        // timeout is something you can do 
        // with interDigitTimeout
        //
        //"interDigitTimeout" => 10,
        // let's remake our prompt      
        // it will have the same speech
        // as our last
        "prompt" => array(
          "sentence" => $application->conferenceSecondAttemptDigits,
          "voice" => $application->conferenceVoice,
          "gender" => $application->conferenceGender
        )
    ));

  }

     
  // we should only evaluate our input
  // once the terminating digit
  // is pressed

  if ($gather->state  == Catapult\GATHER_STATES::completed 
  && $gather->reason == Catapult\GATHER_REASONS::maxDigits) {
   $last = getRow(sprintf(
      "SELECT * FROM %s WHERE call_from = '%s' AND receiver_call_from = '%s'",
      $application->datatable, $call->to, $call->from)); 

    $code = $last['code']; 

    if ($gather->digits == $code) {

      // Important
      //
      // the user has entered
      // the right digits
      // we can  let him in at this point
      $conference = new Catapult\Conference($last['conference_id']);
    
      $call = new Catapult\Call($gatherCallEvent->callId);

      $call->speakSentence(array(
        "voice" => $application->conferenceVoice,
        "gender" => $application->conferenceVoiceGender,
        "sentence" => $application->conferenceJoinMessage
      ));

      $conference->addMember(array(
        "callId" => $callId
      ));

      // Optional
      // 
      // we should generally wait 
      // as we will be using the DB next
      sleep($application->conferenceMemberTimeout);


      // Recommended
      // 
      // as a final step we should
      // update the SQlite data so 
      // everything is viewable 
      // from the interface
      updateRow(sprintf("UPDATE %s SET attended = 1 WHERE receiver_call_from = '%s' AND call_from = '%s'; ", $call->from, $call->to));


    } else {


      // Recommended
      //
      // wrong input for the conference
      // when this happens we need to alert
      // the user.
      
      $call->speakSentence(array(
        "voice" => $application->conferenceVoice,
        "gender" => $application->conferenceVoiceGender,
        "sentence" => $application->conferenceNoEntry
      ));
             
    }

  } 

} 

if ($conferenceMemberEvent->isActive()) {

   // Step 2 
   //
   // this is when our user has joined
   // the conference we can alert the others
   // their full name.
  
   $conferenceMember = new Catapult\ConferenceMember($conferenceMemberEvent->conferenceId, $conferenceMemberEvent->conferenceMemberId);
   $call = new Catapult\Call($conferenceMember->callId);
   $conference = new Catapult\Conference($conferenceMember->conferenceId);

   $name = getRow(sprintf(
      "SELECT name FROM Advanced Conferences Data WHERE call_from = '%s' AND conference_id = '%s';", $call->from, $conference->id
   ));


   if ($conferenceMemberEvent->state == Catapult\CONFERENCE_MEMBER_STATES::active) {

     // Recommended 
     //
     // When a user joins we will alert
     // everyone of his presence
     $conference->speakSentence(array(
        "sentence" => "A user has entered the room.",
        "gender" => $application->conferenceVoiceGender,
        "voice" => $application->conferenceVoice
     ));
   } else if ($conferenceMemberEvent->state == Catapult\CONFERENCE_MEMBER_STATES::done) {

      // Recommended 
      //
      // a user has left. We should alert everyone
      // and cleanup.
      $conference->speakSentence(array(
        "sentence" => "A user has left the room.",
        "gender" => $application->conferenceVoiceGender,
        "voice" => $application->conferenceVoice
      ));
   }
} 

if ($hangupCallEvent->isActive()) {

  // Recommended
  //
  // Hangups in advanced conference will 
  // work two ways when a user initiates
  // it we should clean up his data.
  //
  // When it is the host we should end the conference
  // optionally alert the users
  // of its ending
 
  // get our basic data 
  $call = new Catapult\Call($hangupCallEvent->callId);
  $last = getRow(
    sprintf("SELECT * FROM %s; ", $application->applicationTable)
  );
  $conference = new Catapult\Conference($last['conference_id']);

  if ($hangupCallEvent->from == $application->conferenceInitiateNumber) {
    // Important
    //
    // dealing with the host    
    // hanging up is needed
    // as we can no longer
    // continue our conference without
    //
    // for this demo we will not remove the data as we need to
    // display it

    //$db->query(sprintf(
    //  "DELETE FROM %s WHERE conference_id = '%s'; ", $application->applicationDataTable, $conference->id
    //));

  } else {
     // Recommended
     //
     // cleaning a members
     // data is recommended.
    //$db->query(sprintf(
    //  "DELETE FROM %s WHERE conference_id = '%s' AND call_from = '%s'", $application->datatable, $conference->id, $hangupCallEvent->from
    //));
  } 
   


}

if ($timeoutCallEvent->isActive()) {

  // Recommended 
  //
  // handle timeouts
  // for this application
  $call = new Catapult\Call($timeoutCallEvent->callId);
  $call->hangup();
    
}

if ($errorCallEvent->isActive()) {
  // Recommended
  //
  // handle errors
  // for this application
}

?>
