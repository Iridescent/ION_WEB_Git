<?php

class CuriosityMachineServiceClient {
    
    const connectAction = "system.connect";
    const loginAction = "user.login";
    const logoutAction = "user.logout";
    const IonGetByIdAction = "user.IonGetById";
    const IonGetByPersonalInfoAction = "user.IonGetByPersonalInfo";
    const IonSave = "user.IonSave";
    
    private $amfClient;
    private $sessionId;
    public $LoggedIn;
    
    public function __construct($endpoint){
        $this->amfClient = new Zend_Amf_Client($endpoint);
        $this->LoggedIn = false;
    }
    
    public function Connect(){
        $result = $this->amfClient->sendRequest(self::connectAction, array());
        $this->sessionId = $result->sessid;
        if ($result->user->userid) {
            $this->LoggedIn = true;
        }
    }
    
    public function Login($username, $password){
        if (!$this->LoggedIn) {
            $result = $this->amfClient->sendRequest(self::loginAction, $this->params($username, $password));
            if ($result->sessid && $result->user->userid) {
                $this->LoggedIn = true;
                $this->sessionId = $result->sessid;
            }
        }
    }
    
    public function Logout(){
        if ($this->LoggedIn) {
            $result = $this->amfClient->sendRequest(self::logoutAction, $this->params());
            $this->LoggedIn = !$result;
        }
    }
    
    public function GetCMUserById($userId){
        $result = $this->amfClient->sendRequest(self::IonGetByIdAction, $this->params($userId));
        return $result;
    }
    
    public function GetCMUserByInfo($firstName, $lastName, $birthDate){
        $result = $this->amfClient->sendRequest(self::IonGetByPersonalInfoAction, $this->params($firstName, $lastName, $birthDate));
        return $result;
    }
    
    public function SaveCMUser($user){
        $result = $this->amfClient->sendRequest(self::IonSave, $this->params($user));
        return $result;
    }

    //helpers
    private function params(){
        $args = func_get_args();
        array_unshift($args, $this->sessionId);
        return $args;
    }
}

?>
