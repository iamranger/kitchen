<?php
/*$Id: mymessages.php 829 2012-11-04 20:36:54Z dmitriy $*/

require_once('head_inc.php');

    $cur_page = $page_my_bookmarks;
    $how_many = 50;
    $max_id = 1;
    $last_id = 0;

    $query = 'SELECT count(*) from confa_bookmarks where user=' . $user_id;
    $result = mysql_query($query);
    if (!$result) {
        mysql_log(__FILE__, 'query failed ' . mysql_error() . ' QUERY: ' . $query);
        die(' Query failed ' );
    }
    $row = mysql_fetch_row($result);
    $count = $row[0]; 


    if (is_null($page)) {
        $page = 1;
    }
    $last_id = get_page_last_index('confa_bookmarks where user=' . $user_id , $how_many, $page );
    if (is_null($last_id)) {
      $last_id = 0;
    }
    $query = 'SELECT u.username, u.moder, p.auth, p.closed as post_closed, CONVERT_TZ(p.created, \'' . $server_tz . '\', \'EST\') as created, p.subject, p.content_flags, p.views, p.likes, p.dislikes, p.status, p.id as msg_id, p.chars, b.user, b.post  from confa_posts p, confa_users u, confa_bookmarks b where b.user=' . $user_id . ' and b.post=p.id and p.author=u.id and  p.status != 2 and b.id <= ' . $last_id . ' order by msg_id desc limit 50'; 

    $result = mysql_query($query);
    if (!$result) {
        mysql_log(__FILE__, 'query failed ' . mysql_error() . ' QUERY: ' . $query);
        die('Query failed ' );
    }

    $num = 1;  

    $out = '';
    if (mysql_num_rows($result) == 0) {
        $max_id = $last_id;
    }
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $msg_id = $row['msg_id'];
        $moder = $row['moder'];

        $subj = $row['subject'];
        $subj = htmlentities($subj, HTML_ENTITIES,'UTF-8');
        $enc_user = htmlentities($row['username'], HTML_ENTITIES,'UTF-8');
        $icons = '';
        if ($row['content_flags'] & 0x02) {
          $icons = ' <img border=0 src="' . $root_dir . $image_img . '"/> ';
        }
        if ($row['content_flags'] & 0x04) {
          $icons .= ' <img border=0 src="' . $root_dir . $youtube_img . '"/> ';
        }

        $line = '<li><a target="bottom" name="' . $msg_id . '" href="' . $root_dir . $page_msg . '?id=' . $msg_id . '"> ' . $icons . $subj . ' </a> <b>' . $enc_user . '</b>' . ' ' . '[' . $row['views'] . ' views] '  . $row['created'] . ' <b>' . $row['chars'] . '</b> bytes';
if (!is_null($row['likes'])) {
          $likes = $row['likes'];
          if ($likes > 0) {
            $line .= ' <font color="green"><b>+' . $likes . '</b></font>';
          }
        }
        if (!is_null($row['dislikes'])) {
          $dislikes = $row['dislikes'];
          if ($dislikes > 0) {
            $line .= ' <font color="red"><b>-' . $dislikes . '</b></font>';
          }
        }
        $line .= "</li>";

        $out .= $line;
        $num++;
    }

require_once('html_head_inc.php');

?>
<base target="bottom">
</head>
<body >
<!--<table width="95%"><tr>
<td>-->
<!--<h3><?php print($title);?></h3>-->
<!--</td>

</tr></table>-->
<?php

require('menu_inc.php');

    $max_page = $count/20;
    $max_page++;
    print_pages($max_page, $page, 'contents', $cur_page, '&author_id=' . $user_id);
    if (!is_null($err) && strlen($err) > 0) {
        print('<BR><font color="red"><b>' . $err . '</b></font>');
    }
?>

<ol>
<?php print($out); ?>
</ol>
<!--
<?php 
    if (strlen($err) > 0) {
        print('<br><font color="red"><b>' . $err . '</b></font></br>');
    }
?>
-->

</body>
</html>
<?php

require('tail_inc.php');

?>

