<?php
/*$Id: func.php 988 2014-01-05 01:14:33Z dmitriy $*/

/**********************************************************************/
/*                          Common function                           */ 
/**********************************************************************/ 

require_once('translit.php');
require_once('settings.php');

function update_new_pm_count($user_id) {

    $query = 'SELECT count(*) from confa_pm where receiver=' . $user_id . ' and status=1';
    $result = mysql_query($query);
    if (!$result) {
        mysql_log( __FILE__, 'query failed ' . mysql_error() . ' QUERY: ' . $query);
        die('Query failed');
    }
    $row = mysql_fetch_row($result);
    $new_pm = $row[0];
    $query = 'UPDATE confa_users set new_pm=' . $new_pm . ' where id=' . $user_id;
    $result = mysql_query($query);
    if (!$result) {
        mysql_log( __FILE__, 'query failed ' . mysql_error() . ' QUERY: ' . $query);
        die('Query failed');
    }
}

function get_page_last_index( $where, $page_size, $page) {

    if ( $page == 1 ) {
        $query = 'SELECT max(id) from (select id from ' . $where . ' order by id ) p';
    } else {
        $query = 'SELECT min(id) from (select id from ' . $where . ' order by id desc limit ' . $page_size*($page-1) . ') p';
    }
    $result = mysql_query($query);
    if (!$result) {
        mysql_log(__FILE__, 'get_user_props failed ' . mysql_error() . ' QUERY: ' . $query);
        die('Query failed');
    }
    $row = mysql_fetch_row($result);
    return $row[0];
}

function generatePassword($length=9, $strength=0) {

    $vowels = 'aeuy';
    $consonants = 'bdghjmnpqrstvz';
    if ($strength & 1) {
        $consonants .= 'BDGHJLMNPQRSTVWXZ';
    }
    if ($strength & 2) {
        $vowels .= "AEUY";
    }
    if ($strength & 4) {
        $consonants .= '23456789';
    }
    if ($strength & 8) {
        $consonants .= '@#$%';
    }
 
    $password = '';
    $alt = time() % 2;
    for ($i = 0; $i < $length; $i++) {
        if ($alt == 1) {
            $password .= $consonants[(rand() % strlen($consonants))];
            $alt = 0;
        } else {
            $password .= $vowels[(rand() % strlen($vowels))];
            $alt = 1;
        }
    }
    return $password;
}


function get_thread_starts($min_thread_id, $max_thread_id) {

    global $prop_tz;
    global $server_tz;
    if (is_null($prop_tz)) {
        $prop_tz = -5;
    }
    $query = 'SELECT u.username, u.id as user_id, u.moder, u.ban_ends, p.parent, p.closed as post_closed, p.views, p.likes, p.dislikes, CONVERT_TZ(p.created, \'' . $server_tz . '\', \'' . $prop_tz . ':00\')  as created, p.subject,  p.content_flags, t.closed as thread_closed, t.status as thread_status, t.id as thread_id, p.level, p.status, p.id as msg_id, p.chars, t.counter from confa_posts p, confa_users u, confa_threads t ';
    if ( $min_thread_id < 0 ) {
        $min_thread_id = 0;
    }
    $query .= ' where p.author=u.id and thread_id >= ' . $min_thread_id . ' and thread_id <= ' . $max_thread_id . ' and p.thread_id = t.id and t.status != 2 and level = 0 order by thread_id desc';
    $result = mysql_query($query);
    if (!$result) {
        mysql_log(__FILE__, 'get_thread_starts failed ' . mysql_error() . ' QUERY: ' .$query);
        die('Query failed ');
    }
    return $result;
}

// NG: begin

function get_show_hidden_and_ignored() {
  
  global $link;
  global $user_id;
  global $show_hidden;
  global $ignored;
  
  $show_hidden = 2;
  $ignored = array();

  if (!is_null($user_id)) {
    $query = "SELECT show_hidden from confa_users where id=" . $user_id;
    if (!($result = mysql_query($query))) {
      mysql_log(__FILE__ . ':' . __LINE__, 'query failed: ' . $query . "|" .  mysql_error($link));
      die('query failed');
    } else {
      $row = mysql_fetch_assoc($result);
      $show_hidden = $row['show_hidden'];
    }
    if ($show_hidden == 1 || $show_hidden == 0) {
      $query = "SELECT ignored from confa_ignor where ignored_by=" . $user_id;
      $result_ignored = mysql_query($query);
      if (!$result_ignored) {
        mysql_log(__FILE__, 'query failed ' . mysql_error() . ' QUERY: ' . $query . 'test_user_id="' . $user_id . '"');
        die('Query failed ' );
      }
      while ($row = mysql_fetch_assoc($result_ignored)) {
        array_push($ignored, $row['ignored']);
      } 
    }
  }
}

