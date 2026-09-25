<?php
include "../../helper.php";

$filename = $_FILES['file']['name'];
$filesize = $_FILES['file']['size'];

$location = "../../tmp/".$filename;
if(move_uploaded_file($_FILES['file']['tmp_name'],$location)){

if (strpos($filename,"vcf")) {//vcard
  $location = "../../tmp/".$filename;
  $vcard_path="./tmp/".$filename;
  include "../../vcard_ajax.php";
}  
  exit;
}
else 
{
/* Location */
$id = (int) $_GET["firm_id"];
if (!$id) {echo "error id";exit;}
if (!setPhotosDir("../../photos/$id/")) {echo "error";exit;}
$location = "../../photos/$id/".$filename;

$return_arr = array();

/* Upload file */
if(move_uploaded_file($_FILES['file']['tmp_name'],$location)){
    $src = "default.png";

    // checking file is image or not
    if(is_array(getimagesize($location))){
        $src = $location;
    } else {die("error upload");}
    $return_arr = array("name" => $filename,"size" => $filesize, "src"=> $src);
}

echo json_encode($return_arr);

}
