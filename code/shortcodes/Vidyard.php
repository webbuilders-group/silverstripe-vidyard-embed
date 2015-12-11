<?php
class Vidyard extends Object {
    /**
     * Vidyard API Key
     * @config Vidyard.api_key
     * @see http://support.vidyard.com/articles/Public_Support/Using-the-Vidyard-dashboard-API/
     */
    private static $api_key;
    
    /**
     * Handles processing of the shortcode
     * @param {array} $arguments Map of arguments to apply
     * @param {string} $url Vidyard Video URL
     * @param {ShortcodeParser} $parser Shortcode Parser instance
     * @param {string} $shortcode Shortcode Name
     * @return {string} HTML to be used to display the video, falls back to a link if the api fails
     */
    public static function handle_shortcode($arguments, $url, $parser, $shortcode) {
        if(isset($arguments['type'])) {
            $type = $arguments['type'];
            unset($arguments['type']);
        } else {
            $type = false;
        }
        
        
        $embed=self::get_video_from_url($url, $arguments);
        if($embed && $embed->exists()) {
            //Inject the stylesheet if a width or height has been set
            if(isset($arguments['width']) || isset($arguments['height'])) {
                Requirements::css(VIDYARD_BASE.'/css/VidyardEmbed.css');
            }
            
            return $embed->forTemplate($arguments);
        }else {
            return '<a href="'.$url.'">'.$url.'</a>';
        }
    }
    
    /**
     * Gets the Vidyard_Result object based on the video url
     * @param {string} $url Vidyard Video URL
     * @param {array} $options Map of options to hand off to the result object
     * @return {Vidyard_Result}
     */
    public static function get_video_from_url($url, $options=array()) {
        return new Vidyard_Result($url, false, false, $options);
    }
    
    /**
     * Validates the incoming string to ensure it is an accepted vidyard url
     * @param {string} $url Vidyard Video URL
     * @return {bool} Returns boolean true on success false otherwise
     */
    public static function validateVidyardURL($url) {
        return (preg_match('/^http(s?):\/\/(secure|embed)\.vidyard\.com\/(.*?)\/([a-zA-Z0-9_-]+)(((\/|\?)(.*?))*)$/', $url)==true);
    }
    
    /**
     * Gets the Vidyard UUID for the url
     * @param {string} $url Vidyard Video URL
     * @return {string} Returns the UUID or if it is not supported
     */
    public static function getVidyardCode($url) {
        if(self::validateVidyardURL($url)) {
            return preg_replace('/^http(s?):\/\/(secure|embed)\.vidyard\.com\/(.*?)\/([a-zA-Z0-9_-]+)(((\/|\?)(.*?))*)$/', '$4', $url);
        }
    }
}

class Vidyard_Result extends Oembed_Result {
    protected $videoID;
    
    /**
     * Fetches the JSON data from the Oembed URL (cached).
     * Only sets the internal variable.
     */
    protected function loadData() {
        //Get the video id
        $videoID=Vidyard::getVidyardCode($this->url);
        if(empty($videoID)) {
            return false;
        }
        
        $this->videoID=$videoID;
        
        
        //Retrieve and Check the api key
        $apiKey=Vidyard::config()->api_key;
        if(empty($apiKey)) {
            return false;
        }
        
        if($this->data !== false) {
            return;
        }
        
        // Fetch from Oembed URL (cache for a week by default)
        $service=new VidyardService($videoID, $apiKey, 60*60*24*7);
        $body=$service->request();
        if(!$body || $body->isError()) {
            $this->data=array();
            return;
        }
        
        
        //Get the json response
        $data=$body->json();
        
        
        // Convert all keys to lowercase
        $data=array_change_key_case($data, CASE_LOWER);
        
        
        $data=array(
                    'version'=>'1.0',
                    'thumbnail_url'=>'https://play.vidyard.com/'.rawurlencode($videoID).'.jpg',
                    'type'=>'video',
                    'provider_name'=>'Vidyard',
                    'provider_url'=>'https://www.vidyard.com/',
                    'width'=>$data['width'],
                    'height'=>$data['height'],
                    'title'=>$data['name'],
                    'html'=>'<script type="text/javascript" id="vidyard_embed_code_'.Convert::raw2att($videoID).'" src="//play.vidyard.com/'.rawurlencode($videoID).'.js?v=3.0&type=inline"></script>'
                );
        
        // Purge everything if the type does not match.
        if($this->type && $this->type!=$data['type']) {
            $data=array();
        }
        
        $this->data=$data;
    }
    
    /**
     * Handles renderign this vidyard result object for use in the template
     * @param {array} $options Map of options to be applied to the rendered html
     * @return {string} HTML to be used in the template
     */
    public function forTemplate($options=null) {
        $this->loadData();
        if($this->Type=='video') {
            $extraAttr=array();
            
            //Inject the options into the tag
            if($options) {
                $definedWidth=false;
                $definedHeight=false;
                
                if(isset($options['width'])) {
                    $extraAttr[]='width:'.$options['width'].'px';
                    
                    $definedWidth=true;
                }
                
                if(isset($options['height']) && !isset($options['maxheight'])) {
                    $extraAttr[]='height:'.$options['height'].'px';
                    
                    $definedHeight=true;
                }
                
                if($definedWidth && $definedHeight) {
                    $this->extraClass.=' definedSize';
                }else if($definedWidth) {
                    $this->extraClass.=' definedWidth';
                }else if($definedHeight) {
                    $this->extraClass.=' definedHeight';
                }
            }
            
            
            //Build the html used
            if($this->extraClass) {
                $result='<div class="media vidyard '.$this->extraClass.'"'.(!empty($extraAttr) ? ' style="'.implode(';', $extraAttr):'').'">'.$this->HTML.'</div>';
            }else {
                $result='<div class="media vidyard"'.(!empty($extraAttr) ? ' style="'.implode(';', $extraAttr):'').'">'.$this->HTML.'</div>';
            }
            
            
            //Allow extensions to tap in for additional requirements
            $this->extend('onBeforeRender', $result);
            
            
            return $result;
        }
        
        return '<a class="'.$this->extraClass.'" href="'.$this->origin.'">'.$this->Title.'</a>';
    }
    
    /**
     * Gets the video id in use
     * @return {string}
     */
    public function getVideoID() {
        if(empty($this->videoID)) {
            $this->videoID=Vidyard::getVidyardCode($this->url);
        }
        
        return $this->videoID;
    }
}
?>