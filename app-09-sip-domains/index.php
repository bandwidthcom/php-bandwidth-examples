<html>
<head>
<?php require_once(__DIR__ . "/base.php"); ?>
<?php require_once(__DIR__ . "/../bootstrap.php"); ?> 
<title><?php echo $application->applicationTitle; ?></title>

<!-- 
  Catapult Bandwidth SIP Domains 009 interface
-->

<body>
  <?php generateMenu(); ?>
  <div class="app-content">

    <h2><?php echo $application->applicationName; ?></h2>

   <div class="box">
      <div class="status <?php echo $status; ?>"></div>   
      <small>Status:</small>

      <?php echo $message; ?>
      </div>


      <h5>Initiate A Call</h5> 
      <form name="initiate" action="./initiate.php" method="POST">
      <div style="margin: 5px 0 ; ">
        <div style="float: left;  margin-right: 5px; ">
          <small>Domain To Use</small>
          <br />
          <br />
          <select name="domainId" id="domainSelect">
            <?php foreach ($domains as $domain): ?> 
              <option value="<?php echo $domain['id']; ?>"><?php echo $domain['name']; ?></option>
            <? endforeach; ?>
          </select>
        </div>
        <div style="float: left; ">
          <small>Endpoint To Use</small>
          <br />
          <br />
            
            <?php 
              $cnt = 0;
              foreach ($endpoints as $domainId => $endpoints_):
            ?> 

              <?php if ($cnt == 0): ?>
                <select name="endpointId_<?php echo $domainId; ?>" class="endpoints" id="<?php echo $domainId; ?>" style="display: block; ">              
              <? else: ?>
                <select name"endpointId_<?php echo $domainId; ?>" class="endpoints" id="<?php echo $domainId; ?>" style="display: none; ">
              <? endif; ?>
                  <?php foreach ($endpoints_->get() as $ep): ?>
                    <option value="<?php echo $ep->id; ?>" id="<?php echo $ep->id; ?>"><?php echo $ep->name; ?></option>
                  <? endforeach; ?>
                </select>

              
            <? $cnt ++; endforeach; ?>
        </div>
       </div>
        <div style="clear: both; "></div>
      <div style="margin-top: 10px; ">
        <button>Initiate SIP Call</button>
        <!--<input type="submit" value="Initiate SIP Call" />-->
      </div>
      </form>
      <hr />

      <ul id="playground">
         <h5>Here you can see all your SIP calls so far</h5>

          <hr />
        <?php if ($sipCallsCnt > 0): ?>
          <table>
          <?php 
            $headers =array("from", "to", "meta", "date"); 
            
           foreach ($headers as $header): ?>
            <th><?php echo ucwords($header); ?></th> 
          <? endforeach; ?>
          <?php while ($sipCall =  $sipCalls->fetchArray()): ?>
            <tr>
              <td><?php echo $sipCall['from']; ?></td>
              <td><?php echo $sipCall['to']; ?></td>
              <td><?php echo $sipCall['meta']; ?></td>
              <td><?php echo $sipCall['date']; ?> </td>
            </tr>
          <? endwhile; ?>
          </table>

        
        <? else: ?>
          <h5>There are currently no SIP calls under your application</h5>
        <? endif; ?>
        

      </ul>
      <hr />

      <h3>Creating Domains & Endpoints</h3>
      <a href="./create.php"><button>Create New</button></a>
   
</body>
