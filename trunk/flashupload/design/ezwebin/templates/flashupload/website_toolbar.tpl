{include uri="design:flashupload/flashupload.tpl"
		 css_file="ezwebinupload.css"
		 js_file="ezwebinhandlers.js"
		 flash_element="toolbarUI"
		 progress_element="toolbarUploadProgress"
		 upload_on_queue=true		 }

<span id="toolbarUI" style="display: none;" >
  <input type="image" src={"websitetoolbar/ezwt-icon-upload.gif"|ezimage} name="flashuploadButton" onclick="flashupload.Browse(); return false;" title="{'Upload files here'|i18n('flashupload')}" />
 </span>