<?php
function htmlClean($str)  {

$str= str_replace("<", "&lt;",$str);

$str= str_replace(">", "&gt;",$str);

return $str= str_replace("<", "&lt;",$str);
}
function htmlCleanRow($row) {//čistka string od <>

if (is_array($row)) {


foreach ($row as $key=>$value) {

$row[$key]=htmlClean($value);
	
}
return $row;
}

else {
return htmlClean($row);

}

}

function phoneClear($value) {
if ($value==null) return $value;
$value = str_replace("+420", "", $value);
            $value = str_replace("+", "", $value);
            $value = str_replace(" ", "", $value);
            $value = str_replace("\u00a0","",$value);
            $value = str_replace("\xC2\xA0", "", $value);
            $value = str_replace(" ","",$value);
            $value = str_replace("\u00a0","",$value);
            $value = str_replace("\u00a0","",$value);
            $value = str_replace("\xC2\xA0", "", $value);
            $value = str_replace("00420","",$value);
            $value = str_replace("+420","",$value);
            $value = preg_replace('/\s+/', '', $value);
            $value = preg_replace('/\D/', '', $value);
return $value;            
}

function phoneFormat($number) {
  if ($number==null) return $number;
  $number = phoneClear($number);
  return preg_replace('/(\d{3})(\d{3})(\d{3})/', '$1 $2 $3', $number);
}


function setPhotosDir($directory) {

if (is_dir($directory)) return 1;
return mkdir ($directory);
} 


function getFirmPhotos($id) {

  $a = array();  

  $directory = "./photos/$id";
  
  if (is_dir($directory) ) {
  
    $directory = "./photos/$id/";
    $images = glob($directory . "/*.jpg");
    
    foreach($images as $image)
    {
      array_push($a,$directory.$image);
    }

    $images = glob($directory . "/*.png");
    
    foreach($images as $image)
    {
      array_push($a,$image);
    }

    
}

return $a;
}

function convertDatetoCzech($date) {
  if ($date==null) return "";
  //if ($date=="null") return "";
  $pattern = "/(\d{4})-(\d{2})-(\d{2})/";

  if (preg_match($pattern, $date, $matches)) {
      // Convert to Czech date format DD.MM.YYYY
      $czech_date = $matches[3] . '.' . $matches[2] . '.' . $matches[1];
      return  $czech_date; // Outputs: 22.09.2024
  } else {
          return $date;
  }


  }

  function convertDateTimetoCzech($date) {
    if ($date==null) return "";
    // Pattern to match date and time format YYYY-MM-DD HH:MM:SS
  $pattern = "/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/";

  if (preg_match($pattern, $date, $matches)) {
    // Convert to Czech date format DD.MM.YYYY HH:MM:SS
    $czech_date = $matches[3] . '.' . $matches[2] . '.' . $matches[1] . ' ' . $matches[4] . ':' . $matches[5] . ':' . $matches[6];
    return $czech_date; // Outputs: 22.09.2024 14:32:00
    } else {
            return $date;
    }


    }
?>