function get_threads_ex($limit = 200, $thread_id = null) {

  global $prop_tz;
  global $work_page;

  if (is_null($prop_tz)) {
      $prop_tz = -5;
  }

  $query = 'SELECT u.username, u.id as user_id, u.moder, u.ban_ends, p.parent, p.closed as post_closed, p.views, p.likes, p.dislikes, p.level, CONVERT_TZ(p.created, \'-5:00\', \'' . $prop_tz . ':00\')  as created, p.subject, p.status, p.thread_id, p.id as msg_id, p.chars, p.content_flags, t.page, t.closed as thread_closed, t.status as thread_status, t.counter from confa_posts p, confa_users u, confa_threads t ';
  $query.= 'where p.author=u.id and t.id = p.thread_id and t.status != 2 ';
	
	if (is_null($thread_id)) {
		$query .= 'and t.page<=' . $work_page . ' and t.id > (select max(id) from confa_threads t where t.page =' . $work_page . ') - ' . $limit; 
	} else {
		$query .= 'and p.thread_id < ' . $thread_id . ' and p.thread_id >=' . ($thread_id - $limit);
	}

	$query .= ' order by thread_id desc, level, msg_id desc';
  $result = mysql_query($query);
  
  if (!$result) {
      mysql_log( __FILE__, 'get_threads failed ' . mysql_error() . ' QUERY: ' . $query);
      die('Query failed ');
  }
  
  return $result;
}

function print_threads_ex($result, &$content, &$max_thread_id, $limit = 200, $collapsed=false) {

    global $work_page;

    $msgs = array();
    $content = array();
    $cur_content = &$content;
    $armass = array();
    $glob = array();
    $l = 0;
    
    while ($row = mysql_fetch_assoc($result)) {
        if ($l > $limit && $row['level'] == 0 && $row['page'] != $work_page) // always print everything on the work page to support Synchronize
          break;
      
        $max_thread_id = $row['thread_id'];      
        $msgs[$row['msg_id']] = print_line($row, $collapsed);

        $armass[$l] = array();

        if ($row['level'] == 0) {
            $content[$row['msg_id']] = &$armass[$l];
        } else {
            $cur_content = &$glob[$row['parent']];
            $cur_content[$row['msg_id']] = &$armass[$l];
        }
        $glob[$row['msg_id']] = &$armass[$l];

        $l++;        
    }
    
    return $msgs;
}

function print_line($row, $collapsed=false) {
  
  global $root_dir;
  global $page_msg;
  global $page_byuser;
  global $page_topthread;
  global $page;
  global $cur_page;
  global $page_collapsed;
  global $page_thread;
  global $prop_bold;
  global $image_img;
  global $youtube_img;

  global $show_hidden;
  global $ignored;

  $b_start = '';
  $b_end = '';
  
  if (!strcmp( $cur_page, $page_collapsed )) {
    $collapsed = true;
  }
  if ( !is_null($prop_bold) && $prop_bold > 0 && !$collapsed ) { 
      $b_start = '<b>';
      $b_end = '</b>';
  }

  if ($show_hidden == 1 && in_array($row['user_id'], $ignored)) {
    return "<font color=\"lightgrey\"/>Hidden msg by " . htmlentities($row['username'], HTML_ENTITIES,'UTF-8') . "</font>";
  }
  if ($show_hidden == 0 && in_array($row['user_id'], $ignored)) {
    return "";
  }
  
  $length = $row['chars'];
  $moder = $row['moder'];
  $banned = false;
  $ban_ends = $row['ban_ends'];
  $subj = $row['subject'];
  $icons = '';
  $style = 'padding:0px 0px 3px 0px;';

  if ($row['content_flags'] & 0x02) {
    $icons = ' <img border=0 src="' . $root_dir . $image_img . '"/> ';
  }
  if ($row['content_flags'] & 0x04) {
    $icons .= ' <img border=0 src="' . $root_dir . $youtube_img . '"/> ';
  }
  $subj = encode_subject($subj);
  $enc_user = htmlentities($row['username'], HTML_ENTITIES,'UTF-8');
  if ( !is_null( $ban_ends ) && strcmp( $ban_ends, '0000-00-00 00:00:00' ) ) {
      $banned = true;
  }
  if ( $banned === true ) {
      $enc_user = '<font color="grey">' . $enc_user . '</font>';
  }
  $enc_user = '<a class="user_link" href="' . $root_dir . $page_byuser . '?author_id=' . $row['user_id'] . '" target="contents">' . $enc_user . '</a>';  
  if ($row['status'] == 2 ) {

      if ($row['level'] == 0) {
          $line = '&nbsp;<img border=0 src="images/bs.gif" width=16 height=16 alt="*" align="top" style="'.$style.'"> <I><font color="gray"><del>This message has been deleted</del></font></I> ';
      } else {
          $line = '&nbsp;<img border=0 src="images/dc.gif" width=16 height=16 alt="*" align="top" style="'.$style.'"> <I><font color="gray"><del>This message has been deleted</del></font></I> ';
      }
  } else {
      if ($row['level'] == 0) {
          $icon = "bs.gif";
          if ($row['thread_closed'] != 0) {
            $icon = "cs.gif";
          } else if ($row['counter'] == 0) {
            $icon = "es.gif";
          }
          $line = '&nbsp;<img border=0 src="images/' . $icon . '" width=16 height=16 alt="*" onclick="javascript:toggle(this);" align="top" style="'.$style.'"><a id="' . $row['msg_id'] . '" name="' . $row['msg_id'] . '" target="bottom" href="' . $root_dir . $page_msg . '?id=' . $row['msg_id'] . '"> ' .  $icons . $b_start . $subj . $b_end . '  </a> ';
      } else {
          $line = '&nbsp;<a id="' . $row['msg_id'] . '" name="' . $row['msg_id'] . '" target="bottom" href="' . $root_dir . $page_msg . '?id=' . $row['msg_id'] . '"><img border=0 src="images/dc.gif" width=16 height=16 alt="*" align="top" style="'.$style.'"> ' . $icons . $subj . '  </a> ';
      }
  }
  
  $line .= '<b>' . $enc_user . '</b>' .  ' [' . $row['views'] . ' views] ' . $row['created'] . ' <b>' . $length . '</b> bytes';
  
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

  if ( $collapsed ) {
      $line .= ' <font color="gray">[ <a href="' . $root_dir . $page_topthread . '?thread=' . $row['thread_id'] . '&page=' . $page . '" target="contents">+' . $row['counter'] . '</a> ] </font>    ';
  }

  $arrow = ''; 
  $arrow.= '<img border=0 src="images/up.gif" width=16 height=16 alt="*" ';
  $arrow.= ' onclick="javascript:scroll2Top(\'body\');" onmouseout="this.style.opacity = 0.3;" style="opacity:0.3" onmouseover="this.style.opacity=1;" align="top">';
        
  return $line . $arrow;
}
// NG: end

