<?php
define('VIDYARD_BASE', basename(dirname(__FILE__)));

ShortcodeParser::get('default')->register('vidyard', array('Vidyard', 'handle_shortcode'));

HtmlEditorConfig::get('cms')->enablePlugins(array('vidyard'=>'../../../'.VIDYARD_BASE.'/javascript/editor_plugin_src.js'));
?>