<?php
/*$Id: head_inc.php 874 2013-01-30 17:01:15Z dmitriy $*/
require_once('settings.php');
$ip          = NULL;
$agent       = NULL;
$auth_cookie = NULL;
$user        = NULL;
$auth        = NULL;
$ban         = false;
$logout      = NULL;
$err         = '';
$user_id     = NULL;
$body        = NULL;
$re          = NULL;
$ban_ends    = NULL;
$pass        = NULL;
$ban_time    = NULL;
$log         = '';
$page        = 1;
$moder       = false;
$logged_in   = false;
$user_id     = NULL;
$doc_type   = NULL;
$user_like = NULL;
$err_login  = NULL;
$prefix     = NULL;
$how_many   = NULL;
$last_id    = NULL;
$views      = NULL;
$in_response = NULL;
$translit_done  = NULL;
$preview        = NULL;
$subj           = NULL;
$password       = NULL;
$email2         = NULL;
$email          = NULL;
$auth_text      = NULL;
$last_answered_id   = NULL;
$byip           = NULL;
$star           = NULL;
$err_ban_reason = NULL;
$ban_reason     = NULL;
$author         = NULL;
$page_dosearch  = NULL;
$out            = NULL;
$prop_bold      = NULL;
$profile_bold   = NULL;
$author_id      = NULL;
$auth_id        = NULL;
$prop_tz        = NULL;
$item_name     = NULL;
$pay_mode       = NULL;
$pay_moder      = NULL;
$pay_victim     = NULL;
$custom         = NULL;
$reply_closed   = false;
$thread_closed = false;
$post_closed   = false;
$ticket = '';
$pm_id = NULL;
$content_flags = 0;
$youtube_img = 'images/youtube12x12w.png';
$image_img = 'images/camera12x12w.png';
$action = NULL;
$likes = 0;
$dislikes = 0;
$n_ff = NULL;
$howmanylikes = NULL;
$likedby = NULL;



//$title       = 'Forum';
$page_title = $title;
//$root_dir    = '/kirdyk/';
$page_expanded = 'top.php';
$page_collapsed = 'collapsed.php';
$page_bydate = 'bydate.php';
$page_reg    = 'reg.php';
$page_welc   = 'welc.php';
$page_new    = 'new.php';
$page_msg    = 'msg.php';
$page_msg_inc = 'msg_inc.php';
$page_new_inc = 'new_inc.php';
$page_my_bookmarks = 'mybookmarks.php';
$page_m_users ='modusers.php';
$page_m_threads ='modthreads.php';
$page_hidden_threads = 'hidthreads.php';
$page_my_messages = 'mymessages.php';
$page_pmail_send = 'send.php';
$page_pmail_sent = 'sent.php';
$page_pmail = 'pmail.php';
$page_search = 'search.php';
$page_forgot = 'forgot.php';
$page_m_user = 'moduser.php';
$page_ip = 'ips.php';
$page_new_user = 'new_user.php';
$page_activate = 'activate.php';
$page_forgot_action = 'newpassword.php';
$page_login = 'login.php';
$page_profile = 'profile.php';
$page_update = 'update.php';
$page_post = 'post.php';
$page_confirm = 'confirm.php';
$page_thread = 'thread.php';
$page_pm = 'pm.php';
$page_pm_confirm = 'pm_confirm.php';
$page_msg_pm = 'pm_msg.php';
$page_pm_del = 'pm_del.php';
$page_byuser = 'byuser.php';
$page_m_censposts = 'modcensposts.php';
$page_m_delposts = 'moddelposts.php';
$page_m_ips = 'modips.php';
$page_ban = 'modban.php';
$page_do_search = 'dosearch.php';
$page_topthread = 'topthread.php';
$page_golo = 'golo_list.php';
$page_golo_create = 'golo_new.php';
$page_answered = 'answered.php';
$page_banned = 'banned.php';
$page_pay_ban = 'pay_ban.php';
$page_my_bookmarhs='mybookmarks.php';








// Redirect if URL is not correct

if ( isset( $_SERVER['HTTP_HOST'] ) && strcmp( $_SERVER['HTTP_HOST'], $host ) ) {
    print( "<HTML><BODY><A target=\"_blank\" href=\"http://" . $host . $root_dir . "\">Wrong URL" . $_SERVER['HTTP_HOST'] . ". Please, click here.</A></BODY></HTML>" );
    die();
}

require_once('func.php');
require_once('mysql_log.php');

// Connecting, selecting database
  $link = mysql_connect($dbhost, $dbuser, $dbpassword);
  if (!$link) {
    mysql_log(__FILE__, 'Could not connect: ' . mysql_error());
    die('Could not connect to database');
  }
  if (!mysql_select_db($dbname)) {
    mysql_log(__FILE__, 'Could not select database ' . mysql_error());
    die('Could not select database');
  }
require_once('get_params_inc.php');
require_once('auth.php');

?>