function get_threads() {

    global $prop_tz;
    global $work_page;
    if (is_null($prop_tz)) {
        $prop_tz = -5;
    }
    $query = 'SELECT u.username, u.id as user_id, u.moder, u.ban_ends, p.parent, p.closed as post_closed, p.views, p.likes, p.dislikes, p.level, CONVERT_TZ(p.created, \'-5:00\', \'' . $prop_tz . ':00\')  as created, p.subject, p.status, p.thread_id, p.id as msg_id, p.chars, p.content_flags, t.page, t.closed as thread_closed, t.status as thread_status, t.counter from confa_posts p, confa_users u, confa_threads t where p.author=u.id and t.id = p.thread_id and t.status != 2 and t.page=' . $work_page . ' order by thread_id desc, level, msg_id desc';

    $result = mysql_query($query);
    if (!$result) {
        mysql_log( __FILE__, 'get_threads failed ' . mysql_error() . ' QUERY: ' . $query);
        die('Query failed ');
    }
    return $result;
}

function get_max_pages_collapsed(&$max_thread_id) {

    $query = 'SELECT count(distinct(thread_id)), max(thread_id)  from  confa_posts where status != 2';
    $result = mysql_query($query);
    if (!$result) {
        mysql_log(__FILE__, 'Query page count failed: ' . mysql_error() . ' QUERY: ' . $query);
        die('Query failed.');
    }
    $row = mysql_fetch_row($result);
    $max_page = $row[0]/50;
    $max_thread_id = $row[1];
    return $max_page;
}

function get_max_pages_users(&$max_user_id, $user_like) {

    $query = 'Select count(*), max(id) from confa_users';
    if (!is_null($user_like)) {
      $query .= ' where username like \'%' . $user_like . '%\'';
    }
    $result = mysql_query($query);
    if (!$result) {
      mysql_log(__FILE__, 'Query users page count failed: ' . mysql_error() . ' QUERY: ' . $query);
      die('Query failed.');
    }
    $row = mysql_fetch_row($result);
    $max_page = $row[0]/50;
    $max_user_id = $row[1];
    return $max_page;
 
}
 
function get_users($min_user_id, $max_user_id, $user_like) {

    global $prop_tz;
    if (is_null($prop_tz)) {
        $prop_tz = -5;
    }
    if (is_null($user_like)) {
      $query = 'select id as user_id, username,  CONVERT_TZ(created, \'-5:00\', \'' . $prop_tz . ':00\')  as created, moder, CONVERT_TZ(ban_ends, \'-5:00\', \'' . $prop_tz . ':00\')  as ban_ends from (select id, username, created, moder, ban_ends from confa_users  where status != 2 order by username) us where id >= ' . $min_user_id . ' and id <= ' . $max_user_id . ' order by username';
    } else {
      $query = 'select id as user_id, username,  CONVERT_TZ(created, \'-5:00\', \'' . $prop_tz . ':00\')  as created, moder, CONVERT_TZ(ban_ends, \'-5:00\', \'' . $prop_tz . ':00\')  as ban_ends from (select id, username, created, moder, ban_ends from confa_users  where status != 2 order by username    ) us where username like \'%' . $user_like . '%\'  order by username';

    }
    $result = mysql_query($query);
    if (!$result) {
        mysql_log( __FILE__, 'get_users failed ' . mysql_error() . ' QUERY: ' . $query);
        die('Query failed ');
    }
    return $result;
}

function print_users($result) {

    global $root_dir;
    global $page_m_user;

    print('<table><tr><th>ID </th><th>Username </th><th>Registered </th><th align="right">Status </th></tr>');
    while ($row = mysql_fetch_assoc($result)) {
        $status = 'Active';
        if (!is_null($row['ban_ends'])) {
            $status = 'Banned till ' . $row['ban_ends'];
        } 
        $line = '<tr><td>' . $row['user_id'] . ' </td><td><a target="bottom" href="' . $root_dir . $page_m_user . '?userid=' . $row['user_id'] . '">' . htmlentities($row['username'], HTML_ENTITIES,'UTF-8') . '</a> </td><td>' . $row['created'] . ' </td><td align="right">' . $status . ' </td></tr>';
        print($line); 
    } 
    print('</table>');
}

