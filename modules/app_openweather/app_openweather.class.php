<?php

/**
 * OpenWeather Application
 *
 * module for MajorDoMo project
 * @author Lutsenko Denis <palacex@gmail.com>
 * @copyright Lutsenko D.V.
 * @version 0.1 December 2014
 */
class app_openweather extends module
{
   /**
    * openweather
    *
    * Module class constructor
    *
    * @access private
    */
   public function __construct()
   {
      $this->name = "app_openweather";
      $this->title = "Погода от OpenWeatherMap";
      $this->module_category = "<#LANG_SECTION_APPLICATIONS#>";
      $this->checkInstalled();
   }
   
   /**
    * saveParams
    *
    * Saving module parameters
    * @access public
    * @param mixed $data Data (default 0)
    * @return void
    */
   public function saveParams($data = 0)
   {
      $p = array();
      
      if(isset($this->id))
         $p["id"] = $this->id;
      
      if(isset($this->view_mode))
         $p["view_mode"] = $this->view_mode;
      
      if(isset($this->edit_mode))
         $p["edit_mode"] = $this->edit_mode;
      
      if(isset($this->tab))
         $p["tab"] = $this->tab;
      
      return parent::saveParams($p);
   }
   
   /**
    * getParams
    * Getting module parameters from query string
    * @access public
    * @return void
    */
   public function getParams()
   {
      global $id;
      global $mode;
      global $view_mode;
      global $edit_mode;
      global $tab;
      global $fact;
      global $forecast;
      
      if (isset($id))
         $this->id=$id;
      
      if (isset($mode))
         $this->mode = $mode;
      
      if (isset($view_mode))
         $this->view_mode = $view_mode;
      
      if (isset($edit_mode))
         $this->edit_mode = $edit_mode;
      
      if (isset($tab))
         $this->tab = $tab;
      
      if (isset($forecast))
         $this->forecast = $forecast;
      
      if (isset($fact))
         $this->fact = $fact;
   }
   
   /**
    * Run
    *
    * Description
    * @access public
    *
    * @return void
    */
   public function run()
   {
      global $session;
      $out = array();
      
      if ($this->action == 'admin')
         $this->admin($out);
      else
         $this->usual($out);
      
      if (isset($this->owner->action))
         $out['PARENT_ACTION'] = $this->owner->action;
      
      if (isset($this->owner->name))
         $out['PARENT_NAME'] = $this->owner->name;
      
      $out['VIEW_MODE'] = $this->view_mode;
      $out['EDIT_MODE'] = $this->edit_mode;
      $out['MODE']      = $this->mode;
      $out['ACTION']    = $this->action;

      if ($this->single_rec)
         $out['SINGLE_REC'] = 1;
      
      $this->data = $out;

      $p = new parser(DIR_TEMPLATES . $this->name . "/" . $this->name . ".html", $this->data, $this);

      $this->result = $p->result;
   }
   
   /**
    * BackEnd
    * Summary of admin
    * @param mixed $out Out
    */
   public function admin(&$out)
   {
      global $ow_subm;
      
      if($ow_subm == 'setting')
      {
         $this->save_setting();
         $this->get_weather(gg('ow_city.id'));
         $this->view_mode = "";
      }
      else if($ow_subm == 'getWeather')
      {
         $this->get_weather(gg('ow_city.id'));
      }
      
      if($this->view_mode == '')
      {
         $ow_city_id = gg('ow_city.id');
         $ow_city_name = gg('ow_city.name');
         
         if($ow_city_id != '' && $ow_city_name != '')
         {
            $out["ow_city_name"] = $ow_city_name;
            $out["ow_data_update"] = gg('ow_city.data_update');
            $this->view_weather($out);
         }
      }
      else if($this->view_mode == 'setting')
      {
         $this->get_setting($out);
      }
   }
   
