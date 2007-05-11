function uploadStart(fileObj) {
	try {
		// You might include code here that prevents the form from being submitted while the upload is in
		// progress.  Then you'll want to put code in the Queue Complete handler to "unblock" the form
		var progress = new FileProgress(fileObj, this.GetSetting("progress_target"));
		progress.SetStatus(SWFUpload.UI_PENDING);
		progress.ToggleCancel(true, this);
		
	} catch (e) { /*Console.Writeln("Upload started");*/ }
		
}

function uploadProgress(fileObj, bytesLoaded) {

	try {
		var percent = Math.ceil((bytesLoaded / fileObj.size) * 100)

		var progress = new FileProgress(fileObj, this.GetSetting("progress_target"));
		progress.SetProgress(percent);
		progress.SetStatus(SWFUpload.UI_UPLOADING);
	} catch (e) { /*Console.Writeln("Upload Progress: " + fileObj.name + " " + percent);*/ }
}

function uploadComplete(fileObj) {
	try {
		var progress = new FileProgress(fileObj, this.GetSetting("progress_target"));
		progress.SetComplete();
		progress.SetStatus(SWFUpload.UI_COMPLETE);
		progress.ToggleCancel(false);

	} catch (e) { /*Console.Writeln("Upload Complete: " + fileObj.name);*/ }
}

function uploadQueueComplete(fileObj) {
	try {
		if ( SWFUpload.fuReloadOnQueueComplete )
			window.location.href = window.location.href;
	} catch (e) { /* Console.Writeln("Queue Done"); */ }
}

function uploadDialogCancel() {
/*	try {
		Console.Writeln("Pressed Cancel");
	} catch (e) { Console.Writeln("Error displaying file cancel information"); }
*/
}

function uploadCancel(fileObj) {
	try {
		var progress = new FileProgress(fileObj, this.GetSetting("progress_target"));
		progress.SetCancelled();
		progress.SetStatus(SWFUpload.UI_CANCELLING);
		progress.ToggleCancel(false);

	}
	catch (e) {}
}