function get_max_pages_expanded(){ 

    $query = 'SELECT max(page) as max_page from confa_threads';
    $result = mysql_query($query);
    if (!$result) {
        mysql_log(__FILE__, 'Query count failed: ' . mysql_error() . ' QUERY: ' . $query);
        die('Query failed.');
    }
    $row = mysql_fetch_assoc($result);
    $max_page = $row['max_page'];
    if (is_null($max_page)) {
        $max_page = 1;
    }
    return $max_page;
}

function print_pages($max_page, $page, $target, $cur_page, $param = '') {

    global $root_dir;
    print('<BR><B>Pages</B>: ');
    $start = $page - 10;
    $end = $page + 9;
    if ( $start > 0 ) {
        if ( $start > 1 ) {
            print(' <a target="' . $target . '" href="' . $root_dir . $cur_page . '?page=1' . $param . '">&lt;&lt;</a> |');
        }
        print(' <a target="' . $target . '" href="' . $root_dir . $cur_page . '?page=' . $start . $param . '">&lt;</a> |');
        $start = $start + 1;
    } else {
        $start = 1;
    } 
    
    if ( $end > $max_page ) {
        $end = $max_page;
    }

    for ($i = $start; $i <= $end; $i++) {
        if ( $i == $page ) {
            print(' ' . $i . ' |');
        } else {
            print(' <a target="' . $target . '" href="' . $root_dir . $cur_page . '?page=' . $i . $param . '">'. $i . '</a> |');
        }
    }
    if ( $end < $max_page ) {
        $end = $end + 1;
        print(' <a target="' . $target . '" href="' . $root_dir . $cur_page . '?page=' . $end . $param . '">&gt;</a> ');
        if ( $end < $max_page ) {
            print(' <a target="' . $target . '" href="' . $root_dir . $cur_page . '?page=' . (int) $max_page . '' . $param . '">&gt;&gt;</a> ');
        }
    }
}

function print_pages_old($max_page, $page, $target, $cur_page) {

    global $root_dir;
    print('<BR><B>Pages</B>: ');
    for ($i = 1; $i <= $max_page; $i++) {
        if ( $i == $page ) {
            print(' ' . $i . ' |');
        } else {
            print(' <a target="' . $target . '" href="' . $root_dir . $cur_page . '?page=' . $i . '">'. $i . '</a> |');
        }
    }
}


function print_msgs($ar, $msgs) {

    $keys = array_keys($ar);
    print("<dl style='position:relative; left:-20px'><dd>\n");
    foreach ($keys as $key) {
        //if ($msgs[$key] != "") {
        print($msgs[$key]);
        print("<BR>\n");
        //}
        if (sizeof($ar[$key]) > 0) {
            print_msgs($ar[$key], $msgs);
        }
    }
    print("</dd></dl>\n");
}


function build_content_tree($msg_line, $msg_id, $msg_level, $msg_parent, &$content, &$msgs, &$glob_map, &$armass, &$l) {

    $msgs[$msg_id] = $line;
    if ($msg_level == 0) {
        $content[$row['id']] = &$armass[$l];
        $glob[$row['id']] = &$armass[$l];
    } else {
        $cur_content = &$glob[$row['parent']];
        $cur_content[$row['id']] = &$armass[$l];
        $glob[$row['id']] = &$armass[$l];
    }
    $l++;
}


function validateEmail($email){

#copyright (c)John W. List 2002-2003 http://www.technotoad.com
/*
This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
*/

    $output = "";   
    $email = trim($email);
    #shortest valid email address is 5 characters
    If ((strlen($email)<5) or strstr($email," ")){
        $output = "Email is too short, " . strlen($email) . " characters.";
        return $output;
    }
    If (substr_count($email, "@")<>1){
        #If we find one and only one @, then the
        #email address is good to go.
        $output = "Email has missing @ character or too many @ characters.";
        return $output; 
    } else { #it has an at,split into 2 halves on the @
        $halves=explode ("@", $email);
        # look for text on either side of the at
        # at least 1 character on the lhs and at least 3 on the RHS
        If (strlen($halves[0])<1 or strlen($halves[1])<=3){
            $output = "Email address is invalid.";
            return $output; 
        }
        #dot search
        #look at LHS
        #no dots on the ends of LHS string
        If (substr($halves[0],0,1)=="." or substr($halves[0],-1,1)=="."){
            $output = "Email address is invalid.";
            //$output = "There is a dot on the end of the LHS of this address.";
            return $output; 
        }
        #look at RHS
        #no dots on the ends of RHS string
        If (substr($halves[1],0,1)=="." or substr($halves[1],-1,1)=="."){
            $output = "Email address is invalid.";
            //$output = "There is a dot on the end of the RHS of this address.";    
            return $output; 
        }
        #check it contains a dot and no dotdots
        if (!strstr($halves[1],".") or strstr($halves[1],"..")){
            //$output = "This address has the wrong number of dots in it.";
            $output = "Email address is invalid.";
            return $output; 
        }
    }
    return $output;
}


function encode_subject($subj) {

    $proceeded = false;
    $subj = htmlentities(translit($subj, $proceeded), HTML_ENTITIES,'UTF-8');
    return $subj;
}

