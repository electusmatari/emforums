function whiletypingSimulateClick(domelem)
{
	try {
		var evt = document.createEvent("MouseEvents");
		evt.initMouseEvent("click", true, true, window,0, 0, 0, 0, 0,false, false, false, false, 0, null);
		domelem.dispatchEvent(evt)
	} catch(er) {
		domelem.click(); //IE
	}
}
