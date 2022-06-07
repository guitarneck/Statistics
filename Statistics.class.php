<?php
if ( !defined('__STATISTICS_CLASS__') ):
   define('__STATISTICS_CLASS__',1);

class Statistics
{
   const    NOTHING              = 0;
   const    COUNT                = 0b1;
   const    SUMMARY              = 0b10;
   const    AVERAGE              = 0b100;
   const    MEDIAN               = 0b1000;
   const    MINIMUM              = 0b10000;
   const    MAXIMUM              = 0b100000;
   const    QUARTILE_ONEVAR      = 0b1000000;
   const    QUARTILE_TURKEY      = 0b10000000;
   const    QUARTILE_EMPIRICAL   = 0b100000000;
   const    PROPORTION           = 0b1000000000;
   const    QUARTILE             = 0b10000000000;

   protected   $statistics;

   function __construct ()
   {
      $this->statistics = array();
   }

   function exists ( $flag )
   {
      return array_key_exists($flag,$this->statistics);
   }

   function mount ( $kind, $args=null )
   {
      //$args = func_get_args();
      //$kind = array_shift($args);

      $all = array(
         Statistics::AVERAGE           => 'StatisticsAverage',
         Statistics::MEDIAN            => 'StatisticsMedian',
         Statistics::COUNT             => 'StatisticsCount',
         Statistics::SUMMARY           => 'StatisticsSummary',
         Statistics::MINIMUM           => 'StatisticsMinimum',
         Statistics::MAXIMUM           => 'StatisticsMaximum',
         Statistics::QUARTILE_ONEVAR   => 'StatisticsQuartileOneVar',
         Statistics::QUARTILE_TURKEY   => 'StatisticsQuartileTurkey',
         Statistics::QUARTILE_EMPIRICAL=> 'StatisticsQuartileEmpirical',
         Statistics::PROPORTION        => 'StatisticsProportion',
         Statistics::QUARTILE          => 'StatisticsQuartile',
      );

      foreach ( $all as $flag => $class )
      {
         if ( !($kind & $flag) ) continue;

         if ( ! $this->exists($flag) )
            $this->statistics[$flag] = new $class($this,$args);
      }

   }

   function & retrieve ( $flag )
   {
      $s = null;
      if ( $this->exists($flag) ) $s = $this->statistics[$flag];
      return $s;
   }

   function reset ()
   {
      foreach ( $this->statistics as & $s ) $s->reset();
   }

   function add ( $amount )
   {
      foreach ( $this->statistics as & $s ) $s->add($amount);
   }

   function execute ( $flag, $options=null )
   {
      if ( $this->exists($flag) ) 
      {
         $s = & $this->statistics[$flag];
         return $s->execute($options);
      }
      return array(0,null);
   }

   function executeAll ( $options=null )
   {
      foreach ( $this->statistics as $flag => $s )
         yield $s->execute( key_exists($flag,$options) ? $options[$flag] : null );
   }
}

class StatisticsKind
{
   protected   $statistics;
   protected   $kind;

   function __construct ( $statistics, $kind )
   {
      $this->statistics = $statistics;
      $this->kind = $kind;
   }
}

class StatisticsCount extends StatisticsKind
{
   protected   $count;

   function __construct ( $statistics )
   {
      parent::__construct( $statistics, Statistics::COUNT );
      $this->count = 0;
   }

   function reset ()
   {
      $this->count = 0;
   }

   function add ( $amount )
   {
      $this->count++;
   }

   function execute ()
   {
      return array($this->kind, $this->count);
   }
}

class StatisticsSummary extends StatisticsKind
{
   protected   $summary;

   function __construct ( $statistics )
   {
      parent::__construct( $statistics, Statistics::SUMMARY );
      $this->summary = 0;
   }

   function reset ()
   {
      $this->summary = 0;
   }

   function add ( $amount )
   {
      $this->summary += $amount;
   }