function print_threads($result, &$content) {

    global $root_dir;
    global $page_msg;
    global $page_byuser;
    global $page_topthread;
    global $page;
    global $cur_page;
    global $page_collapsed;
    global $page_thread;
    global $prop_bold;
    global $image_img;
    global $youtube_img;

    global $show_hidden;
    global $ignored;

    $msgs = array();
    $content = array();
    $cur_content = &$content;
    $stack = array();
    $stack[0] = &$content;
    $level = 0;
    $armass = array();
    $glob = array();
    $l = 0;
    $b_start = '';
    $b_end = '';

    if ( !is_null($prop_bold) && $prop_bold > 0 &&  strcmp( $cur_page, $page_collapsed ) ) { 
        $b_start = '<b>';
        $b_end = '</b>';
    }

    while ($row = mysql_fetch_assoc($result)) {

        $length = $row['chars'];
        $armass[$l] = array();
        $moder = $row['moder'];
        $banned = false;
        $ban_ends = $row['ban_ends'];
        $subj = $row['subject'];
        $icons = '';
        $skip = false;

        if ($show_hidden == 1 && in_array($row['user_id'], $ignored)) {
          $line = "<font color=\"lightgrey\"/>Hidden msg by " . htmlentities($row['username'], HTML_ENTITIES,'UTF-8') . "</font>";
          $skip = true;
        }
        if ($show_hidden == 0 && in_array($row['user_id'], $ignored)) {
          $line = "";
          $skip = true; 
        }

        
        if (!$skip) {



        if ($row['content_flags'] & 0x02) {
          $icons = ' <img border=0 src="' . $root_dir . $image_img . '"/> ';
        }
        if ($row['content_flags'] & 0x04) {
          $icons .= ' <img border=0 src="' . $root_dir . $youtube_img . '"/> ';
        }
        $subj = encode_subject($subj);
        $enc_user = htmlentities($row['username'], HTML_ENTITIES,'UTF-8');
        if ( !is_null( $ban_ends ) && strcmp( $ban_ends, '0000-00-00 00:00:00' ) ) {
            $banned = true;
        }
        if ( $banned === true ) {
            $enc_user = '<font color="grey">' . $enc_user . '</font>';
        }
        $enc_user = '<a class="user_link" href="' . $root_dir . $page_byuser . '?author_id=' . $row['user_id'] . '" target="contents">' . $enc_user . '</a>';  
        if ($row['status'] == 2 ) {

            if ($row['level'] == 0) {
                $line = '&nbsp;<img border=0 src="images/bs.gif" width=16 height=16 alt="*" align="top" style="padding:0px 0px 2px 0px;"> <I><font color="gray"><del>This message has been deleted</del></font></I> ';
            } else {
                $line = '&nbsp;<img border=0 src="images/dc.gif" width=16 height=16 alt="*" align="top" style="padding:0px 0px 2px 0px;"> <I><font color="gray"><del>This message has been deleted</del></font></I> ';
            }
        } else {
            if ($row['level'] == 0) {
                $icon = "bs.gif";
                if ($row['thread_closed'] != 0) {
                  $icon = "cs.gif";
                } else if ($row['counter'] == 0) {
                  $icon = "es.gif";
                }
                $line = '&nbsp;<img border=0 src="images/' . $icon . '" width=16 height=16 alt="*" onclick="javascript:toggle(this);" align="top" style="padding:0px 0px 2px 0px;"><a name="' . $row['msg_id'] . '" target="bottom" href="' . $root_dir . $page_msg . '?id=' . $row['msg_id'] . '"> ' .  $icons . $b_start . $subj . $b_end . '  </a> ';
            } else {
                $line = '&nbsp;<a name="' . $row['msg_id'] . '" target="bottom" href="' . $root_dir . $page_msg . '?id=' . $row['msg_id'] . '"><img border=0 src="images/dc.gif" width=16 height=16 alt="*" align="top" style="padding:0px 0px 2px 0px;"> ' . $icons . $subj . '  </a> ';
            }
        }
        $line .= '<b>' . $enc_user . '</b>' .  ' [' . $row['views'] . ' views] ' . $row['created'] . ' <b>' . $length . '</b> bytes';
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

        if ( !strcmp( $cur_page, $page_collapsed ) ) {
            $line .= ' <font color="gray">[ <a href="' . $root_dir . $page_topthread . '?thread=' . $row['thread_id'] . '&page=' . $page . '" target="contents">+' . $row['counter'] . '</a> ] </font>    ';
        }

        }


        $msgs[$row['msg_id']] = $line;
        if ($row['level'] == 0) {
            $content[$row['msg_id']] = &$armass[$l];
            $glob[$row['msg_id']] = &$armass[$l];
        } else {
            $cur_content = &$glob[$row['parent']];
            $cur_content[$row['msg_id']] = &$armass[$l];
            $glob[$row['msg_id']] = &$armass[$l];
        }

        $l++;
    } 
    return $msgs;
}


