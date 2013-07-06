<?php
################################################################################
##////////////////////////////////////////////////////////////////////////////##
##////////////////////////////////TWITTER BOT/////////////////////////////////##
##////////////////////////////////////////////////////////////////////////////##
##//AUTHOR: M.GOLDENBAUM - WEBKLEX.COM                                      //##
##//LAST UPDATE: 23.06.2013                                                 //##
##//VERSION: 1.0.28                                                         //##
##////////////////////////////////////////////////////////////////////////////##
################################################################################

//VERRINGERT DIE WAHRSCHEINLICHKEIT DAS DAS SCRIPT BEIM AUSFÜHREN ABSTÜRZT (BEI GROßEN MENGEN)
set_time_limit(1200000);

//DIESE WERTE MÜSSEN ZUNÄCHST UNTER dev.twitter.com ANGELEGT WERDEN.
//MELDEN SIE SICH HIERZU DORT AN UND ERSTELLEN SIE ALLE NÖTIGEN KEYS
define('CONSUMER_KEY',          '');
define('CONSUMER_SECRET',       '');
define('ACCESS_TOKEN',          '');
define('ACCESS_TOKEN_SECRET',   '');

define('MYSQL_HOST',    ''); //DATENBANK HOST
define('MYSQL_USER',    ''); //DATENBANK BENUTZERNAME
define('MYSQL_PASSWD',  ''); //DATENBANK PASSWORT ASSISIERT MIT DEM BENUTZER
define('MYSQL_DB',      ''); //DATENBANKNAME

//SETZT DEN CONTENT AUF UTF-8 UM CODIERUNGSFEHLER AUSZUSCHLIEßEN
header('Content-Type: text/html; charset=utf-8');

//BINDET ALLE BENÖTIGTEN DATEIN EIN
include 'class.php';
require_once 'twitter.class.php';

//INITIALISIERT TWITTER KLASSE
$twitter = new Twitter(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

//VERBINDUNG MIT DER DATENBANK WIRD HERGESTELL
$db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWD, MYSQL_DB);
$db->set_charset('utf8');

//DATEN WERDEN VON DER QUELLE ABGEGRIFFEN
$scrape = new Scrape();
$scrape->fetch('http://feeds.n24.de/n24/homepage');
$result  = $scrape->result;

//ZEILENUMBRÜCHE ETC ENFERNT
$output = str_replace(array("\r\n", "\r"), "\n", $result);
$lines = explode("\n", $output);
$new_lines = array();

foreach ($lines as $i => $line) {
    if(!empty($line))
        $new_lines[] = trim($line);
}
 $result = implode($new_lines);