   /**
    * FrontEnd
    * Summary of usual
    * @access public
    *
    * @param mixed $out 
    */
   public function usual(&$out)
   {
      $ow_city_id   = gg('ow_city.id');
      $ow_city_name = gg('ow_city.name');
      
      if ($ow_city_id != '' && $ow_city_name != '')
      {
         $out["ow_city_name"]     = $ow_city_name;
         $out["data_update"] = gg('ow_city.data_update');

         $this->view_weather($out);
      }
      else
      {
         $out["fact_weather"] = constant('LANG_OW_CHOOSE_CITY');
      }
   }

   /**
    * Вывод данных о прогнозе погоды
    * @param array $out Массив с сформированными данными о прогнозе погоды
    * @return void
    */
   public function view_weather(&$out)
   {
      $fact     = $this->fact;
      $forecast = $this->forecast;
      
      if (is_null($forecast))
         $forecast = gg('ow_setting.forecast_interval');
      
      if($fact != 'off')
      {
         $temp = gg('ow_fact.temperature');

         if($temp > 0) $temp = "+" . $temp;

         $out["FACT"]["temperature"]   = $temp;
         $out["FACT"]["weatherIcon"]   = app_openweather::getWeatherIcon(gg('ow_fact.image'));
         $windDirection                = gg('ow_fact.wind_direction');
         $out["FACT"]["windDirection"] = app_openweather::getWindDirection($windDirection) . "(" . $windDirection . "&deg;)";
         $out["FACT"]["windSpeed"]     = gg('ow_fact.wind_speed');
         $out["FACT"]["humidity"]      = gg('ow_fact.humidity');
         $out["FACT"]["clouds"]        = gg('ow_fact.clouds');
         $out["FACT"]["weatherType"]   = gg('ow_fact.weather_type');
         $out["FACT"]["pressure"]      = gg('ow_fact.pressure');
         $out["FACT"]["pressure_mmhg"] = app_openweather::ConvertPressure(gg('ow_fact.pressure'),"hpa", "mmhg");
         $out["FACT"]["data_update"]   = gg('ow_city.data_update');
         
         $out["FACT"]["sunrise"]       = date("H:i:s", gg('ow_fact.sunrise'));
         $out["FACT"]["sunset"]        = date("H:i:s", gg('ow_fact.sunset'));
         $out["FACT"]["day_length"]    = gmdate("H:i", gg('ow_fact.day_length'));
      }

      $forecast = $forecast-1;
      
      if ($forecast > 0)
      {
         $forecastOnLabel = constant('LANG_OW_FORECAST_ON');
         for ($i = 0; $i <= $forecast; $i++)
         {
            $curDate = gg('ow_day' . $i . '.date');

            if ($i == 0)
            {
               $out["FORECAST"][$i]["date"] = constant('LANG_OW_WEATHER_TODAY') . ' ' . $curDate;
            }
            else
            {
               $out["FORECAST"][$i]["date"] = $forecastOnLabel . ' ' . $curDate;
            }
            
            $temp = gg('ow_day' . $i . '.temperature');

            if($temp > 0) $temp = "+" . $temp;
            
            $dayTemp = gg('ow_day'.$i.'.temp_day');
            $eveTemp = gg('ow_day'.$i.'.temp_eve');

            $out["FORECAST"][$i]["temperature"] = $temp;
            $out["FORECAST"][$i]["temp_morn"]   = gg('ow_day'.$i.'.temp_morn');
            $out["FORECAST"][$i]["temp_day"]    = $dayTemp;
            $out["FORECAST"][$i]["temp_eve"]    = $eveTemp;
            $out["FORECAST"][$i]["temp_night"]  = gg('ow_day'.$i.'.temp_night');
            $out["FORECAST"][$i]["temp_min"]    = gg('ow_day'.$i.'.temp_min');
            $out["FORECAST"][$i]["temp_max"]    = gg('ow_day'.$i.'.temp_max');
            
            $out["FORECAST"][$i]["weatherIcon"]   = app_openweather::getWeatherIcon(gg('ow_day' . $i . '.image'));
            $windDirection                        = gg('ow_day' . $i . '.wind_direction');
            $out["FORECAST"][$i]["windDirection"] = app_openweather::getWindDirection($windDirection) . "(" . $windDirection . "&deg;)";
            $out["FORECAST"][$i]["windSpeed"]     = gg('ow_day'.$i.'.wind_speed');
            $out["FORECAST"][$i]["humidity"]      = gg('ow_day'.$i.'.humidity');
            $out["FORECAST"][$i]["weatherType"]   = gg('ow_day'.$i.'.weather_type');
            $out["FORECAST"][$i]["pressure"]      = gg('ow_day'.$i.'.pressure');
            $out["FORECAST"][$i]["pressure_mmhg"] = gg('ow_day'.$i.'.pressure_mmhg');
            $out["FORECAST"][$i]["clouds"]        = gg('ow_day'.$i.'.clouds');
            $out["FORECAST"][$i]["rain"]          = gg('ow_day'.$i.'.rain');
            $out["FORECAST"][$i]["snow"]          = gg('ow_day'.$i.'.snow');
            $out["FORECAST"][$i]["freeze"]        = self::GetFreezePossibility($dayTemp, $eveTemp);
			
            $out["FORECAST"][$i]["sunrise"]    = date("H:i:s", gg('ow_day'.$i.'.sunrise'));
            $out["FORECAST"][$i]["sunset"]     = date("H:i:s", gg('ow_day'.$i.'.sunset'));
            $out["FORECAST"][$i]["day_length"] = gmdate("H:i", gg('ow_day'.$i.'.day_length')); 
         }
      }
   }
	function processSubscription($event_name, $details='') {
		if ($event_name=='HOURLY') {
			$updateTime = gg('ow_setting.updateTime');
			if($updateTime > 0) {
				$count = gg('ow_setting.countTime'); 
				if($count >= $updateTime){
					$this->get_weather(gg('ow_city.id'));
					sg('ow_setting.countTime', 1);
				} else {
					$count++;
					sg('ow_setting.countTime', $count);
				}
			}
		}
	}
   /**
    * Получение погоды по ID города
    * @param int $cityID ID города  
    */
   public function get_weather($cityID)
   {
      $weather    = app_openweather::GetJsonWeatherDataByCityID($cityID);
      $curWeather = self::GetCurrentWeatherDataByCityID($cityID);

      if ($weather->cod == "404" || $curWeather->cod == "404")
      {
         DebMes($weather->message);
         return;
      }
      
      $fact = $curWeather->main;
      
      $date = date("d.m.Y G:i:s T Y", $curWeather->dt);
     
      sg('ow_fact.temperature', $fact->temp);
      sg('ow_fact.weather_type', $curWeather->weather[0]->description);
      sg('ow_fact.wind_direction', $curWeather->wind->deg);
      sg('ow_fact.wind_speed',$curWeather->wind->speed);
      sg('ow_fact.humidity', $fact->humidity);
      sg('ow_fact.pressure', $fact->pressure);
      sg('ow_fact.pressure_mmhg', app_openweather::ConvertPressure($fact->pressure, "hpa", "mmhg", 2));
      sg('ow_fact.image', $curWeather->weather[0]->icon);
      sg('ow_fact.clouds', $curWeather->clouds->all);
      sg('ow_fact.rain', isset($fact->rain) ? $fact->rain : '');
      sg('ow_city.data_update', $date);
      
      $sunInfo = $this->GetSunInfoByCityID($cityID);
      if ($sunInfo)
      {
         $sunRise = $sunInfo["sunrise"];
         $sunSet = $sunInfo["sunset"];
         $dayLength = $sunSet - $sunRise;

         sg('ow_fact.sunrise', $sunRise);
         sg('ow_fact.sunset', $sunSet);
         sg('ow_fact.day_length', $dayLength);
         sg('ow_fact.transit', $sunInfo["transit"]);
         sg('ow_fact.civil_twilight_begin', $sunInfo["civil_twilight_begin"]);
         sg('ow_fact.civil_twilight_end', $sunInfo["civil_twilight_end"]);
      }
      
      $i = 0;
      foreach($weather->list as $day)
      {
         $date = date("d.m.Y", $day->dt);
         sg('ow_day'.$i.'.date', $date);
         
         sg('ow_day'.$i.'.temperature', app_openweather::GetCurrTemp($day->temp));
         sg('ow_day'.$i.'.temp_morn', $day->temp->morn);
         sg('ow_day'.$i.'.temp_day', $day->temp->day);
         sg('ow_day'.$i.'.eve', $day->temp->eve);
         sg('ow_day'.$i.'.temp_night', $day->temp->night);
         sg('ow_day'.$i.'.temp_min', $day->temp->min);
         sg('ow_day'.$i.'.temp_max', $day->temp->max);
         
         sg('ow_day'.$i.'.weather_type', $day->weather[0]->description);
         sg('ow_day'.$i.'.wind_direction', $day->deg);
         sg('ow_day'.$i.'.wind_speed', $day->speed);
         sg('ow_day'.$i.'.humidity', $day->humidity);
         sg('ow_day'.$i.'.pressure', $day->pressure);
         sg('ow_day'.$i.'.pressure_mmhg', app_openweather::ConvertPressure($day->pressure, "hpa", "mmhg", 2));
         sg('ow_day'.$i.'.image', $day->weather[0]->icon);
         sg('ow_day'.$i.'.clouds', $day->clouds);
         sg('ow_day'.$i.'.rain', isset($day->rain) ? $day->rain : 0);
         sg('ow_day'.$i.'.snow', isset($day->snow) ? $day->snow : 0);
         
         $curTimeStamp = strtotime('+' . $i . ' day', time());
         $sunInfo = $this->GetSunInfoByCityID($cityID, $curTimeStamp);
         if ($sunInfo)
         {
            $sunRise = $sunInfo["sunrise"];
            $sunSet = $sunInfo["sunset"];
            $dayLength = $sunSet - $sunRise;
            
            sg('ow_day'.$i.'.sunrise', $sunRise);
            sg('ow_day'.$i.'.sunset', $sunSet);
            sg('ow_day'.$i.'.day_length', $dayLength);
            sg('ow_day'.$i.'.transit', $sunInfo["transit"]);
            sg('ow_day'.$i.'.civil_twilight_begin', $sunInfo["civil_twilight_begin"]);
            sg('ow_day'.$i.'.civil_twilight_end', $sunInfo["civil_twilight_end"]);
         }
         
         $i++;
      }
      runScript(gg('ow_setting.updScript'));
   }