function m_print_msgs($ar, $msgs, $pix) {
    $keys = array_keys($ar);
$pix=$pix+10;
#print ("s ". $pix. "\n");
#    print("<dd style='margin-left:" . $pix . "px' >\n");
    #print("<dl style='position:relative; left:-20px'><dd>\n");
    foreach ($keys as $key) {
print("<dd style='margin-left:" . $pix . "px' >\n");
#print ("inside ". $pix. "\n");
        print($msgs[$key]);
        print("\n");
        if (sizeof($ar[$key]) > 0) {
            m_print_msgs($ar[$key], $msgs, $pix);
        }
    }
#    print("</dd></dl>\n");
$pix=$pix-10;
#print ("e " . $pix . "\n");
}


function m_print_threads($result, &$content) {
#global $pix;
    global $root_dir;
    global $page_msg;
    global $page_byuser;
    global $page_topthread;
    global $page;
    global $cur_page;
    global $page_collapsed;
    global $page_thread;
    global $prop_bold;
#$pix=0;
    $msgs = array();
    $content = array();
    $cur_content = &$content;
    $stack = array();
    $stack[0] = &$content;
    $level = 0;
    $armass = array();
    $glob = array();
    $l = 0;
    $b_start = '';
    $b_end = '';

    if ( !is_null($prop_bold) && $prop_bold > 0 &&  strcmp( $cur_page, $page_collapsed ) ) { 
        $b_start = '<b>';
        $b_end = '</b>';
    }

    while ($row = mysql_fetch_assoc($result)) {

        $length = $row['chars'];
        $armass[$l] = array();
        $auth_text = '';
        $moder = $row['moder'];
        #if (!is_null($moder)) {
        if ($moder == 1) {
            $auth_text = '<font color="green"> *</font>';
        } else {
            $auth_text = '';
            #$auth_text = '<font color="red">*</font>';
        }
        $banned = false;
        $ban_ends = $row['ban_ends'];
        $subj = $row['subject'];

        $subj = encode_subject($subj);
        $enc_user = htmlentities($row['username'], HTML_ENTITIES,'UTF-8');
        if ( !is_null( $ban_ends ) && strcmp( $ban_ends, '0000-00-00 00:00:00' ) ) {
            $banned = true;
        }
        if ( $banned === true ) {
            $enc_user = '<font color="grey">' . $enc_user . '</font>';
        }
        $enc_user = '<a class="user_link" href="' . $root_dir . $page_byuser . '?author_id=' . $row['user_id'] . '" target="contents">' . $enc_user . '</a>';  
        if ($row['status'] == 2 ) {

            if ($row['level'] == 0) {
                $line = '&nbsp;<img border=0 src="images/bs.gif" alt="*"> <I><font color="gray"><del>This message has been deleted</del></font></I> ';
            } else {
                $line = '&nbsp;<img border=0 src="images/dc.gif" alt="*"> <I><font color="gray"><del>This message has been deleted</del></font></I> ';
            }
        } else {
            if ($row['level'] == 0) {
                $line = '<div id="div_mes_' . $row['msg_id'] . '" onclick="this.style.display=\'none\'" class="show_message"></div><a href="#" onclick="show_message(\'' . $row['msg_id'] . '\'); return false;" class="index"><b>' . $row["username"] . ': </b>' . $subj . '</a></dd>';
                #$line = '&nbsp;<a id="' . $row['msg_id'] . '" onClick="SelectAll(\'' . $row['msg_id'] . '\');"  name="' . $row['msg_id'] . '" target="' . detect_mobile() . '" href="' . $root_dir . $page_msg . '?id=' . $row['msg_id'] . '"><img border=0 src="images/bs.gif" width=16 height=16 alt="*"> ' . $b_start . $subj . $b_end . '  </a> ';
            } else {
                $line = '<div id="div_mes_' . $row['msg_id'] . '" onclick="this.style.display=\'none\'" class="show_message"></div><a href="#" onclick="show_message(\'' . $row['msg_id'] . '\'); return false;" class="index"><b>' . $row["username"] . ': </b>' . $subj . '</a></dd>';
                #$line = '&nbsp;<a id="' . $row['msg_id'] . '" onClick="SelectAll(\'' . $row['msg_id'] . '\');"  name="' . $row['msg_id'] . '" target="' . detect_mobile() . '" href="' . $root_dir . $page_msg . '?id=' . $row['msg_id'] . '"><img border=0 src="images/dc.gif" width=16 height=16 alt="*"> ' . $subj . '  </a> ';
            }
        }
        #$line .= '<b>' . $enc_user . '</b>' . $auth_text .  ' ' . $row['created'] . ' <b>' . $length . ' b</b>/' . $row['views'] . ' views';
        if ( !strcmp( $cur_page, $page_collapsed ) ) {
            $line .= ' <font color="gray">[ <a href="' . $root_dir . $page_topthread . '?thread=' . $row['thread_id'] . '&page=' . $page . '" target="contents">+' . $row['counter'] . '</a> ] </font>    ';
        }
        $msgs[$row['msg_id']] = $line;
        if ($row['level'] == 0) {
            $content[$row['msg_id']] = &$armass[$l];
            $glob[$row['msg_id']] = &$armass[$l];
        } else {
            $cur_content = &$glob[$row['parent']];
            $cur_content[$row['msg_id']] = &$armass[$l];
            $glob[$row['msg_id']] = &$armass[$l];
        }

        $l++;
    } 
    return $msgs;
}

