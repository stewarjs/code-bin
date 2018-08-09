function togglePopup(target,self) {
	$.modal({title: document.getElementById(self).innerHTML, parentID: self, loadContent: location.href + ' #d'+target, footer: '<div class="button-group align--right"><button type="reset" class="button button--gray modal__close">Close</button>'});
}