   /**
    * Get weather icon
    * @param string $image weather icon name
    * @return string
    */
   private static function getWeatherIcon($image)
   {
      if ($image == '') retrun;
      
      $fileName = $image . '.png';
      $urlIcon = "http://openweathermap.org/img/w/" . $fileName;
      
      if(gg('ow_setting.ow_imagecache') == 'on')
      {
         $filePath = ROOT.'cached' . DIRECTORY_SEPARATOR . 'openweather' . DIRECTORY_SEPARATOR . 'image';
         
         if (!is_dir($filePath))
         {
            @mkdir(ROOT . 'cached', 0777);
            @mkdir(ROOT . 'cached' . DIRECTORY_SEPARATOR . 'openweather', 0777);
            @mkdir($filePath, 0777);
         }
         
         if (!file_exists($filePath . DIRECTORY_SEPARATOR . $fileName))
         {
            $contents = file_get_contents($urlIcon);
            if ($contents)
            {
               SaveFile($filePath . DIRECTORY_SEPARATOR . $fileName, $contents);
            }
         }
         
         $urlIcon = ROOTHTML . "cached/openweather/image/" . $fileName;
      }
      return $urlIcon;
   }
   
   /**
    * Get wind direction name by direction in degree 
    * @param mixed $degree Wind degree
    * @return string
    */
   private static function getWindDirection($degree)
   {
      $windDirection = ['<#LANG_N#>', '<#LANG_NNE#>', '<#LANG_NE#>', '<#LANG_ENE#>', '<#LANG_E#>', '<#LANG_ESE#>', '<#LANG_SE#>', '<#LANG_SSE#>', '<#LANG_S#>', '<#LANG_SSW#>', '<#LANG_SW#>', '<#LANG_WSW#>', '<#LANG_W#>', '<#LANG_WNW#>', '<#LANG_NW#>', '<#LANG_NNW#>', '<#LANG_N#>'];
      $direction = $windDirection[round($degree / 22.5)];
      
      return $direction;
   }