   function execute ()
   {
      return array($this->kind, $this->summary);
   }
}

class StatisticsAverage extends StatisticsKind
{
   function __construct ( $statistics )
   {
      parent::__construct( $statistics, Statistics::AVERAGE );
      $this->statistics->mount(Statistics::COUNT|Statistics::SUMMARY);
   }

   function reset ()
   {
   }

   function add ( $amount )
   {
   }

   function execute ()
   {
      list(,$count) = $this->statistics->execute(Statistics::COUNT);
      list(,$summary) = $this->statistics->execute(Statistics::SUMMARY);
      return array($this->kind, $summary / $count);
   }
}

class StatisticsMedian extends StatisticsKind
{
   protected   $datas;

   function __construct ( $statistics, $kind=null )
   {
      parent::__construct( $statistics, $kind?:Statistics::MEDIAN );
      $this->datas = array();
   }

   function reset ()
   {
      $this->datas = array();
   }

   function add ( $amount )
   {
      $this->datas[] = $amount;
   }

   function execute ()
   {
      sort($this->datas);
      $n = count($this->datas);
      $f = floor($n * .5);
      return array($this->kind, ($this->datas[$f - (1-($n % 2))] + $this->datas[$f]) * .5);
   }
}

class StatisticsMinimum extends StatisticsKind
{
   protected   $minimum;

   function __construct ( $statistics )
   {
      parent::__construct( $statistics, Statistics::MINIMUM );
      $this->minimum = null;
   }

   function reset ()
   {
      $this->minimum = null;
   }

   function add ( $amount )
   {
      $this->minimum = $this->minimum != null ? min($amount,$this->minimum) : $amount;
   }

   function execute ()
   {
      return array($this->kind, $this->minimum);
   }
}

class StatisticsMaximum extends StatisticsKind
{
   protected   $maximum;

   function __construct ( $statistics )
   {
      parent::__construct( $statistics, Statistics::MAXIMUM );
      $this->maximum = null;
   }

   function reset ()
   {
      $this->maximum = null;
   }

   function add ( $amount )
   {
      $this->maximum = $this->maximum != null ? max($amount,$this->maximum) : $amount;
   }

   function execute ()
   {
      return array($this->kind, $this->maximum);
   }
}

class StatisticsQuartileOneVar extends StatisticsMedian
{
   function __construct ( $statistics )
   {
      parent::__construct( $statistics, Statistics::QUARTILE_ONEVAR );
   }

   function execute ()
   {
      list(,$Q2) = parent::execute(); // sorted and get the median

      $Q0 = $this->datas[0];

      $n = count($this->datas);
      $h = $n * .5;

      //One Var Stats - method
      $f = floor($n * .25);
      $Q1 = ($this->datas[$f - (1-($h % 2))] + $this->datas[$f]) * .5;

      $f = floor($n * .75);
      $Q3 = ($this->datas[$f - (1-($h % 2))] + $this->datas[$f]) * .5;

      $Q4 = $this->datas[$n - 1];

      return array($this->kind, array($Q0,$Q1,$Q2,$Q3,$Q4));
   }
}

class StatisticsQuartileTurkey extends StatisticsMedian
{
   function __construct ( $statistics )
   {
      parent::__construct( $statistics, Statistics::QUARTILE_TURKEY );
   }

   function execute ()
   {
      list(,$Q2) = parent::execute(); // sorted and get the median

      $Q0 = $this->datas[0];

      $n = count($this->datas);

      // Turkey's hinges - method
      $f = floor($n * .25);
      $Q1 = ($this->datas[$f] + $this->datas[$f + ($n % 2)]) * .5;

      $f = floor($n * .75);
      $Q3 = ($this->datas[$f - ($n % 2)] + $this->datas[$f]) * .5;

      $Q4 = $this->datas[$n - 1];

      return array($this->kind, array($Q0,$Q1,$Q2,$Q3,$Q4));
   }
}

