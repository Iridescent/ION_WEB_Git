<?php

Yii::app()->clientScript->registerScript('SearchSchools', "

$(document).ready(function(){
    $('#searchButton').click(function(){
        return Submit();
    });
    
    $('#filter').enterKey(function(){
        return Submit();
    });
});

function Submit() {
    $.fn.yiiGridView.update('schoolGrid', {
        data: $(this).serialize()
    });
    return false;
}

function beforeSchoolGridUpdate(id, options) {
    options.url += '&filter=' + $('#filter').val();
}

");

?>

<div style="margin: 10px;">
    <div>
        <span class='long-input left left-5'><?php echo CHtml::textField('filter', ''); ?></span>
        <span class="styled-bttn right right-5 styled-bttn-long"><?php echo CHtml::button('GO', array ('id'=>'searchButton')); ?></span>
        <div class="clear"></div>
    </div>

<?php $this->widget('application.extensions.KeyTagGridView.KeyTagGridView', array(
        'id'=>'schoolGrid',
        'dataProvider'=>$model->search(),
        'ajaxVar'=>true,
        'ajaxUpdate'=>true,
        'beforeAjaxUpdate'=>'beforeSchoolGridUpdate',
        'template'=>'{items}{pager}{summary}',
        'title'=>'Schools',
        'addActionUrl'=>$this->createUrl('edit'),
        'editActionUrl'=>$this->createUrl('edit'),
        'deleteActionUrl'=>$this->createUrl('delete'),
        'deleteButtonVisible'=>Yii::app()->user->checkAccess(UserRoles::SuperAdmin),
        'idParameterName'=>'schoolId',
        'columns'=>array(
		'Name',
		'Type',
		//'Location',
		'Address',
            ),
    )); 
?>
 

</div>