   public function save_setting()
   {
      global $ow_forecast_interval;
      global $ow_imagecache;
      global $ow_update_interval;
      global $ow_script;
      global $ow_api_key;
	  global $ow_city_id;
      global $ow_city_name;
      global $ow_city_lat;
      global $ow_city_lon;
	  
      if(isset($ow_city_lat))  sg('ow_city.lat', $ow_city_lat);
      if(isset($ow_city_lon))   sg('ow_city.lon', $ow_city_lon);
	
      if(!isset($ow_imagecache)) $ow_imagecache = 'off';
      if(isset($ow_script)) sg('ow_setting.updScript', $ow_script);
      if(isset($ow_api_key)) sg('ow_setting.api_key', $ow_api_key);

      sg('ow_setting.ow_imagecache', $ow_imagecache);
      sg('ow_setting.updatetime',$ow_update_interval);
      sg('ow_setting.forecast_interval', $ow_forecast_interval);
      sg('ow_setting.countTime', 1);
      
      $class = SQLSelectOne("SELECT ID FROM classes WHERE TITLE = 'openweather'");
      if ($class['ID']) 
      {
         SQLExec("DELETE FROM pvalues WHERE object_id IN (SELECT ID FROM objects WHERE CLASS_ID='" . $class['ID'] . "' AND TITLE LIKE 'ow_day%')");
         SQLExec("DELETE FROM properties WHERE object_id IN (SELECT ID FROM objects WHERE CLASS_ID='" . $class['ID'] . "' AND TITLE LIKE 'ow_day%')");
         SQLExec("DELETE FROM objects WHERE CLASS_ID='" . $class['ID'] . "' AND TITLE LIKE 'ow_day%'");
         
         for ($i = 0; $i < $ow_forecast_interval; $i++)
         {
            $obj_rec = array();
            $obj_rec['CLASS_ID'] = $class['ID'];
            $obj_rec['TITLE'] = "ow_day" . $i;
            $obj_rec['DESCRIPTION'] = "Forecast on " . $i+1 . " day(s)";
            $obj_rec['ID'] = SQLInsert('objects', $obj_rec);
         }
      }
   }

