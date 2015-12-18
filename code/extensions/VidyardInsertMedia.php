<?php
class VidyardInsertMedia extends Extension
{
    private static $allowed_actions=array(
                                        'viewvidyard'
                                    );
    
    /**
     * Adjusts the media form to include the controls and ui for Vidyard
     * @param {Form} $form Form to adjust
     */
    public function updateMediaForm(Form $form)
    {
        $apiKey=Vidyard::config()->api_key;
        if (!empty($apiKey)) {
            $numericLabelTmpl='<span class="step-label"><span class="flyout">%d</span><span class="arrow"></span><strong class="title">%s</strong></span>';
            
            
            $tabs=$form->Fields()->offsetGet(1)->fieldByName('MediaFormInsertMediaTabs');
            
            $tabs->push(Tab::create('FromVidyard', _t('VidyardInsertMedia.FROM_VIDYARD', '_From Vidyard'), CompositeField::create(
                                new LiteralField('headerVidyard', '<h4>' . sprintf($numericLabelTmpl, '1', _t('VidyardInsertMedia.ADD_VIDEO', '_Add Video')) . '</h4>'),
                                TextField::create('VideoURL', 'http://')->addExtraClass('remoteurl')->setDescription(_t('VidyardInsertMedia.ADD_VIDEO_DESC', '_The url you should use in this field is the sharing page, you can also use some of the settings pages for the video in Vidyard')),
                                new LiteralField('addVidyard', '<button class="action ui-action-constructive ui-button field add-url add-vidyard" data-icon="addMedia">'._t('VidyardInsertMedia.ADD_VIDEO', '_Add Video').'</button>')
                            )->addExtraClass('content ss-uploadfield')
                        )
                        ->addExtraClass('htmleditorfield-from-web')
                        ->setTabSet($tabs)
                        ->setForm($form));
            
            
            Requirements::javascript(VIDYARD_BASE.'/javascript/VidyardInsertMedia.js');
        }
    }
    
    /**
     * Handles requests to view a vidyard video in the cms
     * @param {SS_HTTPRequest} $request HTTP Request object
     * @return {string} Rendered view on success null on error
     * @throws SS_HTTPResponse_Exception
     */
    public function viewvidyard(SS_HTTPRequest $request)
    {
        $file=null;
        $url=null;
        
        if ($fileUrl=$request->getVar('VidyardURL')) {
            // If this isn't an absolute URL, or is, but is to this site, try and get the File object
            // that is associated with it
            if (Director::is_absolute_url($fileUrl) && !Director::is_site_url($fileUrl) && Vidyard::validateVidyardURL($fileUrl)) {
                list($file, $url)=$this->getVideoByURL($fileUrl);
            }
            // If this is an absolute URL, but not to this site, use as an oembed source (after whitelisting URL)
            else {
                throw new SS_HTTPResponse_Exception('"VidyardURL" is not a valid Vidyard Video', 400);
            }
        }
        // Or we could have been passed nothing, in which case panic
        else {
            throw new SS_HTTPResponse_Exception('Need "VidyardURL" parameter to identify the file', 400);
        }
        
        
        $fileWrapper=new VidyardInsertMedia_Embed($url, $file);
        
        $fields=$this->getFieldsForVidyard($url, $fileWrapper);
        
        return $fileWrapper->customise(array(
                                            'Fields'=>$fields,
                                        ))->renderWith('HtmlEditorField_viewfile');
    }
    
    /**
     * Gets the video by it's url
     * @param {string} $url Vidyard Video URL to lookup
     * @return {array} Array in which the first key is a file instance and the second key is the url
     */
    protected function getVideoByURL($url)
    {
        //Get the video id
        $videoID=Vidyard::getVidyardCode($url);
        if (empty($videoID)) {
            $exception=new SS_HTTPResponse_Exception('Could not get the Video ID from the URL', 400);
            $exception->getResponse()->addHeader('X-Status', $exception->getMessage());
            throw $exception;
        }
        
        
        //Retrieve and Check the api key
        $apiKey=Vidyard::config()->api_key;
        if (empty($apiKey)) {
            $exception=new SS_HTTPResponse_Exception('Vidyard.api_key is not set, please configure your api key see http://support.vidyard.com/articles/Public_Support/Using-the-Vidyard-dashboard-API/ for how to get your API key.', 401);
            $exception->getResponse()->addHeader('X-Status', $exception->getMessage());
            throw $exception;
        }
        
        return array(
                    new File(array(
                                'Title'=>$videoID,
                                'Filename'=>$url
                            )),
                    $url
                );
    }
    
