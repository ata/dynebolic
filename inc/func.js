function makeMsg(uid){
	var win = window.open('index.php?page=message&uid='+uid,'',
		'width=350,height=480,scrollbars=no,resizable=no,status=yes,toolbar=no,location=no'
	);
	return win;
}
