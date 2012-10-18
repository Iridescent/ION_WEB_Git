<?php

class QuerySurveyItem extends CActiveRecord {
    
    public $filter = "";
    
    public static function model($classname=__CLASS__){
        return parent::model($classname);
    }
    
    public function tableName(){
        return 'surveyreplyitem';
    }

    public function rules() {
        return array(
            array('Points', 'numerical', 'integerOnly'=>true),
            array('Points', 'length', 'max'=>6),
            array('Points', 'match', 'pattern' => '/^[0-9]+$/', 'message'=>'Score must contain numbers'),
           ); 
    }
    
    public function relations(){
        return array(
            'SurveyReplyRelation'=>array (self::BELONGS_TO, 'QuerySurveyReply', 'SurveyReplyId', 'select'=>'*'),            
        );
    }

    public function scopes(){
        
        return array('bySurveyReply' => array('condition' => 'SurveyReplyId = :reply_id', 'params' => array(':reply_id'=>(int)$_POST['surveyReplyId'])));
    }
    
    public function search($pageSize=20){
        $criteria=new CDbCriteria;
        
        $with = array();
        
        $with['SurveyReplyRelation'] = array();
        $criteria->with = $with; 
        
        return new CActiveDataProvider($this, array('criteria'=>$criteria));
    }
    
    public function toEvaluate($surveyReplyId){
        
        $criteria=new CDbCriteria;
        $questionTypes = array(QuestionType::IMAGE,QuestionType::TEXT_AREA,QuestionType::TEXT_INPUT,QuestionType::VIDEO);
        
        $criteria->addCondition('SurveyReplyId = '.$surveyReplyId);         
        $criteria->addInCondition('QuestionType', $questionTypes);
      //  $criteria->params = array(':surveyReplyId' => $surveyReplyId); // ':questionTypes' => $questionTypes,          
        
        return $this->findAll($criteria); 
    }
    
}

?>
