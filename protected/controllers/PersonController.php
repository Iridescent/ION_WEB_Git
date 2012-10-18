<?php

class PersonController extends BaseManageController {
    public function secondLevelNavigationType() {
        return NavigationType::MANAGE_PERSONS;
    }
    
    public function accessRules(){
        return array(array('allow', 'users'=>array('@')),array('deny', 'users'=>array('*')),);
    }
    
    public function actionEdit($householdId = NULL){
        $personId;
     
        if (isset($_POST['personId'])){
            $personId = $_POST['personId'];
        }
        
        $model = $personId ? $this->loadModel($personId) : new Person();
        if (!$personId && $householdId) {
             $model->Household = $householdId;
             $household = Household::model()->findByPk($householdId);
             $model->AdjustEmergencyContact($household,$model->SchoolRelation);
        }
                
        if(isset($_POST['Person'])){ 
            $model->attributes=$_POST['Person'];
            //temp
            if (!$model->GradeLevel || $model->GradeLevel == ''){
                $model->GradeLevel = NULL;
            }
            if (!$model->School || $model->School == ''){
                $model->School = NULL;
            }
            if (!$model->Subtype || $model->Subtype == ''){
                $model->Subtype = NULL;
            }
         
            // Household releted Person
            if (isset($householdId)) {
                if ($_POST['cancel']) {
                    $this->redirect(array('household/edit', 'personHouseholdId'=>$householdId));
                }
               
                if($model->save()){
                    $this->updateHousehold($model);
                    $this->redirect(array('household/edit', 'personHouseholdId'=>$householdId));
                }                
            }
            else {
                if ($_POST['cancel']) {
                    $this->redirect(array('index'));
                }
                if($model->save()){
                    $this->updateHousehold($model);
                    $model->saveLinks();
                    $this->redirect(array('index'));
                }
            }
        }
        // if Household related Person
        if (isset($householdId)) {
            $this->render('edit_related',array('model'=>$model, 'householdId'=>$householdId));
        } else {
            $this->render('edit',array('model'=>$model));
        }
        
    }

    public function actionDelete(){
        $programId = $_POST['personId'];
        if(Yii::app()->request->isPostRequest)
        {
            $this->loadModel($programId)->delete();

            if(!isset($_GET['ajax'])){
                $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
            }
        }
        else{
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
        }
    }
    
    public function actionIndex(){
        $model=new QueryPerson();
        $model->unsetAttributes();
        if(isset($_GET['QueryPerson'])){
            $model->attributes=$_GET['QueryPerson'];
        }
        if(isset($_GET['filter'])){
            $model->filter=$_GET['filter'];
        }
	$this->render('index', array('model'=>$model,));
    }
    
    public function actionGetEmergencyContacts($householdId){
        $result = array();
        if ($householdId){
            $household = Household::model()->findByPk($householdId);
            $result[] = (object)array('firstName'=>$household->Emergency1FirstName, 'lastName'=>$household->Emergency1LastName,
                                      'relationship'=>$household->Emergency1Relationship, 'mobile'=>$household->Emergency1MobilePhone);
            $result[] = (object)array('firstName'=>$household->Emergency2FirstName, 'lastName'=>$household->Emergency2LastName,
                                      'relationship'=>$household->Emergency2Relationship, 'mobile'=>$household->Emergency2MobilePhone);
        }
        $this->ajaxResopnse($result);
    }

    public function loadModel($id){
        $model=Person::model()->findByPk($id);
        if($model===null){
            throw new CHttpException(404,'The requested page does not exist.');
        }
        return $model;
    }