   public function get_setting(&$out)
   {
      $out["ow_city_name"] = gg('ow_city.name');
	  $out["ow_city_id"] = gg('ow_city.id');
	  $out["ow_city_lat"] = gg('ow_city.lat');
	  $out["ow_city_lon"] = gg('ow_city.lon');
      $out["ow_imagecache"] = gg('ow_setting.ow_imagecache');
      $out["updatetime"] = gg('ow_setting.updatetime');
      $out["script"] = gg('ow_setting.updScript');
      $out["forecast_interval"] = gg('ow_setting.forecast_interval');
      $out["ow_api_key"] = gg('ow_setting.api_key');	  
   }


   
   /**
    * Get Weather data from openweathermap.org by city id
    * @param mixed $cityID City ID
    * @param mixed $unit   Unit(metric/imperial)
    * @return mixed
    */
   protected static function GetJsonWeatherDataByCityID($cityID, $unit = "metric")
   {
      if (!isset($cityID)) return null;

      $apiKey = gg('ow_setting.api_key');
      
      $unit = app_openweather::GetUnits($unit);
      $query  = "http://api.openweathermap.org/data/2.5/forecast/daily?id=" . $cityID . "&mode=json&units=" . $unit . "&cnt=16&lang=ru" . "&appid=" . $apiKey;
      
      $data =  getURL($query);
      
      $data   = json_decode($data);
      return $data;
   }

