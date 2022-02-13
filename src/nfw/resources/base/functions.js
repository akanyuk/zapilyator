/* Foramt date and time from UNIX timestamp
 */
function formatDateTime (unixTimestamp, withTime, noSeconds) {
    var dt = new Date(unixTimestamp * 1000);

    var day = dt.getDate();
    var month = dt.getMonth() + 1;
    var year = dt.getFullYear();
    
    day = day < 10 ? '0' + day : day;
    month = month < 10 ? '0' + month : month;
    
    if (!withTime) return day + '.' + month + '.' + year;    	
    
    var hours = dt.getHours();
    var minutes = dt.getMinutes();
    var seconds = dt.getSeconds();

    hours = hours < 10 ? '0' + hours : hours;
    minutes = minutes < 10 ? '0' + minutes : minutes;
    seconds = seconds < 10 ? '0' + seconds : seconds;

    return noSeconds ? day + '.' + month + '.' + year + ' ' + hours + ':' + minutes : day + '.' + month + '.' + year + ' ' + hours + ':' + minutes + ':' + seconds;
}

function htmlspecialchars(str) {
	if (typeof(str) == "string") {
		str = str.replace(/&/g, "&amp;"); /* must do &amp; first */
		str = str.replace(/"/g, "&quot;");
		str = str.replace(/'/g, "&#039;");
		str = str.replace(/</g, "&lt;");
		str = str.replace(/>/g, "&gt;");
	}
	
	return str;
}

function number_format(number, decimals, dec_point, thousands_sep) {	// Format a number with grouped thousands
	// 
	// +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
	// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +	 bugfix by: Michael White (http://crestidg.com)
	var i, j, kw, kd, km, minus = '';

	if (number < 0) {
		minus = "-";
		number = number*-1;
	}


	// input sanitation & defaults
	if (isNaN(decimals = Math.abs(decimals)) ){
		decimals = 2;
	}
	if (dec_point == undefined ){
		dec_point = ".";
	}
	if (thousands_sep == undefined ){
		thousands_sep = " ";
	}

	i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

	if( (j = i.length) > 3 ){
		j = j % 3;
	} else{
		j = 0;
	}

	km = (j ? i.substr(0, j) + thousands_sep : "");
	kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
	//kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).slice(2) : "");
	kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");


	return minus + km + kw + kd;
}

function randomString(len) {
	var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
	var randomstring = '';
	for (var i=0; i<len; i++) {
		var rnum = Math.floor(Math.random() * chars.length);
		randomstring += chars.substring(rnum,rnum+1);
	}
	return randomstring;
}