    protected function performAjaxValidation($model){
        if(isset($_POST['ajax']) && $_POST['ajax']==='person-form')
        {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    public function actionSubtypeByType(){
      $sql = "SELECT ID, PersonType, Name FROM personsubtype ".
             "WHERE PersonType = :type_id";
      $command = Yii::app()->db->createCommand($sql);
      $command->bindValue(':type_id', $_POST['type_id']);
      $data = $command->queryAll(true);;
      $data = CHtml::listData($data,'ID','Name');

      $data = ReportsController::unionArrays(array("" => "Select a relationship:") , $data);
      //print_r($data); die();
      foreach($data as $value=>$name)
      {
        echo CHtml::tag('option',
        array('value'=>$value),CHtml::encode($name),true);
      }
    }

    public function actionExport() {
        // TODO: 
        // - User name and passwd -> config
        // - Refactoring
        // - Message

        $email    = 'datairidescent@gmail.com'; # GMail or Google Apps account
        $password = 'S2SekX8Xf7ux';
        $fileName = 'household_'.date('mdY_His').'.csv';
        $fileContentType = 'text/csv';
        // $folder = 'https://docs.google.com/feeds/default/private/full/folder%3Aroot/contents'; // root folder
        $folder = 'https://docs.google.com/feeds/default/private/full/folder%3A0B5QTBluXc1jWRUVWWF9zeERXMDQ/contents';
        $delimiter = ',';
        
        Yii::import('ext.googlelogin');
        Yii::import('ext.xhttp');

        $model=new QueryPerson();
        $model->unsetAttributes();

        Yii::import('ext.ECSVExport');
 
        $res = Yii::app()->db->createCommand()
                ->select('p.BarcodeID AS Barcode, p.FirstName AS First name, p.LastName AS Last name, h.Name AS Household, p.WorkPhone as Work phone, p.EmailAddress AS Email, t.Name AS Role')
                ->from('persons p')
                ->leftJoin('household h', 'p.Household=h.ID')
                ->leftJoin('persontype t', 'p.Type=t.ID');
                //->order('ISNULL(h.Name), h.Name ASC');

        $csv = new ECSVExport($res);

        //$csv = new ECSVExport($model->search());
        //$csv->setHeader('Name', 'Household');
        //$csv->setExclude(array('ID', 'Sex', 'GradeLevel', 'Type', 'Subtype', 'School', 'DateOfBirth', 'WorkPhone', 'MobilePhone', 'HomePhone', 'Notes', 'SpecialCircumstances','PhysicianName','PhysicianPhoneNumber','Allergies','Medications','InsuranceCarrier','InsuranceNumber','LastUpdated','UpdateUserId','PicasaLink','GDocSurvey','GDocApplication'));

        $outputCsv = $csv->toCSV(); 

        $login = new googlelogin($email, $password, googlelogin::documents);

        $data['headers'] = array(
            'Authorization' => $login->toAuthorizationheader(),
            'GData-Version' => '3.0',
            'Slug' => rawurlencode($fileName),
            'Content-Type' => $fileContentType ,
        );

        $data['post'] = $outputCsv;

        $uploadresponse = xhttp::fetch($folder, $data);
        $message = 'Check file "'.$fileName.'" in export folder.';

        if($uploadresponse['successful']) {
            $xmlFilesInfo = new SimpleXMLElement($uploadresponse['body']);

            foreach ($xmlFilesInfo->link as $link) {
                if( ($link['type'] == 'text/html') && ($link['rel'] == 'alternate') ) {
                    $this->redirect($link['href']);
                }
            }
        }

        echo $message;
        die;
    }
    
    private function updateHousehold(&$personModel){
        $household = Household::model()->updateByPk($personModel->Household, array(
            'Emergency1FirstName'=>$personModel->Emergency1FirstName,
            'Emergency1LastName'=>$personModel->Emergency1LastName,
            'Emergency1Relationship'=>$personModel->Emergency1Relationship,
            'Emergency1MobilePhone'=>$personModel->Emergency1MobilePhone,
            'Emergency2FirstName'=>$personModel->Emergency2FirstName,
            'Emergency2LastName'=>$personModel->Emergency2LastName,
            'Emergency2Relationship'=>$personModel->Emergency2Relationship,
            'Emergency2MobilePhone'=>$personModel->Emergency2MobilePhone,
        ));
    }
}
