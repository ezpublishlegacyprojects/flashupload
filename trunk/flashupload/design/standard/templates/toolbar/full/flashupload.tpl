{def $nav=$navigation_part.identifier}

{section show=and( ne( $ui_context, 'edit' ), ne( $ui_context, 'browse' ), or( eq( $nav, 'ezcontentnavigationpart' ), eq( $nav, 'ezmedianavigationpart' ), eq( $nav, 'ezusernavigationpart' ) ) ) }

<div id="toolbar_flashupload">

<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr">{section show=$first}<div class="box-tl"><div class="box-tr">{/section}

{section show=ezpreference( 'flashupload' )}

     <h4><a class="showhide" href={'/user/preferences/set/flashupload/0'|ezurl} title="{'Hide Flashupload.'|i18n( 'flashupload' )}"><span class="bracket">[</span>-<span class="bracket">]</span></a> {'Flashupload'|i18n( 'design/admin/pagelayout' )}</h4>

</div></div></div></div>{section show=$first}</div></div>{/section}

{section show=$last}
<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content">
{section-else}
<div class="box-ml"><div class="box-mr"><div class="box-content">
{/section}

<div class="block">
{include uri="design:flashupload/flashupload.tpl"
		 css_file="toolbarupload.css"
		 js_file="toolbarhandlers.js"
		 flash_element="flashUI1"
		 degraded_element="degradedUI1"
		 progress_element="fsUploadProgress1"
		 upload_on_queue="true" }

	<div id="flashUI1" style="display: none;">
		<form id="flashuploadform" action="/flashupload/upload/{$module_result.content_info.node_id}" method="post" enctype="multipart/form-data">
			<fieldset class="flash" id="fsUploadProgress1">
			</fieldset>
			<div>
				<input id="btnUpload" class="button" type="button" value="{"Upload file(s)"|i18n("flashupload")}" onclick="flashupload.Browse()" />
				<input id="btnCancel1" type="button" value="{"Cancel Uploads"|i18n("flashupload")}" onclick="flashupload.CancelQueue();" disabled="disabled" /><br />
			</div>
		</div>
		<div id="degradedUI1">
			<fieldset>
				<input type="file" name="Filedata" size="4" /><br/>
				<input type="hidden" name="RedirectURL" size="{$uri_string}" />
			</fieldset>
			<div>
				<input class="button" type="submit" value="{"Upload File"|i18n("flashupload")}" />
			</div>
		</form>
	</div>

</div>

</div></div></div>{section show=$last}</div></div></div>{/section}

{section-else}
    <h4><a class="showhide" href={'/user/preferences/set/flashupload/1'|ezurl} title="{'Show Flashupload.'|i18n( 'flashupload' )}"><span class="bracket">[</span>+<span class="bracket">]</span></a> {'Flashupload'|i18n( 'flashupload' )}</h4>

    
</div></div></div></div>{section show=$first}</div></div>{/section}

{section show=$last}
<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content">
</div></div></div></div></div></div>
{/section}

{/section}                       



</div>

{section-else}

{section show=$last}
<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content">
</div></div></div></div></div></div>
{/section}

{/section}