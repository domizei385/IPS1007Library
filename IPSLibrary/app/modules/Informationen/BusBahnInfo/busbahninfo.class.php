<?
/***************************************************************************//**
* @ingroup busbahninfo
* @{
* @defgroup busbahninfoclass BusBahnInfo API
* @{
* @file          busbahninfo.class.php
* @author        Frederik Granna (sysrun)
* @version       0.1
*
* @brief Bus und Bahn API
*
* @class bahn
********************************************************************************/

    class bahn{
          var $_BASEURL="http://reiseauskunft.bahn.de/bin/bhftafel.exe/dn?maxJourneys=20";
          var $_PARAMS=array();
          var $timetable=array();
          var $bahnhof=false;
          var $_FETCHMETHOD;
          function bahn($bahnhof=null,$type="abfahrt")
              {
              $type=strtolower($type);
              if(!$bahnhof)
                $bahnhof="Hannover HBF";
              $this->_init($bahnhof);
              $this->fetchMethodCURL(true);
              $this->boardType($type);
              //$this->_query();
              }

    function TypeICE($state=true)     {$this->_PARAMS['GUIREQProduct_0'] = ($state) ? "on" : false;}
    function TypeIC($state=true)      {$this->_PARAMS['GUIREQProduct_1'] = ($state) ? "on" : false;}
    function TypeIR($state=true)      {$this->_PARAMS['GUIREQProduct_2'] = ($state) ? "on" : false;}
    function TypeRE($state=true)      {$this->_PARAMS['GUIREQProduct_3'] = ($state) ? "on" : false;}     
    function TypeSBAHN($state=true)	  {$this->_PARAMS['GUIREQProduct_4'] = ($state) ? "on" : false;}
    function TypeBUS($state=true)     {$this->_PARAMS['GUIREQProduct_5'] = ($state) ? "on" : false;}
    function TypeFAEHRE($state=true)	{$this->_PARAMS['GUIREQProduct_6'] = ($state) ? "on" : false;}     
    function TypeUBAHN($state=true)	  {$this->_PARAMS['GUIREQProduct_7'] = ($state) ? "on" : false;}
    function TypeTRAM($state=true)		{$this->_PARAMS['GUIREQProduct_8'] = ($state) ? "on" : false;}     


/***************************************************************************//**
* 
*******************************************************************************/
function boardType($type)
    {
    if($type=="ankunft")
      $this->_PARAMS['boardType']="arr";
    else
      $this->_PARAMS['boardType']="dep";
    }


/***************************************************************************//**
* 
*******************************************************************************/
function datum($datum)
    {
    $this->_PARAMS['date']=$datum;
    } 


/***************************************************************************//**
* 
*******************************************************************************/
function zeit($zeit)
    {
    $this->_PARAMS['time']=$zeit;
    }


/***************************************************************************//**
* 
*******************************************************************************/
function fetch($proxy,$html=null)
    {
    if($html)
      {
      return $this->_parse($html);
      }
    else
      if($this->_FETCHMETHOD=="CURL")
        {
        return $this->_queryCurl($proxy);
        }
    }


/***************************************************************************//**
* 
*******************************************************************************/
function _queryCurl($proxy)
    {
    $this->buildQueryURL();
    $result=$this->_call($proxy); if ( !$result ) return false ;
    return $this->_parse($result);
    }


/***************************************************************************//**
* 
*******************************************************************************/
function buildQueryURL()
    {
    $fields_string="";
    foreach($this->_PARAMS as $key=>$value)
        {
        if($value)
          $fields_string .= $key.'='.urlencode($value).'&';
        };
    rtrim($fields_string,'&');

    $this->_URL=$this->_BASEURL.$fields_string;
    return $this->_URL;
    }


/***************************************************************************//**
* 
*******************************************************************************/
function _parse($data)
    {
    $dom = new DOMDocument();
    @$dom->loadHTML($data);

    $select=$dom->getElementById("rplc0");
    
    if($select->tagName=="select")
      {
      $options=$select->getElementsByTagName("option");
      foreach($options AS $op)
          {
          echo utf8_decode($op->getAttribute("value")."-".$op->nodeValue)."n";
          }
      return false;
      }
    else
      {
      $this->bahnhof=utf8_decode($select->getAttribute("value"));
      $this->_process_dom($dom);
      return true;
      }
    }

/***************************************************************************//**
* 
*******************************************************************************/
function _process_dom($dom)
    {
    $test=$dom->getElementById("sqResult")->getElementsByTagName("tr");
	
    $data=array();

    foreach($test as $k=>$t)
		{
      $tds=$t->getElementsByTagName("td");
		
		foreach($tds AS $td)
			{
         $dtype=$td->getAttribute("class");

			switch($dtype)
				{
            case 'train':
                        	if($a=$td->getElementsByTagName("a")->item(0))
										{
                              $data[$k]['train']=str_replace(" ","",$a->nodeValue);
                              if($img=$a->getElementsByTagName("img")->item(0))
											{
                                 if (preg_match('%/([a-z]*)_%', $img->getAttribute("src"), $regs))
												{
                                    switch($regs[1])
													{
                                       case 'EC':
                                             		$data[$k]['type']="IC";
                                          			break;
                                       default:
                                                	$data[$k]['type']=strtoupper($regs[1]);
                                            			break;
                                       }
                                 	}
                              	}
                            	}

                        	break;

				case 'route':
                     		if($span=@$td->getElementsByTagName("span")->item(0))
										{
                              $data[$k]['route_ziel'] = (trim($span->nodeValue));
                            	}
								   else break;
								   
									//echo "ROUTE:" . $data[$k]['route_ziel'] ."\n";
									
                           
                           $tmp=array();
                           
									$td->nodeValue = trim($td->nodeValue);
									
                           $route=explode( "\n",$td->nodeValue);
									array_splice($route,0,7);
									$count = count($route);
									//print_r($route);

									if ( $count )
									   {
									   $yy = 0;
										for ( $x=0;$x<$count;$x=$x+3)
									   	{
									   	//echo "\n".$x."[".$route[$x]."]";
									   	$zwischenhalt = "?";
									   	$zwischenhalt = @$route[$x+1] . " - " .$route[$x];
									   	$data[$k]['route'][$yy] = utf8_decode($zwischenhalt);
									   	$yy++;
									   	}
										}
										
									/*
									foreach($data[$k]['route'] AS $dk=>$dv)
                              	{
                              	//echo "[[".$dv."]]";
                              	 //$data[$k]['route'][$dk]=utf8_decode(trim(html_entity_decode(str_replace("\n","",$dv))));
                                 $data[$k]['route'][$dk]=(str_replace("\n","",$dv));

                                 }

									*/
                        	break;

				case 'time':

				case 'platform':

				case 'ris':
                        	$data[$k][$dtype]=$td->nodeValue;
                        	break;


                    }
                    //echo "n";
                }
            }


            foreach($data AS $d){ 
                if(array_key_exists("train",$d)){
                   foreach($d AS $dk=>$dv)
                      if(!is_array($dv))
                          $d[$dk]=ltrim(str_replace("\n","",utf8_decode(trim(html_entity_decode($dv)))),"-");
                    $d['route_start']=$this->bahnhof;
                    $this->timetable[]=$d;
             }
            }
    }

    
    
/***************************************************************************//**
* 
*******************************************************************************/
function fetchMethodCURL($state)
    {
    if($state)
      {
      $this->_FETCHMETHOD="CURL";
      }
    else
      {
      $this->_FETCHMETHOD="OTHER";
      }
    }



/***************************************************************************//**
* 
*******************************************************************************/
function _call($proxy)
    {
     
    $this->_CH = curl_init();

    if ( $proxy != '' )
      {                                             
      curl_setopt($this->_CH, CURLOPT_HTTPPROXYTUNNEL, 1);
      curl_setopt($this->_CH, CURLOPT_PROXY, $proxy);
      }
      
    curl_setopt($this->_CH,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($this->_CH,CURLOPT_URL,$this->_URL);
    $result = curl_exec($this->_CH);
    curl_close($this->_CH);
    return $result;
    }


/***************************************************************************//**
* 
*******************************************************************************/
function _init($bahnhof)
    {
    $this->_PARAMS=array
        (
        'country'=>'DEU',                   // Deutschland
        'rt'=>1,
        'GUIREQProduct_0'=>'on',            // ICE
        'GUIREQProduct_1'=>'on',            // Intercity- und Eurocityz�ge
        'GUIREQProduct_2'=>'on',            // Interregio- und Schnellz�ge
        'GUIREQProduct_3'=>'on',            // Nahverkehr, sonstige Z�ge
        'GUIREQProduct_4'=>'on',            // S-Bahn
        'GUIREQProduct_5'=>'on',            // BUS
        'GUIREQProduct_6'=>'on',            // Schiffe
        'GUIREQProduct_7'=>'on',            // U-Bahn
        'GUIREQProduct_8'=>'on',            // Strassenbahn
        'REQ0JourneyStopsSID'=>'',
        'REQTrain_name'=>'',
        'REQTrain_name_filterSelf'=>'1',
        'advancedProductMode'=>'',
        'boardType'=>'dep',                 // dep oder arr
        'date'=>date("d.m.Y"),
        'input'=>$bahnhof,
        'start'=>'Suchen',
        'time'=>date("H:i")
        );
    }

}

/***************************************************************************//**
* @}
* @}
*******************************************************************************/


?>