   /**
    * Get current weather data
    * @param mixed $cityID City ID
    * @param mixed $unit   Weather Unit (metric/imperial)
    * @return mixed
    */
   private static function GetCurrentWeatherDataByCityID($cityID, $unit = "metric")
   {
      if (!isset($cityID))
         return null;
      
      $apiKey = gg('ow_setting.api_key');
      
      $unit = app_openweather::GetUnits($unit);
      $query  = "http://api.openweathermap.org/data/2.5/weather?id=" . $cityID . "&mode=json&units=" . $unit . "&lang=ru" . "&appid=" . $apiKey;
      
      $data =  getURL($query);
      
      $data   = json_decode($data);
      return $data;
   }
   
   /**
    * Convert Pressure from one system to another. 
    * If error or system not found then function return current pressure.
    * @param $vPressure 
    * @param $vFrom
    * @param $vTo
    * @param $vPrecision
    * @return
    */
   public static function ConvertPressure($pressure, $from, $to, $precision = 2)
   {
      if (empty($from) || empty($to) || empty($pressure))
         return $pressure;
      
      if (!is_numeric($pressure))
         return $pressure;
      
      $pressure = (float) $pressure;
      $from     = strtolower($from);
      $to       = strtolower($to);
      
      if ($from == "hpa" && $to == "mmhg")
         return round($pressure * 0.75006375541921, $precision);
      
      if ($from == "mmhg" && $to == "hpa")
         return round($pressure * 1.33322, $precision);
      
      return $pressure;
   }
   
   /**
    * Check units for weather. If unit unknown or incorrect then units = metric
    * @param $vUnits
    * @return
    */
   private static function GetUnits($unit)
   {
      $units = "metric";
      
      if (!isset($unit)) return $units;
      
      if ($unit === "imperial")
         return $unit;
      
      return $units;
   }
   
   private static function GetCurrTemp($temp)
   {
      $time = date("H");
      
      if ($time >= 4 && $time < 13)
      {
         return $temp->morn;
      }
      else if ($time >= 12 && $time < 18) 
      {
         return $temp->day;
      }
      else if($time >= 18 && $time < 24) 
      {
         return $temp->eve;
      }
      else
      {
         return $temp->night;
      }
   }
   
   /**
    * Get sun info by coords and timestamp
    * 
    * sunrise                     - Время восхода солнца
    * sunset                      - Время заката
    * transit                     - Время прохождения планеты через меридиан
    * civil_twilight_begin        - Время начала гражданских сумерек
    * civil_twilight_end          - Время конца гражданских сумерек
    * nautical_twilight_begin     - Время начала навигационных сумерек
    * nautical_twilight_end       - Время конца навигационных сумерек
    * astronomical_twilight_begin - Время начала астрономических сумерек
    * astronomical_twilight_end   - Время конца астрономических сумерек
    * 
    * @param mixed $cityLat GeoCoord Latitude
    * @param mixed $cityLong GeoCoord Longitude
    * @param mixed $timeStamp TimeStamp. 
    * @return array|bool
    */
   private function GetSunInfoByGeoCoord($cityLat, $cityLong, $timeStamp = -1)
   {
      if($timeStamp == '' or $timeStamp == -1)
         $timeStamp = time(); 
      
      if(empty($cityLat) || empty($cityLong))
      {
         DebMes("CityCoords not found");
         return FALSE;
      }
      $info = date_sun_info($timeStamp, $cityLat, $cityLong);
	  
      return $info;
   }
   
