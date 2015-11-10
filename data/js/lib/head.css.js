if(!head) {
	head = {};
}
var filesadded=""
head.css = function(filename) {
	if (filesadded.indexOf("["+filename+"]")==-1){
		var fileref=document.createElement("link");
		fileref.setAttribute("rel", "stylesheet");
		fileref.setAttribute("type", "text/css");
		fileref.setAttribute("href", filename);
		document.getElementsByTagName("head")[0].appendChild(fileref);
		filesadded+="["+filename+"]";
	}
};