function bbcode_format($str){
   // Convert all special HTML characters into entities to display literally
   // $str = htmlentities($str);
   // The array of regex patterns to look for
   $format_search =  array(
      '#\[b\](.*?)\[/b\]#is', // Bold ([b]text[/b]
      '#\[i\](.*?)\[/i\]#is', // Italics ([i]text[/i]
      '#\[u\](.*?)\[/u\]#is', // Underline ([u]text[/u])
      '#\[s\](.*?)\[/s\]#is', // Strikethrough ([s]text[/s])
      '#\[code\](.*?)\[/code\]#is', // Monospaced code [code]text[/code])
      '#\[size=([1-9]|1[0-9]|20)\](.*?)\[/size\]#is', // Font size 1-20px [size=20]text[/size])
      '#\[color=(.*?)\](.*?)\[/color\]#is', // Font color ([color=#00F]text[/color]) or Font color ([color={color_name}]text[/color])
//    '#\[color=\#?([A-F0-9]{3}|[A-F0-9]{6})\](.*?)\[/color\]#is', // Font color ([color=#00F]text[/color])
      '#\[url=((?:ftp|https?)://.*?)\](.*?)\[/url\]#is', // Hyperlink with descriptive text ([url=http://url]text[/url])
      '#\[url\]((?:ftp|https?)://.*?)\[/url\]#i', // Hyperlink ([url]http://url[/url]),
      '#\[img=(https?://\S*?)\]#i', // Image ([img=http://url_to_image[/img])
	  '#((?:ftp|https?)://[^\s>"]+\.(?:jpg|jpeg|gif|png|bmp)(?:\?[^\s<"]*)?)(?=[^"]*(?:"[^"]*"[^"]*)*$)#is', // unprocessed images (i.e. without quotes around them)
      '@[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/](?=[^"]*(?:"[^"]*"[^"]*)*$)@is' // unprocessed URLs (i.e. without quotes around them)
   );
   
   // The matching array of strings to replace matches with
   $format_replace = array(
      '<strong>$1</strong>',
      '<em>$1</em>',
      '<span style="text-decoration: underline;">$1</span>',
      '<span style="text-decoration: line-through;">$1</span>',
      '<pre>$1</'.'pre>',
      '<span style="font-size: $1px;">$2</span>',
      '<span style="color: $1;">$2</span>',
      '<a target="_blank" href="$1">$2</a>',
      '<a target="_blank" href="$1">$1</a>',
      '<img src="$1" alt=""/>',
      '<img src="$1" alt=""/>',
      '<a target="_blank" href="\\0">\\0</a>'
   );
   // Perform the actual conversion
   $str = preg_replace($format_search, $format_replace, $str);
   // Convert line breaks in the <br /> tag
   // $str = nl2br($str);
  
   // Deal with quotes
   $format_search =  array(
      '#\[quote(?!.*\[quote)\](.*?)\[/quote\]#is', // Quote ([quote]text[/quote])
	  '#\[quote(?!.*\[quote)=(.*?)\](.*?)\[/quote\]#is', // Quote with author ([quote=author]text[/quote])
   );
   
   // The matching array of strings to replace matches with
   $format_replace = array(
      '<blockquote><div>$1</div></blockquote>',
      '<blockquote><div><cite>$1:</cite>$2</div></blockquote>',
   );
   
   // Perform the actual quotes conversion
   $count = 1;
   while ($count > 0) {
     $str = preg_replace($format_search, $format_replace, $str, -1, $count);
   }
   
   return $str;
}

function can_edit_post($msg_author, $msg_time, $current_user) {
	
   $time = strtotime($msg_time); // time in EST
   $curtime = time();

   $diff = $curtime-$time;
   print('Author=' . $msg_author . ' current user=' . $current_user . ' diff=' . strval($diff) . ' time=' . $time .''); 
	
   return !strcmp($msg_author, $current_user) || true; // 5 min
}

function sendmail($address, $subj, $body) {
	$body = str_replace("\n.", "\n..", $body);
	$body = wordwrap($body, 70, "\r\n");
	$headers = 'From: kirdyk.forum@gmail.com' . "\r\n" .
    'Reply-To: do_not_reply@kirdyk.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
	
	$result = mail ( $address, $subject, $body, $headers);
	
	return $result;
}

