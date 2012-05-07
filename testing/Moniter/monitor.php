<?php
//Loading config
include('monitor.cfg.php');

//Default Sort. Can be 'deaths', 'score', 'kills', or 'ping'
    $sort = 'score';

    //Page Refresh Rate in Seconds
    $refreshrate = '600';


function monitor ($server,$port) 
{
    GLOBAL $sort, $refreshrate, $GAMESERVER, $GAMEPORT, $ROOT;

    //URL to Images folder. No trailing slash.
    $images = $ROOT;

    //Border Color of the Stats
    $bordercolor = '#000000';

    //Body Color of inside the stats table
    $bodycolor = '#5C5F52';

    //Top and Bottom Cell Colors
    $topbottomcell = '#383C33';

    //Page Background Color
    $backgroundcolor = '#3C3A36';

    //Server Admin Name, will show as red on the playerlist, or Use serveradmintag to highlight
    //anyone with that tag, must escape regexp chars ([,.{( and so on)
    $serveradmin = 'Admin';
    $serveradmintag = '[MXC]';

    //Font Color
    $fontcolor = '#FFFFFF';

    //Number of Ping to be know as a Lagger, Anything above this number will be marked
    $lagger = '120';

    //Your Webpage/Serverpage URL

    $weburl = 'http://bf.mxc.ru';

    //Insert Image URL if using your own Logo in the stats, or leave blank if not. Do not make your image wider than 800
    //pixels or it will make the table look funny :)
    $titlepic = '';

    //If Using your own logo, specify alignment (left, center, right)
    $align = 'left';

    //If Using your own logo, specify color behind your Logo
    $titlepicbg = '#BAB3A1';

    //Colors for Laggers, Admin, and Teamkiller
    $laggercolor = '#999999';
    $admincolor = '#CC6600';
    $teamkillercolor = '#E4FF4D';

    //Colors for Player Count if Server is Full or if Server has an Open Slot
    $servernotfullcolor = 'yellow';
    $serverfullcolor = '#FB0A04';

    //Comments that you may want to add on your Live View for people to read.
    //Will show up under the Rules Section
    //Set $usecomments to 1 for On and 0 for Off
    $usecomments = '0';
    $comments = 'Comments that you may want to add on your Live View for people to read';

    //Mark 1 to display your Community Logo, and 1 to display your Sponsor Logo
    $community_logo = '1';
    $sponsor_logo = '1';

    //Message to display when server times out or is down
    $timeoutmessage = 'Сервер не работает или идет смена карты';

    if (!isset($_GET['sort']))
    {
        $sort=$sort;
    } else {

        $sort=$_GET['sort'];
    }


    if (!isset($_GET['ip'])) {
        $server=$server;
    } else {
        $server=$_GET['ip'];
    }

    if (!isset($_GET['port'])) {
        $port=$port;
    } else {
        $port=$_GET['port'];
    }

    $sock = @fsockopen("udp://".$server, $port);
    @socket_set_timeout($sock, 0, 800000);

    $queryString = "\xFE\xFD\x00\x10\x20\x30\x40\xFF\xFF\xFF\x01";
    @fwrite($sock, $queryString);


    $end=false;
    $i=1;
    $Display = array(1 => '', 2=> '', 3 => '');
    while(!$end) 
    {
        $bytes = @fread($sock, 1);
        $status = @socket_get_status($sock);
        $length = $status['unread_bytes'];

        if($length>0) 
        {
            $Info[$i] = $bytes.fread($sock, $length);

            preg_match("/splitnum(...)/is",$Info[$i],$regs);
            $String = $regs[1];

            $num = ord(substr($String,1,1));

            if($num == 128 || $num == 0) {
                $Display[1] = $Info[$i];
            }

            if ($num == 129 || $num == 1) {
                $Display[2] = $Info[$i];
            }

            if ($num == 130) {
                $Display[3] = $Info[$i];
            }
        }

        if($length==0) 
        {
            $end=true;
        }
        $i++;

    }

    @fclose($sock);


    $Info = $Display[1].$Display[2].$Display[3];
    if(!$Info) { ?>
        <br /><br /><br /><table border="1" cellpadding="1" cellspacing="0" style="border-collapse: collapse" width="400"  bordercolor="<?=$bordercolor?>">
        <tr><td bgcolor="<?=$bodycolor?>" height="25" valign="middle" align="center"><span class=s><b><?=$timeoutmessage?></b></span></td></tr>
        </table>
    <?
    } 
    else 
    {
        $output = str_replace("\\","",$Info);
        $changeChr = chr(0);
        $output = str_replace($changeChr, "\\", $output);
        //print $output;
        $rules = "x".substr($output,0,strpos($output,"\\\\".chr(1)));
        $players = "\\".substr($output,strpos($output,"\\\\".chr(1))+3);

        $p3 = strpos($players,"\\\\".chr(2));

        if(!$p3) {
            $p3 = strpos($players,"\\\\team_t");
        }
        if(!$p3) {
            $p3 = strpos($players,"\Ѓteam_t");
        }


        $players = substr($players,0,$p3);

        $players = str_replace("\\ 0@splitnum\Ѓ","",$players);
        $players = str_replace("\\ 0@splitnum\\Ѓ","",$players);
                                //\\ 0@splitnum\Ѓteam_t
        $players = str_replace(" 0@splitnum\\","",$players);
        $players = str_replace(" 0@splitnum\\‚","",$players);


        //Parse Rules
        $rule_temp = substr($rules,1);
        $rule_temp = str_replace("Ђ","\\",$rule_temp);
        $rules_arr = explode("\\",$rule_temp);
        $rules_count = count($rules_arr);

        for($i=0;$i<$rules_count/2;$i++) 
        {
            $r1[$i] = $rules_arr[$i*2];
            $r2[$i] = $rules_arr[($i*2)+1];
            $rule[$r1[$i]] = $r2[$i];

        }


        $tags = explode("\\",$players);


        $index = 0;
        $currentProp = "";
        $newIndexFlag = false;
        $propCount = 0;

        for($i = 0; $i < count($tags) -1; $i++) 
        {
            if($tags[$i] == "" && substr($tags[$i+1],strlen($tags[$i+1])-1,1) == "_" && $tags[$i+1]!=$currentProp && ord($tags[$i+2])==0) {

                $currentProp = $tags[$i+1];
                $index = 0;
                $prop[$propCount] = $currentProp;
                $propCount++;
            } 
            else 
            {

                if($tags[$i] == $currentProp && ord($tags[$i+1]) != 0) 
                {
                    $index = ord($tags[$i+1]);
                    $newIndexFlag = true;

                } 
                else 
                {
                    if($tags[$i]!="" && $currentProp!="" && $tags[$i]!=$currentProp) 
                    {
                        $player[$currentProp][$index] = $tags[$i];
                        if($newIndexFlag) 
                        {
                            $player[$currentProp][$index] = substr($tags[$i],1);
                            $newIndexFlag = false;
                        }
                        $index++;
                    }
                }
            }
        }


        for ($p = 0; $p < $rule['numplayers']; $p ++) 
        {
            $data[] = array('name' => $player["player_"][$p], 'score' => $player["score_"][$p], 'deaths' => $player["deaths_"][$p], 'ping' => $player["ping_"][$p], 'team' => $player["team_"][$p], 'kills' => $player["skill_"][$p], 'pid' => $player["pid_"][$p]);
        }

        if($rule['numplayers']) 
        {
            foreach ($data as $key => $row) 
            {
                $name[$key]  = $row['name'];
                $score[$key] = $row['score'];
                $deaths[$key] = $row['deaths'];
                $ping[$key] = $row['ping'];
                $team[$key] = $row['team'];
                $kills[$key] = $row['kills'];
                $pid[$key] = $row['pid'];
            }

            array_multisort($sort, SORT_DESC, $data);
        }

        if($rule['numplayers']==$rule['maxplayers']) 
        {
            $currentplayers = "<font color=".$serverfullcolor." class=nc>".$rule['numplayers']."</font>";
            $maxplayers = "<font color=".$serverfullcolor." class=nc>".$rule['maxplayers']."</font>";

        } 
        elseif ($rule['numplayers'] > 0 && $rule['numplayers']!=$rule['maxplayers']) 
        {
            $currentplayers = "<font color=".$servernotfullcolor." class=nc>".$rule['numplayers']."</font>";
            $maxplayers = "<font color=".$servernotfullcolor." class=nc>".$rule['maxplayers']."</font>";

        } 
        else 
        {
            $currentplayers = "<font class=ab>".$rule['numplayers']."</font>";
            $maxplayers = "<font class=ab>".$rule['maxplayers']."</font>";
        }
        ?>
        <br>
        <table border="1" cellpadding="1" cellspacing="0" style="border-collapse: collapse" width="800"  bordercolor="<?=$bordercolor?>">
        <?if($titlepic)
        { //--> $titlepic ?>
            <tr><td width="100%" bgcolor="<?=$topbottomcell?>" colspan="3" align="center"><b><font class=s2><?if($rule['bf2_sponsortext']) echo $rule['bf2_sponsortext'];?>&nbsp;</b></td></tr>
            <tr><td width="100%" bgcolor="<?=$titlepicbg?>" colspan="3" align="<?=$align?>" valign="bottom">
                <img src="<?=$titlepic?>">
            </td></tr>
        <?
        } //<-- $titlepic ?>


            <tr><td style="padding: 5px" width="100%" bgcolor="<?=$topbottomcell?>" colspan="3"><b><font class=s3>&nbsp;<a title="Обновить" href="monitor.php" target="_self"><?=$rule['hostname']?></a></b></td></tr>
            <tr>
              <td width="34%" align="center" valign="top" bgcolor="<?=$bodycolor?>">
                <table width="100%">

        <tr><td valign="top" bgcolor="<?=$bodycolor?>" align="center">
        <table height=20 border=1 width=99% cellpadding=0 bordercolor="<?=$bordercolor?>" style="border-collapse: collapse" align=center>
        <tr><td bgcolor="#000000" width="100%" valign="middle" style="PADDING-left:4px"><font class=ab>На сервере </font><font class=ab><b><?=$currentplayers?></b><font class=ab> из </font><b><?=$maxplayers?></b><font class=ab> игроков</font></font></td>
        </tr></table>
        </td></tr>
        <?
            $check_image = @GetImageSize("".$images."maps/".strtolower(str_replace(" ","%20",$rule['mapname'])).".jpg");
            if ($check_image) 
            {
        ?>
                <tr><td align="center" valign="top" bgcolor="<?=$bodycolor?>"><IMG title="<?=$rule['mapname']?> на <?=$rule['bf2_mapsize']?> игроков" SRC="<?=$images?>maps/<?=$rule['mapname']?>.jpg" width=260 height=200 border=0></td></tr>
        <?
            } 
            else 
            {
        ?>
                <tr><td align="center" valign="top" bgcolor="<?=$bodycolor?>">
                <IMG SRC="<?=$images?>maps/default.gif" width=260 height=200 border=0>
                </td></tr>
        <?
            }
        ?>

        <tr><td>
        <table border=0 width=99% cellpadding=0 bordercolor="<?=$bordercolor?>" style="border-collapse: collapse" align=center>
        <tr><td bgcolor="#000000" width="100%"><font class=ab><b>
        &nbsp;&nbsp;<?=str_replace("_"," ",$rule['mapname'])?> <?=$rule['bf2_mapsize']?></b></font></td><td align=right bgcolor="#000000"><?if($rule['bf2_ranked']) echo "<img src=".$images."/ranked.gif alt=\"This is a Ranked Server\">&nbsp;";?><?if($rule['bf2_anticheat']) echo "<img src=".$images."/pb.gif alt=\"Работает PunkBuster\">&nbsp;";?><?if($rule['bf2_voip']) echo "<img src=".$images."/voip.gif alt=\"Работает VoIP\">&nbsp;";?></td>
        </tr></table>
        </td></tr>


        <?if($rule['bf2_sponsorlogo_url'] && $sponsor_logo){?>
        <tr><td>
        <table border=1 width=99% cellpadding=0 bordercolor="<?=$bordercolor?>" style="border-collapse: collapse" align=center>
        <tr><td bgcolor=<?=$topbottomcell?>>
        <font class=s>
        <b>Sponsor Logo</b>
        </font>
        </td></tr>
        <tr><td align="center" valign="middle"><a href="javascript:ppUpLogo('<?=$rule['bf2_sponsorlogo_url']?>')"><img src="<?=$rule['bf2_sponsorlogo_url']?>" width="260" height="65" border="0"></a></td></tr>
        </table>
        <br/>
        </td></tr>
        <?}?>


        <?if($rule['bf2_communitylogo_url'] && $community_logo){?>
        <tr><td valign="bottom">
        <table border=1 width=99% cellpadding=0 bordercolor="<?=$bordercolor?>" style="border-collapse: collapse" align=center>
        <tr><td align="center" valign="middle"><a href="javascript:ppUpLogo('<?=$rule['bf2_communitylogo_url']?>')"><img src="<?=$rule['bf2_communitylogo_url']?>" width="260" height="65" border="0"></a></td></tr>
        </table>
        </td></tr>
        <?}?>


        </table>
        </td>
              <td width="36%" rowspan="1" align="left" valign="top" bgcolor="<?=$bodycolor?>">

        <?
        $totalscore_team1=0;
        $totaldeaths_team1=0;
        $totalping_team1=0;
        $totalplayers_team1=0;
        $totalkills_team1=0;
        $totalscore_team2=0;
        $totaldeaths_team2=0;
        $totalping_team2=0;
        $totalplayers_team2=0;
        $totalkills_team2=0;
            
        if (!$rule['numplayers'])
        {
        ?>

            <br><br><br><br><br><br><br><br><br>
            <center><b><span class=s>What?</span></b></center>


        <?
         } 
         else 
         {
            if ($_GET['ip']) 
            {
                $extra= "&ip=".$_GET['ip'];
            }

            if ($_GET['port']) {
                $extra .= "&port=".$_GET['port'];
            }



            $totalplayers_team1= 0;
            foreach ($data as $key => $row) 
            {
                if ($row['team']==1)
                {
                    $totalplayers_team1=($totalplayers_team1+1);
                }
            }


        ?>

            <TABLE width=100% border=0 cellspacing=0 cellpadding=1>
            <TR><TD width='100%' colspan="6" bgcolor="#000000">&nbsp;<img src=<?=$images?><?=strtolower($rule['bf2_team1'])?>.jpg width="17" height="10">&nbsp;<font class=ab><b>(<?=$totalplayers_team1?>) <?=armyname($rule['bf2_team1'])?></b></font></TD></tr>

            <TR><TD width="60%" bgcolor="#847D49" colspan="2"><img src="<?=$images?>players.gif"></TD>
            <TD width="10%" align="center" bgcolor="#847D49"><a href="<?=$_SERVER['PHP_SELF']?>?sort=score<?=$extra?>"><img src="<?=$images?>score.jpg" border="0" alt="Сортировать по счету"></a></TD>
            <TD width="10%" align="center" bgcolor="#847D49"><a href="<?=$_SERVER['PHP_SELF']?>?sort=kills<?=$extra?>"><img src="<?=$images?>kills.jpg" border="0" alt="Сортировать по убийствам"></a></TD>
            <TD width="10%" align="center" bgcolor="#847D49"><a href="<?=$_SERVER['PHP_SELF']?>?sort=deaths<?=$extra?>"><img src="<?=$images?>deaths.jpg" border="0" alt="Сортировать по смертям"></a></TD>
            <TD width="10%" align="center" bgcolor="#847D49"><a href="<?=$_SERVER['PHP_SELF']?>?sort=ping<?=$extra?>"><img src="<?=$images?>ping.jpg" border="0" alt="Сортировать по пингу"></a></TD></TR>

        <?
            

            foreach ($data as $key => $row) 
            {
                if ($row['team']==1)
                {
                    $namecolor = "";
                    if($row['ping'] > $lagger) $namecolor=$laggercolor;
                    if($row['deaths'] < 0 || $row['score'] < 0) $namecolor=$teamkillercolor;
                    if(stristr($row['name'], $serveradmintag)) $namecolor=$admincolor;
                    if($row['name'] == $serveradmin) $namecolor=$admincolor;
                    if(!$namecolor) $namecolor=$fontcolor;

                    echo "<TR><td style=\"padding: 2px\"><a href=\"{$ROOT}?pid=".$row['pid']."\" target=_new><img src=\"".$images."stats.jpg\" width=10 height=12 border=0 alt=\"Посмотреть подробную статистику игрока\"></a></td><TD width=70% bgcolor=".$bodycolor."><font class=nc color=".$namecolor.">".htmlentities($row['name'])."</font></TD><TD  width=10% align=center bgcolor=".$bodycolor."><font class=s>".$row['score']." </font></TD><TD  width=10% align=center bgcolor=".$bodycolor."><font class=s>".$row['kills']." </font></TD><TD  width=10% align=center bgcolor=".$bodycolor."> <font class=s>".$row['deaths']." </font></TD><TD  width=10% align=center bgcolor=".$bodycolor."><font class=s>".$row['ping']." </font></TD></TR>";

                    $totalscore_team1=($totalscore_team1+$row['score']);
                    $totaldeaths_team1=($totaldeaths_team1+$row['deaths']);
                    $totalping_team1=($totalping_team1+$row['ping']);
                    $totalplayers_team1=($totalplayers_team1+1);
                    $totalkills_team1=($totalkills_team1+$row['kills']);
                }
            }


            $totalplayers_team2= 0;
            foreach ($data as $key => $row) 
            {
                if ($row['team']==2)
                {
                    $totalplayers_team2=($totalplayers_team2+1);
                }
            }
            ?>


            <TR><TD width='100%' colspan="6" height="20" bgcolor="<?=$bodycolor?>">&nbsp;</TD></tr>
            <TR><TD width='100%' colspan="6" bgcolor="#000000">&nbsp;<img src=<?=$images?><?=strtolower($rule['bf2_team2'])?>.jpg width="17" height="10">&nbsp;<font class=ab><b>(<?=$totalplayers_team2?>) <?=armyname($rule['bf2_team2'])?></b></font></TD></tr>
                <TR><TD width="60%" bgcolor="#847D49" colspan="2"><img src="<?=$images?>/players.gif"></TD>
                <TD width="10%" align="center" bgcolor="#847D49"><a href="<?=$_SERVER['PHP_SELF']?>?sort=score<?=$extra?>"><img src="<?=$images?>score.jpg" border="0" alt="Сортировать по счету"></a></TD>
                <TD width="10%" align="center" bgcolor="#847D49"><a href="<?=$_SERVER['PHP_SELF']?>?sort=kills<?=$extra?>"><img src="<?=$images?>kills.jpg" border="0" alt="Сортировать по убийствам"></a></TD>
                <TD width="10%" align="center" bgcolor="#847D49"><a href="<?=$_SERVER['PHP_SELF']?>?sort=deaths<?=$extra?>"><img src="<?=$images?>deaths.jpg" border="0" alt="Сортировать по смертям"></a></TD>
                <TD width="10%" align="center" bgcolor="#847D49"><a href="<?=$_SERVER['PHP_SELF']?>?sort=ping<?=$extra?>"><img src="<?=$images?>ping.jpg" border="0" alt="Сортировать по пингу"></a></TD>


            <?

            foreach ($data as $key => $row) 
            {
                if ($row['team']==2)
                {
                    $namecolor = "";
                    if($row['ping'] > $lagger) $namecolor=$laggercolor;
                    if($row['deaths'] < 0 || $row['score'] < 0) $namecolor=$teamkillercolor;
                    if(stristr($row['name'], $serveradmintag)) $namecolor=$admincolor;
                    if($row['name'] == $serveradmin) $namecolor=$admincolor;
                    if(!$namecolor) $namecolor=$fontcolor;


                    echo "<TR><td style=\"padding: 2px\"><a href=\"{$ROOT}?pid=".$row['pid']."\" target=_new><img src=\"".$images."stats.jpg\" width=10 height=12 border=0 alt=\"Посмотреть подробную статистику игрока\"></a></td><TD width=60% bgcolor=".$bodycolor."><font class=nc color=".$namecolor.">".htmlentities($row['name'])."</font></TD><TD  width=10% align=center bgcolor=".$bodycolor."><font class=s>".$row['score']." </font></TD><TD  width=10% align=center bgcolor=".$bodycolor."><font class=s>".$row['kills']." </font></TD><TD  width=10% align=center bgcolor=".$bodycolor."> <font class=s>".$row['deaths']." </font></TD><TD  width=10% align=center bgcolor=".$bodycolor."><font class=s>".$row['ping']." </font></TD></TR>";

                    $totalscore_team2=($totalscore_team2+$row['score']);
                    $totaldeaths_team2=($totaldeaths_team2+$row['deaths']);
                    $totalping_team2=($totalping_team2+$row['ping']);
                    $totalplayers_team2=($totalplayers_team2+1);
                    $totalkills_team2=($totalkills_team2+$row['kills']);
                }
            }

            ?>


            <TR><TD width='100%' colspan="5" height="20" bgcolor="<?=$bodycolor?>">&nbsp;</TD></tr>
            </TD></TR>
            </TABLE>


        <?
        }

        //Team 1 Averages
        if ($totalscore_team1 && $totalplayers_team1)
        {
            $getave= ($totalscore_team1/$totalplayers_team1);
            $team1_score_ave = round($getave);
        } 
        else 
        {
            $team1_score_ave = 0;
        }

        if ($totaldeaths_team1 && $totalplayers_team1)
        {
            $getave= ($totaldeaths_team1/$totalplayers_team1);
            $team1_deaths_ave = round($getave);
        } 
        else 
        {
            $team1_deaths_ave = 0;
        }

        if ($totalping_team1 && $totalplayers_team1)
        {
            $getave= ($totalping_team1/$totalplayers_team1);
            $team1_ping_ave = round($getave);
        } 
        else 
        {
            $team1_ping_ave = 0;
        }

        if ($totalkills_team1 && $totalplayers_team1)
        {
            $getave= ($totalkills_team1/$totalplayers_team1);
            $team1_kills_ave = round($getave);
        } 
        else 
        {
            $team1_kills_ave = 0;
        }

        //Team 2 Averages
        if ($totalscore_team2 && $totalplayers_team2)
        {
            $getave= ($totalscore_team2/$totalplayers_team2);
            $team2_score_ave = round($getave);
        } 
        else 
        {
            $team2_score_ave = 0;
        }

        if ($totaldeaths_team2 && $totalplayers_team2)
        {
            $getave= ($totaldeaths_team2/$totalplayers_team2);
            $team2_deaths_ave = round($getave);
        } 
        else 
        {
            $team2_deaths_ave = 0;
        }

        if ($totalping_team2 && $totalplayers_team2)
        {
            $getave= ($totalping_team2/$totalplayers_team2);
            $team2_ping_ave = round($getave);
        } 
        else 
        {
            $team2_ping_ave = 0;
        }

        if ($totalkills_team2 && $totalplayers_team2)
        {
            $getave= ($totalkills_team2/$totalplayers_team2);
            $team2_kills_ave = round($getave);
        } 
        else 
        {
            $team2_kills_ave = 0;
        }
    ?>

          </td>
          <td width="30%" rowspan="1" align="left" valign="top" bgcolor="<?=$bodycolor?>">
        <table height=20 border=1 width=99% cellpadding=0 bordercolor="<?=$bordercolor?>" style="border-collapse: collapse" align=center>
        <tr><td bgcolor="#000000" width="100%" valign="middle" style="PADDING-left:4px"><font class=ab><b>Установки сервера</b></font></td>
        </tr></table>
        <table style="padding-left: 5px; padding-right: 5px" border="0" width="100%" cellspacing="0" cellpadding="0">
        <tr><td class=s>Timelimit</td><td align="right" class=s><?=$rule['timelimit']?></td></tr>
        <tr><td class=s>Gametype</td><td align="right" class=s><?=$rule['gametype']?></td></tr>
        <tr><td class=s>Scorelimit</td><td align="right" class=s><?=$rule['bf2_scorelimit']?></td></tr>
        <tr><td class=s>Gamemode</td><td align="right" class=s><?=$rule['gamemode']?></td></tr>
        <tr><td class=s>Dedicated</td><td align="right" class=s><?=$rule['bf2_dedicated']?></td></tr>
        <tr><td class=s>OS</td><td align="right" class=s><?=$rule['bf2_os']?></td></tr>
        <tr><td class=s>Ticket Ratio</td><td align="right" class=s><?=$rule['bf2_ticketratio']?></td></tr>
        <tr><td class=s>Team Ratio</td><td align="right" class=s><?=round($rule['bf2_teamratio'])?></td></tr>
        <tr><td class=s>Punkbuster</td><td align="right" class=s><?=$rule['bf2_anticheat']?></td></tr>
        <tr><td class=s>Auto Balance Teams</td><td align="right" class=s><?=$rule['bf2_autobalanced']?></td></tr>
        <tr><td class=s>Reserved Slots</td><td align="right" class=s><?=$rule['bf2_reservedslots']?></td></tr>
        <tr><td class=s>Game Start Delay</td><td align="right" class=s><?=$rule['bf2_startdelay']?></td></tr>
        <tr><td class=s>Global Unlocks</td><td align="right" class=s><?=$rule['bf2_globalunlocks']?></td></tr>
        <tr><td class=s>Map Size</td><td align="right" class=s><?=$rule['bf2_mapsize']?></td></tr>
        <tr><td class=s>Friendly Fire</td><td align="right" class=s><?=$rule['bf2_friendlyfire']?></td></tr>
        <tr><td class=s>Start Delay</td><td align="right" class=s><?=$rule['bf2_startdelay']?></td></tr>
        <tr><td class=s>Spawn Time</td><td align="right" class=s><?=round($rule['bf2_spawntime'])?></td></tr>
        <tr><td class=s>Ticket Ratio</td><td align="right" class=s><?=$rule['bf2_ticketratio']?></td></tr>
        <tr><td class=s>TK Mode</td><td align="right" class=s><?=$rule['bf2_tkmode']?></td></tr>
        <tr><td class=s>FPS</td><td align="right" class=s><?=round($rule['bf2_fps'])?></td></tr>
        <tr><td class=s>Ranked</td><td align="right" class=s><?=$rule['bf2_ranked']?></td></tr>
        <tr><td class=s>Pure</td><td align="right" class=s><?=$rule['bf2_pure']?></td></tr>
        <tr><td class=s>Voice</td><td align="right" class=s><?=$rule['bf2_voip']?></td></tr>

        <tr><td class=s>Server Website</td><td align="right" class=s><a href="<?=$weburl?>">Visit Us</a>
        </td></tr>
        <tr><td colspan="2">
        <br/>
        <table border=1 width=95% cellpadding=1 bordercolor="<?=$bordercolor?>" style="border-collapse: collapse" align=center>
        <tr><td bgcolor=<?=$topbottomcell?>>
        <font class=s>
        <b>Средние значения по серверу</b>
        </font>
        </td></tr>
        <tr><td>
        <table width="100%" cellpadding="0" cellspacing="0">
            <TR><TD width="60%" bgcolor="#847D49" bordercolor="#847D49"><img src="<?=$images?>/teamavg.gif"></TD>
            <TD width="10%" align="center" bgcolor="#847D49"><img src="<?=$images?>score.jpg"></TD>
            <TD width="10%" align="center" bgcolor="#847D49"><img src="<?=$images?>kills.jpg"></TD>
            <TD width="10%" align="center" bgcolor="#847D49"><img src="<?=$images?>deaths.jpg"></TD>
            <TD width="10%" align="center" bgcolor="#847D49"><img src="<?=$images?>ping.jpg"></TD>
            <TR><TD width="60%" class=s>&nbsp;<?=$rule['bf2_team1']?></font></TD>
            <TD width="10%" align="center" class=s><?=$team1_score_ave?></TD>
            <TD width="10%" align="center" class=s><?=$team1_kills_ave?></TD>
            <TD width="10%" align="center" class=s><?=$team1_deaths_ave?></TD>
            <TD width="10%" align="center" class=s><?=$team1_ping_ave?></TD></TR>
            <TR><TD width="60%" class=s>&nbsp;<?=$rule['bf2_team2']?></font></TD>
            <TD width="10%" align="center" class=s><?=$team2_score_ave?></TD>
            <TD width="10%" align="center" class=s><?=$team2_kills_ave?></TD>
            <TD width="10%" align="center" class=s><?=$team2_deaths_ave?></TD>
            <TD width="10%" align="center" class=s><?=$team2_ping_ave?></TD></TR>
        </table></td></tr>
        </table>
        <br/>
        </td></tr>


        <?if($usecomments){?>
        <tr><td colspan="2">
        <table border=1 width=95% cellpadding=1 bordercolor="<?=$bordercolor?>" style="border-collapse: collapse" align=center>
        <tr><td bgcolor=<?=$topbottomcell?> class=s>
        <b>Server Admin Comments</b>
        </td></tr>
        <tr><td class=s>
        <?=$comments?>
        </td></tr>
        </table>
        <br>
        </td></tr>
        <?}?>


        </table>
        </td>
              </td>
            </tr>
            <tr>
              <td align="left" bgcolor="<?=$topbottomcell?>" bordercolor="<?=$topbottomcell?>" valign="middle" colspan="1">
        </td>
              <td align="right" bgcolor="<?=$topbottomcell?>" valign="middle" colspan="2" class=s>
        <b>Подсветка:</b>&nbsp;&nbsp;<font class=nc color="<?=$teamkillercolor?>">Тимкиллер/Самоубийца</font><font class=s>&nbsp;-&nbsp;</font><font class=nc color="<?=$admincolor?>">Админ</font><font class=s>&nbsp;-&nbsp;</font><font class=nc color="<?=$laggercolor?>">Внешний/С лагами</font></td>
            </tr>

        </table>
        <br>
        <?
    } //else

} //function

