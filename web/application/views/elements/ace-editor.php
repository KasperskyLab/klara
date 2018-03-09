<script src="<?php echo base_url("media/js/ace-min/ace.js")?>" type="text/javascript" charset="utf-8"></script>
<script>
    var ace_editor = ace.edit("yara_rules");
    ace_editor.setTheme("ace/theme/chrome");
    ace_editor.setHighlightActiveLine(false);
    // Session settings
    ace_editor.session.setMode("ace/mode/c_cpp");
    ace_editor.session.setUseSoftTabs(false);
    ace_editor.session.setTabSize(4);
    ace_editor.setValue("//Please submit your YARA rules here\n\n");
    ace_editor.focus();
</script>
