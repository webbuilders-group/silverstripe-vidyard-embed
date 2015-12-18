<?php
class VidyardService extends RestfulService
{
    protected $videoID;
    protected $apiKey;
    
    /**
     * Initializes the Vidyard Service
     * @param {string} $videoID UUID of the video to lookup
     * @param {string} $apiKey Vidyard API Key
     * @param {int} $expiry Cache expiry
     */
    public function __construct($videoID, $apiKey, $expiry)
    {
        $this->videoID=$videoID;
        $this->apiKey=$apiKey;
        
        parent::__construct('https://api.vidyard.com/dashboard/v1/players/uuid='.$videoID.'?auth_token='.$apiKey, $expiry);
    }
    
    /**
     * Makes a request to the RESTful server, and return a {@link RestfulService_Response} object for parsing of the result.
     * @return {VidyardService_Response} If curl request produces error, the returned response's status code will be 500
     */
    public function request($subURL='', $method='GET', $data=null, $headers=null, $curlOptions=array())
    {
        $finalHeaders=array('Accept: application/json');
        if (is_array($headers)) {
            $finalHeaders=array_merge($headers, $finalHeaders);
        }
        
        return parent::request($subURL, $method, $data, $finalHeaders, $curlOptions);
    }
    
    /**
     * Extracts the response body and headers from a full curl response
     * @param curl_handle $ch The curl handle for the request
     * @param string $rawResponse The raw response text
     * @return VidyardService_Response The response object
     */
    protected function extractResponse($ch, $rawHeaders, $rawBody)
    {
        //get the status code
        $statusCode=curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        //get a curl error if there is one
        $curlError=curl_error($ch);
        
        //normalise the status code
        if (curl_error($ch)!=='' || $statusCode==0) {
            $statusCode=500;
        }
        
        //parse the headers
        $parts=array_filter(explode("\r\n\r\n", $rawHeaders));
        $lastHeaders=array_pop($parts);
        $headers=$this->parseRawHeaders($lastHeaders);
        
        //return the response object
        return new VidyardService_Response($rawBody, $statusCode, $headers);
    }
}

class VidyardService_Response extends RestfulService_Response
{
    private $json=false;
    
    /**
     * Not implemented
     */
    public function simpleXML()
    {
        user_error('Not implemented', E_USER_ERROR);
    }
    
    /**
     * Not implemented
     */
    public function xpath($xpath)
    {
        user_error('Not implemented', E_USER_ERROR);
    }
    
    /**
     * Gets the json data from the response body
     * @return {array} Array representing the API response
     */
    public function json()
    {
        if (!$this->json) {
            if (!$this->json=json_decode($this->body, true)) {
                user_error('String could not be parsed as JSON.', E_USER_WARNING);
            }
        }
        
        return $this->json;
    }
}
