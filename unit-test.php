<?php

require 'Statistics.class.php';

function const2string ( $type )
{
   $s = '???';
   switch ( $type )
   {
      case Statistics::COUNT: $s='COUNT'; break;
      case Statistics::MINIMUM: $s='MINIMUM'; break;
      case Statistics::MAXIMUM: $s='MAXIMUM'; break;
      case Statistics::MEDIAN: $s='MEDIAN'; break;
      case Statistics::AVERAGE: $s='AVERAGE'; break;
      case Statistics::QUARTILE_ONEVAR: $s='QUARTILE_ONEVAR'; break;
      case Statistics::QUARTILE_TURKEY: $s='QUARTILE_TURKEY'; break;
      case Statistics::QUARTILE_EMPIRICAL: $s='QUARTILE_EMPIRICAL'; break;
      case Statistics::SUMMARY: $s='SUMMARY'; break;
      case Statistics::PROPORTION: $s='PROPORTION'; break;
   }
   return $s;
}

$suiteOdd = array(
   array(6, 7, 15, 36, 39, 40, 41, 42, 43, 47, 49),
   array(
      array(Statistics::COUNT,               11),
      array(Statistics::MINIMUM,             6),
      array(Statistics::MAXIMUM,             49),
      array(Statistics::MEDIAN,              40),
      array(Statistics::AVERAGE,             33.1818181818),
      array(Statistics::QUARTILE_ONEVAR,     array(6,15,40,43,49)),
      array(Statistics::QUARTILE_TURKEY,     array(6,25.5,40,42.5,49)),
      array(Statistics::QUARTILE_EMPIRICAL,  array(6,15,40,43,49)),
      array(Statistics::SUMMARY,             365),
      array(Statistics::PROPORTION,          array(0.016438356164384, 0.019178082191781, 0.041095890410959, 0.098630136986301, 0.10684931506849, 0.10958904109589, 0.11232876712329, 0.11506849315068, 0.11780821917808, 0.12876712328767, 0.13424657534247))
   ));

$suiteEven = array(
   array(7, 15, 36, 39, 40, 41),
   array(
      array(Statistics::COUNT,               6),
      array(Statistics::MINIMUM,             7),
      array(Statistics::MAXIMUM,             41),
      array(Statistics::MEDIAN,              37.5),
      array(Statistics::AVERAGE,             29.666666666667),
      array(Statistics::QUARTILE_ONEVAR,     array(7,15,37.5,40,41)),
      array(Statistics::QUARTILE_TURKEY,     array(7,15,37.5,40,41)),
      array(Statistics::QUARTILE_EMPIRICAL,  array(7,13,37.5,40.25,41)),
      array(Statistics::SUMMARY,             178),
      array(Statistics::PROPORTION,          array(0.039325842696629,0.084269662921348,0.20224719101124,0.21910112359551,0.2247191011236,0.23033707865169))
   ));

function test ( $suites )
{
   $stats = new Statistics();
   $stats->reset();
   foreach ( $suites[1] as $data ) $stats->mount( $data[0] );
   foreach ( $suites[0] as $value ) $stats->add($value);
   echo '<',implode(',',$suites[0]),'>',PHP_EOL;
   foreach ( $suites[1] as $data )
   {
      list($type,$result) = $stats->execute( $data[0] );
      if ( is_array($result) )
      {
         array_walk($result,function(&$v){$v=round($v,4);});
         array_walk($data[1],function(&$v){$v=round($v,4);});
         $result = implode(',',$result);
         $data[1] = implode(',',$data[1]);
      }
      else
      {
         $result = round($result,4);
         $data[1] = round($data[1],4);
      }

      echo '  ',$data[0]==$type?'ok':'not ok',' : ',const2string($type),PHP_EOL;
      echo '  ',$data[1]==$result?'ok':'not ok',' : ',$result,PHP_EOL;
   }
}

test($suiteOdd);
test($suiteEven);