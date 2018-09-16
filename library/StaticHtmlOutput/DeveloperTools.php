<?php

/*
   http://php.net/manual/en/function.microtime.php

  Example usage:

  unset($t);  
  $t['start'] = microtime(true); 

  // some code

  $t['start_of_some_action'] = microtime(true); 
    
  // some code

  $t['start_of_another_action'] = microtime(true); 

  $str_result_bench=mini_bench_to($t);
  error_log($str_result_bench); // string return


 */
function mini_bench_to($arg_t, $arg_ra=false) {
  $aff = '';
  $tttime=round((end($arg_t)-$arg_t['start'])*1000,4);
  if ($arg_ra) $ar_aff['total_time']=$tttime;
  else $aff="total time : ".$tttime."ms ";
  $prv_cle='start';
  $prv_val=$arg_t['start'];

  foreach ($arg_t as $cle=>$val)
  {
      if($cle!='start')   
      {
          $prcnt_t=round(((round(($val-$prv_val)*1000,4)/$tttime)*100),1);
          if ($arg_ra) $ar_aff[$prv_cle.' -> '.$cle]=$prcnt_t;
          $aff.=$prv_cle.' -> '.$cle.' : '.$prcnt_t."% ";
          $prv_val=$val;
          $prv_cle=$cle;
      }
  }
  if ($arg_ra) return $ar_aff;
  return $aff;
}


