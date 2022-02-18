<?
include "settings.php";
$_s['comments']=1;
$_s['ntobr']=1;
$_s['ppp']=1000;
$_s['tmset']=0;
$_l['anonym']="Гость";
$_l['commadd']="<br><center><b>Комментарий добавлен!</b></center>";
$_s['ver']="Beta 1.0.0";
include "sess.php";
$_v=$_c=array();
foreach($_GET as $k=>$v)$_v[$k]=$v;
foreach($_POST as $k=>$v)$_v[$k]=$v;
foreach($_COOKIE as $k=>$v)$_c[$k]=$v;$pages_link=$post['title']="";
if(get_magic_quotes_gpc()){
	foreach($_v as $k=>$v)$_v[$k]=stripslashes($v);
}
function fsave($fle,$mod,$txt){
	$fh=fopen($fle,$mod);flock($fh,LOCK_EX);
	fwrite($fh,$txt);
	flock($fh,LOCK_UN);
	fclose($fh);
	@chmod($fle,0777);
}
function ginfo($ide){
	global $post,$_s;
	$fp=fopen("data/".$ide,"r");
	$Data=fread($fp,filesize("data/".$ide));
	fclose($fp);$inon=explode("[comments]",$Data);
	list($post['title'],$post['raw'])=explode("\n",$Data,2);
	list($post['title'],$post['text'])=explode("\n",$inon[0],2);
	$post['id']=$ide+$_s['tmset'];
	$post['date']=date("d.m.Y, H:i",$ide+$_s['tmset']*3600);
	@$post['commts']=explode("\n",$inon[1]);
	@$post['comtn']=sizeof($post['commts']);
	if(is_admin()){
		$post['edit']="<a href='index.php?p=".$post['id']."&act=nedit'>Изменить</a>";
		$post['del']="<a href='index.php?p=".$post['id']."&act=del'>Удалить</a>";
	}
	return $post;
}
function redirect($u){
	print"<meta http-equiv='refresh' content='2;url=".$u."'> <center><b>Ждите пару сек, ща все будет!</b><br>[ <a href='$u'>если перенаправление не работает, нажмите здесь</a> ]</center>";
}
function is_admin(){
	global $_s,$_c;
	if(@isset($_c['sid'])&&@$_c['sid']==@$_s['sid'])return 1;
	return 0;
}
function ntobr(){
	global $post;
	$post['text']=str_replace(array("\r","\n"),array("","\r"),$post['text']);
	$post['text']=str_replace("\r","\n",$post['text']);
	$post['text']=str_replace("\n","<br>",$post['text']);
	return $post;
}
if(isset($_v['pass'])&&$_v['pass']==$_s['pass']){
	$sid=md5(uniqid(""));
	setcookie("sid",$sid,time()+604800);
	fsave("sess.php","w+",'<? $_s["sid"] = "'.$sid.'"; ?>');
	header("Location: ".$_SERVER['PHP_SELF']);
	exit();
}
if(isset($_v['act'])&&$_v['act']=="login"){
	if(!is_admin()){
		include "tpl/head.html";
		print"<h2>Вход в систему</h2><center><form method=POST>Пароль: <input type=text placeholder='Пароль' name=pass> <button>Войти!</button></form></center>";
		include "tpl/foother.html";
		exit();
	}else{
		setcookie("sid","");header("Location: index.php");}
}
include "tpl/head.html";
if(isset($_v['act'])){
	switch($_v['act']){
			case "comm":if(isset($_v['p'])&&is_numeric($_v['p'])&&file_exists("data/".$_v['p'])){
				if($_s['comments']){if(@trim($_v['text'])){
					$text=substr($_v['text'],0,4096);
					$nick=substr($_v['nick'],0,24);
					$text=str_replace(array("\r","\n","<",">"),array("","\r","&lt;","&gt;"),$text);$text=str_replace("\r","\n",	$text);
					$text=str_replace("\n","<br>",$text);
					$text=str_replace("[comments]","",$text);
					$nick=strip_tags($nick);
					if(preg_match("[comments]",file_get_contents("data/".$_v['p']))){
						fsave("data/".$_v['p'],"a+",$nick.">>".$text."\n");
					}else{
						fsave("data/".$_v['p'],"a+","\n[comments]\n".$nick.">>".$text."\n");
					}
						print$_l['commadd'];
						redirect("index.php?p=".$_v['p']);
					}
				}
			}
		break;
		case "del":if(is_admin()){
			@unlink("data/".$_v['p']);
			redirect($_SERVER['PHP_SELF']);
		} else {
			print"Извините! Но, вы не можете просмотревать этот контент. Пожалуйста, авторизуйтесь.";
		}
		case "nedit":if(is_admin()){
			if(!isset($_v['title'])||!isset($_v['text'])){
				if(isset($_v['p'])){ginfo($_v['p']);$_v['p']=$post['id'];$post['text']=str_replace(array("\r","\n"),array("",	"\r"),$post['raw']);
				$post['text']=str_replace("\r","\n",$post['raw']);
				$post['text']=htmlspecialchars($post['raw'],ENT_QUOTES);
				include "tpl/post_form.html";
				}else{
					$_v['p']=time();
					include "tpl/post_form.html";
				}
			}else{
				$p=time();
				fsave("data/".$_v['p'],"w+",$_v['title']."\n".$_v['text']);
				redirect("index.php?p=".$_v['p']);
			}
		} else {
			print"Извините! Но, вы не можете просмотревать этот контент. Пожалуйста, авторизуйтесь.";
		}
	}
}
if(!isset($_v['act'])&&!isset($_v['p'])){
	$d=dir("data");
	while(false!==($entry=$d->read()))
		if(is_numeric($entry)){
			$posts[]=$entry;
		}elseif($entry!="."&&$entry!=".."){
			@$newn=getlastmod("data/".$entry);
			@rename("data/".$entry,"data/".$newn);
			chmod("data/".$newn,0777);
			$posts[]=$newn;
		}
		$d->close();
		if(isset($posts)&&sizeof($posts)>0){
			$pages=sizeof($posts)/$_s['ppp'];
			$skip=(isset($_v['skip'])&&is_numeric($_v['skip'])?$_v['skip']:0);
			if(sizeof($posts)>$_s['ppp'])$posts=array_slice($posts,$skip,$_s['ppp']);
			rsort($posts);
			for($i=0;$i<sizeof($posts);$i++){
				ginfo($posts[$i]);
				if($_s['ntobr'])ntobr();
				include "tpl/post.html";
			}
		if($pages>1){
			$j=1;
			while($j<=$pages){
				$pn=$j+1;if($j*$_s['ppp']==$skip){
					$pages_link.="<a href=?skip=".$j*$_s['ppp']."><strong>".$pn."</strong></a> ";
				}else{
					$pages_link.="<a href=?skip=".$j*$_s['ppp'].">".$pn."</a> ";
				}
				$j++;
			}
			$pages_link="<a href=index.php>1</a> ".$pages_link;
		}
	}
}
if(isset($_v['p'])&&!isset($_v['act'])&&is_numeric($_v['p'])&&file_exists("data/".$_v['p'])){
	ginfo($_v['p']);
	if($_s['ntobr']){
		ntobr();
	}
	include "tpl/post.html";
	print"<a name=cmt></a>";
	print"<br><font style='margin-left:12px;' size='+2'>Комментарии</font><br><br>";
	for($i=1;$i<($post['comtn']-1);$i++){
		list($cmnt['nick'],$cmnt['text'])=explode(">>",$post['commts'][$i]);
		$cmnt['nick']=(@trim($cmnt['nick'])?$cmnt['nick']:$_l['anonym']);
		include "tpl/comment.html";
	}
	if($_s['comments']){
		include "tpl/comment_form.html";
	}
}
if(isset($_v['act'])&&$_v['act']=="about"){
	include "tpl/about.html";
}
if(isset($_v['act'])&&$_v['act']=="settings"){
	if(!is_admin()){
		print"Извините! Но, вы не можете просмотревать этот контент. Пожалуйста, авторизуйтесь.";
	}else{
		include "tpl/settings.html";
	}
}
include "tpl/foother.html";
?>