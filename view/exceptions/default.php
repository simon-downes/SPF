<?php
   header("Content-type: text/html");
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en">

<head>
   <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
   <title><?php echo get_class($error); ?></title>
   <link rel="icon" href="/favicon.ico" type="image/vnd.microsoft.icon"/>
   <style type="text/css">
      
      html * {
         margin:0;
         padding:0;
      }
      
      body {
         font-family: Verdana;
      }
      
      #header {
         background : #CD1818;
         color : #ffffff;
         padding:25px 20px 20px 20px;
      }
      
      #header p {
         font-size:20px;
      }
      
      h1 {
         margin-bottom:10px;
      }
      
      h2 {
         margin-bottom: 10px;
         color:#328ADC;
         font-size:18px;
      }
      
      #file, #trace {
         padding : 10px 20px 0px 20px;
         margin-bottom:20px;
      }
      
      .panel {
         background: #F1F5FB;
         padding:10px;
         border-radius:7px;
         -moz-border-radius:7px;
         -webkit-border-radius:7px;
      }
      
      ol {
      }
      
      li {
         margin: 0 0 10px 25px;
      }
      
      td.value {
         white-space:pre;
         font-family: monospace;
      }
      
   </style>
</head>

<body>

<div id="header">
   <h1><?php echo get_class($error); ?></h1>
   <p><?php if( $error->getCode() ) echo $error->getCode(). ' - '; echo $error->getMessage() ?></p>
</div>

<div id="file">
   
   <h2>Source File:</h2>
   
   <div class="panel">
      <p>
         <strong>File:</strong>
         <code><?php echo $error->getFile(); ?></code>
         <strong>Line: </strong>
         <code><?php echo $error->getLine(); ?></code>
      </p>
   </div>
   
</div>

<div id="trace">
   
   <h2>Trace:</h2>
   
   <ol class="panel">
      <?php foreach( $error->getTrace() as $i => $item ) { ?>
      <li>
         <p>
            <?php echo $item['file']; ?> (<?php echo $item['line']; ?>) <a href="">source</a> <abbr>&#x25ba;</abbr>
            <?php echo $item['class']. $item['type']. $item['function']. '( arguments <abbr>&#x25ba;</abbr>)'; ?>
         </p>
         <table>
            <?php foreach( $item['args'] as $var => $value ) { ?>
            <tr>
               <td><?php echo $var; ?></td>
               <td class="value"><?php print_r($value); ?></td>
            </tr>
            <?php } ?>
         </table>
      </li>
      <?php } ?>
   </ol>
   
</div>
   
</body>

</html>
