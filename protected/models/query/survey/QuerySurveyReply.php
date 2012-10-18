<?php

class QuerySurveyReply extends CActiveRecord {
    
    public $filter;
    public $score;
    
    public static function model($classname=__CLASS__){
        return parent::model($classname);
    }
    
    public function tableName(){
        return 'surveyreply';
    }
    
    
    public function relations(){
        return array(
            'SurveyRelation'=>array (self::BELONGS_TO, 'Survey', 'SurveyId', 'select'=>'Title'),
            'SurveyItemRelation'=>array (self::HAS_MANY, 'SurveyItem', 'SurveyReplyId', 'together' => true, 'select' => 'Points'),
            'PersonRelation'=>array (self::BELONGS_TO, 'QueryPerson', 'PersonId', 'select'=>'LastName, FirstName'),
            'PersonRelation_1'=>array (self::BELONGS_TO, 'QueryPerson', 'PersonId', 'select'=>'*'),
            'HouseholdRelation'=>array (self::HAS_ONE, 'QueryHousehold', 'Household', 'through' => 'PersonRelation', 'select' => 'Name'),
            'RoleRelation'=>array (self::HAS_ONE, 'PersonType', 'Type', 'through' => 'PersonRelation_1', 'joinType' => 'INNER JOIN', 'select' => 'Name')
        );
    }

    public function scopes(){
        
        return array('bySession' => array('condition' => 'SessionId = :session_id', 'params' => array(':session_id'=>(int)$_GET['session']),));
    }
    
    public function search($pageSize=20){
        $criteria=new CDbCriteria;
        
        $with = array();

        $with['SurveyRelation'] = array();

        $with['SurveyItemRelation'] = array();      
        $with['PersonRelation'] = array();
    
        $with['HouseholdRelation'] = array();
        $with['RoleRelation'] = array();
        
        
        $criteria->with = $with;
 
        $criteria->select =  array('*', "IF (SUM(SurveyItemRelation.Points) is not NULL, SUM(SurveyItemRelation.Points), 0) AS score");        
        $criteria->group = "PersonId, SurveyId";    
        
        //$criteria->order = "PersonRelation.LastName, score";    
        
        $criteria->compare('PersonRelation.LastName', $this->filter, true, 'OR');
        $criteria->compare('PersonRelation.FirstName', $this->filter, true, 'OR');
        $criteria->compare('SurveyRelation.Title', $this->filter, true, 'OR');
       
        $sort = new CSort();
        $sort->attributes = array(
            'Name'=>array(
                'asc'=>'PersonRelation.LastName ASC',
                'desc'=>'PersonRelation.LastName DESC',
            ),
            'Role'=>array(
                'asc'=>'RoleRelation.Name ASC',
                'desc'=>'RoleRelation.Name DESC',
            ),
            'Household'=>array(
                'asc'=>'HouseholdRelation.Name ASC',
                'desc'=>'HouseholdRelation.Name DESC',
            ),

            'Survey'=>array(
                'asc'=>'SurveyRelation.Title ASC',
                'desc'=>'SurveyRelation.Title DESC',
            ),
            'Score'=>array(
                'asc'=>'score ASC',
                'desc'=>'score DESC',
            ),
        );
            
        return  new CActiveDataProvider($this, array('criteria'=>$criteria, 'sort'=>$sort, 'pagination'=>array('pageSize'=>$pageSize,)));
        
    }
        
    public function itemsToEvaluate($replyId){
        $criteria=new CDbCriteria;
        
        $criteria->condition = 'ID_ = '.(int)$replyId;  
        $with = array();
        $with['SurveyItemRelation_'] = array();
        $criteria->with = $with;
        
        return new CActiveDataProvider($this, array('criteria'=>$criteria));
    }
    
    public function GetBySurveyIdPersonId($survey_id, $person_id) {
        $criteria = new CDbCriteria;
        
        $criteria->condition = 'SurveyId = :survey_id AND PersonId = :person_id';
        $criteria->params = array(':survey_id' => $survey_id, ':person_id' => $person_id);
        
        $result = $this->findAll($criteria);
        if ($result && count($result) > 0){
            return $result[0];
        }
        return null;
    }
    
}

?>