class StatisticsQuartileEmpirical extends StatisticsMedian
{
   function __construct ( $statistics )
   {
      parent::__construct( $statistics, Statistics::QUARTILE_EMPIRICAL );
   }

   private
   function quantile ( $p, $n )
   {
      $n = $n + 1;
      $k = intval($p * $n);
      $alpha = ($p * $n) - $k;
      $k--; // start at x0, not x1
      return $this->datas[$k] + ($alpha * ($this->datas[$k + 1] - $this->datas[$k]));
   }

   function execute ()
   {
      sort($this->datas);

      $Q0 = $this->datas[0];

      $n = count($this->datas);

      $Q1 = $this->quantile(.25, $n);
      $Q2 = $this->quantile( .5, $n);
      $Q3 = $this->quantile(.75, $n);

      $Q4 = $this->datas[$n - 1];

      return array($this->kind, array($Q0,$Q1,$Q2,$Q3,$Q4));
   }
}

class StatisticsProportion extends StatisticsMedian
{
   const    NORMALIZE   = 0b1;
   const    PERCENT     = 0b10;

   private  $options;
   private  $summary;

   function __construct ( $statistics, $options=StatisticsProportion::NORMALIZE )
   {
      parent::__construct( $statistics, Statistics::PROPORTION );
      $this->statistics->mount(Statistics::SUMMARY);
      $this->options = $options;
   }

   function execute ( $options=null )
   {
      $options = $options ? $options : $this->options;

      list(,$this->summary) = $this->statistics->execute(Statistics::SUMMARY);
      // $v * (1/$summary)
      if ( $options & StatisticsProportion::PERCENT ) $this->summary /= 100;
      return array($this->kind, array_map(function ($v) { return $v/$this->summary; }, $this->datas) );
   }
}

class StatisticsQuartile extends StatisticsMedian
{
   const    ONEVAR      = 0b1;
   const    TURKEY      = 0b10;
   const    EMPIRICAL   = 0b100;

   private  $options;

   function __construct ( $statistics, $options=StatisticsQuartile::ONEVAR )
   {
      parent::__construct( $statistics, Statistics::QUARTILE );
      $this->options = $options;
   }

   private
   function quantile ( $p, $n )
   {
      $n = $n + 1;
      $k = intval($p * $n);
      $alpha = ($p * $n) - $k;
      $k--; // start at x0, not x1
      return $this->datas[$k] + ($alpha * ($this->datas[$k + 1] - $this->datas[$k]));
   }

   function execute ( $options=null )
   {
      $options = $options ? $options : $this->options;

      list(,$Q2) = parent::execute(); // sorted and get the median

      $Q0 = $this->datas[0];
      $n = count($this->datas);

      $Q1 = $Q3 = 0;

      if ( $options & StatisticsQuartile::ONEVAR )
      {
            $h = $n * .5;
            $f = floor($n * .25);
            $Q1 = ($this->datas[$f - (1-($h % 2))] + $this->datas[$f]) * .5;
            $f = floor($n * .75);
            $Q3 = ($this->datas[$f - (1-($h % 2))] + $this->datas[$f]) * .5;      
      }         
      if ( $options & StatisticsQuartile::TURKEY )
      {
            $f = floor($n * .25);
            $Q1 = ($this->datas[$f] + $this->datas[$f + ($n % 2)]) * .5;
            $f = floor($n * .75);
            $Q3 = ($this->datas[$f - ($n % 2)] + $this->datas[$f]) * .5;
      }
      if ( $options & StatisticsQuartile::EMPIRICAL )
      {
            $Q1 = $this->quantile(.25, $n);
            //$Q2 = $this->quantile( .5, $n);
            $Q3 = $this->quantile(.75, $n);
      }

      $Q4 = $this->datas[$n - 1];

      return array($this->kind, array($Q0,$Q1,$Q2,$Q3,$Q4));
   }
}

endif;