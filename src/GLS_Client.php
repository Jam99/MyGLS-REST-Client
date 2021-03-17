<?php
/**
 * GLS REST API CLIENT
 * @author Jámbor Máté
 * @version 1.0
 *
 * GLS_REST_Client constructor:
 * @param $options - an associative array to configure the client
 *  OPTIONS:
 *      - client_number (int) - Your MyGLS client number
 *      - username (string) - Your MyGLS username (email)
 *      - password (string) - Your MyGLS password
 *      - country (string) - Available countries: Hungary, Croatia, Czechia, Romania, Slovenia, Slovakia
 *      - test_client (bool) - If set to TRUE: GLS test API will be used. Must be set to FALSE to use live API.
 *
 * Note that you must claim API access from GLS to be authorized.
 */

class GLS_REST_Client{

    private $clientNumber;
    private $username;
    private $password;
    private $country;
    private $baseURL;
    private $isTestClient;


    private const serviceName = "ParcelService";

    private const countryDomains = array(
        "Hungary" => array(
            "test" => "https://api.test.mygls.hu/",
            "live" => "https://api.mygls.hu/"
        ),
        "Croatia" => array(
            "test" => "https://api.test.mygls.hr/",
            "live" => "https://api.mygls.hr/"
        ),
        "Czechia" => array(
            "test" => "https://api.test.mygls.cz/",
            "live" => "https://api.mygls.cz/"
        ),
        "Romania" => array(
            "test" => "https://api.test.mygls.rp/",
            "live" => "https://api.mygls.ro/"
        ),
        "Slovenia" => array(
            "test" => "https://api.test.mygls.si/",
            "live" => "https://api.mygls.si/"
        ),
        "Slovakia" => array(
            "test" => "https://api.test.mygls.sk/",
            "live" => "https://api.mygls.sk/"
        )
    );

    public static $supportedStatusLangIsoCodes = array("EN", "HR", "CS", "HU", "RO", "SK", "SL");

    /**
     * GLS_REST_Client constructor.
     * @param $options
     * @throws Exception if there is any invalid / missing option.
     */
    public function __construct($options) {

        /**
         * TEST OPTION
         */
        if($options["test_client"] === (bool)$options["test_client"]) {
            $this->isTestClient = (bool)$options["test_client"];
        }
        else{
            throw new Exception("Option 'test_client' should be set to (boolean) TRUE or FALSE.");
        }


        /**
         * Country Domain
         */
        if(isset($this::countryDomains[$options["country"]])){

            $this->country = $options["country"];
            $this->baseURL = $this->getAPIBaseURL();

        }
        else{
            throw new Exception("Country [{$options["country"]}] is not set / not supported / invalid.");
        }


        /**
         * ClientNumber, Username and Password
         */
        if(isset($options["client_number"]) && isset($options["username"]) && isset($options["password"])){
            $this->clientNumber = $options["client_number"];
            $this->username = $options["username"];
            $this->password = $options["password"];
        }
        else{
            throw new Exception("Option(s) 'client_number' / 'username' / 'password' not set.");
        }
    }


    /**
     * Returns the base URL of the API (for example 'https://api.test.mygls.hu/')
     */
    private function getAPIBaseURL(){
        if($this->isTestClient)
            $assoc_index = "test";
        else
            $assoc_index = "live";

        return $this::countryDomains[$this->country][$assoc_index];
    }


    private function toSHA512ByteArrayString($str){
        return "[".implode(',',unpack('C*', hash('sha512', $str, true)))."]";
    }


    private function getRequestString($method, $args = []){

        switch($method){
            case "PrintLabels":
                $show_print_dialog = $args["show_print_dialog"] ? 1 : 0;
                $print_position = $args["print_position"] ? $args["print_position"] : 1;
                return '{"Username":"'.$this->username.'","Password":'.$this->toSHA512ByteArrayString($this->password).',"ParcelList":'.json_encode($args["parcel_list"]).',"PrintPosition":'.$print_position.',"ShowPrintDialog":'.$show_print_dialog.'}';
            case "DeleteLabels":
                $show_print_dialog = $args["show_print_dialog"] ? 1 : 0;
                $print_position = $args["print_position"] ? $args["print_position"] : 1;
                return '{"Username":"'.$this->username.'","Password":'.$this->toSHA512ByteArrayString($this->password).',"ParcelIdList":'.json_encode($args["parcel_id_list"]).',"PrintPosition":'.$print_position.',"ShowPrintDialog":'.$show_print_dialog.'}';
            case "GetParcelStatuses":
                $return_pod = $args["return_pod"] ? 1 : 0;
                return '{"Username":"'.$this->username.'","Password":'.$this->toSHA512ByteArrayString($this->password).',"ParcelNumber":'.$args["parcel_number"].',"ReturnPOD":'.$return_pod.',"LanguageIsoCode":"'.$args["language_iso_code"].'"}';
        }

        return false;
    }


