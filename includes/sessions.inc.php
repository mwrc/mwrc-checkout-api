<!------- MWRC SESSION HANDLING -------->

<?php if(isset($_GET['mwrc_session_code']) && !$_COOKIE['mwrc_session_code_1_1']):?>
<script type="text/javascript">
<!--
	document.cookie="mwrc_session_code_1_1=<?php echo $_GET['mwrc_session_code'] ?>; path=/";
//-->
</script>
<?php endif; ?>

<?php if(empty($_COOKIE["mwrc_sync"]) || $_COOKIE["mwrc_sync"]!="yes"):
	$set_session_url="//leki.mwrc.net/set_session.php/". strtr(base64_encode(implode("", array_reverse(preg_split("//", gzcompress(serialize($_COOKIE)), -1, PREG_SPLIT_NO_EMPTY)))), array("+"=>"-", "/"=>"_", "="=>".")). "/cookie"; ?>

<script type="text/javascript" src="<?php echo $set_session_url ?>.js"></script>
<script type="text/javascript">
<!--
    if (typeof mwrc_cookie_name != 'undefined' && mwrc_cookie_name.length && mwrc_cookie_value.length) {document.cookie=mwrc_cookie_name+"="+mwrc_cookie_value+"; path=/"; location.reload(true);}
    document.cookie="mwrc_sync=yes; path=/";
    if (mwrc_cookie_redirect=="yes") { location.reload(true); }
    else if (mwrc_cookie_redirect=="location") { window.location.assign('<?php echo $set_session_url?>.php'); }
//-->
</script>

<?php endif; ?>

<!------- /MWRC SESSION HANDLING -------->