   /**
    * Get sun info by cityID on date
    * @param mixed $cityID    CityID
    * @param mixed $timeStamp TimeStamp
    * @return array|bool
    */
   public function GetSunInfoByCityID($cityID, $timeStamp = -1)
   {
	  $cityLat=gg('ow_city.lat');
	  $cityLong=gg('ow_city.lon');
      if($timeStamp == '' or $timeStamp == -1)
         $timeStamp = time();
      
	  if (!isset($cityLat) || !isset($cityLong))
         return FALSE;
      
      $info = $this->GetSunInfoByGeoCoord($cityLat, $cityLong, $timeStamp);
      return $info;
   }

   /**
    * Get possibility freeze by evening and day temperature
    * @param mixed $tempDay      Temperature at 13:00
    * @param mixed $tempEvening  Termerature at 21:00
    * @return double|int         Freeze possibility %
    */
   public function GetFreezePossibility($tempDay, $tempEvening)
   {
      // Температура растет или Температура ниже нуля
      if ( $tempEvening >= $tempDay || $tempEvening < 0)
         return -1;

      $tempDelta = $tempDay - $tempEvening;

      if ( $tempEvening < 11 && $tempDelta < 11 )
      {
         $t_graph = array(0 => array(0.375, 11, 0),
                          1 => array(0.391, 8.7, 10),
                          2 => array(0.382, 6.7, 20),
                          3 => array(0.382, 4.7, 40),
                          4 => array(0.391, 2.7, 60),
                          5 => array(0.4, 1.6, 80));

         $graphCount = count($t_graph);

         for ($i = 0; $i < $graphCount; $i++)
         {
            $y1 = $t_graph[$i][0] * $tempDelta + $t_graph[$i][1];
            
            if ( $tempEvening > $y1)
            {
               return (int)$t_graph[$i][2];
            }
         }

         return 100;
      }
      
      return -1;
   }
   
   /**
    * Install
    * Module installation routine
    * @access private
    */
   public function install($parent_name = '')
   {
	  subscribeToEvent($this->name, 'HOURLY');
      $className = 'openweather';
      $objectName = array('ow_city', 'ow_setting', 'ow_fact', 'ow_day0', 'ow_day1', 'ow_day2');
      $objDescription = array('Местоположение', 'Настройки', 'Текущая температура', 'Прогноз погоды на день', 'Прогноз погоды на завтра', 'Прогноз погоды на послезавтра');

      $rec = SQLSelectOne("SELECT ID FROM classes WHERE TITLE LIKE '" . DBSafe($className) . "'");
      
      if (!$rec['ID'])
      {
         $rec = array();
         $rec['TITLE'] = $className;
         $rec['DESCRIPTION'] = 'Погода Open Weather Map';
         $rec['ID'] = SQLInsert('classes', $rec);
      }
      
      for ($i = 0; $i < count($objectName); $i++)
      {
         $obj_rec = SQLSelectOne("SELECT ID FROM objects WHERE CLASS_ID='" . $rec['ID'] . "' AND TITLE LIKE '" . DBSafe($objectName[$i]) . "'");
         
         if (!$obj_rec['ID'])
         {
            $obj_rec = array();
            $obj_rec['CLASS_ID'] = $rec['ID'];
            $obj_rec['TITLE'] = $objectName[$i];
            $obj_rec['DESCRIPTION'] = $objDescription[$i];
            $obj_rec['ID'] = SQLInsert('objects', $obj_rec);
         }
      }
      parent::install();
      // --------------------------------------------------------------------
   }
   
   public function uninstall()
   {
	  unsubscribeFromEvent($this->name, 'HOURLY');
      SQLExec("delete from pvalues where property_id in (select id FROM properties where object_id in (select id from objects where class_id = (select id from classes where title = 'openweather')))");
      SQLExec("delete from properties where object_id in (select id from objects where class_id = (select id from classes where title = 'openweather'))");
      SQLExec("delete from objects where class_id = (select id from classes where title = 'openweather')");
      SQLExec("delete from classes where title = 'openweather'");
      SQLExec('drop table if exists OPENWEATHER_CITY');
      SQLExec('drop table if exists COUNTRY');
      parent::uninstall();
   }
}
?>
