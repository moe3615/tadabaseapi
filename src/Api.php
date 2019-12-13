<?php
namespace TadabaseApi;

use TadabaseApi\EventDispatcher;
use TadabaseApi\Request;

class Api extends EventDispatcher {

    private $config;

    private $request;

    private $baseUrl;

    public function __construct(){
        $this->config = include_once "Config.php";
        $this->baseUrl = $this->config['apiUrl'];
        $this->request = new Request();
    }

    public function setApi($arr){
        if(empty($arr)) return;

        $this->config = $arr;
    }

    public function setHeader(){
        $this->request
            ->addHeader('X-Tadabase-App-id', $this->config['appId'])
            ->addHeader('X-Tadabase-App-Key', $this->config['appKey'])
            ->addHeader('X-Tadabase-App-Secret', $this->config['appSecret']);
    }


    public function connectionCheck(){
        $this->setHeader();
        $this->request
            ->setMethod('get')
            ->send($this->baseUrl);
        return $this->checkResponse();
    }


    /**
     * Make an api request.
     *
     * @param  string $url
     * @param  string $httpMethod The http method to use with this request
     * @param  string $data Optional data to send with request
     * @param  string $contentType
     * @return array The json parsed response from the server
     */
    public function api($url, $httpMethod = 'GET', $data = null, $contentType = null){
        
        $url = $this->baseUrl . $url;
        // trigger an event
        $this->trigger('api_request_init', array('url' => $url));

        // save this request in case we need to use the refresh token
        $lastRequest = array(
            'url'    => $url,
            'method' => $httpMethod,
            'data'   => $data
        );

        $this->setHeader();

        $this->request
            ->setMethod($httpMethod)
            ->setData($data);
        if ($contentType) {
            $this->request->setContentType($contentType);
        }

        $this->trigger('api_request_send', array('url' => $url, 'http_method' => $httpMethod, 'data' => $data));

        // now send the request
        $this->request->send($url);

        $this->trigger('api_request_complete', array('url' => $url, 'response' => $this->request->getResponse()));

        // check the response for any errors
        $vaild = $this->checkResponse();

        return $this->request->getResponse();
    }

    /**
     * Check if the response failed and if the token is expired.
     *
     * Any errors returned from the API server will be thrown as an Exception.
     *
     * @param  array $response
     * @return boolean
     */
    private function checkResponse(){
        $response = $this->request->getResponse();        
        $httpCode = $this->request->getResponseCode();
    
        /*if (isset($response['type']) && $response['type'] == 'error' ) {
            $this->request->debug();
            die("API Error : " . $response['msg']);
            return false;
        }*/

        if ($httpCode == 400 && isset($response['error'])){
            // throw an Exception with the returned error message
            $msg = isset($response['error_description']) ? $response['error_description'] : $response['error'];
            throw new \Exception($msg);
            return false;
        }
        return true;
    }

    /******************
    * Section : Tables
    /******************/

    /**
     * List all tables
     * @method GET
     * @return an array of tables
     */
    public function getTableList(){
        return $this->api("data-tables/", 'GET');
    }

    /**
     * See list of fields inside a table
     * discription : See list of fields and their type for a particular table in your app.
     * @method GET
     * @return an array of fields
     */
    public function getTableField($tableId){
        $url = "data-tables/$tableId/fields";
        return $this->api($url, 'GET');
    }

     /**
     * See list of fields inside a table
     * discription : See list of fields and their full description for a particular table in your app.
     * @method GET
     * @return an array of fields
     */
    public function getTableFullField($tableId){
        $url = "data-tables/$tableId/full-fields-info";
        return $this->api($url, 'GET');
    }

    /*************************
    * Section : Retrieving Records
    /******************/

