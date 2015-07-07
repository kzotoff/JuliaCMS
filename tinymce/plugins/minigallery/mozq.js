addElement = function(d) {
	if ((typeof d.tagName == 'undefined')
		|| (typeof d.target == 'undefined')
		) {
		return;
	}
	newElement = document.createElement(d.tagName);
	if (d.className) {
		newElement.className=d.className;
	}
	if (d.id) {
		newElement.id=d.id;
	}
	d.target.appendChild(newElement);
	return newElement;
}
