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

//BINDET ALLE BENÖTIGTEN DATEIN EIN
require_once 'twitteroauth.php';

//DIESE WERTE MÜSSEN ZUNÄCHST UNTER dev.twitter.com ANGELEGT WERDEN.
//MELDEN SIE SICH HIERZU DORT AN UND ERSTELLEN SIE ALLE NÖTIGEN KEYS
define('CONSUMER_KEY',          '');
define('CONSUMER_SECRET',       '');
define('ACCESS_TOKEN',          '');
define('ACCESS_TOKEN_SECRET',   '');

ob_start();

//VERRINGERT DIE WAHRSCHEINLICHKEIT DAS DAS SCRIPT BEIM AUSFÜHREN ABSTÜRZT (BEI GROßEN MENGEN)
set_time_limit(0);

function autoFollow($action){
    //auth with twitter.
    $toa = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

    //get the last 5000 followers
    $followers = $toa->get('followers/ids', array('cursor' => -1));
    $followerIds = array();

    foreach ($followers->ids as $i => $id) {
        $followerIds[] = $id;
    }

    //get the last 5000 people you've followed
    $friends = $toa->get('friends/ids', array('cursor' => -1));
    $friendIds = array();
    foreach ($friends->ids as $i => $id) {
        $friendIds[] = $id;
    }


    if($action=="unfollow"){
        //unfollow all users that aren't following back.
        $usersNotFollowingBackcount = 0;
        $usersNotFollowingBack = array();

        foreach($friendIds as $id){ 
            if(!in_array($id,$followerIds) ){
                array_push($usersNotFollowingBack, $id); 
                //unfollow the user
                $toa->post('friendships/destroy', array('id' => $id));
                $usersNotFollowingBackcount++;
                ob_flush();
                flush();
            }
        } 

        echo sizeof($usersNotFollowingBack).' Benutzer wurden "entfolgt".';
    }
    if($action =="follow"){                 
        //follow all users that you're not following back.
        $usersYoureNotFollowingBackcount = 0;
        $usersYoureNotFollowingBack = array();

        foreach($followerIds as $id){ 
            if(!in_array($id,$friendIds) ){
                array_push($usersYoureNotFollowingBack, $id); 
                //follow the user
                $toa->post('friendships/create', array('id' => $id));
                $usersYoureNotFollowingBackcount++;
                ob_flush();
                flush();
            }
        } 

        echo sizeof($usersYoureNotFollowingBack).' Benutzern folgen Sie nun.';
    }
}

//UM FOLLOWERN AUTOMATISCH ZU FOGEN - GESETZT LASSEN
autoFollow('follow');

//UM NICHT FOLGENDEN TWIITER ACCOUNTS ZU ENTFOLGEN - GESETZT LASSEN
autoFollow('unfollow');


ob_end_flush();
?>
