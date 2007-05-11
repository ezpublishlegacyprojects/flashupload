	{if is_set($css_file)|not }
		{def $css_file='toolbarupload.css' }
	{/if}
	{if is_set($js_file)|not }
		{def $js_file='toolbarhandlers.js' }
	{/if}
	{if is_set($flash_element)|not }
		{def $flash_element='flashUI' }
	{/if}
	{if is_set($upload_on_queue)|not }
		{def $upload_on_queue='true' }
	{/if}
	{if is_set($debug)|not }
		{def $debug=ezini( 'FileUploadSettings', 'Debug', 'flashupload.ini' ) }
	{/if}

	{def $classes=array()
		 $type_array=array()
		 $file_types='*.*'
		 $file_types_description="'All Files'|i18n('flashupload')"
		 $first=true() }
	
	{if ezini( 'FileUploadSettings', 'FileTypesFromClass', 'flashupload.ini' )|eq('true') }
		{foreach ezcreateclasslistgroups( $content_object.can_create_class_list ) as $group}
			{foreach $group.items as $class}
				{if ezini( 'WebsiteToolbarSettings', 'HiddenContentClasses', 'websitetoolbar.ini' )|contains( $class.identifier )}
					{continue}
				{else}
				{set $classes=$classes|append($class.identifier) }
				{/if}
			{/foreach}
		{/foreach}
		{foreach $classes as $class }
			{if ezini( concat($class, '_Settings'), 'file_types', 'flashupload.ini',,,'hasVariable' ) }
				{if eq( ezini( concat($class, '_Settings'), 'file_types', 'flashupload.ini' ), '*.*' ) }
					{set $file_types='*.*' }
					{set $file_types_description='All Files' }
					{break}
				{else}
					{if $first|eq(true) }
						{set $file_types=ezini( concat($class, '_Settings'), 'file_types', 'flashupload.ini' ) }
						{set $file_types_description=ezini( concat($class, '_Settings'), 'file_types_description', 'flashupload.ini' ) }
						{set $first=false() }
					{else}
						{set $file_types=concat($file_types, ';', ezini( concat($class, '_Settings'), 'file_types', 'flashupload.ini' ) ) }
						{set $file_types_description=concat($file_types_description, ',', ezini( concat($class, '_Settings'), 'file_types_description', 'flashupload.ini' ) ) }
					{/if}
						
				{/if}
			{/if}
		{/foreach}
	{/if}


	<style type="text/css">
		@import url({concat('stylesheets/',$css_file)|ezdesign});
	</style>
	{run-once}
	<script type="text/javascript" src={'javascript/swfuploadr5.js'|ezdesign}></script>
	<!--[if IE]><script defer="defer" src={'javascript/ie_onload.js'|ezdesign}></script><![endif]-->	
	{/run-once}
	<script type="text/javascript" src={concat('javascript/',$js_file)|ezdesign}></script>
	
	<script type="text/javascript">
		var toolbarupload;
		var org_onload = window.onload;
		
		function initUploadToolbar() {ldelim}
			// quit if this function has already been called
			if (arguments.callee.done) return;
		
			// flag this function so we don't do the same thing twice
			arguments.callee.done = true;

			if (typeof(org_onload) == "function") {ldelim}
				org_onload();
			{rdelim}
			if (typeof(SWFUpload) == "undefined") return;
			
			SWFUpload.UI_PENDING = "{"Pending..."|i18n("flashupload")}";
			SWFUpload.UI_UPLOADING = "{"Uploading..."|i18n("flashupload")}";
			SWFUpload.UI_COMPLETE = "{"Complete!"|i18n("flashupload")}";
			SWFUpload.UI_CANCELLING = "{"Cancelling..."|i18n("flashupload")}";
			
			SWFUpload.UI_UPLOAD_ERROR = "{"Upload Error"|i18n("flashupload")}";
			SWFUpload.UI_CONFIGURATION_ERROR = "{"Configuration Error"|i18n("flashupload")}";
			SWFUpload.UI_UPLOAD_FAILED = "{"Upload Failed."|i18n("flashupload")}";
			SWFUpload.UI_IO_ERROR = "{"Server (IO) Error"|i18n("flashupload")}";
			SWFUpload.UI_SECURITY_ERROR = "{"Security Error"|i18n("flashupload")}";
			SWFUpload.UI_FILE_EXCEEDS_SIZE_LIMIT = "{"File is too big"|i18n("flashupload")}";
			SWFUpload.UI_ZERO_BYTE_FILE = "{"Zero Byte file"|i18n("flashupload")}";
			SWFUpload.UI_UPLOAD_LIMIT_EXCEEDED = "{"Upload limit exceeded"|i18n("flashupload")}";
			SWFUpload.UI_UNKNOWN_ERROR = "{"Unknown Error"|i18n("flashupload")}";
			
			SWFUpload.UI_PERMISSION_DENIED = "{"Permission denied"|i18n("flashupload")}";
			SWFUpload.UI_NO_HTTP_FILE = "{"No HTTP file found"|i18n("flashupload")}";
			SWFUpload.UI_BAD_UPLOAD_HANDLER = "{"Bad upload handler"|i18n("flashupload")}";
			SWFUpload.UI_NO_CLASS_FOUND = "{"No class found"|i18n("flashupload")}";
			SWFUpload.UI_BAD_UPLOAD_POSITION = "{"Bad upload position"|i18n("flashupload")}";
			SWFUpload.UI_NO_FILE_ATTRIBUTE = "{"No file attribute found"|i18n("flashupload")}";
			SWFUpload.UI_NO_FILE_FOUND = "{"No file found"|i18n("flashupload")}";
			
			SWFUpload.fuReloadOnQueueComplete = {ezini( 'FileUploadSettings', 'ReloadOnQueueComplete', 'flashupload.ini' )};

			flashupload = new SWFUpload({ldelim}
				// Backend Settings
				upload_target_url: "http://{ezsys('hostname')}/flashupload/upload/{$module_result.content_info.node_id}",
				upload_cookies: "{session_name()}",

				// File Upload Settings
				file_size_limit : "{ezini('FileUploadSettings', 'file_size_limit', 'flashupload.ini')}",
				file_types : "{$file_types}",
				file_types_description : "{$file_types_description}",
				file_upload_limit : "{ezini('FileUploadSettings', 'file_upload_limit', 'flashupload.ini')}",
				begin_upload_on_queue : {$upload_on_queue},

				// Event Handler Settings
				file_queued_handler : uploadStart,
				file_progress_handler : uploadProgress,
				file_cancelled_handler : uploadCancel,
				file_complete_handler : uploadComplete,
				queue_complete_handler : uploadQueueComplete,
				error_handler : uploadError,

				// Flash Settings
				flash_url : {"flash/swfuploadr5.swf"|ezdesign},

				// UI Settings
				ui_container_element : "{$flash_element}",
				{if is_set($degraded_element) }
				degraded_container_element : "{$degraded_element}",
				{/if}
				// Debug Settings
				debug: {$debug}
			{rdelim});
			{if is_set($progress_element) }
			flashupload.AddSetting("progress_target", "{$progress_element}");
			{/if}
		{rdelim}
		
		// moz style
		if (document.addEventListener) {ldelim}
			document.addEventListener("DOMContentLoaded", initUploadToolbar, false);
		{rdelim}
		
		window.onload = initUploadToolbar;

	</script>