function uploadError(error_code, fileObj, message) {
	try {
		if (error_code == SWFUpload.ERROR_CODE_QUEUE_LIMIT_EXCEEDED) {
			alert("You have attempted to upload too many files.\nPlease select up to " + this.GetSetting("file_upload_limit"));
			return;
		}

		var progress = new FileProgress(fileObj, this.GetSetting("progress_target"));
		progress.SetError();
		progress.ToggleCancel(false);

		switch(error_code) {
			case SWFUpload.ERROR_CODE_HTTP_ERROR:
				switch (message) {
					case 410: progress.SetStatus(SWFUpload.UI_PERMISSION_DENIED); break;
					case 411: progress.SetStatus(SWFUpload.UI_NO_HTTP_FILE); break;
					case 412: progress.SetStatus(SWFUpload.UI_BAD_UPLOAD_HANDLER); break;
					case 413: progress.SetStatus(SWFUpload.UI_NO_CLASS_FOUND); break;
					case 414: progress.SetStatus(SWFUpload.UI_BAD_UPLOAD_POSITION); break;
					case 415: progress.SetStatus(SWFUpload.UI_NO_FILE_ATTRIBUTE); break;
					case 416: progress.SetStatus(SWFUpload.UI_NO_FILE_FOUND); break;
					default: progress.SetStatus(SWFUpload.UI_UPLOAD_FAILED);
				}
				if (this.debug) Console.Writeln("Error Code: HTTP Error, File name: " + file.name + ", Message: " + message);
				break;
			case SWFUpload.ERROR_CODE_MISSING_UPLOAD_TARGET:
				progress.SetStatus(SWFUpload.UI_CONFIGURATION_ERROR);
				if (this.debug) Console.Writeln("Error Code: No backend file, File name: " + file.name + ", Message: " + message);
				break;
			case SWFUpload.ERROR_CODE_UPLOAD_FAILED:
				progress.SetStatus(SWFUpload.UI_UPLOAD_FAILED);
				if (this.debug) Console.Writeln("Error Code: Upload Failed, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
				break;
			case SWFUpload.ERROR_CODE_IO_ERROR:
				progress.SetStatus(SWFUpload.UI_IO_ERROR);
				if (this.debug) Console.Writeln("Error Code: IO Error, File name: " + file.name + ", Message: " + message);
				break;
			case SWFUpload.ERROR_CODE_SECURITY_ERROR:
				progress.SetStatus(SWFUpload.UI_SECURITY_ERROR);
				if (this.debug) Console.Writeln("Error Code: Security Error, File name: " + file.name + ", Message: " + message);
				break;
			case SWFUpload.ERROR_CODE_FILE_EXCEEDS_SIZE_LIMIT:
				progress.SetStatus(SWFUpload.UI_FILE_EXCEEDS_SIZE_LIMIT);
				if (this.debug) Console.Writeln("Error Code: File too big, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
				break;
			case SWFUpload.ERROR_CODE_ZERO_BYTE_FILE:
				progress.SetStatus(SWFUpload.UI_ZERO_BYTE_FILE);
				if (this.debug) Console.Writeln("Error Code: Zero byte file, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
				break;
			case SWFUpload.ERROR_CODE_UPLOAD_LIMIT_EXCEEDED:
				progress.SetStatus(SWFUpload.UI_UPLOAD_LIMIT_EXCEEDED);
				if (this.debug) Console.Writeln("Error Code: Upload Limit Exceeded, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
				break;
			default:
				progress.SetStatus(SWFUpload.UI_UNKNOWN_ERROR);
				if (this.debug) Console.Writeln("Error Code: " + error_code + ", File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
				break;
		}
	} catch (e) {}
}


function FileProgress(fileObj, target_id) {
	this.file_progress_id = fileObj.id;
	
	this.opacity = 100;
	this.height = 0;
	
	this.fileProgressWrapper = document.getElementById(this.file_progress_id);
	if (!this.fileProgressWrapper) {
		this.fileProgressWrapper = document.createElement("div");
		this.fileProgressWrapper.className = "progressWrapper";
		this.fileProgressWrapper.id = this.file_progress_id;
		
		this.fileProgressElement = document.createElement("div");
		this.fileProgressElement.className = "progressContainer";
		
		var progressCancel = document.createElement("a");
		progressCancel.className = "progressCancel";
		progressCancel.href = "#";
		progressCancel.style.visibility = "hidden";
		progressCancel.appendChild(document.createTextNode(" "));
		
		var filename = fileObj.name;
		if ( filename.length > 25 )
		{
			var end = filename.substring(filename.length-12);
			filename = filename.substring(0, 13) + "..." + end;
		}
		
		var progressText = document.createElement("div");
		progressText.className = "progressName";
		progressText.appendChild(document.createTextNode(filename));
		
		var progressBar = document.createElement("div");
		progressBar.className = "progressBarInProgress";
		
		var progressStatus = document.createElement("div");
		progressStatus.className = "progressBarStatus";
		progressStatus.innerHTML = "&nbsp;";
		
		this.fileProgressElement.appendChild(progressCancel);
		this.fileProgressElement.appendChild(progressText);
		this.fileProgressElement.appendChild(progressStatus);
		this.fileProgressElement.appendChild(progressBar);
		
		this.fileProgressWrapper.appendChild(this.fileProgressElement);
		
		document.getElementById(target_id).appendChild(this.fileProgressWrapper);
	} else {
		this.fileProgressElement = this.fileProgressWrapper.firstChild;
	}

	this.height = this.fileProgressWrapper.offsetHeight;

}
FileProgress.prototype.SetProgress = function(percentage) {
	this.fileProgressElement.className = "progressContainer green";
	this.fileProgressElement.childNodes[3].className = "progressBarInProgress";
	this.fileProgressElement.childNodes[3].style.width = percentage + "%";
}
FileProgress.prototype.SetComplete = function() {
	this.fileProgressElement.className = "progressContainer blue";
	this.fileProgressElement.childNodes[3].className = "progressBarComplete";
	this.fileProgressElement.childNodes[3].style.width = "";

	var oSelf = this;
	setTimeout(function() { oSelf.Disappear(); }, 10000);
}
FileProgress.prototype.SetError = function() {
	this.fileProgressElement.className = "progressContainer red";
	this.fileProgressElement.childNodes[3].className = "progressBarError";
	this.fileProgressElement.childNodes[3].style.width = "";

	var oSelf = this;
	setTimeout(function() { oSelf.Disappear(); }, 5000);
}
FileProgress.prototype.SetCancelled = function() {
	this.fileProgressElement.className = "progressContainer";
	this.fileProgressElement.childNodes[3].className = "progressBarError";
	this.fileProgressElement.childNodes[3].style.width = "";

	var oSelf = this;
	setTimeout(function() { oSelf.Disappear(); }, 2000);
}
FileProgress.prototype.SetStatus = function(status) {
	this.fileProgressElement.childNodes[2].innerHTML = status;
}

FileProgress.prototype.ToggleCancel = function(show, upload_obj) {
	this.fileProgressElement.childNodes[0].style.visibility = show ? "visible" : "hidden";
	if (upload_obj) {
		var file_id = this.file_progress_id;
		this.fileProgressElement.childNodes[0].onclick = function() { upload_obj.CancelUpload(file_id); return false; };
	}
}

FileProgress.prototype.Disappear = function() {
	
	var reduce_opacity_by = 15;
	var reduce_height_by = 4;
	var rate = 30;	// 15 fps
	
	if (this.opacity > 0) {
		this.opacity -= reduce_opacity_by;
		if (this.opacity < 0) this.opacity = 0;
		
		if (this.fileProgressWrapper.filters) {
			try {
				this.fileProgressWrapper.filters.item("DXImageTransform.Microsoft.Alpha").opacity = this.opacity;
			} catch (e) { 
				// If it is not set initially, the browser will throw an error.  This will set it if it is not set yet.
				this.fileProgressWrapper.style.filter = 'progid:DXImageTransform.Microsoft.Alpha(opacity=' + this.opacity + ')';
			}
		} else {
			this.fileProgressWrapper.style.opacity = this.opacity / 100;
		}
	}
	
	if (this.height > 0) {
		this.height -= reduce_height_by;
		if (this.height < 0) this.height = 0;
	
		this.fileProgressWrapper.style.height = this.height + "px";
	}
	
	if (this.height > 0 || this.opacity > 0) {
		var oSelf = this;
		setTimeout(function() { oSelf.Disappear(); }, rate);
	} else {
		this.fileProgressWrapper.style.display = "none";
	}
}