function armyname($name) 
{
	switch(strtolower($name)) 
    {
		case "ukr":
		return "Хохлы";
      	break;

		case "ger":
		return "Немцы";
      	break;

		case "mec":
		return "Middle Eastern Coalition";
      	break;

		case "us":
		return "United States Marine Corps";
      	break;

		case "usa":
		return "Американцы";
      	break;

		case "ch":
		return "People's Liberation Army";
      	break;

		case "seal":
		return "Seals";
      	break;

		case "sas":
		return "SAS";
      	break;

		case "spets":
		return "Spetsnaz";
      	break;

		case "mecsf":
		return "Middle Eastern Coalition SF";
      	break;

		case "rebels":
		return "Rebels";
      	break;

		case "Insurgents":
		return "insurgents";
      	break;

		case "European Union":
		return "eu";
      	break;
	}
}


?>

<html>
<head>
<meta http-equiv="refresh" content="<?=$refreshrate?>">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251"/>
<title>монитор</title>
<link rel="icon" href="favicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<link rel="stylesheet" type="text/css" media="screen" href="css/two-tiers.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/nt.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/default.css">
<link rel="stylesheet" type="text/css" media="screen" href="css/monitor.css">

<script type="text/javascript">/* no frames */ if(top.location != self.location) top.location.replace(self.location);</script>
<script type="text/javascript" src="js/nt2.js"></script>