    /**
     * @param $methodName
     * @param $requestString
     * @return mixed - respone object
     * @throws Exception
     */
    private function getResponse($methodName, $requestString){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_URL, $this->baseURL . $this::serviceName . ".svc/json/". $methodName);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 600);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $requestString);

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($requestString))
        );


        $response = curl_exec($curl);
        if ($response === false)
            throw new Exception('curl_error:"' . curl_error($curl) . '";curl_errno:' . curl_errno($curl));


        $response_code = (string) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if($response_code[0] != "2")
            throw new Exception("Error: Server responded with HTTP response code ".$response_code);


        curl_close($curl);
        $response = json_decode($response);


        return $response;
    }


    public function createServiceObject($code, $parameter_name, $parameters){
        return (object)[
            "Code"          => $code,
            $parameter_name => (object)$parameters
        ];
    }


    public function createParcelObject($args = []){
        return (object)[
            "ClientNumber"          => $this->clientNumber,
            "ClientReference"       => $args["ClientReference"],
            "CODAmount"             => (int)$args["CODAmount"],
            "CODReference"          => $args["CODReference"],
            "Content"               => $args["Content"],
            "Count"                 => (int)$args["Count"],
            "DeliveryAddress"       => $args["DeliveryAddress"],
            "PickupAddress"         => $args["PickupAddress"],
            "PickupDate"            => null, //"/Date(".(strtotime("2021-03-24 23:59:59") * 1000).")/",
            "ServiceList"           => $args["ServiceList"]
        ];
    }


    /**
     * @param $args
     *  - parcel_list (required)
     *  - print_position (optional)
     *  - show_print_dialog (optional)
     * @throws Exception
     * @return object - response
     */
    public function printLabels($args){
        return $this->getResponse("PrintLabels", $this->getRequestString("PrintLabels", $args));
    }


    /**
     * @param $args
     *  - parcel_id_list
     *  - print_position (optional)
     *  - show_print_dialog (optional)
     * @return mixed
     * @throws Exception
     */
    public function deleteLabels($args){
        return $this->getResponse("DeleteLabels", $this->getRequestString("DeleteLabels", $args));
    }


    /**
     * @param $args
     *  - parcel_number
     *  - return_pod
     *  - language_iso_code
     * @return mixed
     * @throws Exception
     */
    public function getParcelStatuses($args){
        return $this->getResponse("GetParcelStatuses", $this->getRequestString("GetParcelStatuses", $args));
    }


    /** TEST PURPOSES ONLY
     * @throws Exception
     */
    public function test(){
        $pickup_address = (object)[
            "City"              => "Debrecen",
            "ContactEmail"      => "info@clustermedia.hu",
            "ContactName"       => "Jámbor Máté",
            "ContactPhone"      => "+36701234567",
            "CountryIsoCode"    => "HU",
            "HouseNumber"       => "66",
            "Name"              => "ADDRESS",
            "Street"            => "STREET",
            "ZipCode"           => "4000",
            "HouseNumberInfo"   => "/a"
        ];

        $delivery_address = (object)[
            "City"              => "Budapest",
            "ContactEmail"      => "info@clustermedia.hu",
            "ContactName"       => "John Doe",
            "ContactPhone"      => "+36701234567",
            "CountryIsoCode"    => "HU",
            "HouseNumber"       => "66",
            "Name"              => "ADDRESS",
            "Street"            => "STREET",
            "ZipCode"           => "1007",
            "HouseNumberInfo"   => "/a"
        ];

        $services = [
            $this->createServiceObject("PSD", "PSDParameter", ["StringValue" => "1051-CSOMAGPONT01"])
        ];

        $parcel_object = $this->createParcelObject([
            "ClientReference" => "TEST_method",
            "PickupAddress" => $pickup_address,
            "DeliveryAddress" => $delivery_address
        ]);

        //echo "<pre>".json_encode($parcel_object, JSON_PRETTY_PRINT)."</pre>";

        $this->printLabels([
            "parcel_list" => [$parcel_object]
        ]);
    }
}