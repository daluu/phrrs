<?php
//a mashup of code snippets from http://php.net/manual/en/function.date-default-timezone-set.php
namespace PhpRobotRemoteServer;

class TimezoneManager{
    public function setTimezone($default) {
        $timezone = "";

        // On many systems (Mac, for instance) "/etc/localtime" is a symlink
        // to the file with the timezone info
        if (is_link("/etc/localtime")) {

            // If it is, that file's name is actually the "Olsen" format timezone
            $filename = readlink("/etc/localtime");

            $pos = strpos($filename, "zoneinfo");
            if ($pos) {
                // When it is, it's in the "/usr/share/zoneinfo/" folder
                $timezone = substr($filename, $pos + strlen("zoneinfo/"));
            } else {
                // If not, bail
                $timezone = $default;
            }
        }
        else if(file_exists("/etc/timezone")){
            // On other systems, like Ubuntu, there's file with the Olsen time
            // right inside it.
            $timezone = file_get_contents("/etc/timezone");
            if (!strlen($timezone)) {
                $timezone = $default;
            }
        }
        else {
            /* not sure if most Windows PHP installs can support this code below, or if need install Windows dependencies first
            try{
                $shell = new COM("WScript.Shell") or die("Requires Windows Scripting Host");
                $time_bias = -($shell->RegRead("HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control\\TimeZoneInformation\\Bias"))/60;
                $ab = -($shell->RegRead("HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control\\TimeZoneInformation\\ActiveTimeBias"))/60;
                $sc = $shell->RegRead("HKEY_USERS\\.DEFAULT\\Control Panel\\International\\sCountry");
                foreach(timezone_abbreviations_list() as $tza) foreach($tza as $entry){
                    $country = strtok($entry['timezone_id'],'/');
                    $locale = strtok('|');
                    if($country==$sc && $ab==($entry['offset']/60/60) && ($ds = $time_bias!=$ab)==$entry['dst']){
                        date_default_timezone_set($timezone_identifier = $country."/".$locale);
                        return sprintf('%.1f',$ab)."/".($ds?'':'no ').'DST'." ".$timezone_identifier;
                    }
                }
                $timezone = $default;
            }catch (Exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
                $timezone = $default;
            }
            */
            $timezone = $default;
        }
        date_default_timezone_set($timezone);
        return $timezone;
    }
}
?>
