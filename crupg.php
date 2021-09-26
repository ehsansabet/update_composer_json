#!/usr/bin/php -q
<?php
if(!defined("STDIN")) {
    define("STDIN", fopen('php://stdin','r'));
}
welcome();

$file = __DIR__ . "/composer.json";
if(!file_exists($file)){
    showError("Not found {$file} file here!");
}
$composerFile = file_get_contents($file);
if(empty($composerFile)){
    showError("Composer file is invalid!");
}
$composer = json_decode($composerFile, true);
if(!empty(json_last_error())){
    showError("Composer json content is invalid!");
}
$dir = explode("/", __DIR__);
$vn = (string) get_current_user() . "/" . end($dir);
if(empty($composer['name'])){
    do{
        echo "\nPackage name (<vendor>/<name>)\e[1;33m[{$vn}]\e[0m: ";
        $name = fread(STDIN, 140);
        if(empty(trim($name))){
            $name = $vn;
        }
        list($ok, $name) = ValidateName($name);
        if(!$ok){
            showError($name, false);
        }
    }while(!$ok);
    $composer['name'] = $name;
}
if(empty($composer['description'])){
    echo "Description []: ";
    $desc = fread(STDIN, 300); 
    if(empty(trim($desc))){
        $desc = "This is {$composer['name']} project";
    }
    $composer['description'] = $desc;
}
echo "\n";
$clear = [
    ".git", ".php", "-"
];
$length = count($composer['repositories']);
$i = 1;
foreach($composer['repositories'] as &$repo){
    if(empty($repo['package']['source']['url'])){
        continue;
    }
    $u = explode("/", $repo['package']['source']['url']);
    $n = end($u);
    $n = prev($u) . "/" . $n;
    $n = str_replace($clear, "", $n);
    if(strrpos($n, ":")){
        $n = substr($n, ( strrpos($n, ":") +1 ), (strlen($n) - strrpos($n, ":") + 1));
    }
    if(!empty($composer['require'][$repo['package']['name']])){
        echo "\t\e[0;37m{$repo['package']['name']} \e[0m\e[0;32m => \e[0m";
        $oldVal = $composer['require'][$repo['package']['name']];
        unset($composer['require'][$repo['package']['name']]);
        $repo['package']['name'] = strtolower($n);
        echo "{$repo['package']['name']}\n";
        $composer['require'][strtolower($repo['package']['name'])] =$oldVal;
    }
}
ksort($composer);
$data = stripslashes(json_encode($composer,JSON_PRETTY_PRINT));
echo PHP_EOL . $data . PHP_EOL;
echo "Do you confirm upgrading [ \e[1;37myes\e[0m ]?";
$confirm = fread(STDIN, 3);
if(!in_array(trim($confirm), ["", "yes", "YES", "Y", "Yes"])){
    echo "\e[1;33mCanceled!\e[0m";
    exit;
}
$myFile = fopen($file, "w") or die("Unable to open file!");
fwrite($myFile, $data);
fclose($myFile);
echo "\n\e[1;37;42mDone is done!\e[0m\n";

function welcome(){
    echo    "\n\e[0;35;45m  Upgrade composer file from version 1 to 2!  \e[0m\n" .
    "\e[1;37;45m  Upgrade composer file from version 1 to 2!  \e[0m\n" .
    "\e[0;35;45m  Upgrade composer file from version 1 to 2!  \e[0m\n\n";

    echo "This command will guide you through upgrade your composer.json config.\n";
}

function showError(string $err, $exit = true){
    echo "\n\e[1;37;41mError: {$err}\e[0m\n";
    if($exit){
        exit;
    }
}

function ValidateName($name){
    $name = strtolower($name);
    $errMsg = "The package name $name is invalid, it should be lowercase and have a vendor name, a forward slash, and a package name, matching: [a-z0-9_.-]+/[a-z0-9_.-]+";
    if(!preg_match('/^[a-z0-9]([_.-]?[a-z0-9]+)*\/[a-z0-9](([_.]?|-{0,2})[a-z0-9]+)*$/m', $name)){
        return [false, $errMsg];
    }
    return [true, $name];
}