    /**
     * Gets the fields for displaying in the cms
     * @param {string} $url Vidyard Video URL
     * @param {VidyardInsertMedia_Embed} $file Vidyard Insert Media Embed Object
     * @return {FieldList} Field List of fields used in the cms
     */
    protected function getFieldsForVidyard($url, VidyardInsertMedia_Embed $file)
    {
        $thumbnailURL=Convert::raw2att($file->Oembed->thumbnail_url);
        
        $fileName=Convert::raw2att($file->Name);
        
        $fields=new FieldList(
            $filePreview=CompositeField::create(
                CompositeField::create(
                    new LiteralField(
                        "ImageFull",
                        "<img id='thumbnailImage' class='thumbnail-preview' "
                            . "src='{$thumbnailURL}?r=" . rand(1, 100000) . "' alt='$fileName' />\n"
                    )
                )->setName("FilePreviewImage")->addExtraClass('cms-file-info-preview'),
                CompositeField::create(
                    CompositeField::create(
                        new ReadonlyField("FileType", _t('AssetTableField.TYPE', 'File type') . ':', $file->Type),
                        $urlField=ReadonlyField::create(
                            'ClickableURL',
                            _t('AssetTableField.URL', 'URL'),
                            sprintf(
                                '<a href="%s" target="_blank" class="file">%s</a>',
                                Convert::raw2att($url),
                                Convert::raw2att($url)
                            )
                        )->addExtraClass('text-wrap')
                    )
                )->setName("FilePreviewData")->addExtraClass('cms-file-info-data')
            )->setName("FilePreview")->addExtraClass('cms-file-info'),
            DropdownField::create(
                                'CSSClass',
                                _t('HtmlEditorField.CSSCLASS', 'Alignment / style'),
                                array(
                                    'leftAlone'=>_t('HtmlEditorField.CSSCLASSLEFTALONE', 'On the left, on its own.'),
                                    'center'=>_t('HtmlEditorField.CSSCLASSCENTER', 'Centered, on its own.'),
                                    'left'=>_t('HtmlEditorField.CSSCLASSLEFT', 'On the left, with text wrapping around.'),
                                    'right'=>_t('HtmlEditorField.CSSCLASSRIGHT', 'On the right, with text wrapping around.')
                                )
                            )->addExtraClass('last')
        );
        
        if ($file->Width != null) {
            $fields->push(
                FieldGroup::create(
                    _t('HtmlEditorField.IMAGEDIMENSIONS', 'Dimensions'),
                    TextField::create(
                        'Width',
                        _t('HtmlEditorField.IMAGEWIDTHPX', 'Width'),
                        $file->InsertWidth
                    )->setMaxLength(5),
                    TextField::create(
                        'Height',
                        _t('HtmlEditorField.IMAGEHEIGHTPX', 'Height'),
                        $file->InsertHeight
                    )->setMaxLength(5)
                )->addExtraClass('dimensions last')
            );
        }
        $urlField->dontEscape=true;
        
        $fields->push(new HiddenField('URL', false, $file->getURL()));
        
        return $fields;
    }
}

class VidyardInsertMedia_Embed extends HtmlEditorField_Embed
{
    /**
     * Performs the initial request to load the video details
     * @param {string} $url Vidyard Video URL
     * @param {File} $file File Object
     */
    public function __construct($url, $file=null)
    {
        HtmlEditorField_File::__construct($url, $file);
        
        $this->oembed=Vidyard::get_video_from_url($this->url);
        if (!$this->oembed) {
            $controller=Controller::curr();
            $controller->response->addHeader('X-Status', rawurlencode(_t('HtmlEditorField.URLNOTANOEMBEDRESOURCE',
                                "The URL '{url}' could not be turned into a media resource.",
                                "The given URL is not a valid Vidyard video; the embed element couldn't be created.",
                                array('url'=>$url)
                            )));
            $controller->response->setStatusCode(404);
    
            throw new SS_HTTPResponse_Exception($controller->response);
        }
    }
    
    /**
     * @param string
     * @deprecated since version 4.0
     */
    public function setCachedBody($content)
    {
        Deprecation::notice('4.0', 'Setting the response body is now deprecated, set the cached request instead');
        if (!$this->cachedResponse) {
            $this->cachedResponse=new VidyardService_Response($content);
        } else {
            $this->cachedResponse->setBody($content);
        }
    }
    
    /**
     * Returns the classes used in the template
     * @return {string}
     */
    public function appCategory()
    {
        return 'embed vidyard';
    }
}