<SCRIPT LANGUAGE="JavaScript">
<!-- Begin
function ppUp(URL) {
    day = new Date();
    id = day.getTime();
    eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=435,height=435,left = 430,top = 302');");
}
// End -->

<!-- Begin
function ppUpLogo(URL) 
{
    day = new Date();
    id = day.getTime();
    eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=420,height=120,left = 430,top = 302');");
}
// End -->
</script>


</head>

<body class="inner">

<div id="page-1">
	<div id="page-2">

		<ul id="navitems">
			<li>|<a href="/">Главная</a>|</li>
            <li><a href="/forum">Форум</a>|</li>
            <li><a href="/?go=stats">Все лидеры</a>|</li>
            <li><a href="/?go=currentranking">Зал славы</a>|</li>
            <li><a href="/bans">БанЛисты</a>|</li>
            <li><a href="/monitor.php">Монитор</a>|</li>
			<li><a href="/pb">FAQ по PB</a>|</li>
            <li><a href="/?go=ubar">UBAR</a>|</li>
			<li><a href="http://wiki.bf2s.com/">Wiki</a>|</li>
		</ul>
		
		<form action="/?go=search" method="post" id="getstats">
			<label for="pid">Поиск игрока по ID или Нику:</label>
			<input type="text" name="searchvalue" id="pid" value="" />
			<input type="submit" class="btn" value="Go" />
		</form>

	<h1 id="page-title">Монитор серверов</h1>

	<div id="page-3">

	<div id="content"><div id="content-id"><!-- template header end == begin content below -->

<div align="center">
  <center>



<?php monitor($server, $port); ?> <!-- 16567:29900 -->


  </center>
</div>

</div></div></div></div></div>
</body>
</html>