function youtube($body, $embed = true) {
  global $host;
	$result = preg_replace_callback('#(?<!\[url(=|]))((?:https?://)?(?:www\.)?(?:youtu\.be/|youtube\.com/(?:embed|v|watch\?v=))([\w-]{10,12})(?:(?:\?|&)[^\s<\]"]*)?)#i',
		function ($matches) use ($embed, $host) {
      $url = $matches[2];
			$id  = $matches[3];
			$obj2 = file_get_contents(
"https://www.googleapis.com/youtube/v3/videos?part=contentDetails,snippet&id=" . $id . "&key=AIzaSyAMBQ3QfviQCDu8G1jeLlPsex16hhbw9jI&fields=pageInfo(totalResults),items(id,contentDetails/duration,snippet(title,thumbnails/default))");
			$ar2 = json_decode($obj2);
			/*
			 1. check pageInfo.totalResults (== 1)
			 2. .items[0].contentDetails.duration
			 3. .items[0].snippet.title
			 4. .items[0].snippet.thumbnails.default
			*/
			// var_dump($ar2);         			 
			$new_body = $url;
			if ($ar2->pageInfo->totalResults == 1) {
        $duration = '';
				$di = new DateInterval($ar2->items[0]->contentDetails->duration);
				if ($di->h > 0) {
          $duration .= $di->h.':';
				}
				$duration .= $di->i . ':' . $di->s;
				$title = $ar2->items[0]->snippet->title;
        if ($embed) {
          $new_body = '[iframe id="youtube" type="text/html" width="480" height="320" src="http://www.youtube.com/embed/' . $id . '?enablejsapi=1' . '&origin=http://' . $host . '" frameborder="0"]';
          $new_body .= "\n[i][color=lightslategrey][url=".$url. "][b]" . $title . "[/b]; " . $duration . "[/url][/color][/i] ";
       } else {
          $thumbnail = $ar2->items[0]->snippet->thumbnails->{'default'}->url;
          $new_body .= "\n[i][color=lightslategrey]( " . "[b]" . $title . "[/b]; " . $duration . ")[/color][/i] ";
          $new_body .= "\n[img=" . $thumbnail . "]";
        }
			}
			return $new_body;
		},
		$body
	);
  
	return rutube($result);
}

function rutube($body, $embed = true) {
	$result = preg_replace_callback('#(?<!\[url(=|]))((?:https?://)?(?:www\.)?rutube\.ru/video/([\w-]{10,32})/(?:(?:\?|&)[^\s<\]"]*)?)#i',
		function ($matches) use ($embed) {
      
      $url = $matches[2];
			$id  = $matches[3];
			$obj2 = file_get_contents("http://www.rutube.ru/api/video/" . $id . "/");
			//var_dump($obj2);
      
			$ar2 = json_decode($obj2);       
			$new_body = $url;
      
			if ( $ar2 !== false ) {
        // calculate duration
        $di = intval($ar2->duration);
        $duration = '';
        if (((int)($di / 3600)) > 0) {
          $duration .= ((int)($di / 3600)).':';
        }
        $di %= 3600;
        $duration .= ((int)($di / 60)) . ':' . ($di % 60);
        $title = $ar2->title;
        
        if ($embed) {
          $new_body = preg_replace(array('#<iframe (.*)></iframe>#i', '#width="([0-9]*)"#i', '#height="([0-9]*)"#i'), array('[iframe $1]','width="480"','height="320"'), $ar2->html);
          $new_body .= "\n[i][color=lightslategrey][url=".$url. "][b]" . $title . "[/b]; " . $duration . "[/url][/color][/i] ";
        } else {
          $thumbnail = $ar2->thumbnail_url;
          $new_body .= "\n[i][color=lightslategrey]( " . "[b]" . $title . "[/b]; " . $duration . ")[/color][/i] ";
          $new_body .= "\n[img=" . $thumbnail . "]";
        }
        
			 }
			 return $new_body;
			},
			$body
		);
		
	return $result;
}

// Draft
function vimeo($body, $embed = true) {
  return $body;
}

// Returns random Chuck Norris fact in $percentage cases
function chuck($percentage) {
 if (rand(0, 100) < $percentage) {
	 $obj2 = file_get_contents("http://api.icndb.com/jokes/random?exclude=[explicit]");
	 //var_dump($obj2);                     			 
	 $ar2 = json_decode($obj2);
	 if ( $ar2 !== false && $ar2->type == "success") {
		return $ar2->value->joke;
	 }
 }
 return "";
}

/** 
 * Replaces target for URLs that reference messages of this forum
 */
function fix_msg_target($body) {
  global $host;
  return str_replace('<a target="_blank" href="http://'.$host.'/msg.php?id=', '<a target="bottom" href="http://'.$host.'/msg.php?id=', $body);
}


/** 
 * Run this before bbcode is called to render content before bbcode() had a chance to mess it up
 */
function before_bbcode($body) {
  
  $body = preg_replace( array (
    // search
    '#(?<!\[url(=|]))((?:https?://)?(?:www\.)?vimeo\.com/([0-9]*)(?:(?:\?|&)[^\s<\]"]*)?)#is' // Vimeo on-the-fly e.g. https://vimeo.com/129252030
    ), array (
    // replace
    '<br/><iframe src="https://player.vimeo.com/video/$3" width="500" height="281" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe><br/><a href="$2">$2</a>'
    ), $body);    
       
  return $body;
}

/** 
 * Run this after bbcode is called to finalize the rendering of the message body 
 */
function after_bbcode($body) {
  
  $body = preg_replace( array (
    // search
    '#\[iframe (.*)\]#i',
    '#\[b\](.*?)\[/b\]#is', // Bold ([b]text[/b]
    '#\[i\](.*?)\[/i\]#is', // Italics ([i]text[/i]
    '#\[u\](.*?)\[/u\]#is', // Underline ([u]text[/u])
    '#\[s\](.*?)\[/s\]#is'  // Strikethrough ([s]text[/s])
    ), array (
    // replace
    '<iframe $1></iframe>',
    '<strong>$1</strong>',
    '<em>$1</em>',
    '<span style="text-decoration: underline;">$1</span>',
    '<span style="text-decoration: line-through;">$1</span>'
    ), $body);    
       
  return fix_msg_target($body);
}