//ITEMS WERDEN RAUSGELÖST
preg_match_all('/(?:<item>)(.*?)(?=<\/item>)/m',$result, $item);
foreach ($item[0] as $key => $i){
    //TITEL WUIRD EXTRAHIERT
    preg_match_all('/(?:<title>)(.*?)(?=<\/title>)/m',str_replace(':', '', $i), $title, PREG_SET_ORDER);
    //URL WIRD EXTRAHIERT
    preg_match_all('/(?:<link>)(.*?)(?=<\/link>)/m',$i, $link, PREG_SET_ORDER);
    
    //PRIORISIERTE BEGRIFFE
    $preffer_words = array('Sale','Neu','Sortiment');
    //ZU IGNORIERENDE BEGRIFFE
    $ignore_words = array('', ' ', '#', ':', 'Die', 'Der', '+++', 'Wer', 'ab', '-', 'Wird', 'Immer', 'In', 'in', 'im', 'Im',1,2,3,4,5,6,7,8,9,0);
    
    //HOOLT ERSTE KATEGRIE
    $cat1 = array_filter(explode(' ', str_replace('"','',str_replace(':','',$title[0][1]))));
    
    //KATEGRRIE WIRD GEFILTERT
    $i = 0;
    foreach($cat1 as $key => $val){
        if(in_array($cat1[$key],$ignore_words)){
            $cat1[$key] = '';
        }
    }
    $cat1 = array_filter($cat1);
    $a = 0;
    while(!in_array($cat1[$a], $preffer_words)){
        if($a >= count($cat1)){
            $a++;
            break;
        }
        $a++;
    }
    
    if(!empty($cat1[$a])){
        $cat1[0] = $cat1[$a];
    }
    
    //ZWEITE KATEGORIE WIRD GEHOLT
    if(preg_match('/-/',$title[0][1])){
        $cat2 = preg_split('/-/', str_replace('"','',str_replace(':','',$$title[0][1])));
        $cat2 = explode(' ', $cat2[1]);
    }else{
        $cat2[0] = 'News';
    }
    
    //ZWEITE KATEGRIE WIRD GEFILTERT
    $i = 0;
    while($i <= count($cat2)){
        if(!isset($cat2[$i])){
            break;
        }
        if(in_array($cat2[$i], $ignore_words)){
            $cat2[$i] = '';
        }
        $i++;
    }
    $cat2 = array_filter($cat2);
    $a = 0;
    while(!in_array($cat2[$a], $preffer_words)){
        if($a >= count($cat2)){
            $a++;
            break;
        }
        $a++;
    }
    
    if(!empty($cat2[$a])){
        $cat2[0] = $cat2[$a];
    }
    
    //KATEGORIEN WERDEN GEFILTERT
    $cat1 = array_filter($cat1);
    $cat2 = array_filter($cat2);
    for($i = 0; count($cat1) >= $i; $i++){
        if($i != 0){
            $cat2[] = $cat1[$i];
        }
    }
    
    //LINK WIRD GEKÜRZT
    $scrape->fetch('http://api.adf.ly/api.php?key=9f7a757edc77cda2a8394aa6088c5c1a&uid=4299476&advert_type=int&domain=adf.ly&url='.$link[0][1]);
    $link  = $scrape->result;
    
    //TWEET WIRD ZUSAMMENGESTELLT
    //90 + 3
    if(strlen($title[0][1]) > 92){
        $title[0][1] = substr($title[0][1], 0, 90).'..';
    }
    $t[$key]['title'] = $title[0][1];
    
    //21
    $t[$key]['link'] = $link;
    
    //5
    $t[$key]['author'] = '#N24';
    
    //KATEGORIE WIRD ERSTELLT
    //21
    if(preg_match('/-/',$cat1[0])){
        $cat1[0] = str_replace('-', ' #', $cat1[0]);
    }
    if(strpos($cat1[0],$cat2[0]) === true){
        $t[$key]['category'] = '#'.$cat1[0];
    }else{
        $t[$key]['category'] = '#'.$cat1[0].' #'.$cat2[0];
    }
    $length = strlen($t[$key]['title'].' '.$t[$key]['link'].'  '.$t[$key]['author']);
    if(strlen($t[$key]['category']) > (140 - $length)){
        if(strlen('#'.$cat1[0]) <= (140 - $length)){
            $t[$key]['category'] = '#'.$cat1[0];
        }elseif(strlen('#'.$cat2[0]) <= (140 - $length)){
            $t[$key]['category'] = '#'.$cat2[0];
        }else{
            $t[$key]['category'] = '#News';
        }
    }
    
    $t[$key]['category'] = implode(' #',array_filter(explode('#',trim($t[$key]['category']))));
   //<=140
    $t[$key]['report'] = $t[$key]['title'].' '.$t[$key]['link'].' '.$t[$key]['category'].' '.$t[$key]['author'];
    
    $t[$key]['lenth'] = strlen($t[$key]['report']);
    
    //DOPPELPOST WIRD AUSGESCHLOSSEN
    $sql = 'SELECT title FROM news_bot WHERE title = "'.$db->real_escape_string($t[$key]['title']).'"';
    if($db->query($sql)->num_rows == 0){
        //TWEET WIRD VERÖFENTLICHT
        $status = $twitter->send($t[$key]['report']);
        $t[$key]['posted'] = $status ? 1 : 0;
        
        //TWEET WIRD IN DER DATENBANK ABGELEGT
        $sql = 'INSERT INTO news_bot(
                    title, link, category, author, report, lenth, hash, date_created, posted
                )VALUES(
                    "'.$db->real_escape_string($t[$key]['title']).'",
                    "'.$db->real_escape_string($t[$key]['link']).'",
                    "'.$db->real_escape_string($t[$key]['category']).'",
                    "'.$db->real_escape_string($t[$key]['author']).'",
                    "'.$db->real_escape_string($t[$key]['report']).'",
                    '.$t[$key]['lenth'].',
                    "'.$db->real_escape_string(sha1($t[$key]['report'].$t[$key]['lenth'])).'",
                    '.time().',
                    '.$t[$key]['posted'].'
                )';
        //echo $sql;
        if($t[$key]['posted'] == 1){
            $db->query($sql);
        }
        //REPORT WIRD AUSGEGEBEN
        echo $t[$key]['report'].' POSTED['.($status ? 'JA' : 'NEIN').'] '."\t\r\n";
    }
}
///////////////////////////////////////////////////////
///////////////////////////////////////////////////////

?>