    /**
     * Get All Records
     * description : Sending a GET request to view records of a data table
     * @method GET
     * @param tableId = id of data-table (show in URL)
     * @param $data = 
            Pagination = array( "limit" => 25, "page" => 1 )
            Sorting Records = array( "order" => "field_1", "order_by" => "desc" )
            Sort by multiple conditions = array( sort_by[0][sort]=field_1&sort_by[0][by]=desc&sort_by[1][sort]=field_2&sort_by[1][by]=asc )
            Filtering Records = https://developer.tadabase.io/?version=latest#5edb1ac0-7486-46b1-95ce-31ee180498ab
                array( filters[items][0][field_id]=field_33&filters[items][0][operator]=is&filters[items][0][val]=Yes&filters[items][1][field_id]=field_35&filters[items][1][operator]=is after&filters[items][1][val]=2019-07-24&filters[condition]=AND )
     */
    public function getRecords($tableId, $data = array()){
        $url = 'data-tables/' . $tableId . '/records/';
        return $this->api($url, 'GET', $data);
    }

    /**
     * Get a single record
     * description : Sending a GET request to view a single record in the table
     * @method GET
     * @param tableId = id of data-table (show in URL)
     * @param recordId = record id
     */
    public function getRecord($tableId, $recordId){
        $url = 'data-tables/'.$tableId.'/records/' . $recordId . '/';
        return $this->api($url, 'GET');
    }


    /*************************
    * Section : Saving Records
    **************************/

    /**
     * Save new record
     * description : To save a new record you must include in the payload the form-data the values you'd like to save.
     * @url : https://developer.tadabase.io/?version=latest#d00c236a-f9cd-4329-ad55-58b92cdfeec3
     * @method POST
     * @param Content-Type = application/x-www-form-urlencoded
     * @param tableId = id of data-table (show in URL)
     * @param data = 
        Save new record : array("field_31" => "John Doe" ) // Text Field

        Saving to Name Field : array(
                        "field_83" => array( 
                            "title" => "Mrs", 
                            "first_name" => "Prakash", 
                            "middle_name" => "M", 
                            "last_name" => "Anjara" 
                        )
                    );
        
        Saving to connection field : array( "field_39" => "7oOjD1drB9" );

        Saving to connection field one to many : array(
                        "field_39" => array("7oOjD1drB9", "l5nQx1LjxY")
                    );

        Saving address field : array(
                    "field_55" => array(
                        "address" => "123 Main Street", // Address 1 Field 
                        "address2" => "", // Address 2 Field
                        "city" => "Los Angeles", // City Field
                        "state" => "CA", // State Field
                        "country" => "USA", // Country Field
                        "zip" => "90010", // Zip/Postal Code Field
                        "lng" => "USA", // Longitute Field
                        "lat" => "USA", // Latitude Field
                    )
                );

        Saving date fields : array( "field_35" => "2019-07-25" ); // Date Field
        
        Saving time fields : array( "field_40" => '12:25:00' ); // Time Field

        Saving date and time fields : array( "field_41" => '2019-07-26 12:34:00' ); // Date and time field

     */
    public function saveRecord($tableId, $data = array()){
        $url = 'data-tables/' . $tableId . '/records';
        return $this->api($url, 'POST', $data, "application/x-www-form-urlencoded");
    }


    /*************************
    * Section : Update Records
    **************************/

    /**
     * Update existing record
     * description : Update existing record based on record ID
     * @method POST
     * @param Content-Type = application/x-www-form-urlencoded
     * @param tableId = id of data-table (show in URL)
     * @param recordId = id of record
     * @param data = same as Save Record
        Update record : array("field_31" => "John Doe" ) // Text Field
     */
    public function updateRecord($tableId, $recordId, $data = array()){
        $url = 'data-tables/' . $tableId . '/records/' . $recordId;
        return $this->api($url, 'POST', $data, "application/x-www-form-urlencoded");
    }


    /*************************
    * Section : Delete Records
    **************************/
    /**
     * description : Delete a record from the data table.
     * @method POST
     * @param the numeric ID of the view the record is in
     * @param the numeric ID of the record you want to update
     * @return Nothing if successful, else an error message
     */
    public function deleteRecord($tableId, $recordId){
        $url = 'data-tables/' . $tableId . '/records/' . $recordId;
        return $this->api($url, 'DELETE');
    }

}
