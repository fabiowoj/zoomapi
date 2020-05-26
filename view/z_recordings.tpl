<div class="col-md-12">
  <div class="bs-callout bs-callout-warning" id="callout-btn-group-accessibility">
    <h4>{{ 'Recordings'|get_plugin_lang('ZoomApiPlugin') }}</h4>
    <br>
    <p><i class="fa fa-file" aria-hidden="true"></i>&nbsp; <strong>{{ 'Total_size'|get_plugin_lang('ZoomApiPlugin') }}:</strong> <em>{{ getRecordings.total_size }}</em></p>
    <p><i class="fa fa-files-o" aria-hidden="true"></i>&nbsp; <strong>{{ 'Total_files'|get_plugin_lang('ZoomApiPlugin') }}:</strong> <em>{{ getRecordings.recording_count }}</em></p>
    <p><i class="fa fa-share-square-o" aria-hidden="true"></i>&nbsp; <strong>{{ 'Share'|get_plugin_lang('ZoomApiPlugin') }}:</strong> <em><a href="{{ getRecordings.share_url }}" target="_new">{{ 'Share'|get_plugin_lang('ZoomApiPlugin') }}</a></em></p>



    {% for recordlist in getRecordings.recording_files %}
      <div class="panel panel-default">
        <div class="panel-heading">{{ 'File'|get_plugin_lang('ZoomApiPlugin') }}</div>
        <div class="panel-body">
          <p><i class="fa fa-calendar fa-fw" aria-hidden="true"></i>&nbsp; <strong>{{ 'start_datetime'|get_plugin_lang('ZoomApiPlugin') }}:</strong> <em>{{ recordlist.recording_start }}</em></p>
          <p><i class="fa fa-calendar fa-fw" aria-hidden="true"></i>&nbsp; <strong>{{ 'end_datetime'|get_plugin_lang('ZoomApiPlugin') }}:</strong> <em>{{ recordlist.recording_end }}</em></p>
          <p><i class="fa fa-file-o" aria-hidden="true"></i>&nbsp; <strong>{{ 'file_type'|get_plugin_lang('ZoomApiPlugin') }}:</strong> <em>{{ recordlist.file_type }}</em></p>
          <p><i class="fa fa-file" aria-hidden="true"></i>&nbsp; <strong>{{ 'file_size'|get_plugin_lang('ZoomApiPlugin') }}:</strong> <em>{{ recordlist.file_size }}</em></p>
          <p><i class="fa fa-play" aria-hidden="true"></i>&nbsp; <strong>{{ 'watch_record'|get_plugin_lang('ZoomApiPlugin') }}:</strong> <em><a href="{{ recordlist.play_url }}" class="btn btn-sm btn-primary" target="_new">{{ 'watch'|get_plugin_lang('ZoomApiPlugin') }}</a></em></p>
          <p><i class="fa fa-cloud-download" aria-hidden="true"></i>&nbsp; <strong>{{ 'download_record'|get_plugin_lang('ZoomApiPlugin') }}:</strong> <em><a href="{{ recordlist.download_url }}" class="btn btn-sm btn-primary" target="_new">{{ 'download'|get_plugin_lang('ZoomApiPlugin') }}</a></em></p>
          <p><i class="fa fa-file-o" aria-hidden="true"></i>&nbsp; <strong>{{ 'recording_type'|get_plugin_lang('ZoomApiPlugin') }}:</strong> <em>{{ recordlist.recording_type }}</em></p>
        </div>
      </div>
    {% endfor %}
  </div>
</div>